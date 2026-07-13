<?php
/**
 * AbilitiesLoaderTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesCategories;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderAddNote;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductDelete;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductsQuery;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for the canonical WooCommerce domain abilities and their loader.
 */
class AbilitiesLoaderTest extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Shared administrator used as the default current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	private const CANONICAL_ABILITY_IDS = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

	/**
	 * Ability IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_ids = self::CANONICAL_ABILITY_IDS;

	/**
	 * Category IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_category_ids = array();

	/**
	 * Product IDs created by these tests.
	 *
	 * @var array
	 */
	private $created_product_ids = array();

	/**
	 * Order IDs created by these tests.
	 *
	 * @var array
	 */
	private $created_order_ids = array();

	/**
	 * Original action counts captured for restoration in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	/**
	 * Original HPOS/COT state captured before tests that toggle storage.
	 *
	 * @var bool|null
	 */
	private $original_cot_state = null;

	/**
	 * Whether HPOS/COT tables were set up by the current test.
	 *
	 * @var bool
	 */
	private $cot_setup_for_test = false;

	/**
	 * Create immutable class fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$abilities_bootstrap = WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $abilities_bootstrap ) ) {
				require_once $abilities_bootstrap;
			}
		}

		// WordPress 6.9+ requires init to have fired before the Abilities API registry can be initialized.
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( self::$administrator_id );

		$this->register_woocommerce_category();
		$this->register_domain_abilities();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		foreach ( $this->created_order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$order->delete( true );
			}
		}

		foreach ( $this->created_product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$product->delete( true );
			}
		}

		foreach ( $this->registered_ability_ids as $ability_id ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}

		foreach ( $this->registered_category_ids as $category_id ) {
			if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( $category_id ) ) {
				wp_unregister_ability_category( $category_id );
			}
		}

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} elseif ( isset( $wp_actions[ $action ] ) ) {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		if ( null !== $this->original_cot_state ) {
			if ( $this->cot_setup_for_test ) {
				$this->clean_up_cot_setup();
			}

			$this->toggle_cot_feature_and_usage( $this->original_cot_state );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/*
	 * ---------------------------------------------------------------------
	 * Registration / Loader
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should register every canonical ability with WooCommerce metadata.
	 */
	public function test_canonical_abilities_register_with_woocommerce_metadata(): void {
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$ability = wp_get_ability( $ability_id );

			$this->assertNotNull( $ability, "{$ability_id} should be registered." );
			$this->assertSame( 'woocommerce', $ability->get_category(), "{$ability_id} should belong to the woocommerce category." );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] ?? false, "{$ability_id} should be exposed in REST." );
			$this->assertTrue( $meta['mcp']['public'] ?? false, "{$ability_id} should be flagged as MCP-public." );
			$this->assertSame( 'tool', $meta['mcp']['type'] ?? '', "{$ability_id} should be an MCP tool." );
			$this->assertArrayHasKey( 'readonly', $meta['annotations'] );
			$this->assertArrayHasKey( 'destructive', $meta['annotations'] );
			$this->assertArrayHasKey( 'idempotent', $meta['annotations'] );
			$this->assertArrayNotHasKey( 'expose_in_deprecated_woocommerce_mcp', $meta );
		}
	}

	/**
	 * @testdox Should mark write abilities as destructive and queries as readonly/idempotent.
	 */
	public function test_canonical_ability_annotations_match_intent(): void {
		$expectations = array(
			'woocommerce/products-query'      => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/product-create'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/product-update'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/product-delete'      => array(
				'readonly'    => false,
				'idempotent'  => true,
				'destructive' => true,
			),
			'woocommerce/orders-query'        => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/order-update-status' => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/order-add-note'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
		);

		foreach ( $expectations as $ability_id => $annotations ) {
			$meta = wp_get_ability( $ability_id )->get_meta();

			foreach ( $annotations as $key => $value ) {
				$this->assertSame(
					$value,
					$meta['annotations'][ $key ] ?? null,
					"{$ability_id} should have annotations.{$key}=" . ( $value ? 'true' : 'false' ) . '.'
				);
			}
		}
	}

	/**
	 * @testdox Should advertise product statuses that product responses can return.
	 */
	public function test_product_output_schema_allows_response_statuses(): void {
		$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();
		$status_enum   = $output_schema['properties']['product']['properties']['status']['enum'] ?? array();

		$this->assertContains( 'auto-draft', $status_enum );
		$this->assertContains( 'trash', $status_enum );
		$this->assertContains( 'publish', $status_enum );
	}

	/**
	 * @testdox Should describe product output primitives using WooCommerce registries.
	 */
	public function test_product_output_schema_uses_woocommerce_primitive_constraints(): void {
		$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();
		$product       = $output_schema['properties']['product']['properties'] ?? array();

		$this->assertSame( 'uri', $product['permalink']['format'] ?? null );
		$this->assertSame( array( 'string', 'null' ), $product['permalink']['type'] ?? null );
		$this->assertContains( get_woocommerce_currency(), $product['currency']['enum'] ?? array() );
		$this->assertSame(
			array( wc_is_stock_amount_integer() ? 'integer' : 'number', 'null' ),
			$product['stock_quantity']['type'] ?? null
		);
	}

	/**
	 * @testdox Should describe collection pagination metadata in query outputs.
	 */
	public function test_collection_output_schemas_describe_pagination_metadata(): void {
		$schemas = array(
			'woocommerce/products-query' => 'products',
			'woocommerce/orders-query'   => 'orders',
		);

		foreach ( $schemas as $ability_id => $collection_key ) {
			$properties = wp_get_ability( $ability_id )->get_output_schema()['properties'] ?? array();

			$this->assertArrayHasKey( $collection_key, $properties );
			$this->assertArrayHasKey( 'total_pages', $properties );
			$this->assertArrayHasKey( 'page', $properties );
			$this->assertArrayHasKey( 'per_page', $properties );
			$this->assertArrayNotHasKey( 'total', $properties );
			$this->assertNotEmpty( $properties[ $collection_key ]['description'] ?? '' );
			$this->assertNotEmpty( $properties['total_pages']['description'] ?? '' );
			$this->assertNotEmpty( $properties['page']['description'] ?? '' );
			$this->assertNotEmpty( $properties['per_page']['description'] ?? '' );
		}
	}

	/**
	 * @testdox Should expose agent-friendly product type alias inputs for product operations.
	 */
	public function test_product_schema_uses_agent_friendly_product_types(): void {
		$query_schema  = wp_get_ability( 'woocommerce/products-query' )->get_input_schema();
		$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$update_schema = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();

		$expected_aliases = array( 'physical', 'virtual', 'digital', 'affiliate', 'grouped' );

		$this->assertSame( $expected_aliases, $query_schema['properties']['product_type_alias']['enum'] ?? array() );
		$this->assertSame( $expected_aliases, $this->get_product_type_aliases_from_schema_branches( $create_schema ) );
		$this->assertSame( $expected_aliases, $this->get_product_type_aliases_from_schema_branches( $update_schema ) );
		$this->assertSame( 'physical', $this->get_product_type_alias_schema_branch( $create_schema, 'physical' )['properties']['product_type_alias']['default'] ?? null );
		$this->assertArrayNotHasKey( 'default', $this->get_product_type_alias_schema_branch( $update_schema, 'physical' )['properties']['product_type_alias'] ?? array() );
		$this->assertNotContains( 'variable', $query_schema['properties']['product_type_alias']['enum'] ?? array() );
		$this->assertNotContains( 'variable', $this->get_product_type_aliases_from_schema_branches( $create_schema ) );
		$this->assertArrayNotHasKey(
			'product_type_alias',
			wp_get_ability( 'woocommerce/product-create' )->get_output_schema()['properties']['product']['properties'] ?? array()
		);
		$this->assertArrayNotHasKey( 'default', $create_schema );
		$this->assertArrayNotHasKey( 'default', $update_schema );
	}

	/**
	 * @testdox Should expose only alias-relevant product fields in mutation schema branches.
	 */
	public function test_product_mutation_schema_branches_only_include_alias_relevant_fields(): void {
		$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$update_schema = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();

		$physical_properties  = $this->get_product_type_alias_schema_branch( $create_schema, 'physical' )['properties'];
		$affiliate_properties = $this->get_product_type_alias_schema_branch( $create_schema, 'affiliate' )['properties'];
		$grouped_properties   = $this->get_product_type_alias_schema_branch( $create_schema, 'grouped' )['properties'];
		$common_properties    = $update_schema['oneOf'][0]['properties'];

		$this->assertArrayNotHasKey( 'external_url', $physical_properties );
		$this->assertArrayNotHasKey( 'button_text', $physical_properties );
		$this->assertArrayNotHasKey( 'grouped_products', $physical_properties );

		$this->assertArrayNotHasKey( 'manage_stock', $affiliate_properties );
		$this->assertArrayNotHasKey( 'stock_quantity', $affiliate_properties );
		$this->assertArrayNotHasKey( 'grouped_products', $affiliate_properties );

		$this->assertArrayNotHasKey( 'regular_price', $grouped_properties );
		$this->assertArrayNotHasKey( 'sale_price', $grouped_properties );
		$this->assertArrayNotHasKey( 'stock_status', $grouped_properties );

		$this->assertArrayNotHasKey( 'product_type_alias', $common_properties );
		$this->assertArrayNotHasKey( 'regular_price', $common_properties );
		$this->assertArrayNotHasKey( 'external_url', $common_properties );
		$this->assertArrayNotHasKey( 'grouped_products', $common_properties );
	}

	/**
	 * @testdox Should describe order output primitives using WooCommerce registries.
	 */
	public function test_order_output_schema_uses_woocommerce_primitive_constraints(): void {
		$output_schema = wp_get_ability( 'woocommerce/orders-query' )->get_output_schema();
		$order         = $output_schema['properties']['orders']['items']['properties'] ?? array();

		$this->assertContains( 'auto-draft', $order['status']['enum'] ?? array() );
		$this->assertContains( 'trash', $order['status']['enum'] ?? array() );
		$this->assertContains( 'checkout-draft', $order['status']['enum'] ?? array() );
		$this->assertContains( get_woocommerce_currency(), $order['currency']['enum'] ?? array() );
		$this->assertSame( array( 'string', 'null' ), $order['billing_email']['type'] ?? null );
		$this->assertSame( 'email', $order['billing_email']['format'] ?? null );
		$this->assertNotEmpty( $order['line_items']['description'] ?? '' );
		$this->assertSame( 'array', $order['line_items']['type'] ?? null );
		$this->assertSame( 'object', $order['line_items']['items']['type'] ?? null );
		$this->assertArrayHasKey( 'quantity', $order['line_items']['items']['properties'] ?? array() );
	}

	/**
	 * @testdox Should exclude checkout-draft from order status inputs.
	 */
	public function test_order_status_input_schemas_exclude_checkout_draft(): void {
		$query_schema  = wp_get_ability( 'woocommerce/orders-query' )->get_input_schema();
		$update_schema = wp_get_ability( 'woocommerce/order-update-status' )->get_input_schema();

		$this->assertNotContains( 'checkout-draft', $query_schema['properties']['status']['enum'] ?? array() );
		$this->assertNotContains( 'checkout-draft', $update_schema['properties']['status']['enum'] ?? array() );
	}

	/**
	 * @testdox Should register extension ability classes appended via the loader filter.
	 */
	public function test_loader_filter_accepts_valid_extension_classes(): void {
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_extension_ability_definition_class' ) );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_extension_ability_definition_class' ) );

		$this->registered_ability_ids[] = TestExtensionAbilityDefinition::ABILITY_ID;
		$ability                        = wp_get_ability( TestExtensionAbilityDefinition::ABILITY_ID );

		$this->assertNotNull( $ability, 'Extension ability should be registered.' );
		$this->assertSame( 'woocommerce', $ability->get_category() );

		$result = $ability->execute();

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['ok'] );
	}

	/**
	 * @testdox Should skip extension ability classes that use the reserved WooCommerce namespace.
	 */
	public function test_loader_filter_skips_extension_classes_using_reserved_woocommerce_namespace(): void {
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_reserved_woocommerce_ability_definition_class' ) );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_reserved_woocommerce_ability_definition_class' ) );

		$ability = wp_get_ability( TestReservedWooAbilityDefinition::ABILITY_ID );

		$this->assert_products_query_ability_is_canonical( $ability );
	}

	/**
	 * @testdox Should preserve core WooCommerce definitions when the reserved namespace was registered first.
	 */
	public function test_loader_preserves_core_definition_when_reserved_namespace_ability_exists(): void {
		$this->unregister_domain_abilities();

		$shadow_callback = static function (): void {
			wp_register_ability(
				TestReservedWooAbilityDefinition::ABILITY_ID,
				TestReservedWooAbilityDefinition::get_registration_args()
			);
		};
		$logger          = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		$logger_filter   = static function () use ( $logger ) {
			return $logger;
		};

		$logger
			->expects( $this->once() )
			->method( 'warning' )
			->with(
				$this->stringContains( 'previously registered ability' ),
				$this->callback(
					static function ( $context ): bool {
						return is_array( $context )
							&& 'woocommerce-abilities' === ( $context['source'] ?? null )
							&& TestReservedWooAbilityDefinition::ABILITY_ID === ( $context['ability_name'] ?? null )
							&& ProductsQuery::class === ( $context['definition_class'] ?? null )
							&& 'woocommerce/' === ( $context['reserved_prefix'] ?? null );
					}
				)
			);

		add_filter( 'woocommerce_logging_class', $logger_filter );
		add_action( 'wp_abilities_api_init', $shadow_callback, 5 );
		add_action( 'wp_abilities_api_init', array( AbilitiesLoader::class, 'register_abilities' ), 10 );

		try {
			do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
			remove_action( 'wp_abilities_api_init', $shadow_callback, 5 );
			remove_action( 'wp_abilities_api_init', array( AbilitiesLoader::class, 'register_abilities' ), 10 );
		}

		$ability = wp_get_ability( TestReservedWooAbilityDefinition::ABILITY_ID );

		$this->assert_products_query_ability_is_canonical( $ability );
	}

	/**
	 * @testdox Should not replace canonical abilities when registration is called again in the same request.
	 */
	public function test_register_abilities_is_idempotent_for_existing_canonical_abilities(): void {
		$logger        = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		$logger_filter = static function () use ( $logger ) {
			return $logger;
		};

		$logger
			->expects( $this->never() )
			->method( 'warning' );

		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			$this->register_domain_abilities();
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$this->assertNotNull( wp_get_ability( $ability_id ), "{$ability_id} should remain registered." );
		}
	}

	/**
	 * @testdox Should ignore filter entries that are not strings or AbilityDefinition implementations.
	 */
	public function test_loader_filter_skips_invalid_entries(): void {
		$callback = static function ( array $classes ): array {
			$classes[] = '\\Some\\Class\\That\\Does\\Not\\Exist';
			// Real class but not an AbilityDefinition.
			$classes[] = \WC_Order::class;
			// Wrong type.
			$classes[] = 42;

			return $classes;
		};

		add_filter( 'woocommerce_ability_definition_classes', $callback );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', $callback );

		// Canonical abilities should still register; nothing extra.
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$this->assertNotNull( wp_get_ability( $ability_id ), "{$ability_id} should remain registered." );
		}
	}

	/**
	 * @testdox Should keep canonical abilities when the loader filter returns an invalid value.
	 */
	public function test_loader_filter_cannot_remove_canonical_abilities_with_invalid_return(): void {
		$this->unregister_domain_abilities();

		$callback = static function () {
			return 'not-a-class-list';
		};

		add_filter( 'woocommerce_ability_definition_classes', $callback );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', $callback );

		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$this->assertNotNull( wp_get_ability( $ability_id ), "{$ability_id} should remain registered." );
		}
	}

	/**
	 * @testdox Should register WooCommerce ability categories idempotently.
	 */
	public function test_ability_category_registration_is_idempotent(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			$this->markTestSkipped( 'Abilities API category registry is not available.' );
		}

		$callback = static function () {
			AbilitiesCategories::register_categories();
			AbilitiesCategories::register_categories();
		};

		add_action( 'wp_abilities_api_categories_init', $callback );
		do_action( 'wp_abilities_api_categories_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise category registration on its required lifecycle hook.
		remove_action( 'wp_abilities_api_categories_init', $callback );

		$this->assertTrue( wp_has_ability_category( 'woocommerce' ) );
		$this->assertTrue( wp_has_ability_category( 'woocommerce-rest' ) );
		$this->registered_category_ids[] = 'woocommerce-rest';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Permission negative paths
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should reject every canonical ability when no user is authenticated.
	 *
	 * @dataProvider provider_canonical_abilities_with_minimal_input
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Minimal valid-shape input.
	 */
	public function test_abilities_reject_unauthenticated_user( string $ability_id, array $input ): void {
		wp_set_current_user( 0 );

		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result, "{$ability_id} should reject unauthenticated users." );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject every canonical ability for a subscriber with no shop caps.
	 *
	 * @dataProvider provider_canonical_abilities_with_minimal_input
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Minimal valid-shape input.
	 */
	public function test_abilities_reject_subscriber_without_caps( string $ability_id, array $input ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result, "{$ability_id} should reject subscribers without shop caps." );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Provides ability IDs and minimal valid-shape input for cap negative-path tests.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_canonical_abilities_with_minimal_input(): array {
		// Valid-shape input only — permission check should fire before execute.
		return array(
			'products-query'      => array(
				'woocommerce/products-query',
				array(
					'id' => 1,
				),
			),
			'product-create'      => array(
				'woocommerce/product-create',
				array(
					'name' => 'Forbidden',
				),
			),
			'product-update'      => array(
				'woocommerce/product-update',
				array(
					'id'   => 1,
					'name' => 'Forbidden',
				),
			),
			'product-delete'      => array(
				'woocommerce/product-delete',
				array(
					'id' => 1,
				),
			),
			'orders-query'        => array(
				'woocommerce/orders-query',
				array(
					'id' => 1,
				),
			),
			'order-update-status' => array(
				'woocommerce/order-update-status',
				array(
					'id'     => 1,
					'status' => 'processing',
				),
			),
			'order-add-note'      => array(
				'woocommerce/order-add-note',
				array(
					'id'   => 1,
					'note' => 'denied',
				),
			),
		);
	}

	/**
	 * @testdox Should require object edit and delete permissions for product mutations.
	 */
	public function test_product_mutations_require_object_permissions(): void {
		$product_to_update           = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Owned by another user' ) );
		$product_to_delete           = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Also owned by another user' ) );
		$this->created_product_ids[] = $product_to_update->get_id();
		$this->created_product_ids[] = $product_to_delete->get_id();
		$limited_product_manager_id  = $this->create_user_with_caps(
			array(
				'edit_products',
				'edit_published_products',
				'delete_products',
				'delete_published_products',
			)
		);
		wp_set_current_user( $limited_product_manager_id );

		$cases = array(
			'woocommerce/product-update' => array(
				'id'   => $product_to_update->get_id(),
				'name' => 'Unauthorized update',
			),
			'woocommerce/product-delete' => array(
				'id' => $product_to_delete->get_id(),
			),
		);

		foreach ( $cases as $ability_id => $input ) {
			$result = wp_get_ability( $ability_id )->execute( $input );

			$this->assertWPError( $result, "{$ability_id} should reject users without object-level access." );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should require object edit permissions for order mutations.
	 */
	public function test_order_mutations_require_object_edit_permissions(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$limited_order_manager_id  = $this->create_user_with_caps(
			array(
				'edit_shop_orders',
				'edit_published_shop_orders',
			)
		);
		wp_set_current_user( $limited_order_manager_id );

		$cases = array(
			'woocommerce/order-update-status' => array(
				'id'     => $order->get_id(),
				'status' => 'processing',
			),
			'woocommerce/order-add-note'      => array(
				'id'   => $order->get_id(),
				'note' => 'Unauthorized note',
			),
		);

		foreach ( $cases as $ability_id => $input ) {
			$result = wp_get_ability( $ability_id )->execute( $input );

			$this->assertWPError( $result, "{$ability_id} should reject users without object-level access." );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should require publish_products to transition an existing draft to statuses that publish capability protects.
	 *
	 * @dataProvider provider_product_statuses_requiring_publish_cap
	 *
	 * @param string $status Product status.
	 */
	public function test_product_update_publish_transition_requires_publish_cap( string $status ): void {
		$product                     = \WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );
		$this->created_product_ids[] = $product->get_id();

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'edit_products' );
		$user->add_cap( 'edit_others_products' );
		$user->add_cap( 'edit_published_products' );
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'     => $product->get_id(),
				'status' => $status,
			)
		);

		$this->assertWPError( $result, "A user without publish_products should not be able to set status {$status}." );
		$this->assertSame( 'woocommerce_product_publish_forbidden', $result->get_error_code() );
	}

	/**
	 * @testdox Should allow non-status updates to published products without publish_products.
	 */
	public function test_product_update_allows_published_product_edits_without_publish_cap_when_status_is_unchanged(): void {
		$product                     = \WC_Helper_Product::create_simple_product( true, array( 'status' => 'publish' ) );
		$this->created_product_ids[] = $product->get_id();

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'edit_products' );
		$user->add_cap( 'edit_others_products' );
		$user->add_cap( 'edit_published_products' );
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Updated Published Product',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'Updated Published Product', $result['product']['name'] );
		$this->assertSame( 'publish', $result['product']['status'] );
	}

	/**
	 * Product statuses that require publish_products for a transition.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_product_statuses_requiring_publish_cap(): array {
		return array(
			'publish' => array( 'publish' ),
			'future'  => array( 'future' ),
			'private' => array( 'private' ),
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Input validation
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should reject order status updates with an unknown status slug.
	 */
	public function test_order_update_status_rejects_invalid_status_slug(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'totally-bogus',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject product creation with a status not in the allowed enum.
	 */
	public function test_product_create_rejects_unknown_status(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'   => 'Bad Status Product',
				'status' => 'not-a-real-status',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject auto-draft as a product mutation status.
	 */
	public function test_product_create_rejects_auto_draft_status(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'   => 'Auto Draft Product',
				'status' => 'auto-draft',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject extra unknown fields on product create input.
	 */
	public function test_product_create_rejects_unknown_input_field(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'        => 'Mass Assigned',
				'invoice_url' => 'https://attacker.example.com',
				'admin_notes' => 'tampering',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject orders-query orderby values outside the allowed enum.
	 */
	public function test_orders_query_rejects_unknown_orderby(): void {
		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array( 'orderby' => 'malicious_field' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject uppercase order directions.
	 */
	public function test_orders_query_rejects_uppercase_order_direction(): void {
		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array( 'order' => 'ASC' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject non-numeric product prices during schema validation.
	 *
	 * @dataProvider provider_invalid_product_price_inputs
	 *
	 * @param string $price Invalid price input.
	 */
	public function test_product_create_rejects_non_numeric_prices( string $price ): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Invalid Price Product',
				'regular_price' => $price,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Provides invalid product price inputs.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_invalid_product_price_inputs(): array {
		return array(
			'text'               => array( 'free' ),
			'currency-symbol'    => array( '$10.00' ),
			'thousand-separator' => array( '1,234.56' ),
		);
	}

	/**
	 * @testdox Should accept the configured WooCommerce decimal separator for product prices.
	 */
	public function test_product_create_accepts_configured_decimal_separator_for_prices(): void {
		$original_decimal_separator = get_option( 'woocommerce_price_decimal_sep' );
		$created                    = null;

		$this->unregister_domain_abilities();
		update_option( 'woocommerce_price_decimal_sep', ',' );

		try {
			$this->register_domain_abilities();

			$created = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'          => 'Localized Price Product',
					'regular_price' => '10,99',
				)
			);
		} finally {
			$this->unregister_domain_abilities();
			update_option( 'woocommerce_price_decimal_sep', $original_decimal_separator );
			$this->register_domain_abilities();
		}

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( '10.99', $created['product']['regular_price'] );
	}

	/**
	 * @testdox Should reject whitespace-only order notes during schema validation.
	 */
	public function test_order_add_note_schema_rejects_whitespace_only_note(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => '   ',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject negative entity IDs during schema validation.
	 *
	 * @dataProvider provider_negative_entity_id_inputs
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Ability input.
	 */
	public function test_entity_id_schemas_reject_negative_ids( string $ability_id, array $input ): void {
		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Provides negative ID inputs for entity abilities.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_negative_entity_id_inputs(): array {
		return array(
			'products-query'      => array(
				'woocommerce/products-query',
				array( 'id' => -123 ),
			),
			'product-update'      => array(
				'woocommerce/product-update',
				array(
					'id'   => -123,
					'name' => 'Invalid',
				),
			),
			'product-delete'      => array(
				'woocommerce/product-delete',
				array( 'id' => -123 ),
			),
			'orders-query'        => array(
				'woocommerce/orders-query',
				array( 'id' => -123 ),
			),
			'order-update-status' => array(
				'woocommerce/order-update-status',
				array(
					'id'     => -123,
					'status' => 'processing',
				),
			),
			'order-add-note'      => array(
				'woocommerce/order-add-note',
				array(
					'id'   => -123,
					'note' => 'Invalid',
				),
			),
		);
	}

	/**
	 * @testdox Should reject negative order query filter IDs during schema validation.
	 *
	 * @dataProvider provider_negative_order_query_filter_id_inputs
	 *
	 * @param array $input Ability input.
	 */
	public function test_orders_query_filter_id_schemas_reject_negative_ids( array $input ): void {
		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( $input );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Provides negative order query filter ID inputs.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_negative_order_query_filter_id_inputs(): array {
		return array(
			'customer-id' => array(
				array( 'customer_id' => -123 ),
			),
			'parent'      => array(
				array( 'parent' => -123 ),
			),
		);
	}

	/**
	 * @testdox Should allow customer_id=0 to filter guest orders.
	 */
	public function test_orders_query_allows_customer_id_zero_for_guest_orders(): void {
		$email = wp_unique_id( 'abilities-guest-order-' ) . '@example.com';
		$order = \WC_Helper_Order::create_order();
		$order->set_customer_id( 0 );
		$order->set_billing_email( $email );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'customer_id'   => 0,
				'billing_email' => $email,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( array( $order->get_id() ), array_column( $result['orders'], 'id' ) );
	}

	/**
	 * Provides order storage engine states.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function provider_order_storage_engines(): array {
		return array(
			'legacy' => array( false ),
			'hpos'   => array( true ),
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Execution behaviors
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should not force products-query to published products by default.
	 */
	public function test_products_query_does_not_force_publish_status_by_default(): void {
		$published                   = \WC_Helper_Product::create_simple_product(
			true,
			array( 'name' => 'Public Item' )
		);
		$this->created_product_ids[] = $published->get_id();

		$draft                       = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Draft Item',
				'status' => 'draft',
			)
		);
		$this->created_product_ids[] = $draft->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array() );

		$this->assertNotWPError( $result );
		$ids = array_column( $result['products'], 'id' );
		$this->assertContains( $published->get_id(), $ids );
		$this->assertContains( $draft->get_id(), $ids );
	}

	/**
	 * @testdox Should return drafts when explicitly filtered by status=draft.
	 */
	public function test_products_query_returns_drafts_when_explicitly_requested(): void {
		$draft                       = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Hidden Draft',
				'status' => 'draft',
				'sku'    => 'hidden-draft-sku',
			)
		);
		$this->created_product_ids[] = $draft->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'status' => 'draft',
				'sku'    => 'hidden-draft-sku',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total_pages'] );
		$this->assertSame( $draft->get_id(), $result['products'][0]['id'] );
	}

	/**
	 * @testdox Should include currency context in product responses.
	 */
	public function test_product_response_includes_currency_context(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Priced Product',
				'regular_price' => '12.34',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$this->assertSame( get_woocommerce_currency(), $created['product']['currency'] );
		$this->assertNotEmpty( $created['product']['currency_symbol'] );
		$this->assertSame( '12.34', $created['product']['regular_price'] );
	}

	/**
	 * @testdox Should map digital products to simple virtual downloadable products.
	 */
	public function test_product_create_maps_digital_product_type_to_simple_downloadable_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Digital Product',
				'product_type_alias' => 'digital',
				'regular_price'      => '19.99',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'simple', $created['product']['type'] );
		$this->assertTrue( $created['product']['virtual'] );
		$this->assertTrue( $created['product']['downloadable'] );
	}

	/**
	 * @testdox Should map virtual products to simple virtual non-downloadable products.
	 */
	public function test_product_create_maps_virtual_product_type_alias_to_simple_virtual_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Virtual Product',
				'product_type_alias' => 'virtual',
				'regular_price'      => '29.99',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'simple', $created['product']['type'] );
		$this->assertTrue( $created['product']['virtual'] );
		$this->assertFalse( $created['product']['downloadable'] );
	}

	/**
	 * @testdox Should query simple products by agent-friendly physical, virtual, and digital product type aliases.
	 */
	public function test_products_query_maps_agent_friendly_product_types_to_product_fields(): void {
		$physical_sku = wp_unique_id( 'abilities-physical-' );
		$virtual_sku  = wp_unique_id( 'abilities-virtual-' );
		$digital_sku  = wp_unique_id( 'abilities-digital-' );
		$hybrid_sku   = wp_unique_id( 'abilities-downloadable-shippable-' );

		$physical = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Physical Query Product',
				'product_type_alias' => 'physical',
				'sku'                => $physical_sku,
			)
		);

		$this->assertNotWPError( $physical );
		$this->created_product_ids[] = $physical['product']['id'];

		$virtual = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Virtual Query Product',
				'product_type_alias' => 'virtual',
				'sku'                => $virtual_sku,
			)
		);

		$this->assertNotWPError( $virtual );
		$this->created_product_ids[] = $virtual['product']['id'];

		$digital = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Digital Query Product',
				'product_type_alias' => 'digital',
				'sku'                => $digital_sku,
			)
		);

		$this->assertNotWPError( $digital );
		$this->created_product_ids[] = $digital['product']['id'];

		$hybrid = \WC_Helper_Product::create_simple_product();
		$hybrid->set_sku( $hybrid_sku );
		$hybrid->set_virtual( false );
		$hybrid->set_downloadable( true );
		$hybrid->save();
		$this->created_product_ids[] = $hybrid->get_id();

		$physical_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'product_type_alias' => 'physical',
				'sku'                => $physical_sku,
			)
		);

		$this->assertNotWPError( $physical_result );
		$this->assertSame( 1, $physical_result['total_pages'] );
		$this->assertSame( $physical['product']['id'], $physical_result['products'][0]['id'] );

		$virtual_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'product_type_alias' => 'virtual',
				'sku'                => $virtual_sku,
			)
		);

		$this->assertNotWPError( $virtual_result );
		$this->assertSame( 1, $virtual_result['total_pages'] );
		$this->assertSame( $virtual['product']['id'], $virtual_result['products'][0]['id'] );

		$digital_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'product_type_alias' => 'digital',
				'sku'                => $digital_sku,
			)
		);

		$this->assertNotWPError( $digital_result );
		$this->assertSame( 1, $digital_result['total_pages'] );
		$this->assertSame( $digital['product']['id'], $digital_result['products'][0]['id'] );

		$mismatched_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'product_type_alias' => 'physical',
				'sku'                => $digital_sku,
			)
		);

		$this->assertNotWPError( $mismatched_result );
		$this->assertSame( 0, $mismatched_result['total_pages'] );

		$unfiltered_hybrid_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'sku' => $hybrid_sku,
			)
		);

		$this->assertNotWPError( $unfiltered_hybrid_result );
		$this->assertSame( $hybrid->get_id(), $unfiltered_hybrid_result['products'][0]['id'] );

		$physical_hybrid_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'product_type_alias' => 'physical',
				'sku'                => $hybrid_sku,
			)
		);

		$this->assertNotWPError( $physical_hybrid_result );
		$this->assertSame( 0, $physical_hybrid_result['total_pages'] );
	}

	/**
	 * @testdox Should map affiliate products to external products.
	 */
	public function test_product_create_maps_affiliate_product_type_to_external_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Affiliate Product',
				'product_type_alias' => 'affiliate',
				'regular_price'      => '49.99',
				'external_url'       => 'https://example.com/buy',
				'button_text'        => 'Buy elsewhere',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'external', $created['product']['type'] );
		$this->assertSame( 'https://example.com/buy', $created['product']['external_url'] );
		$this->assertSame( 'Buy elsewhere', $created['product']['button_text'] );
	}

	/**
	 * @testdox Should map grouped products to grouped WooCommerce products with children.
	 */
	public function test_product_create_maps_grouped_product_type_to_grouped_product(): void {
		$child                       = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $child->get_id();

		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Grouped Product',
				'product_type_alias' => 'grouped',
				'grouped_products'   => array( $child->get_id() ),
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'grouped', $created['product']['type'] );
		$this->assertSame( array( $child->get_id() ), $created['product']['grouped_products'] );
	}

	/**
	 * @testdox Should support the WooCommerce SKU wildcard for products with any non-empty SKU.
	 */
	public function test_products_query_supports_sku_wildcard_for_products_with_any_sku(): void {
		$sku_product = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name' => 'SKU Product',
				'sku'  => wp_unique_id( 'abilities-any-sku-' ),
			)
		);

		$this->assertNotWPError( $sku_product );
		$this->created_product_ids[] = $sku_product['product']['id'];

		$no_sku_product = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name' => 'No SKU Product',
			)
		);

		$this->assertNotWPError( $no_sku_product );
		$this->created_product_ids[] = $no_sku_product['product']['id'];

		$result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'sku'      => '*',
				'per_page' => 100,
			)
		);

		$this->assertNotWPError( $result );

		$ids = array_column( $result['products'], 'id' );
		$this->assertContains( $sku_product['product']['id'], $ids );
		$this->assertNotContains( $no_sku_product['product']['id'], $ids );
	}

	/**
	 * @testdox Should reject invalid grouped product IDs before setting children.
	 */
	public function test_product_create_rejects_invalid_grouped_product_ids(): void {
		$result = ProductCreate::execute(
			array(
				'name'               => 'Invalid Grouped Product',
				'product_type_alias' => 'grouped',
				'grouped_products'   => array( -12 ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_grouped_products_invalid', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject fields that are unsupported by the selected product type alias.
	 */
	public function test_product_create_rejects_fields_not_supported_by_product_type_alias(): void {
		$result = ProductCreate::execute(
			array(
				'name'               => 'Invalid Grouped Product',
				'product_type_alias' => 'grouped',
				'regular_price'      => '19.99',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );
	}

	/**
	 * @testdox Should return an error when product creation fails to persist.
	 */
	public function test_product_create_returns_error_when_save_fails(): void {
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		try {
			$result = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name' => 'Failed Product',
				)
			);
		} finally {
			remove_filter( 'wp_insert_post_empty_content', '__return_true' );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_create_failed', $result->get_error_code() );
	}

	/**
	 * @testdox Should support fractional product stock quantities when WooCommerce is configured for them.
	 */
	public function test_product_stock_quantity_schema_allows_fractional_stock_amounts(): void {
		$this->unregister_domain_abilities();
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', array( $this, 'preserve_fractional_stock_amount' ) );

		try {
			$this->register_domain_abilities();

			$input_schema  = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
			$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();

			$this->assertSame( 'number', $this->get_product_type_alias_schema_branch( $input_schema, 'physical' )['properties']['stock_quantity']['type'] ?? null );
			$this->assertSame(
				array( 'number', 'null' ),
				$output_schema['properties']['product']['properties']['stock_quantity']['type'] ?? null
			);

			$created = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'           => 'Fractional Stock Product',
					'manage_stock'   => true,
					'stock_quantity' => 1.5,
				)
			);

			$this->assertNotWPError( $created );
			$this->created_product_ids[] = $created['product']['id'];
			$this->assertSame( 1.5, $created['product']['stock_quantity'] );
		} finally {
			remove_filter( 'woocommerce_stock_amount', array( $this, 'preserve_fractional_stock_amount' ) );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}
	}

	/**
	 * @testdox Should update product properties and reflect changes in the response.
	 */
	public function test_product_update_changes_props_and_returns_updated_response(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'physical',
				'name'               => 'Updated Name',
				'regular_price'      => '99.00',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'Updated Name', $result['product']['name'] );
		$this->assertSame( '99.00', $result['product']['regular_price'] );
	}

	/**
	 * @testdox Should return a structured error when product updates throw during save.
	 */
	public function test_product_update_returns_error_when_save_throws_exception(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();
		$throw_on_update             = static function (): void {
			throw new \Exception( 'Generic product save failure.' );
		};
		$logger                      = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		$logger_filter               = static function () use ( $logger ) {
			return $logger;
		};

		$logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				$this->stringContains( 'failed to save product' ),
				$this->callback(
					static function ( $context ) use ( $product ): bool {
						return is_array( $context )
							&& 'woocommerce-abilities' === ( $context['source'] ?? null )
							&& 'woocommerce_product_update_failed' === ( $context['failure_code'] ?? null )
							&& $product->get_id() === ( $context['product_id'] ?? null )
							&& \Exception::class === ( $context['exception'] ?? null )
							&& 'Generic product save failure.' === ( $context['error_message'] ?? null );
					}
				)
			);

		add_filter( 'woocommerce_logging_class', $logger_filter );
		add_action( 'woocommerce_update_product', $throw_on_update );

		try {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'   => $product->get_id(),
					'name' => 'Updated Name',
				)
			);
		} finally {
			remove_action( 'woocommerce_update_product', $throw_on_update );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_update_failed', $result->get_error_code() );
		$this->assertSame( 'Failed to save product.', $result->get_error_message() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should apply product type configuration when updating a product type alias.
	 */
	public function test_product_update_applies_product_type_configuration(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'digital',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'simple', $result['product']['type'] );
		$this->assertTrue( $result['product']['virtual'] );
		$this->assertTrue( $result['product']['downloadable'] );
	}

	/**
	 * @testdox Should reject product updates that include no changed fields.
	 */
	public function test_product_update_requires_at_least_one_mutation_field(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id' => $product->get_id(),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_update_no_fields', $result->get_error_code() );
	}

	/**
	 * @testdox Should hard-delete a product when force=true.
	 */
	public function test_product_delete_force_true_hard_deletes(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertSame( $product->get_id(), $result['id'] );
		$this->assertNull( get_post( $product->get_id() ) );
	}

	/**
	 * @testdox Should reject product variation IDs when deleting products.
	 */
	public function test_product_delete_rejects_variation_ids(): void {
		$variation_id = $this->create_variation_product_id_for_test();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $variation_id,
				'force' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
		$this->assertNotNull( wc_get_product( $variation_id ) );
	}

	/**
	 * @testdox Should trash a product by default.
	 */
	public function test_product_delete_defaults_to_trash(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id' => $product->get_id(),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should reject soft deletes when product trashing is not supported.
	 */
	public function test_product_delete_rejects_soft_delete_when_trash_not_supported(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		add_filter( 'woocommerce_product_object_trashable', '__return_false' );

		try {
			$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
				array(
					'id' => $product->get_id(),
				)
			);
		} finally {
			remove_filter( 'woocommerce_product_object_trashable', '__return_false' );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_trash_not_supported', $result->get_error_code() );
		$this->assertNotSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should return an error when product deletion is blocked.
	 */
	public function test_product_delete_returns_error_when_delete_is_blocked(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$callback = static function () {
			return false;
		};

		add_filter( 'woocommerce_pre_delete_product', $callback );

		try {
			$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
				array(
					'id' => $product->get_id(),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_delete_product', $callback );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_delete_failed', $result->get_error_code() );
		$this->assertNotSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should return a not-found error when querying an unknown product ID.
	 */
	public function test_products_query_returns_not_found_for_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject product mutation unknown IDs without leaking existence.
	 *
	 * @dataProvider provider_product_mutation_unknown_id_inputs
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Ability input.
	 */
	public function test_product_mutations_reject_unknown_ids_without_leaking_existence( string $ability_id, array $input ): void {
		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Provides product mutation inputs with unknown IDs.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_product_mutation_unknown_id_inputs(): array {
		return array(
			'product-update' => array(
				'woocommerce/product-update',
				array(
					'id'   => 999999,
					'name' => 'Unknown Product',
				),
			),
			'product-delete' => array(
				'woocommerce/product-delete',
				array(
					'id' => 999999,
				),
			),
		);
	}

	/**
	 * @testdox Should reject product variation IDs when querying products by ID.
	 */
	public function test_products_query_rejects_variation_ids(): void {
		$variation_id = $this->create_variation_product_id_for_test();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $variation_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
	}

	/**
	 * @testdox Should return products without public permalinks without output validation errors.
	 */
	public function test_products_query_handles_products_without_public_permalink(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_status( 'auto-draft' );
		$product->save();
		$this->created_product_ids[] = $product->get_id();

		$filter_permalink = static function ( $permalink, $post ) use ( $product ) {
			return $post instanceof \WP_Post && $post->ID === $product->get_id() ? false : $permalink;
		};

		add_filter( 'post_type_link', $filter_permalink, 20, 2 );

		try {
			$result = wp_get_ability( 'woocommerce/products-query' )->execute(
				array(
					'id' => $product->get_id(),
				)
			);
		} finally {
			remove_filter( 'post_type_link', $filter_permalink, 20 );
		}

		$this->assertNotWPError( $result );
		$this->assertSame( 'auto-draft', $result['products'][0]['status'] );
		$this->assertNull( $result['products'][0]['permalink'] );
	}

	/**
	 * @testdox Should return a not-found error when orders-query receives an unknown order ID.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_returns_not_found_for_unknown_id( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_order_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should query orders by billing email and include line items when requested.
	 */
	public function test_orders_query_filters_by_billing_email_with_line_items(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_billing_email( 'domain-order-query@example.com' );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email'      => 'domain-order-query@example.com',
				'include_line_items' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total_pages'] );
		$this->assertSame( $order->get_id(), $result['orders'][0]['id'] );
		$this->assertNotEmpty( $result['orders'][0]['line_items'] );
		$this->assertNotEmpty( $result['orders'][0]['currency_symbol'] );
	}

	/**
	 * @testdox Should exclude checkout-draft orders from default order queries.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_excludes_checkout_draft_orders_by_default( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$email = wp_unique_id( 'abilities-checkout-draft-' ) . '@example.com';

		$processing_order = \WC_Helper_Order::create_order();
		$processing_order->set_billing_email( $email );
		$processing_order->set_status( 'processing' );
		$processing_order->save();
		$this->created_order_ids[] = $processing_order->get_id();

		$checkout_draft = \WC_Helper_Order::create_order();
		$checkout_draft->set_billing_email( $email );
		$checkout_draft->set_status( 'checkout-draft' );
		$checkout_draft->save();
		$this->created_order_ids[] = $checkout_draft->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'per_page'      => 10,
			)
		);

		$this->assertNotWPError( $result );
		$ids = array_column( $result['orders'], 'id' );
		$this->assertContains( $processing_order->get_id(), $ids );
		$this->assertNotContains( $checkout_draft->get_id(), $ids );
	}

	/**
	 * @testdox Should sort orders by ID across order storage engines.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_sorts_by_id_across_order_storage_engines( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$email  = wp_unique_id( 'abilities-order-id-sort-' ) . '@example.com';
		$first  = $this->create_order_for_query_sorting( $email, '2025-01-01T12:00:00', '2025-01-01T12:00:00' );
		$second = $this->create_order_for_query_sorting( $email, '2020-01-01T12:00:00', '2020-01-01T12:00:00' );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'orderby'       => 'id',
				'order'         => 'asc',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame(
			array( $first->get_id(), $second->get_id() ),
			array_column( $result['orders'], 'id' )
		);
	}

	/**
	 * @testdox Should sort orders by modified date across order storage engines.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_sorts_by_modified_date_across_order_storage_engines( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$email  = wp_unique_id( 'abilities-order-modified-sort-' ) . '@example.com';
		$first  = $this->create_order_for_query_sorting( $email, '2020-01-01T12:00:00', '2025-01-01T12:00:00' );
		$second = $this->create_order_for_query_sorting( $email, '2025-01-01T12:00:00', '2020-01-01T12:00:00' );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'orderby'       => 'date_modified',
				'order'         => 'asc',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame(
			array( $second->get_id(), $first->get_id() ),
			array_column( $result['orders'], 'id' )
		);
	}

	/**
	 * @testdox Should return null for absent billing email values.
	 */
	public function test_orders_query_returns_null_for_absent_billing_email(): void {
		$order = wc_create_order();
		$this->assertNotWPError( $order );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id' => $order->get_id(),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertNull( $result['orders'][0]['billing_email'] );
	}

	/**
	 * @testdox Should narrow orders-query results by date range.
	 */
	public function test_orders_query_filters_by_date_range(): void {
		$old_order = \WC_Helper_Order::create_order();
		$old_order->set_date_created( '2020-01-15T00:00:00' );
		$old_order->set_billing_email( 'date-range@example.com' );
		$old_order->save();
		$this->created_order_ids[] = $old_order->get_id();

		$new_order = \WC_Helper_Order::create_order();
		$new_order->set_date_created( '2025-01-15T00:00:00' );
		$new_order->set_billing_email( 'date-range@example.com' );
		$new_order->save();
		$this->created_order_ids[] = $new_order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => 'date-range@example.com',
				'date_after'    => '2024-01-01T00:00:00',
			)
		);

		$this->assertNotWPError( $result );
		$ids = array_column( $result['orders'], 'id' );
		$this->assertContains( $new_order->get_id(), $ids );
		$this->assertNotContains( $old_order->get_id(), $ids );
	}

	/**
	 * @testdox Should filter modified_before with time precision across order storage engines.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_filters_modified_before_with_time_precision( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$email = wp_unique_id( 'abilities-order-modified-before-' ) . '@example.com';
		$early = $this->create_order_for_query_sorting( $email, '2025-01-15T09:00:00', '2025-01-15T10:00:00' );
		$late  = $this->create_order_for_query_sorting( $email, '2025-01-15T09:00:00', '2025-01-15T12:00:00' );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email'   => $email,
				'modified_before' => '2025-01-15T11:00:00',
				'per_page'        => 2,
			)
		);

		$this->assertNotWPError( $result );
		$ids = array_column( $result['orders'], 'id' );
		$this->assertContains( $early->get_id(), $ids );
		$this->assertNotContains( $late->get_id(), $ids );
	}

	/**
	 * @testdox Should change order status and surface failure when status update fails.
	 */
	public function test_order_update_status_changes_status(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'processing',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'processing', $result['order']['status'] );
		$this->assertSame( 'processing', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should reject unchanged order status updates without adding notes.
	 */
	public function test_order_update_status_rejects_unchanged_status_without_adding_notes(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$note_count_before         = count(
			wc_get_order_notes(
				array(
					'order_id' => $order->get_id(),
				)
			)
		);

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => $order->get_status(),
				'note'   => 'This should be added through Add order note.',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_order_status_unchanged', $result->get_error_code() );
		$this->assertStringContainsString( 'woocommerce/order-add-note', $result->get_error_message() );
		$this->assertSame(
			$note_count_before,
			count(
				wc_get_order_notes(
					array(
						'order_id' => $order->get_id(),
					)
				)
			)
		);
	}

	/**
	 * @testdox Should treat order status updates as manual status changes.
	 */
	public function test_order_update_status_uses_manual_status_update(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$manual_update_observed    = false;

		$callback = static function ( $order_id, $status ) use ( $order, &$manual_update_observed ) {
			if ( $order->get_id() === $order_id && 'processing' === $status ) {
				$manual_update_observed = true;
			}
		};

		add_action( 'woocommerce_order_edit_status', $callback, 10, 2 );

		try {
			$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
				array(
					'id'     => $order->get_id(),
					'status' => 'processing',
				)
			);
		} finally {
			remove_action( 'woocommerce_order_edit_status', $callback, 10 );
		}

		$this->assertNotWPError( $result );
		$this->assertTrue( $manual_update_observed );
	}

	/**
	 * @testdox Should reject status updates against unknown order IDs without leaking existence.
	 */
	public function test_order_update_status_rejects_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => 999999,
				'status' => 'processing',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should attribute order notes to the acting user.
	 */
	public function test_order_add_note_attributes_to_acting_user(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$user_id = $this->factory->user->create(
			array(
				'role'         => 'shop_manager',
				'display_name' => 'Audit Trail Admin',
			)
		);
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Tracked by acting user.',
			)
		);

		$this->assertNotWPError( $result );

		$comment = get_comment( $result['note_id'] );
		$this->assertNotNull( $comment );
		$this->assertSame( 'Audit Trail Admin', $comment->comment_author );
	}

	/**
	 * @testdox Should preserve safe HTML in order notes.
	 */
	public function test_order_add_note_preserves_safe_html(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Packed with <strong>care</strong><script>alert("x")</script>.',
			)
		);

		$this->assertNotWPError( $result );

		$comment = get_comment( $result['note_id'] );
		$this->assertStringContainsString( '<strong>care</strong>', $comment->comment_content );
		$this->assertStringNotContainsString( '<script>', $comment->comment_content );
	}

	/**
	 * @testdox Should reject whitespace-only order notes during execution.
	 */
	public function test_order_add_note_execute_rejects_whitespace_only_note(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = OrderAddNote::execute(
			array(
				'id'   => $order->get_id(),
				'note' => '   ',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_order_note_required', $result->get_error_code() );
	}

	/**
	 * @testdox Should mark notes as customer-facing when customer_note=true.
	 */
	public function test_order_add_note_persists_customer_note_flag(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'            => $order->get_id(),
				'note'          => 'Visible to customer.',
				'customer_note' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( '1', get_comment_meta( $result['note_id'], 'is_customer_note', true ) );
	}

	/**
	 * Register the WooCommerce ability category for this test.
	 */
	private function register_woocommerce_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( 'woocommerce' ) ) {
			return;
		}

		$callback = static function () {
			wp_register_ability_category(
				'woocommerce',
				array(
					'label'       => 'WooCommerce',
					'description' => 'Canonical store management abilities.',
				)
			);
		};

		add_action( 'wp_abilities_api_categories_init', $callback );
		do_action( 'wp_abilities_api_categories_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_categories_init', $callback );

		$this->registered_category_ids[] = 'woocommerce';
	}

	/**
	 * Register canonical domain abilities for this test.
	 */
	private function register_domain_abilities(): void {
		$callback = array( AbilitiesLoader::class, 'register_abilities' );

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister canonical domain abilities for this test.
	 */
	private function unregister_domain_abilities(): void {
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Assert that a registered ability matches the canonical products-query definition.
	 *
	 * @param \WP_Ability|null $ability Ability instance.
	 */
	private function assert_products_query_ability_is_canonical( $ability ): void {
		$this->assertNotNull( $ability, 'Canonical products-query ability should be registered.' );

		$registration_args = ProductsQuery::get_registration_args();

		$this->assertSame( ProductsQuery::get_name(), $ability->get_name() );
		$this->assertSame( $registration_args['category'], $ability->get_category() );
		$this->assertSame( $registration_args['input_schema'], $ability->get_input_schema() );
		$this->assertSame( $registration_args['output_schema'], $ability->get_output_schema() );
	}

	/**
	 * Get product type aliases from a branched product input schema.
	 *
	 * @param array $schema Input schema.
	 * @return array<int, string>
	 */
	private function get_product_type_aliases_from_schema_branches( array $schema ): array {
		$aliases = array();

		foreach ( $schema['oneOf'] ?? array() as $branch ) {
			$alias = $branch['properties']['product_type_alias']['enum'][0] ?? null;

			if ( is_string( $alias ) ) {
				$aliases[] = $alias;
			}
		}

		return array_values( array_unique( $aliases ) );
	}

	/**
	 * Get a product input schema branch for a product type alias.
	 *
	 * @param array  $schema             Input schema.
	 * @param string $product_type_alias Product type alias.
	 * @return array
	 */
	private function get_product_type_alias_schema_branch( array $schema, string $product_type_alias ): array {
		foreach ( $schema['oneOf'] ?? array() as $branch ) {
			if ( in_array( $product_type_alias, $branch['properties']['product_type_alias']['enum'] ?? array(), true ) ) {
				return $branch;
			}
		}

		$this->fail( "Missing product type alias schema branch: {$product_type_alias}" );
		return array();
	}

	/**
	 * Create a user with specific capabilities.
	 *
	 * @param array<int, string> $capabilities Capabilities to grant.
	 * @return int
	 */
	private function create_user_with_caps( array $capabilities ): int {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		if ( $user instanceof \WP_User ) {
			foreach ( $capabilities as $capability ) {
				$user->add_cap( $capability );
			}
		}

		return $user_id;
	}

	/**
	 * Add the test extension ability definition class.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_test_extension_ability_definition_class( array $classes ): array {
		$classes[] = TestExtensionAbilityDefinition::class;

		return $classes;
	}

	/**
	 * Add the test reserved WooCommerce ability definition class.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_reserved_woocommerce_ability_definition_class( array $classes ): array {
		$classes[] = TestReservedWooAbilityDefinition::class;

		return $classes;
	}

	/**
	 * Preserve fractional stock amounts for filtered stock quantity tests.
	 *
	 * @param mixed $amount Stock amount.
	 * @return float
	 */
	public function preserve_fractional_stock_amount( $amount ): float {
		return (float) $amount;
	}

	/**
	 * Create a product variation and track it for cleanup.
	 *
	 * @return int Variation product ID.
	 */
	private function create_variation_product_id_for_test(): int {
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		$this->created_product_ids[] = $variation_id;
		$this->created_product_ids[] = $parent->get_id();

		return $variation_id;
	}

	/**
	 * Set order storage mode for a test.
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled.
	 */
	private function set_order_storage_for_test( bool $hpos_enabled ): void {
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		if ( null === $this->original_cot_state ) {
			$this->original_cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		}

		if ( $hpos_enabled && ! $this->cot_setup_for_test ) {
			$this->setup_cot();
			$this->cot_setup_for_test = true;
			return;
		}

		$this->toggle_cot_feature_and_usage( $hpos_enabled );
	}

	/**
	 * Create an order with controlled dates for query tests.
	 *
	 * @param string $billing_email Billing email.
	 * @param string $date_created  Created date.
	 * @param string $date_modified Modified date.
	 * @return \WC_Order
	 */
	private function create_order_for_query_sorting( string $billing_email, string $date_created, string $date_modified ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->set_billing_email( $billing_email );
		$order->set_date_created( $date_created );
		$order->set_date_modified( $date_modified );
		$order->save();

		$this->created_order_ids[] = $order->get_id();
		$this->set_order_modified_date_for_query_test( $order, $date_modified );

		return $order;
	}

	/**
	 * Set an order modified date directly in the authoritative storage table.
	 *
	 * @param \WC_Order $order         Order object.
	 * @param string    $date_modified Modified date.
	 */
	private function set_order_modified_date_for_query_test( \WC_Order $order, string $date_modified ): void {
		global $wpdb;

		$timestamp = wc_string_to_datetime( $date_modified )->getTimestamp();
		$gmt_date  = gmdate( 'Y-m-d H:i:s', $timestamp );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->update(
				OrdersTableDataStore::get_orders_table_name(),
				array(
					'date_updated_gmt' => $gmt_date,
				),
				array( 'id' => $order->get_id() )
			);

			return;
		}

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified_gmt' => $gmt_date,
				'post_modified'     => get_date_from_gmt( $gmt_date ),
			),
			array( 'ID' => $order->get_id() )
		);
		clean_post_cache( $order->get_id() );
	}
}
