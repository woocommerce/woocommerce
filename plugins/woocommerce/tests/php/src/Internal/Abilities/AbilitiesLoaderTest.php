<?php
/**
 * AbilitiesLoaderTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesCategories;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderAddNote;
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
		'woocommerce/order-notes-query',
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
	 * Whether the current test uses the permanent HPOS/COT tables.
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
		self::setup_cot_tables();
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
			'woocommerce/order-notes-query'   => array(
				'readonly'    => true,
				'idempotent'  => true,
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
	 * @testdox Should expose registered product types as inputs for product operations.
	 */
	public function test_product_schema_uses_registered_product_types(): void {
		$query_schema  = wp_get_ability( 'woocommerce/products-query' )->get_input_schema();
		$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$update_schema = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();

		$expected_types = array( 'simple', 'grouped', 'external', 'variable', 'variation' );

		$this->assertSame( $expected_types, $query_schema['properties']['type']['enum'] ?? array() );
		$this->assertSame( $expected_types, $create_schema['properties']['type']['enum'] ?? array() );
		$this->assertSame( $expected_types, $update_schema['properties']['type']['enum'] ?? array() );
		$this->assertSame( 'simple', $create_schema['properties']['type']['default'] ?? null );
		$this->assertArrayNotHasKey( 'default', $update_schema['properties']['type'] ?? array() );
		$this->assertArrayNotHasKey(
			'product_type_alias',
			wp_get_ability( 'woocommerce/product-create' )->get_output_schema()['properties']['product']['properties'] ?? array()
		);
		$this->assertArrayNotHasKey( 'default', $create_schema );
		$this->assertArrayNotHasKey( 'default', $update_schema );
	}

	/**
	 * @testdox Should list every field in the flat create and update schemas.
	 */
	public function test_product_mutation_schemas_list_every_field(): void {
		$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$update_schema = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();

		foreach ( array( $create_schema, $update_schema ) as $schema ) {
			$this->assertArrayNotHasKey( 'oneOf', $schema );
			$this->assertFalse( $schema['additionalProperties'] );
			foreach ( array( 'type', 'virtual', 'downloadable', 'regular_price', 'external_url', 'grouped_products', 'manage_stock' ) as $field ) {
				$this->assertArrayHasKey( $field, $schema['properties'] );
			}
		}

		$this->assertSame( array( 'name' ), $create_schema['required'] );
		$this->assertArrayNotHasKey( 'id', $create_schema['properties'] );
		$this->assertStringContainsString( 'A product without a type is simple.', $create_schema['properties']['type']['description'] );
		$this->assertSame( array( 'id' ), $update_schema['required'] );
		$this->assertStringNotContainsString( 'without a type', $update_schema['properties']['type']['description'] );
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

		$this->assertNotContains( 'checkout-draft', $query_schema['properties']['status']['anyOf'][0]['enum'] ?? array() );
		$this->assertNotContains( 'checkout-draft', $update_schema['properties']['status']['enum'] ?? array() );
	}

	/**
	 * @testdox Should list shop_order by default, and not refunds or HPOS placeholders.
	 */
	public function test_orders_query_type_defaults_to_shop_order(): void {
		$schema = wp_get_ability( 'woocommerce/orders-query' )->get_input_schema();
		$types  = $schema['properties']['type']['enum'] ?? array();

		$this->assertSame( 'shop_order', $schema['properties']['type']['default'] ?? null );
		$this->assertContains( 'shop_order', $types );
		$this->assertNotContains( 'shop_order_refund', $types );
		$this->assertNotContains( 'shop_order_placehold', $types );

		$query_args = $this->capture_order_query_args( array() );

		$this->assertSame( 'shop_order', $query_args['type'] ?? null );
	}

	/**
	 * @testdox Should query an order type and status that an extension registers.
	 */
	public function test_orders_query_accepts_registered_order_type_and_status(): void {
		global $wc_order_types, $post_type_meta_caps;

		$meta_caps = $post_type_meta_caps;
		$this->unregister_domain_abilities();
		wc_register_order_type(
			'shop_test_contract',
			array(
				'capability_type'          => 'shop_order',
				'exclude_from_order_views' => true,
				'add_order_meta_boxes'     => false,
			)
		);
		register_post_status(
			'wc-test-active',
			array(
				'label'               => 'Active',
				'exclude_from_search' => false,
			)
		);

		try {
			$this->register_domain_abilities();

			$ability = wp_get_ability( 'woocommerce/orders-query' );
			$input   = $ability->get_input_schema()['properties'];
			$output  = $ability->get_output_schema()['properties']['orders']['items']['properties'];

			$this->assertContains( 'shop_test_contract', $input['type']['enum'] );
			$this->assertContains( 'test-active', $input['status']['anyOf'][0]['enum'] );
			$this->assertContains( 'test-active', $output['status']['enum'] );

			$query_args = $this->capture_order_query_args(
				array(
					'type'   => 'shop_test_contract',
					'status' => 'test-active',
				)
			);

			$this->assertSame( 'shop_test_contract', $query_args['type'] ?? null );
			$this->assertSame( array( 'wc-test-active' ), $query_args['status'] ?? null );
		} finally {
			$this->unregister_domain_abilities();
			unset( $wc_order_types['shop_test_contract'], $GLOBALS['wp_post_statuses']['wc-test-active'] );
			unregister_post_type( 'shop_test_contract' );
			$post_type_meta_caps = $meta_caps; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- unregister_post_type() removes the shop_order meta caps too.
			$this->register_domain_abilities();
		}
	}

	/**
	 * @testdox Should pass the default order statuses without the wc- prefix, as before, for shop_order.
	 */
	public function test_orders_query_default_statuses_match_trunk_for_shop_order(): void {
		$expected = array();
		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			if ( 'wc-checkout-draft' !== $status ) {
				$expected[] = OrderUtil::remove_status_prefix( $status );
			}
		}

		$query_args = $this->capture_order_query_args( array() );

		$this->assertSame( $expected, $query_args['status'] ?? null );
	}

	/**
	 * @testdox Should not accept a status that is excluded from search, but return it in the output.
	 */
	public function test_a_status_excluded_from_search_is_not_queryable(): void {
		global $wc_order_types, $post_type_meta_caps;

		$meta_caps = $post_type_meta_caps;
		$this->unregister_domain_abilities();
		wc_register_order_type(
			'shop_test_contract',
			array(
				'capability_type'          => 'shop_order',
				'exclude_from_order_views' => true,
				'add_order_meta_boxes'     => false,
			)
		);
		register_post_status(
			'wc-test-hidden',
			array(
				'label'               => 'Hidden',
				'exclude_from_search' => true,
			)
		);

		try {
			$this->register_domain_abilities();

			$ability = wp_get_ability( 'woocommerce/orders-query' );
			$input   = $ability->get_input_schema()['properties'];
			$output  = $ability->get_output_schema()['properties']['orders']['items']['properties'];

			$this->assertNotContains( 'test-hidden', $input['status']['anyOf'][0]['enum'] );
			$this->assertContains( 'test-hidden', $output['status']['enum'] );

			$query_args = $this->capture_order_query_args( array( 'type' => 'shop_test_contract' ) );

			$this->assertNotContains( 'test-hidden', $query_args['status'] ?? array() );
			$this->assertNotContains( 'wc-test-hidden', $query_args['status'] ?? array() );
		} finally {
			$this->unregister_domain_abilities();
			unset( $wc_order_types['shop_test_contract'], $GLOBALS['wp_post_statuses']['wc-test-hidden'] );
			unregister_post_type( 'shop_test_contract' );
			$post_type_meta_caps = $meta_caps; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- unregister_post_type() removes the shop_order meta caps too.
			$this->register_domain_abilities();
		}
	}

	/**
	 * @testdox Should accept a list of statuses and reject a list with an unknown status.
	 */
	public function test_orders_query_accepts_a_list_of_statuses(): void {
		$query_args = $this->capture_order_query_args( array( 'status' => array( 'on-hold', 'processing' ) ) );

		$this->assertSame( array( 'on-hold', 'processing' ), $query_args['status'] ?? null );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'status' => array( 'processing', 'not-a-status' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse a status that another order type registers, and keep the order status.
	 */
	public function test_order_update_status_refuses_status_of_another_order_type(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$status_before             = $order->get_status();

		$this->unregister_domain_abilities();
		register_post_status(
			'wc-test-active',
			array(
				'label'               => 'Active',
				'exclude_from_search' => false,
			)
		);

		try {
			$this->register_domain_abilities();

			$ability = wp_get_ability( 'woocommerce/order-update-status' );
			$this->assertContains( 'test-active', $ability->get_input_schema()['properties']['status']['enum'] );

			$result = $ability->execute(
				array(
					'id'     => $order->get_id(),
					'status' => 'test-active',
				)
			);
		} finally {
			$this->unregister_domain_abilities();
			unset( $GLOBALS['wp_post_statuses']['wc-test-active'] );
			$this->register_domain_abilities();
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_order_status_invalid', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] ?? null );
		$this->assertSame( $status_before, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should reject an order type that is not registered.
	 */
	public function test_orders_query_rejects_unknown_order_type(): void {
		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'type' => 'shop_order_refund' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should check the permission against the actual type of the order ID.
	 */
	public function test_orders_query_refuses_id_of_unsupported_order_type(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$refund                    = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'   => $refund->get_id(),
				'type' => 'shop_order',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Run the orders query ability and return the arguments of its order query.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	private function capture_order_query_args( array $input ): array {
		$captured = null;
		$capture  = static function ( $args ) use ( &$captured ) {
			$captured ??= $args;
			return $args;
		};

		add_filter( 'woocommerce_order_query_args', $capture );
		try {
			$result = wp_get_ability( 'woocommerce/orders-query' )->execute( $input );
		} finally {
			remove_filter( 'woocommerce_order_query_args', $capture );
		}

		$this->assertNotWPError( $result );

		return $captured ?? array();
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
			do_action( 'wp_abilities_api_init' );
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
		do_action( 'wp_abilities_api_categories_init' );
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
			'order-notes-query'   => array(
				'woocommerce/order-notes-query',
				array(
					'id' => 1,
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
			'order-notes-query'   => array(
				'woocommerce/order-notes-query',
				array( 'id' => -123 ),
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
	public function test_product_create_sets_virtual_and_downloadable_on_a_simple_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Digital Product',
				'type'          => 'simple',
				'virtual'       => true,
				'downloadable'  => true,
				'regular_price' => '19.99',
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
	public function test_product_create_sets_virtual_on_a_simple_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Virtual Product',
				'type'          => 'simple',
				'virtual'       => true,
				'regular_price' => '29.99',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'simple', $created['product']['type'] );
		$this->assertTrue( $created['product']['virtual'] );
		$this->assertFalse( $created['product']['downloadable'] );
	}

	/**
	 * @testdox Should query simple products by type, virtual and downloadable.
	 */
	public function test_products_query_filters_by_type_virtual_and_downloadable(): void {
		$physical_sku = wp_unique_id( 'abilities-physical-' );
		$virtual_sku  = wp_unique_id( 'abilities-virtual-' );
		$digital_sku  = wp_unique_id( 'abilities-digital-' );
		$hybrid_sku   = wp_unique_id( 'abilities-downloadable-shippable-' );

		$physical = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name' => 'Physical Query Product',
				'type' => 'simple',
				'sku'  => $physical_sku,
			)
		);

		$this->assertNotWPError( $physical );
		$this->created_product_ids[] = $physical['product']['id'];

		$virtual = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'    => 'Virtual Query Product',
				'type'    => 'simple',
				'virtual' => true,
				'sku'     => $virtual_sku,
			)
		);

		$this->assertNotWPError( $virtual );
		$this->created_product_ids[] = $virtual['product']['id'];

		$digital = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'         => 'Digital Query Product',
				'type'         => 'simple',
				'virtual'      => true,
				'downloadable' => true,
				'sku'          => $digital_sku,
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
				'type'         => 'simple',
				'virtual'      => false,
				'downloadable' => false,
				'sku'          => $physical_sku,
			)
		);

		$this->assertNotWPError( $physical_result );
		$this->assertSame( 1, $physical_result['total_pages'] );
		$this->assertSame( $physical['product']['id'], $physical_result['products'][0]['id'] );

		$string_booleans_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'virtual'      => 'false',
				'downloadable' => 'false',
				'sku'          => $physical_sku,
			)
		);

		$this->assertNotWPError( $string_booleans_result );
		$this->assertSame( array( $physical['product']['id'] ), array_column( $string_booleans_result['products'], 'id' ) );

		$virtual_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'type'         => 'simple',
				'virtual'      => true,
				'downloadable' => false,
				'sku'          => $virtual_sku,
			)
		);

		$this->assertNotWPError( $virtual_result );
		$this->assertSame( 1, $virtual_result['total_pages'] );
		$this->assertSame( $virtual['product']['id'], $virtual_result['products'][0]['id'] );

		$digital_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'type'         => 'simple',
				'virtual'      => true,
				'downloadable' => true,
				'sku'          => $digital_sku,
			)
		);

		$this->assertNotWPError( $digital_result );
		$this->assertSame( 1, $digital_result['total_pages'] );
		$this->assertSame( $digital['product']['id'], $digital_result['products'][0]['id'] );

		$mismatched_result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'type'         => 'simple',
				'virtual'      => false,
				'downloadable' => false,
				'sku'          => $digital_sku,
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
				'type'         => 'simple',
				'virtual'      => false,
				'downloadable' => false,
				'sku'          => $hybrid_sku,
			)
		);

		$this->assertNotWPError( $physical_hybrid_result );
		$this->assertSame( 0, $physical_hybrid_result['total_pages'] );
	}

	/**
	 * @testdox Should create external products.
	 */
	public function test_product_create_creates_external_product(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Affiliate Product',
				'type'          => 'external',
				'regular_price' => '49.99',
				'external_url'  => 'https://example.com/buy',
				'button_text'   => 'Buy elsewhere',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'external', $created['product']['type'] );
		$this->assertSame( 'https://example.com/buy', $created['product']['external_url'] );
		$this->assertSame( 'Buy elsewhere', $created['product']['button_text'] );
	}

	/**
	 * @testdox Should create grouped products with children.
	 */
	public function test_product_create_creates_grouped_product(): void {
		$child                       = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $child->get_id();

		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'             => 'Grouped Product',
				'type'             => 'grouped',
				'grouped_products' => array( $child->get_id() ),
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( 'grouped', $created['product']['type'] );
		$this->assertSame( array( $child->get_id() ), $created['product']['grouped_products'] );
	}

	/**
	 * @testdox Should create products of a type that an extension registers, with the Core fields of its class.
	 */
	public function test_product_create_accepts_a_registered_product_type(): void {
		$this->unregister_domain_abilities();
		add_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10, 2 );

		try {
			$this->register_domain_abilities();

			$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
			$update_schema = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();
			$bundle        = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'         => 'Bundle',
					'type'         => 'test-bundle',
					'virtual'      => true,
					'downloadable' => true,
				)
			);
			$subscription  = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'          => 'Subscription',
					'type'          => 'test-subscription',
					'regular_price' => '9.00',
				)
			);
			$bundle_price  = wp_get_ability( 'woocommerce/product-create' )->execute_change(
				array(
					'name'          => 'Bundle with price',
					'type'          => 'test-bundle',
					'regular_price' => '9.00',
				)
			);
			$missing_class = wp_get_ability( 'woocommerce/product-create' )->execute_change(
				array(
					'name' => 'No class',
					'type' => 'test-missing',
				)
			);
		} finally {
			remove_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
			remove_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10 );
			$this->unregister_domain_abilities();
			$this->register_domain_abilities();
		}

		$this->assertNotWPError( $bundle );
		$this->created_product_ids[] = $bundle['product']['id'];
		$this->assertNotWPError( $subscription );
		$this->created_product_ids[] = $subscription['product']['id'];

		$this->assertContains( 'test-bundle', $create_schema['properties']['type']['enum'] );
		$this->assertNotContains( 'test-missing', $create_schema['properties']['type']['enum'] );
		$this->assertContains( 'test-bundle', $update_schema['properties']['type']['enum'] );
		$this->assertContains( 'test-subscription', $update_schema['properties']['type']['enum'] );
		$this->assertNotContains( 'test-missing', $update_schema['properties']['type']['enum'] );
		$this->assertSame( 'test-bundle', $bundle['product']['type'] );
		$this->assertTrue( $bundle['product']['virtual'] );
		$this->assertTrue( $bundle['product']['downloadable'] );
		$this->assertSame( 'test-subscription', $subscription['product']['type'] );
		$this->assertSame( '9.00', $subscription['product']['regular_price'] );
		$this->assertWPError( $bundle_price );
		$this->assertSame( 'woocommerce_product_field_unsupported', $bundle_price->get_error_code() );
		$this->assertSame( 'The test-bundle product type does not take regular_price. It takes name, sku, description, short_description, status, virtual, downloadable.', $bundle_price->get_error_message() );
		$this->assertWPError( $missing_class );
		$this->assertSame( 'woocommerce_invalid_product_type', $missing_class->get_error_code() );
	}

	/**
	 * @testdox Should change a product to a type that an extension registers, refuse a type without a class, and query by the type.
	 */
	public function test_product_update_changes_the_type_to_a_registered_product_type(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$this->unregister_domain_abilities();
		add_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10, 2 );

		try {
			$this->register_domain_abilities();

			$subscription  = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'            => $product->get_id(),
					'type'          => 'test-subscription',
					'regular_price' => '12.00',
				)
			);
			$missing_class = wp_get_ability( 'woocommerce/product-update' )->execute_change(
				array(
					'id'   => $product->get_id(),
					'type' => 'test-missing',
				)
			);
			$bundle        = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name' => 'Bundle',
					'type' => 'test-bundle',
				)
			);
			$bundles       = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'type' => 'test-bundle' ) );
		} finally {
			remove_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
			remove_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10 );
			$this->unregister_domain_abilities();
			$this->register_domain_abilities();
		}

		$this->assertNotWPError( $bundle );
		$this->created_product_ids[] = $bundle['product']['id'];

		$this->assertNotWPError( $subscription );
		$this->assertSame( 'test-subscription', $subscription['product']['type'] );
		$this->assertSame( '12.00', $subscription['product']['regular_price'] );
		$this->assertWPError( $missing_class );
		$this->assertSame( 'woocommerce_invalid_product_type', $missing_class->get_error_code() );
		$this->assertNotWPError( $bundles );
		$this->assertSame( array( $bundle['product']['id'] ), array_column( $bundles['products'], 'id' ) );
	}

	/**
	 * @testdox Should let the owner of a product type add a Core field to it with the woocommerce_ability_product_type_fields filter.
	 */
	public function test_product_type_fields_filter_adds_a_core_field_to_a_registered_product_type(): void {
		$this->unregister_domain_abilities();
		add_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10, 2 );
		add_filter( 'woocommerce_ability_product_type_fields', array( $this, 'add_price_to_test_bundle' ), 10, 2 );

		try {
			$this->register_domain_abilities();

			$create_schema = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
			$bundle        = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'          => 'Bundle with price',
					'type'          => 'test-bundle',
					'regular_price' => '9.00',
				)
			);
		} finally {
			remove_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
			remove_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10 );
			remove_filter( 'woocommerce_ability_product_type_fields', array( $this, 'add_price_to_test_bundle' ), 10 );
			$this->unregister_domain_abilities();
			$this->register_domain_abilities();
		}

		$this->assertNotWPError( $bundle );
		$this->created_product_ids[] = $bundle['product']['id'];

		$this->assertSame( '9.00', $bundle['product']['regular_price'] );
		$this->assertArrayNotHasKey( 'not-a-core-field', $create_schema['properties'] );
	}

	/**
	 * Give the test bundle type a regular price, and ignore a name that is not a Core field.
	 *
	 * @internal
	 *
	 * @param array  $fields       Field names.
	 * @param string $product_type Product type.
	 * @return array
	 */
	public function add_price_to_test_bundle( array $fields, string $product_type ): array {
		if ( 'test-bundle' === $product_type ) {
			$fields[] = 'regular_price';
			$fields[] = 'not-a-core-field';
		}
		return $fields;
	}

	/**
	 * @testdox Should create a variable product with its variations in one call and sync the parent.
	 */
	public function test_product_create_builds_a_variable_product_with_its_variations(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$parent = wc_get_product( $created['product']['id'] );
		$this->assertInstanceOf( \WC_Product_Variable::class, $parent );
		$this->assertSame( 'variable', $created['product']['type'] );
		$this->assertCount( 2, $created['product']['variation_ids'] );
		$this->assertSame( $parent->get_children(), $created['product']['variation_ids'] );
		$this->assertSame(
			array(
				array(
					'id'      => 0,
					'name'    => 'Size',
					'options' => array( 'S', 'M' ),
					'visible' => true,
				),
			),
			$created['product']['attributes']
		);
		$this->assertSame( '10.00', $created['product']['price'] );

		$small = wc_get_product( $created['product']['variation_ids'][0] );
		$this->assertInstanceOf( \WC_Product_Variation::class, $small );
		$this->assertSame( $parent->get_id(), $small->get_parent_id() );
		$this->assertSame( 'shirt-s', $small->get_sku() );
		$this->assertSame( array( 'size' => 'S' ), $small->get_attributes() );
		$this->assertSame( '10.00', $small->get_regular_price() );

		$medium = wc_get_product( $created['product']['variation_ids'][1] );
		$this->assertTrue( $medium->get_manage_stock() );
		$this->assertSame( 3, $medium->get_stock_quantity() );
	}

	/**
	 * @testdox Should leave no row behind when a later variation fails to save.
	 */
	public function test_product_create_rolls_back_every_save_when_a_variation_fails(): void {
		global $wpdb;

		$queries = array();
		$log     = static function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};
		$fail    = static function ( $product ) {
			if ( $product instanceof \WC_Product_Variation && 'shirt-m' === $product->get_sku( 'edit' ) ) {
				throw new \RuntimeException( 'Disk full.' );
			}
		};
		add_filter( 'query', $log );
		add_action( 'woocommerce_before_product_object_save', $fail );
		try {
			$result = wp_get_ability( 'woocommerce/product-create' )->execute_change( $this->get_shirt_input() );
		} finally {
			remove_filter( 'query', $log );
			remove_action( 'woocommerce_before_product_object_save', $fail );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_create_failed', $result->get_error_code() );
		$this->assertContains( 'SAVEPOINT wc_1', $queries );
		$this->assertContains( 'ROLLBACK TO SAVEPOINT wc_1', $queries );
		$this->assertNotContains( 'RELEASE SAVEPOINT wc_1', $queries );
		$this->assertSame(
			'0',
			$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation' ) AND post_title LIKE 'Shirt%'" )
		);
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
	}

	/**
	 * @testdox Should refuse variations that do not match the attributes of the parent, and save nothing.
	 */
	public function test_product_create_refuses_variations_that_do_not_match_the_attributes(): void {
		$ability = wp_get_ability( 'woocommerce/product-create' );
		$input   = $this->get_shirt_input();

		$input['variations'][1]['attributes'][0]['option'] = 'S';
		$duplicate = $ability->execute_change( $input );

		$input['variations'][1]['attributes'][0]['option'] = 'XL';
		$unknown_option                                    = $ability->execute_change( $input );

		$input['variations'][1]['attributes'] = array();
		$missing                              = $ability->execute_change( $input );

		$input['variations'][1]['attributes'] = array(
			array(
				'name'   => 'Size',
				'option' => 'M',
			),
		);
		$input['variations'][1]['sku']        = 'shirt-s';
		$same_sku                             = $ability->execute_change( $input );

		$this->assertWPError( $duplicate );
		$this->assertSame( 'woocommerce_product_variation_duplicate', $duplicate->get_error_code() );
		$this->assertWPError( $unknown_option );
		$this->assertSame( 'woocommerce_product_variation_attributes_invalid', $unknown_option->get_error_code() );
		$this->assertStringContainsString( 'Variation 2', $unknown_option->get_error_message() );
		$this->assertWPError( $missing );
		$this->assertSame( 'woocommerce_product_variation_attributes_invalid', $missing->get_error_code() );
		$this->assertWPError( $same_sku );
		$this->assertSame( 'woocommerce_product_variation_sku_duplicate', $same_sku->get_error_code() );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
	}

	/**
	 * @testdox Should treat a variation as a product in update, create, query and delete.
	 */
	public function test_variations_are_products_in_the_other_abilities(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$parent_id                   = $created['product']['id'];
		$small_id                    = $created['product']['variation_ids'][0];

		$updated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'            => $small_id,
				'regular_price' => '8.00',
			)
		);
		$this->assertNotWPError( $updated );
		$this->assertSame( 'variation', $updated['product']['type'] );
		$this->assertSame( $parent_id, $updated['product']['parent_id'] );
		$this->assertSame( '8.00', $updated['product']['regular_price'] );
		$this->assertSame(
			array(
				array(
					'id'     => 0,
					'name'   => 'Size',
					'option' => 'S',
				),
			),
			$updated['product']['attributes']
		);
		$this->assertSame( '8.00', wc_get_product( $parent_id )->get_price() );

		$direct_options = wp_get_ability( 'woocommerce/product-update' )->execute_change(
			array(
				'id'         => $small_id,
				'attributes' => array(
					array(
						'name'   => 'Size',
						'option' => 'M',
					),
				),
			)
		);
		$this->assertWPError( $direct_options );
		$this->assertSame( 'woocommerce_product_field_unsupported', $direct_options->get_error_code() );
		$this->assertStringContainsString( 'attributes', $direct_options->get_error_message() );

		$resized = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $parent_id,
				'attributes' => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M', 'L' ),
					),
				),
			)
		);
		$this->assertNotWPError( $resized );
		$large = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'type'          => 'variation',
				'parent_id'     => $parent_id,
				'attributes'    => array(
					array(
						'name'   => 'Size',
						'option' => 'L',
					),
				),
				'regular_price' => '15.00',
			)
		);
		$this->assertNotWPError( $large );
		$this->assertSame( $parent_id, $large['product']['parent_id'] );
		$this->assertContains( $large['product']['id'], wc_get_product( $parent_id )->get_children() );

		$orphan = wp_get_ability( 'woocommerce/product-create' )->execute_change(
			array(
				'type'       => 'variation',
				'parent_id'  => $small_id,
				'attributes' => array(
					array(
						'name'   => 'Size',
						'option' => 'L',
					),
				),
			)
		);
		$this->assertWPError( $orphan );
		$this->assertSame( 'woocommerce_product_parent_invalid', $orphan->get_error_code() );

		$listed = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'parent_id' => $parent_id ) );
		$this->assertNotWPError( $listed );
		$this->assertCount( 3, $listed['products'] );
		$this->assertSame( array( 'variation' ), array_values( array_unique( array_column( $listed['products'], 'type' ) ) ) );

		$deleted = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $large['product']['id'],
				'force' => true,
			)
		);
		$this->assertNotWPError( $deleted );
		$this->assertNull( get_post( $large['product']['id'] ) );
		$this->assertNotContains( $large['product']['id'], wc_get_product( $parent_id )->get_children() );

		$taken = wp_get_ability( 'woocommerce/product-update' )->execute_change(
			array(
				'id'         => $parent_id,
				'variations' => array(
					array(
						'id'         => $small_id,
						'attributes' => array(
							array(
								'name'   => 'Size',
								'option' => 'M',
							),
						),
					),
				),
			)
		);
		$this->assertWPError( $taken );
		$this->assertSame( 'woocommerce_product_variation_duplicate', $taken->get_error_code() );

		$reslotted = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $parent_id,
				'variations' => array(
					array(
						'id'         => $small_id,
						'attributes' => array(
							array(
								'name'   => 'Size',
								'option' => 'L',
							),
						),
					),
				),
			)
		);
		$this->assertNotWPError( $reslotted );
		$this->assertSame( 'L', wc_get_product( $small_id )->get_attribute( 'size' ) );
	}

	/**
	 * @testdox Should change, add and delete variations through the parent, and leave the others alone.
	 */
	public function test_product_update_changes_adds_and_deletes_variations(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[]  = $created['product']['id'];
		$parent_id                    = $created['product']['id'];
		list( $small_id, $medium_id ) = $created['product']['variation_ids'];

		$updated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $parent_id,
				'attributes' => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M', 'L' ),
					),
				),
				'variations' => array(
					array(
						'id'            => $small_id,
						'regular_price' => '8.00',
					),
					array(
						'attributes'    => array(
							array(
								'name'   => 'Size',
								'option' => 'L',
							),
						),
						'regular_price' => '15.00',
					),
				),
			)
		);
		$this->assertNotWPError( $updated );
		$this->assertCount( 3, $updated['product']['variation_ids'] );
		$this->assertSame( '8.00', $updated['product']['price'] );
		$this->assertSame( '8.00', wc_get_product( $small_id )->get_regular_price() );
		$this->assertSame( '12.00', wc_get_product( $medium_id )->get_regular_price() );
		$large_id = $updated['product']['variation_ids'][2];
		$this->assertSame( '15.00', wc_get_product( $large_id )->get_regular_price() );

		$deleted = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $parent_id,
				'variations' => array(
					array(
						'id'     => $medium_id,
						'delete' => true,
					),
				),
			)
		);
		$this->assertNotWPError( $deleted );
		$this->assertSame( array( $small_id, $large_id ), $deleted['product']['variation_ids'] );
		$this->assertNull( get_post( $medium_id ) );

		$other                       = \WC_Helper_Product::create_variation_product();
		$this->created_product_ids[] = $other->get_id();
		$foreign                     = wp_get_ability( 'woocommerce/product-update' )->execute_change(
			array(
				'id'         => $parent_id,
				'variations' => array( array( 'id' => $other->get_children()[0] ) ),
			)
		);
		$this->assertWPError( $foreign );
		$this->assertSame( 'woocommerce_product_variation_not_found', $foreign->get_error_code() );

		$duplicate = wp_get_ability( 'woocommerce/product-update' )->execute_change(
			array(
				'id'         => $parent_id,
				'variations' => array(
					array(
						'attributes' => array(
							array(
								'name'   => 'Size',
								'option' => 'L',
							),
						),
					),
				),
			)
		);
		$this->assertWPError( $duplicate );
		$this->assertSame( 'woocommerce_product_variation_duplicate', $duplicate->get_error_code() );

		$simple                      = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $simple->get_id();
		$not_variable                = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $simple->get_id(),
				'variations' => array( array( 'regular_price' => '1.00' ) ),
			)
		);
		$this->assertWPError( $not_variable );
		$this->assertSame( 'woocommerce_product_field_unsupported', $not_variable->get_error_code() );
	}

	/**
	 * @testdox Should leave every variation as it was when a later save of an update fails.
	 */
	public function test_product_update_rolls_back_every_save_when_a_variation_fails(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$small_id                    = $created['product']['variation_ids'][0];

		$fail = static function ( $product ) {
			if ( $product instanceof \WC_Product_Variation && 'shirt-l' === $product->get_sku( 'edit' ) ) {
				throw new \RuntimeException( 'Disk full.' );
			}
		};
		add_action( 'woocommerce_before_product_object_save', $fail );
		try {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute_change(
				array(
					'id'         => $created['product']['id'],
					'name'       => 'Renamed shirt',
					'attributes' => array(
						array(
							'name'    => 'Size',
							'options' => array( 'S', 'M', 'L' ),
						),
					),
					'variations' => array(
						array(
							'id'            => $small_id,
							'regular_price' => '8.00',
						),
						array(
							'attributes'    => array(
								array(
									'name'   => 'Size',
									'option' => 'L',
								),
							),
							'sku'           => 'shirt-l',
							'regular_price' => '15.00',
						),
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_before_product_object_save', $fail );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_update_failed', $result->get_error_code() );
		$this->assertSame( 'Shirt', get_post( $created['product']['id'] )->post_title );
		$this->assertSame( '10.00', get_post_meta( $small_id, '_regular_price', true ) );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-l' ) );
	}

	/**
	 * @testdox Should check the permissions of a variation on its parent.
	 */
	public function test_variation_permissions_follow_the_parent(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$parent_id                   = $created['product']['id'];
		$small_id                    = $created['product']['variation_ids'][0];

		$checked = array();
		$capture = static function ( $caps, $cap, $user_id, $args ) use ( &$checked ) {
			if ( in_array( $cap, array( 'edit_post', 'delete_post', 'read_private_products' ), true ) ) {
				$checked[ $cap ] = (int) ( $args[0] ?? 0 );
			}
			return $caps;
		};
		add_filter( 'map_meta_cap', $capture, 10, 4 );
		try {
			$this->assertTrue( ProductUpdate::can_update_product( array( 'id' => $small_id ) ) );
			$this->assertTrue( ProductDelete::can_delete_product( array( 'id' => $small_id ) ) );
			$this->assertTrue( ProductsQuery::can_query_products( array( 'id' => $small_id ) ) );
		} finally {
			remove_filter( 'map_meta_cap', $capture, 10 );
		}

		$this->assertSame( $parent_id, $checked['edit_post'] ?? null );
		$this->assertSame( $parent_id, $checked['delete_post'] ?? null );
	}

	/**
	 * @testdox Should nest transactions with savepoints.
	 */
	public function test_transaction_query_nests_with_savepoints(): void {
		$queries = array();
		$log     = static function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};

		add_filter( 'query', $log );
		try {
			wc_transaction_query( 'start' );
			wc_transaction_query( 'start' );
			wc_transaction_query( 'rollback' );
			wc_transaction_query( 'commit' );
		} finally {
			remove_filter( 'query', $log );
		}

		$this->assertSame(
			array( 'SAVEPOINT wc_1', 'SAVEPOINT wc_2', 'ROLLBACK TO SAVEPOINT wc_2', 'RELEASE SAVEPOINT wc_1' ),
			array_values( array_filter( $queries, static fn( $query ) => false !== strpos( $query, 'SAVEPOINT' ) ) )
		);
	}

	/**
	 * @testdox Should create a variable product with a global attribute, its terms and two variations.
	 */
	public function test_product_create_uses_a_global_attribute(): void {
		$attribute = \WC_Helper_Product::create_attribute( 'size', array( 'small', 'large' ) );
		$created   = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_global_size_shirt_input( $attribute['attribute_id'], 'large' ) );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$product = wc_get_product( $created['product']['id'] );
		$stored  = $product->get_attributes()['pa_size'] ?? null;
		$medium  = get_term_by( 'slug', 'medium', 'pa_size' );
		$this->assertInstanceOf( \WC_Product_Attribute::class, $stored );
		$this->assertTrue( $stored->is_taxonomy() );
		$this->assertSame( $attribute['attribute_id'], $stored->get_id() );
		$this->assertInstanceOf( \WP_Term::class, $medium );
		$this->assertEqualsCanonicalizing( array_merge( $attribute['term_ids'], array( $medium->term_id ) ), $stored->get_options() );
		$this->assertEqualsCanonicalizing( array( 'small', 'large', 'medium' ), wp_get_post_terms( $product->get_id(), 'pa_size', array( 'fields' => 'slugs' ) ) );

		list( $small_id, $large_id ) = $created['product']['variation_ids'];
		$this->assertSame( 'small', get_post_meta( $small_id, 'attribute_pa_size', true ) );
		$this->assertSame( 'large', get_post_meta( $large_id, 'attribute_pa_size', true ) );
		$this->assertSame( $attribute['attribute_id'], $created['product']['attributes'][0]['id'] );
		$this->assertSame( 'size', $created['product']['attributes'][0]['name'] );
	}

	/**
	 * @testdox Should refuse a variation whose option is not a term of the global attribute.
	 */
	public function test_product_create_refuses_a_variation_option_that_is_not_a_term(): void {
		$attribute = \WC_Helper_Product::create_attribute( 'size', array( 'small', 'large' ) );
		$result    = wp_get_ability( 'woocommerce/product-create' )->execute_change( $this->get_global_size_shirt_input( $attribute['attribute_id'], 'xl' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_variation_attributes_invalid', $result->get_error_code() );
		$this->assertFalse( get_term_by( 'slug', 'xl', 'pa_size' ) );
	}

	/**
	 * @testdox Should return the global attribute id, name and term slugs from products-query.
	 */
	public function test_products_query_returns_global_attributes(): void {
		$attribute = \WC_Helper_Product::create_attribute( 'size', array( 'small', 'large' ) );
		$created   = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_global_size_shirt_input( $attribute['attribute_id'], 'large' ) );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$parent     = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $created['product']['id'] ) );
		$variations = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'parent_id' => $created['product']['id'] ) );
		$this->assertNotWPError( $parent );
		$this->assertNotWPError( $variations );

		$parent_attribute = $parent['products'][0]['attributes'][0];
		$this->assertSame( $attribute['attribute_id'], $parent_attribute['id'] );
		$this->assertSame( 'size', $parent_attribute['name'] );
		$this->assertEqualsCanonicalizing( array( 'small', 'large', 'medium' ), $parent_attribute['options'] );

		$options = array();
		foreach ( $variations['products'] as $variation ) {
			$this->assertSame( $attribute['attribute_id'], $variation['attributes'][0]['id'] );
			$this->assertSame( 'size', $variation['attributes'][0]['name'] );
			$options[] = $variation['attributes'][0]['option'];
		}
		$this->assertEqualsCanonicalizing( array( 'small', 'large' ), $options );
	}

	/**
	 * The input of a variable shirt with the global size attribute and two
	 * variations. The parent names the attribute by name and adds the term
	 * Medium, which does not exist yet. The first variation names the
	 * attribute by name, the second by id.
	 *
	 * @param int    $attribute_id  ID of the global size attribute.
	 * @param string $second_option Option of the second variation.
	 * @return array
	 */
	private function get_global_size_shirt_input( int $attribute_id, string $second_option ): array {
		return array(
			'name'       => 'Global shirt',
			'type'       => 'variable',
			'attributes' => array(
				array(
					'name'    => 'size',
					'options' => array( 'small', 'Large', 'Medium' ),
				),
			),
			'variations' => array(
				array(
					'attributes'    => array(
						array(
							'name'   => 'size',
							'option' => 'small',
						),
					),
					'regular_price' => '10.00',
				),
				array(
					'attributes'    => array(
						array(
							'id'     => $attribute_id,
							'option' => $second_option,
						),
					),
					'regular_price' => '12.00',
				),
			),
		);
	}

	/**
	 * The input of a variable shirt with two variations.
	 *
	 * @return array
	 */
	private function get_shirt_input(): array {
		return array(
			'name'       => 'Shirt',
			'type'       => 'variable',
			'attributes' => array(
				array(
					'name'    => 'Size',
					'options' => array( 'S', 'M' ),
				),
			),
			'variations' => array(
				array(
					'attributes'    => array(
						array(
							'name'   => 'Size',
							'option' => 'S',
						),
					),
					'sku'           => 'shirt-s',
					'regular_price' => '10.00',
				),
				array(
					'attributes'     => array(
						array(
							'name'   => 'Size',
							'option' => 'M',
						),
					),
					'sku'            => 'shirt-m',
					'regular_price'  => '12.00',
					'manage_stock'   => true,
					'stock_quantity' => 3,
				),
			),
		);
	}

	/**
	 * Add test product types to the product type registry.
	 *
	 * @internal
	 *
	 * @param array $types Product types.
	 * @return array
	 */
	public function add_test_product_types( array $types ): array {
		$types['test-bundle']       = 'Test bundle';
		$types['test-subscription'] = 'Test subscription';
		$types['test-missing']      = 'Test type without a class';
		return $types;
	}

	/**
	 * Map the test product types to their classes.
	 *
	 * @internal
	 *
	 * @param string $classname    Product class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function get_test_product_class( $classname, $product_type ) {
		$classes = array(
			'test-bundle'       => TestBundleProduct::class,
			'test-subscription' => TestSubscriptionProduct::class,
		);
		return $classes[ $product_type ] ?? $classname;
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
		$result = wp_get_ability( 'woocommerce/product-create' )->execute_change(
			array(
				'name'             => 'Invalid Grouped Product',
				'type'             => 'grouped',
				'grouped_products' => array( -12 ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_grouped_products_invalid', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject fields that are unsupported by the selected product type.
	 */
	public function test_product_create_rejects_fields_not_supported_by_product_type(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute_change(
			array(
				'name'          => 'Invalid Grouped Product',
				'type'          => 'grouped',
				'regular_price' => '19.99',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );
		$this->assertSame( 'The grouped product type does not take regular_price. It takes name, sku, description, short_description, status, grouped_products.', $result->get_error_message() );
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

			$this->assertSame( 'number', $input_schema['properties']['stock_quantity']['type'] ?? null );
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
	 * @testdox Should update a per-type field such as regular_price without a type in the input.
	 */
	public function test_product_update_changes_props_and_returns_updated_response(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'            => $product->get_id(),
				'name'          => 'Updated Name',
				'regular_price' => '99.00',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'Updated Name', $result['product']['name'] );
		$this->assertSame( '99.00', $result['product']['regular_price'] );
	}

	/**
	 * @testdox Should refuse an update field that the type of the loaded product does not support.
	 */
	public function test_product_update_rejects_fields_not_supported_by_loaded_product_type(): void {
		$product                     = \WC_Helper_Product::create_grouped_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'            => $product->get_id(),
				'regular_price' => '19.99',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );
		$this->assertSame( '', wc_get_product( $product->get_id() )->get_regular_price() );
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
	 * @testdox Should apply product type configuration when updating a product type.
	 */
	public function test_product_update_applies_product_type_configuration(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'           => $product->get_id(),
				'type'         => 'simple',
				'virtual'      => true,
				'downloadable' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'simple', $result['product']['type'] );
		$this->assertTrue( $result['product']['virtual'] );
		$this->assertTrue( $result['product']['downloadable'] );
	}

	/**
	 * @testdox Should apply the forced properties of a product type only when the type changes.
	 */
	public function test_product_update_applies_forced_properties_only_on_a_type_change(): void {
		$grouped = new \WC_Product_Grouped();
		$grouped->set_name( 'Grouped' );
		$grouped->set_manage_stock( true );
		$grouped->set_stock_quantity( 5 );
		$grouped->save();
		$this->created_product_ids[] = $grouped->get_id();

		$simple                      = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $simple->get_id();
		$simple->set_manage_stock( true );
		$simple->set_stock_quantity( 5 );
		$simple->save();

		$repeated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $grouped->get_id(),
				'type' => 'grouped',
				'name' => 'Renamed',
			)
		);
		$changed  = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $simple->get_id(),
				'type' => 'grouped',
			)
		);

		$this->assertNotWPError( $repeated );
		$this->assertSame( 'yes', get_post_meta( $grouped->get_id(), '_manage_stock', true ) );
		$this->assertSame( '5', get_post_meta( $grouped->get_id(), '_stock', true ) );
		$this->assertNotWPError( $changed );
		$this->assertSame( 'no', get_post_meta( $simple->get_id(), '_manage_stock', true ) );
		$this->assertSame( '', get_post_meta( $simple->get_id(), '_stock', true ) );
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
	 * @testdox Should delete a product variation by ID.
	 */
	public function test_product_delete_accepts_variation_ids(): void {
		$variation_id = $this->create_variation_product_id_for_test();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $variation_id,
				'force' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertNull( get_post( $variation_id ) );
	}

	/**
	 * @testdox Should refuse a type change on a variable product, because WooCommerce would delete its variations.
	 */
	public function test_product_update_rejects_a_type_change_on_a_variable_product(): void {
		$variable                    = \WC_Helper_Product::create_variation_product();
		$this->created_product_ids[] = $variable->get_id();
		$children                    = $variable->get_children();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $variable->get_id(),
				'type' => 'simple',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
		$this->assertSame( 'variable', wc_get_product( $variable->get_id() )->get_type() );
		$this->assertSame( $children, wc_get_product( $variable->get_id() )->get_children() );
	}

	/**
	 * @testdox Should reject product variation IDs when updating products, with and without a type.
	 */
	public function test_product_update_rejects_variation_ids(): void {
		$variation_id = $this->create_variation_product_id_for_test();

		foreach ( array( array(), array( 'type' => 'simple' ) ) as $type_input ) {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute(
				array_merge(
					array(
						'id'   => $variation_id,
						'name' => 'Changed variation',
					),
					$type_input
				)
			);

			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
		}

		$this->assertSame( 'variation', wc_get_product( $variation_id )->get_type() );
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
	 * @testdox Should return a product variation by ID with its parent and attributes.
	 */
	public function test_products_query_accepts_variation_ids(): void {
		$variation_id = $this->create_variation_product_id_for_test();
		$variation    = wc_get_product( $variation_id );

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $variation_id ) );

		$this->assertNotWPError( $result );
		$this->assertSame( 'variation', $result['products'][0]['type'] );
		$this->assertSame( $variation->get_parent_id(), $result['products'][0]['parent_id'] );
		$this->assertSame( count( $variation->get_attributes() ), count( $result['products'][0]['attributes'] ) );
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
	 * @testdox Should list every note of an order, newest first.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_order_notes_query_lists_every_note( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$newer_note_id = $order->add_order_note( 'Internal note.' );
		$order->add_order_note( 'Customer note.', true );

		// A note with a higher ID but an older date must sort after the newer one.
		wp_update_comment(
			array(
				'comment_ID'       => $newer_note_id,
				'comment_date'     => '2030-01-01 00:00:00',
				'comment_date_gmt' => '2030-01-01 00:00:00',
			)
		);

		update_option( 'timezone_string', 'America/Sao_Paulo' );

		$result = wp_get_ability( 'woocommerce/order-notes-query' )->execute( array( 'id' => $order->get_id() ) );

		$this->assertNotWPError( $result );

		$by_content = array_column( $result['notes'], 'customer_note', 'note' );
		$this->assertTrue( $by_content['Customer note.'] );
		$this->assertFalse( $by_content['Internal note.'] );

		$dates_by_content = array_column( $result['notes'], 'date_created', 'note' );
		$this->assertSame( '2029-12-31T21:00:00-03:00', $dates_by_content['Internal note.'] );

		$this->assertContainsOnly( 'int', array_column( $result['notes'], 'id' ) );
		$this->assertSame( $newer_note_id, $result['notes'][0]['id'], 'Notes should be newest first by creation date.' );
	}

	/**
	 * @testdox Should return a not-found error when order-notes-query receives an unknown order ID.
	 */
	public function test_order_notes_query_returns_not_found_for_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/order-notes-query' )->execute( array( 'id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_order_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should check the order-notes-query permission against the actual type of the order ID.
	 */
	public function test_order_notes_query_refuses_id_of_unsupported_order_type(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$refund                    = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);

		$result = wp_get_ability( 'woocommerce/order-notes-query' )->execute( array( 'id' => $refund->get_id() ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should check the order-notes-query permission against the capabilities of an order type that an extension registers.
	 */
	public function test_order_notes_query_uses_the_capabilities_of_the_order_type(): void {
		global $wc_order_types, $post_type_meta_caps, $wpdb;

		$meta_caps = $post_type_meta_caps;
		wc_register_order_type(
			'shop_test_private',
			array(
				'capability_type'          => 'shop_test_private',
				'exclude_from_order_views' => true,
				'add_order_meta_boxes'     => false,
			)
		);
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'shop_test_private' ), array( 'ID' => $order->get_id() ) );
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'type' => 'shop_test_private' ), array( 'id' => $order->get_id() ) );
		clean_post_cache( $order->get_id() );
		wp_cache_flush();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$ability = wp_get_ability( 'woocommerce/order-notes-query' );

		try {
			$this->assertSame( 'shop_test_private', OrderUtil::get_order_type( $order->get_id() ) );

			$refused = $ability->execute( array( 'id' => $order->get_id() ) );

			$this->assertWPError( $refused );
			$this->assertSame( 'ability_invalid_permissions', $refused->get_error_code() );

			wp_get_current_user()->add_cap( 'read_private_shop_test_privates' );

			$this->assertIsArray( $ability->execute( array( 'id' => $order->get_id() ) ) );
		} finally {
			unset( $wc_order_types['shop_test_private'] );
			unregister_post_type( 'shop_test_private' );
			$post_type_meta_caps = $meta_caps; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- unregister_post_type() removes the shop_order meta caps too.
		}
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
		do_action( 'wp_abilities_api_categories_init' );
		remove_action( 'wp_abilities_api_categories_init', $callback );

		$this->registered_category_ids[] = 'woocommerce';
	}

	/**
	 * Register canonical domain abilities for this test.
	 */
	private function register_domain_abilities(): void {
		$callback = array( AbilitiesLoader::class, 'register_abilities' );

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
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
			// Keep the permanent HPOS tables outside the per-test transaction.
			remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
			remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
			$this->cot_setup_for_test = true;
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
