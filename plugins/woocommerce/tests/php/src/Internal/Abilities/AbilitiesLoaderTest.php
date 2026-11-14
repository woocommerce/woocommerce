<?php
/**
 * AbilitiesLoaderTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Ability;
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
		'woocommerce/product-terms-query',
		'woocommerce/product-term-create',
		'woocommerce/product-term-update',
		'woocommerce/product-attributes-query',
		'woocommerce/product-attribute-create',
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-send-email',
		'woocommerce/order-create',
		'woocommerce/order-update',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
		'woocommerce/order-notes-query',
		'woocommerce/payment-gateways-query',
		'woocommerce/customers-query',
		'woocommerce/product-review-update',
		'woocommerce/product-review-reply',
		'woocommerce/product-reviews-query',
		'woocommerce/shipping-zones-query',
		'woocommerce/system-status-get',
		'woocommerce/tax-rates-query',
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

		// The Abilities API registry can only be initialized after init has fired.
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
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}

		foreach ( $this->registered_category_ids as $category_id ) {
			if ( wp_has_ability_category( $category_id ) ) {
				wp_unregister_ability_category( $category_id );
			}
		}

		$this->register_domain_abilities();

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
	 * @testdox Should give every input field of a Core ability one type, so clients that accept one type per field can use the tools.
	 */
	public function test_core_ability_inputs_have_one_type_per_field(): void {
		$abilities = array_filter(
			wp_get_abilities(),
			static fn( \WP_Ability $ability ): bool => 0 === strpos( $ability->get_name(), 'woocommerce/' )
		);
		$this->assertNotEmpty( $abilities );

		$problems = array();
		foreach ( $abilities as $ability ) {
			$this->collect_mixed_types( $ability->get_input_schema(), $ability->get_name(), $problems );
		}

		$this->assertSame( array(), $problems );
	}

	/**
	 * Collect the paths of a schema where a field takes a type list, or one value or a list of it.
	 *
	 * @param mixed    $schema   Schema to walk.
	 * @param string   $path     Path of the schema.
	 * @param string[] $problems Paths found so far.
	 */
	private function collect_mixed_types( $schema, string $path, array &$problems ): void {
		if ( ! is_array( $schema ) ) {
			return;
		}
		if ( is_array( $schema['type'] ?? null ) ) {
			$problems[] = $path . ' declares the type list ' . implode( '|', $schema['type'] );
		}
		foreach ( array( 'anyOf', 'oneOf' ) as $keyword ) {
			$types = array_column( $schema[ $keyword ] ?? array(), 'type' );
			if ( in_array( 'array', $types, true ) && count( array_unique( $types ) ) > 1 ) {
				$problems[] = $path . ' takes one value or a list in ' . $keyword;
			}
		}
		foreach ( array( 'properties', 'patternProperties' ) as $keyword ) {
			foreach ( $schema[ $keyword ] ?? array() as $name => $child ) {
				$this->collect_mixed_types( $child, $path . '.' . $name, $problems );
			}
		}
		foreach ( array( 'items', 'additionalProperties' ) as $keyword ) {
			$this->collect_mixed_types( $schema[ $keyword ] ?? null, $path . '.' . $keyword, $problems );
		}
		foreach ( array_merge( $schema['anyOf'] ?? array(), $schema['oneOf'] ?? array() ) as $index => $branch ) {
			$this->collect_mixed_types( $branch, $path . '.' . $index, $problems );
		}
	}

	/**
	 * @testdox Should mark write abilities as destructive and queries as readonly/idempotent.
	 */
	public function test_canonical_ability_annotations_match_intent(): void {
		$expectations = array(
			'woocommerce/products-query'           => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/product-reviews-query'    => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/shipping-zones-query'     => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/tax-rates-query'          => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/product-attributes-query' => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/product-attribute-create' => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/product-term-create'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/product-term-update'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/product-create'           => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/product-update'           => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/product-delete'           => array(
				'readonly'    => false,
				'idempotent'  => true,
				'destructive' => true,
			),
			'woocommerce/orders-query'             => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/order-send-email'         => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/order-create'             => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/order-update'             => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/order-update-status'      => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/order-add-note'           => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => false,
			),
			'woocommerce/order-notes-query'        => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/payment-gateways-query'   => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/system-status-get'        => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/customers-query'          => array(
				'readonly'    => true,
				'idempotent'  => true,
				'destructive' => false,
			),
			'woocommerce/product-review-update'    => array(
				'readonly'    => false,
				'idempotent'  => false,
				'destructive' => true,
			),
			'woocommerce/product-review-reply'     => array(
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
		$this->assertSame( 'string', $product['permalink']['type'] ?? null );
		$this->assertContains( get_woocommerce_currency(), $product['currency']['enum'] ?? array() );
		$this->assertSame(
			wc_is_stock_amount_integer() ? 'integer' : 'number',
			$product['stock_quantity']['type'] ?? null
		);
	}

	/**
	 * @testdox Should describe collection pagination metadata in query outputs.
	 */
	public function test_collection_output_schemas_describe_pagination_metadata(): void {
		$schemas = array(
			'woocommerce/products-query'         => 'products',
			'woocommerce/orders-query'           => 'orders',
			'woocommerce/payment-gateways-query' => 'gateways',
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
	 * @testdox Should list every field in the flat create and update schemas, and the fields of each type in the type description.
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
			$description = $schema['properties']['type']['description'];
			$this->assertStringContainsString( 'Fields each type takes.', $description );
			$this->assertStringContainsString( 'external: name, sku, description, short_description, status, categories, tags, brands, images, catalog_visibility, featured, regular_price, sale_price, date_on_sale_from, date_on_sale_to, tax_status, external_url, button_text', $description );
			$this->assertStringContainsString( 'grouped: name, sku, description, short_description, status, categories, tags, brands, images, catalog_visibility, featured, grouped_products', $description );
			$this->assertStringEndsWith( 'Applies to: grouped.', $schema['properties']['grouped_products']['description'] );
			$this->assertStringContainsString( 'Applies to: ', $schema['properties']['regular_price']['description'] );
			$this->assertStringNotContainsString( 'Applies to: ', $schema['properties']['sku']['description'] ?? '' );
		}

		$this->assertArrayNotHasKey( 'required', $create_schema );
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
		$this->assertSame( 'string', $order['billing_email']['type'] ?? null );
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
		$create_schema = wp_get_ability( 'woocommerce/order-create' )->get_input_schema();

		$this->assertNotContains( 'checkout-draft', $query_schema['properties']['status']['items']['enum'] );
		$this->assertNotContains( 'checkout-draft', $update_schema['properties']['status']['enum'] ?? array() );
		$this->assertNotContains( 'checkout-draft', $create_schema['properties']['status']['enum'] ?? array() );
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
			$this->assertSame( 'array', $input['status']['type'] );
			$this->assertContains( 'test-active', $input['status']['items']['enum'] );
			$this->assertContains( 'test-active', $output['status']['enum'] );
			$this->assertContains( 'shop_test_contract', $output['type']['enum'], 'An order of a registered type should pass the output schema.' );

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

			$this->assertNotContains( 'test-hidden', $input['status']['items']['enum'] );
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
	 * @testdox Should give every canonical ability an input in the permission refusal provider.
	 */
	public function test_permission_refusal_provider_covers_every_canonical_ability(): void {
		$classes = ( new \ReflectionClassConstant( AbilitiesLoader::class, 'CORE_ABILITY_DEFINITION_CLASSES' ) )->getValue();
		$names   = array_map(
			static fn( string $class_name ): string => is_a( $class_name, ActionableAbility::class, true ) ? $class_name::ABILITY_NAME : $class_name::get_name(),
			$classes
		);
		// The loader skips an empty name, which a feature-gated ability reports while its feature is off.
		$core    = array_values( array_filter( $names, static fn( string $name ): bool => '' !== $name ) );
		$covered = array_column( $this->provider_canonical_abilities_with_minimal_input(), 0 );

		$this->assertEqualsCanonicalizing( $core, self::CANONICAL_ABILITY_IDS, 'CANONICAL_ABILITY_IDS should list every Core ability.' );
		$this->assertEqualsCanonicalizing( $core, $covered, 'The permission refusal provider should cover every Core ability.' );
	}

	/**
	 * Provides ability IDs and minimal valid-shape input for cap negative-path tests.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_canonical_abilities_with_minimal_input(): array {
		// Valid-shape input only — permission check should fire before execute.
		return array(
			'product-terms-query'      => array(
				'woocommerce/product-terms-query',
				array(
					'taxonomy' => 'product_cat',
				),
			),
			'product-term-create'      => array(
				'woocommerce/product-term-create',
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Forbidden',
				),
			),
			'product-term-update'      => array(
				'woocommerce/product-term-update',
				array(
					'taxonomy' => 'product_cat',
					'id'       => 1,
					'name'     => 'Forbidden',
				),
			),
			'product-attributes-query' => array(
				'woocommerce/product-attributes-query',
				array(),
			),
			'product-attribute-create' => array(
				'woocommerce/product-attribute-create',
				array(
					'name' => 'Forbidden',
				),
			),
			'products-query'           => array(
				'woocommerce/products-query',
				array(
					'id' => 1,
				),
			),
			'product-create'           => array(
				'woocommerce/product-create',
				array(
					'name' => 'Forbidden',
				),
			),
			'product-update'           => array(
				'woocommerce/product-update',
				array(
					'id'   => 1,
					'name' => 'Forbidden',
				),
			),
			'product-delete'           => array(
				'woocommerce/product-delete',
				array(
					'id' => 1,
				),
			),
			'orders-query'             => array(
				'woocommerce/orders-query',
				array(
					'id' => 1,
				),
			),
			'order-send-email'         => array(
				'woocommerce/order-send-email',
				array(
					'id'    => 1,
					'email' => 'invoice',
				),
			),
			'order-create'             => array(
				'woocommerce/order-create',
				array( 'line_items' => array( array( 'product_id' => 1 ) ) ),
			),
			'order-update'             => array(
				'woocommerce/order-update',
				array(
					'id'       => 1,
					'shipping' => array( 'city' => 'Denied' ),
				),
			),
			'order-update-status'      => array(
				'woocommerce/order-update-status',
				array(
					'id'     => 1,
					'status' => 'processing',
				),
			),
			'product-review-update'    => array(
				'woocommerce/product-review-update',
				array(
					'id'     => 1,
					'status' => 'approved',
				),
			),
			'product-review-reply'     => array(
				'woocommerce/product-review-reply',
				array(
					'id'      => 1,
					'content' => 'denied',
				),
			),
			'order-add-note'           => array(
				'woocommerce/order-add-note',
				array(
					'id'   => 1,
					'note' => 'denied',
				),
			),
			'order-notes-query'        => array(
				'woocommerce/order-notes-query',
				array(
					'id' => 1,
				),
			),
			'payment-gateways-query'   => array(
				'woocommerce/payment-gateways-query',
				array(),
			),
			'system-status-get'        => array(
				'woocommerce/system-status-get',
				array(),
			),
			'customers-query'          => array(
				'woocommerce/customers-query',
				array(),
			),
			'product-reviews-query'    => array(
				'woocommerce/product-reviews-query',
				array(),
			),
			'shipping-zones-query'     => array(
				'woocommerce/shipping-zones-query',
				array(),
			),
			'tax-rates-query'          => array(
				'woocommerce/tax-rates-query',
				array(),
			),
		);
	}

	/**
	 * @testdox Should grant a subscriber access when woocommerce_rest_check_permissions returns a truthy non-boolean, as REST does.
	 *
	 * @dataProvider provider_abilities_checking_rest_permissions
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Input that reaches the REST permission check.
	 */
	public function test_abilities_accept_truthy_rest_permission( string $ability_id, array $input ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		add_filter( 'woocommerce_rest_check_permissions', fn() => 1 );

		$this->assertTrue( wp_get_ability( $ability_id )->check_permissions( $input ) );
	}

	/**
	 * Provides one input per path through which an ability returns a REST permission check.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_abilities_checking_rest_permissions(): array {
		$missing_id = array( 'id' => PHP_INT_MAX );

		return array(
			'products-query'                 => array( 'woocommerce/products-query', $missing_id ),
			'orders-query by type'           => array( 'woocommerce/orders-query', array() ),
			'orders-query by missing order'  => array( 'woocommerce/orders-query', $missing_id ),
			'payment-gateways-query'         => array( 'woocommerce/payment-gateways-query', array() ),
			'system-status-get'              => array( 'woocommerce/system-status-get', array() ),
			'customers-query'                => array( 'woocommerce/customers-query', array() ),
			'shipping-zones-query'           => array( 'woocommerce/shipping-zones-query', array() ),
			'tax-rates-query'                => array( 'woocommerce/tax-rates-query', array() ),
			'product-attributes-query'       => array( 'woocommerce/product-attributes-query', array() ),
			'product-attribute-create'       => array( 'woocommerce/product-attribute-create', array( 'name' => 'Size' ) ),
			'product-terms-query'            => array( 'woocommerce/product-terms-query', array( 'taxonomy' => 'product_cat' ) ),
			'product-terms-query of unknown' => array( 'woocommerce/product-terms-query', array( 'taxonomy' => 'pa_missing' ) ),
			'product-term-create'            => array(
				'woocommerce/product-term-create',
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Granted',
				),
			),
			'product-term-update'            => array(
				'woocommerce/product-term-update',
				$missing_id + array( 'taxonomy' => 'product_cat' ),
			),
			'product-review-update'          => array(
				'woocommerce/product-review-update',
				$missing_id + array( 'status' => 'approved' ),
			),
			'product-review-reply'           => array(
				'woocommerce/product-review-reply',
				$missing_id + array( 'content' => 'Thanks!' ),
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
			'woocommerce/order-send-email'    => array(
				'id'    => $order->get_id(),
				'email' => 'invoice',
			),
			'woocommerce/order-update'        => array(
				'id'       => $order->get_id(),
				'shipping' => array( 'city' => 'Unauthorized city' ),
			),
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
	 * @testdox Should return a not-found error naming the ID when an order mutation receives an ID that is not an order.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_order_mutations_return_not_found_for_id_that_is_not_an_order( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		foreach ( array( 999999, $product->get_id() ) as $id ) {
			foreach ( $this->get_order_mutation_inputs( $id ) as $ability_id => $input ) {
				$result = wp_get_ability( $ability_id )->execute( $input );

				$this->assertWPError( $result, "{$ability_id} should refuse ID {$id}." );
				$this->assertSame( 'woocommerce_order_not_found', $result->get_error_code(), "{$ability_id} should name missing order {$id}, not refuse permission." );
				$this->assertStringContainsString( (string) $id, $result->get_error_message() );
			}
		}
	}

	/**
	 * @testdox Should refuse permission for an unknown order ID to users who cannot edit orders.
	 *
	 * @testWith ["customer"]
	 *           ["read_private_shop_orders"]
	 *
	 * @param string $role_or_cap Role of the user, or the only capability added to a subscriber.
	 */
	public function test_order_mutations_refuse_unknown_id_to_users_who_cannot_edit_orders( string $role_or_cap ): void {
		$user_id = 'customer' === $role_or_cap
			? self::factory()->user->create( array( 'role' => 'customer' ) )
			: $this->create_user_with_caps( array( $role_or_cap ) );
		wp_set_current_user( $user_id );

		foreach ( $this->get_order_mutation_inputs( 999999 ) as $ability_id => $input ) {
			$result = wp_get_ability( $ability_id )->execute( $input );

			$this->assertWPError( $result, "{$ability_id} should refuse a user who cannot edit orders." );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should refuse permission when an order mutation receives the ID of a refund.
	 */
	public function test_order_mutations_refuse_id_of_refund(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$refund                    = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);

		foreach ( $this->get_order_mutation_inputs( $refund->get_id() ) as $ability_id => $input ) {
			$result = wp_get_ability( $ability_id )->execute( $input );

			$this->assertWPError( $result, "{$ability_id} should refuse a refund ID." );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should refuse permission when an order mutation receives an order whose type the wc_order_types filter hides.
	 */
	public function test_order_mutations_refuse_order_of_type_hidden_by_filter(): void {
		global $wc_order_types, $post_type_meta_caps, $wpdb;

		// The posts data store refuses to read a type that the filter hides, so only HPOS can load the order.
		$this->set_order_storage_for_test( true );
		$meta_caps = $post_type_meta_caps;
		wc_register_order_type(
			'shop_test_hidden',
			array(
				'capability_type'          => 'shop_test_hidden',
				'exclude_from_order_views' => true,
				'add_order_meta_boxes'     => false,
			)
		);
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'shop_test_hidden' ), array( 'ID' => $order->get_id() ) );
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'type' => 'shop_test_hidden' ), array( 'id' => $order->get_id() ) );
		clean_post_cache( $order->get_id() );
		wp_cache_flush();
		add_filter(
			'wc_order_types',
			static function ( $types ) {
				return array_values( array_diff( $types, array( 'shop_test_hidden' ) ) );
			}
		);

		try {
			$this->assertSame( 'shop_test_hidden', OrderUtil::get_order_type( $order->get_id() ) );
			$this->assertInstanceOf( \WC_Order::class, wc_get_order( $order->get_id() ) );

			foreach ( $this->get_order_mutation_inputs( $order->get_id() ) as $ability_id => $input ) {
				$result = wp_get_ability( $ability_id )->execute( $input );

				$this->assertWPError( $result, "{$ability_id} should refuse an order whose type is hidden." );
				$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
			}
		} finally {
			unset( $wc_order_types['shop_test_hidden'] );
			unregister_post_type( 'shop_test_hidden' );
			$post_type_meta_caps = $meta_caps; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- unregister_post_type() removes the shop_order meta caps too.
		}
	}

	/**
	 * @testdox Should refuse permission when an order mutation receives an order whose unregistered type an extension gives an order class.
	 */
	public function test_order_mutations_refuse_order_of_unregistered_type_with_class(): void {
		global $wpdb;

		$this->set_order_storage_for_test( true );
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'type' => 'shop_test_unreg' ), array( 'id' => $order->get_id() ) );
		wp_cache_flush();
		add_filter(
			'woocommerce_order_class',
			static function ( $class_name, $order_type ) {
				return 'shop_test_unreg' === $order_type ? \WC_Order::class : $class_name;
			},
			10,
			2
		);

		$this->assertSame( 'shop_test_unreg', OrderUtil::get_order_type( $order->get_id() ) );
		$this->assertInstanceOf( \WC_Order::class, wc_get_order( $order->get_id() ) );

		foreach ( $this->get_order_mutation_inputs( $order->get_id() ) as $ability_id => $input ) {
			$result = wp_get_ability( $ability_id )->execute( $input );

			$this->assertWPError( $result, "{$ability_id} should refuse an order whose type is not registered." );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
	}

	/**
	 * Valid input for each order mutation ability, keyed by ability ID.
	 *
	 * @param int $order_id Order ID to send.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_order_mutation_inputs( int $order_id ): array {
		return array(
			'woocommerce/order-send-email'    => array(
				'id'    => $order_id,
				'email' => 'invoice',
			),
			'woocommerce/order-update'        => array(
				'id'       => $order_id,
				'shipping' => array( 'city' => 'Springfield' ),
			),
			'woocommerce/order-update-status' => array(
				'id'     => $order_id,
				'status' => 'processing',
			),
			'woocommerce/order-add-note'      => array(
				'id'   => $order_id,
				'note' => 'A note.',
			),
		);
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
	 * @testdox Should return the default category that saving assigns to a product created without categories.
	 */
	public function test_product_create_returns_the_default_category(): void {
		$default = wp_insert_term( 'Uncategorized test', 'product_cat' )['term_id'];
		update_option( 'default_product_cat', $default );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute( array( 'name' => 'Default category product' ) );

		$this->assertIsArray( $result );
		$this->created_product_ids[] = $result['product']['id'];
		$this->assertSame( array( $default ), array_column( $result['product']['categories'], 'id' ) );
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
	 * @testdox Should add a private order note when customer_note is the string false.
	 */
	public function test_order_add_note_reads_string_false_as_a_private_note(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'            => $order->get_id(),
				'note'          => 'Packed.',
				'customer_note' => 'false',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertCount(
			0,
			wc_get_order_notes(
				array(
					'order_id' => $order->get_id(),
					'type'     => 'customer',
				)
			)
		);
	}

	/**
	 * @testdox Should register every Core ability with the WooCommerce Ability class.
	 */
	public function test_core_abilities_use_the_ability_class(): void {
		foreach ( array( 'woocommerce/products-query', 'woocommerce/order-add-note', 'woocommerce/product-update' ) as $name ) {
			$this->assertInstanceOf( Ability::class, wp_get_ability( $name ), $name );
		}
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
			'products-query'        => array(
				'woocommerce/products-query',
				array( 'id' => -123 ),
			),
			'product-update'        => array(
				'woocommerce/product-update',
				array(
					'id'   => -123,
					'name' => 'Invalid',
				),
			),
			'product-delete'        => array(
				'woocommerce/product-delete',
				array( 'id' => -123 ),
			),
			'orders-query'          => array(
				'woocommerce/orders-query',
				array( 'id' => -123 ),
			),
			'order-send-email'      => array(
				'woocommerce/order-send-email',
				array(
					'id'    => -123,
					'email' => 'invoice',
				),
			),
			'order-update'          => array(
				'woocommerce/order-update',
				array(
					'id'       => -123,
					'shipping' => array( 'city' => 'Invalid' ),
				),
			),
			'order-update-status'   => array(
				'woocommerce/order-update-status',
				array(
					'id'     => -123,
					'status' => 'processing',
				),
			),
			'product-review-update' => array(
				'woocommerce/product-review-update',
				array(
					'id'     => -123,
					'status' => 'approved',
				),
			),
			'product-review-reply'  => array(
				'woocommerce/product-review-reply',
				array(
					'id'      => -123,
					'content' => 'Invalid',
				),
			),
			'order-add-note'        => array(
				'woocommerce/order-add-note',
				array(
					'id'   => -123,
					'note' => 'Invalid',
				),
			),
			'order-notes-query'     => array(
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
		$this->assertSame( 'The test-bundle product type does not take regular_price. It takes name, sku, description, short_description, status, categories, tags, brands, images, catalog_visibility, featured, virtual, downloadable, weight, dimensions, shipping_class.', $bundle_price->get_error_message() );
	}

	/**
	 * @testdox Should change a product to a type that an extension registers, and query by the type.
	 */
	public function test_product_update_changes_the_type_to_a_registered_product_type(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$this->unregister_domain_abilities();
		add_filter( 'product_type_selector', array( $this, 'add_test_product_types' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10, 2 );

		try {
			$this->register_domain_abilities();

			$subscription = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'            => $product->get_id(),
					'type'          => 'test-subscription',
					'regular_price' => '12.00',
				)
			);
			$bundle       = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name' => 'Bundle',
					'type' => 'test-bundle',
				)
			);
			$bundles      = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'type' => 'test-bundle' ) );
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

			$bundle = wp_get_ability( 'woocommerce/product-create' )->execute(
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
	}

	/**
	 * Give the test bundle type a regular price.
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
		$this->assertSame( $created['product']['variation_ids'], array_column( $created['variations'], 'id' ) );
		$this->assertSame( array( 'shirt-s', 'shirt-m' ), array_column( $created['variations'], 'sku' ) );
		$this->assertSame(
			array(
				array(
					'id'        => 0,
					'name'      => 'Size',
					'options'   => array( 'S', 'M' ),
					'visible'   => true,
					'variation' => true,
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
	 * @testdox Should refuse a new variation that repeats the options or the SKU of an existing variation.
	 */
	public function test_product_create_checks_a_new_variation_against_its_siblings(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$parent_id                   = $created['product']['id'];

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

		$same_options = wp_get_ability( 'woocommerce/product-create' )->execute_change(
			array(
				'type'       => 'variation',
				'parent_id'  => $parent_id,
				'attributes' => array(
					array(
						'name'   => 'Size',
						'option' => 'M',
					),
				),
			)
		);
		$same_sku     = wp_get_ability( 'woocommerce/product-create' )->execute_change(
			array(
				'type'       => 'variation',
				'parent_id'  => $parent_id,
				'attributes' => array(
					array(
						'name'   => 'Size',
						'option' => 'L',
					),
				),
				'sku'        => 'shirt-s',
			)
		);

		$this->assertWPError( $same_options );
		$this->assertSame( 'woocommerce_product_variation_duplicate', $same_options->get_error_code() );
		$this->assertWPError( $same_sku );
		$this->assertSame( 'product_invalid_sku', $same_sku->get_error_code() );
		$this->assertCount( 2, wc_get_product( $parent_id )->get_children() );
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
		$this->assertSame( array( '8.00', '15.00' ), array_column( $updated['variations'], 'regular_price' ), 'The response carries the changed and the new variation, in input order.' );
		$this->assertSame( $small_id, $updated['variations'][0]['id'] );
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
		$this->assertSame( array(), $deleted['variations'] );
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
	 * @testdox Should require the delete permission on the parent for a variation item with delete.
	 */
	public function test_variation_delete_through_the_parent_requires_the_delete_permission(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$update                      = array(
			'id'         => $created['product']['id'],
			'variations' => array(
				array(
					'id'            => $created['product']['variation_ids'][0],
					'regular_price' => '9.00',
				),
			),
		);
		$delete                      = array(
			'id'         => $created['product']['id'],
			'variations' => array(
				array(
					'id'     => $created['product']['variation_ids'][0],
					'delete' => true,
				),
			),
		);

		$deny = static fn( $caps, $cap ) => 'delete_post' === $cap ? array( 'do_not_allow' ) : $caps;
		add_filter( 'map_meta_cap', $deny, 10, 2 );
		try {
			$this->assertTrue( ProductUpdate::can_update_product( $update ) );
			$this->assertFalse( ProductUpdate::can_update_product( $delete ) );
		} finally {
			remove_filter( 'map_meta_cap', $deny, 10 );
		}

		$this->assertTrue( ProductUpdate::can_update_product( $delete ) );
	}

	/**
	 * @testdox Should refuse to publish a variation through the parent without publish_products.
	 */
	public function test_variation_publish_through_the_parent_requires_publish_cap(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$variation_id                = $created['product']['variation_ids'][0];
		$variation                   = wc_get_product( $variation_id );
		$variation->set_status( 'private' );
		$variation->save();

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'edit_products' );
		$user->add_cap( 'edit_others_products' );
		$user->add_cap( 'edit_published_products' );
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $created['product']['id'],
				'variations' => array(
					array(
						'id'     => $variation_id,
						'status' => 'publish',
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_publish_forbidden', $result->get_error_code() );
		$this->assertSame( 'private', get_post_status( $variation_id ) );
	}

	/**
	 * Statuses of a new variation added through the parent.
	 *
	 * @return array
	 */
	public function new_variation_status_provider(): array {
		return array(
			'status omitted' => array( null ),
			'publish'        => array( 'publish' ),
			'private'        => array( 'private' ),
		);
	}

	/**
	 * @testdox Should refuse a new published variation through the parent without publish_products.
	 * @dataProvider new_variation_status_provider
	 *
	 * @param string|null $status Status of the new variation, or null to omit it.
	 */
	public function test_new_variation_through_the_parent_requires_publish_cap( ?string $status ): void {
		$input = $this->get_shirt_input();
		array_pop( $input['variations'] );
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $input );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'edit_products' );
		$user->add_cap( 'edit_others_products' );
		$user->add_cap( 'edit_published_products' );
		wp_set_current_user( $user_id );

		$item = array(
			'attributes'    => array(
				array(
					'name'   => 'Size',
					'option' => 'M',
				),
			),
			'regular_price' => '12.00',
		);
		if ( null !== $status ) {
			$item['status'] = $status;
		}

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $created['product']['id'],
				'variations' => array( $item ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_publish_forbidden', $result->get_error_code() );
		$this->assertSame( array( $created['product']['variation_ids'][0] ), wc_get_product( $created['product']['id'] )->get_children() );
	}

	/**
	 * Delete flag values that the boolean schema accepts and that mean false.
	 *
	 * @return array<string, array{0: bool|string|int}>
	 */
	public function provider_false_delete_flags(): array {
		return array(
			'boolean false' => array( false ),
			'string false'  => array( 'false' ),
			'string zero'   => array( '0' ),
			'integer zero'  => array( 0 ),
		);
	}

	/**
	 * @testdox Should change a variation through the parent when its item sends a false delete flag.
	 * @dataProvider provider_false_delete_flags
	 *
	 * @param bool|string|int $flag Delete flag value.
	 */
	public function test_a_variation_item_with_a_false_delete_flag_is_a_change( $flag ): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$variation_id                = $created['product']['variation_ids'][0];

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $created['product']['id'],
				'variations' => array(
					array(
						'id'            => $variation_id,
						'delete'        => $flag,
						'regular_price' => '9.00',
					),
				),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertNotNull( get_post( $variation_id ) );
		$this->assertSame( '9.00', wc_get_product( $variation_id )->get_regular_price() );

		$kept = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $created['product']['id'],
				'variations' => array(
					array(
						'id'     => $variation_id,
						'delete' => $flag,
					),
				),
			)
		);

		$this->assertNotWPError( $kept );
		$this->assertNotNull( get_post( $variation_id ) );
	}

	/**
	 * @testdox Should move a product to the trash when force is the string false.
	 */
	public function test_product_delete_with_force_string_false_trashes(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => 'false',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should check the permissions of a direct call with a numeric string id or no input, as the MCP adapter sends them.
	 */
	public function test_check_permissions_converts_raw_input(): void {
		$order   = \WC_Helper_Order::create_order();
		$ability = wp_get_ability( 'woocommerce/orders-query' );

		$this->assertTrue( $ability->check_permissions( array( 'id' => (string) $order->get_id() ) ), 'A numeric string id is converted.' );
		$this->assertTrue( $ability->check_permissions( null ), 'No input gets the schema default.' );
	}

	/**
	 * @testdox Should read string false on the attribute flags of a product as false.
	 */
	public function test_product_create_reads_string_false_attribute_flags(): void {
		$input                               = $this->get_shirt_input();
		$input['attributes'][0]['visible']   = 'false';
		$input['attributes'][0]['variation'] = 'false';
		$input['attributes'][0]['global']    = 'false';
		unset( $input['variations'] );

		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $input );

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$attribute                   = $created['product']['attributes'][0];
		$this->assertFalse( $attribute['visible'] );
		$this->assertFalse( $attribute['variation'] );
		$this->assertSame( 0, $attribute['id'] );
	}

	/**
	 * @testdox Should fail a variation delete through the parent when pre_delete_post blocks it.
	 */
	public function test_a_blocked_variation_delete_through_the_parent_fails(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$variation_id                = $created['product']['variation_ids'][0];
		$block                       = static fn( $check, $post ) => 'product_variation' === $post->post_type ? false : $check;

		add_filter( 'pre_delete_post', $block, 10, 2 );
		try {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute_change(
				array(
					'id'         => $created['product']['id'],
					'variations' => array(
						array(
							'id'     => $variation_id,
							'delete' => true,
						),
					),
				)
			);
		} finally {
			remove_filter( 'pre_delete_post', $block, 10 );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_update_failed', $result->get_error_code() );
		$this->assertNotNull( get_post( $variation_id ) );
	}

	/**
	 * @testdox Should refuse a variation that repeats the SKU of its parent.
	 */
	public function test_product_create_refuses_a_variation_with_the_parent_sku(): void {
		$input                         = $this->get_shirt_input();
		$input['sku']                  = 'shirt';
		$input['variations'][1]['sku'] = 'shirt';

		$result = wp_get_ability( 'woocommerce/product-create' )->execute_change( $input );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_variation_sku_duplicate', $result->get_error_code() );
		$this->assertStringContainsString( 'Variation 2', $result->get_error_message() );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt' ) );
	}

	/**
	 * @testdox Should apply the product type fields filter to a variation item of the parent.
	 */
	public function test_variation_items_follow_the_product_type_fields_filter(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$variation_id                = $created['product']['variation_ids'][0];
		$drop                        = static fn( array $fields, string $type ) => 'variation' === $type ? array_values( array_diff( $fields, array( 'regular_price' ) ) ) : $fields;

		add_filter( 'woocommerce_ability_product_type_fields', $drop, 10, 2 );
		try {
			$nested = wp_get_ability( 'woocommerce/product-update' )->execute_change(
				array(
					'id'         => $created['product']['id'],
					'variations' => array(
						array(
							'id'            => $variation_id,
							'regular_price' => '9.00',
						),
					),
				)
			);
			$direct = wp_get_ability( 'woocommerce/product-update' )->execute_change(
				array(
					'id'            => $variation_id,
					'regular_price' => '9.00',
				)
			);
		} finally {
			remove_filter( 'woocommerce_ability_product_type_fields', $drop, 10 );
		}

		$this->assertWPError( $nested );
		$this->assertSame( 'woocommerce_product_field_unsupported', $nested->get_error_code() );
		$this->assertWPError( $direct );
		$this->assertSame( 'woocommerce_product_field_unsupported', $direct->get_error_code() );
		$this->assertSame( '10.00', wc_get_product( $variation_id )->get_regular_price() );
	}

	/**
	 * @testdox Should treat a name as a local attribute unless global is true, and refuse an unknown global attribute.
	 */
	public function test_product_create_uses_a_global_attribute_only_when_asked(): void {
		\WC_Helper_Product::create_attribute( 'size', array( 'small', 'large' ) );
		$input = $this->get_shirt_input();

		$local                            = wp_get_ability( 'woocommerce/product-create' )->execute( $input );
		$input['attributes'][0]['global'] = true;
		$input['attributes'][0]['name']   = 'Color';
		$unknown                          = wp_get_ability( 'woocommerce/product-create' )->execute_change( $input );

		$this->assertNotWPError( $local );
		$this->created_product_ids[] = $local['product']['id'];
		$this->assertSame( 0, $local['product']['attributes'][0]['id'] );
		$this->assertArrayHasKey( 'size', wc_get_product( $local['product']['id'] )->get_attributes() );
		$this->assertWPError( $unknown );
		$this->assertSame( 'woocommerce_product_attributes_invalid', $unknown->get_error_code() );
		$this->assertSame( 'There is no global attribute "Color".', $unknown->get_error_message() );
	}

	/**
	 * @testdox Should treat a variation attribute with no option as any option.
	 */
	public function test_a_variation_attribute_with_no_option_matches_any_option(): void {
		$input                                  = $this->get_shirt_input();
		$input['attributes'][]                  = array(
			'name'    => 'Color',
			'options' => array( 'Red', 'Blue' ),
		);
		$input['variations'][0]['attributes'][] = array( 'name' => 'Color' );
		$input['variations'][1]['attributes'][] = array(
			'name'   => 'Color',
			'option' => 'Red',
		);

		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $input );

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];
		$this->assertSame( '', wc_get_product( $created['product']['variation_ids'][0] )->get_attributes()['color'] );
	}

	/**
	 * @testdox Should let a variation match any option when it sends no option, leave the option out of the output, and refuse a null option.
	 */
	public function test_product_create_accepts_any_option_variations(): void {
		$input                                  = $this->get_shirt_input();
		$input['attributes'][]                  = array(
			'name'    => 'Color',
			'options' => array( 'Red', 'Blue' ),
		);
		$input['variations'][0]['attributes'][] = array( 'name' => 'Color' );
		$input['variations'][1]['attributes'][] = array(
			'name'   => 'Color',
			'option' => 'Red',
		);

		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $input );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$small = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $created['product']['variation_ids'][0] ) );
		$this->assertNotWPError( $small );
		$this->assertSame(
			array(
				array(
					'id'     => 0,
					'name'   => 'Size',
					'option' => 'S',
				),
				array(
					'id'   => 0,
					'name' => 'Color',
				),
			),
			$small['products'][0]['attributes']
		);
		$this->assertSame( '', wc_get_product( $created['product']['variation_ids'][0] )->get_attributes()['color'] );

		$input['variations'][0]['attributes'][1]['option'] = null;
		$input['sku']                                      = 'shirt-null-option';
		$refused = wp_get_ability( 'woocommerce/product-create' )->execute( $input );
		$this->assertWPError( $refused );
		$this->assertSame( 'ability_invalid_input', $refused->get_error_code() );
	}

	/**
	 * @testdox Should not require a variation to pick an attribute that is not for variations.
	 */
	public function test_product_create_accepts_attributes_that_are_not_for_variations(): void {
		$input                 = $this->get_shirt_input();
		$input['attributes'][] = array(
			'name'      => 'Material',
			'options'   => array( 'Cotton' ),
			'variation' => false,
		);

		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $input );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$this->assertCount( 2, $created['product']['variation_ids'] );
		$this->assertSame( array( true, false ), array_column( $created['product']['attributes'], 'variation' ) );
		$this->assertFalse( wc_get_product( $created['product']['id'] )->get_attributes()['material']->get_variation() );
	}

	/**
	 * @testdox Should check siblings and list variation ids from the data store, not through the woocommerce_get_children filter.
	 */
	public function test_variation_checks_ignore_the_get_children_filter(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute( $this->get_shirt_input() );
		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		add_filter( 'woocommerce_get_children', '__return_empty_array' );
		try {
			$duplicate = wp_get_ability( 'woocommerce/product-create' )->execute_change(
				array(
					'type'       => 'variation',
					'parent_id'  => $created['product']['id'],
					'attributes' => array(
						array(
							'name'   => 'Size',
							'option' => 'S',
						),
					),
				)
			);
			$read      = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $created['product']['id'] ) );
		} finally {
			remove_filter( 'woocommerce_get_children', '__return_empty_array' );
		}

		$this->assertWPError( $duplicate );
		$this->assertSame( 'woocommerce_product_variation_duplicate', $duplicate->get_error_code() );
		$this->assertNotWPError( $read );
		$this->assertSame( $created['product']['variation_ids'], $read['products'][0]['variation_ids'] );
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
					'global'  => true,
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
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'             => 'Invalid Grouped Product',
				'type'             => 'grouped',
				'grouped_products' => array( -12 ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
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
		$this->assertSame( 'The grouped product type does not take regular_price. It takes name, sku, description, short_description, status, categories, tags, brands, images, catalog_visibility, featured, grouped_products.', $result->get_error_message() );
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
				'number',
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
	 * @testdox Should omit non-date filter values without failing a product update.
	 * @testWith ["2026-07-15T10:00:00"]
	 *           [1784109600]
	 *           ["1784109600"]
	 *           ["invalid date"]
	 *           [[]]
	 *           [false]
	 *           [null]
	 * @param mixed $date Filtered date.
	 */
	public function test_product_output_handles_filtered_dates( $date ): void {
		update_option( 'timezone_string', 'Europe/Amsterdam' );
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();
		$filter                      = static function () use ( $date ) {
			return $date;
		};
		add_filter( 'woocommerce_product_get_date_created', $filter );
		add_filter( 'woocommerce_product_get_date_modified', $filter );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Filtered date update',
			)
		);

		$this->assertNotWPError( $result );
		$query = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) );
		$this->assertNotWPError( $query );
		foreach ( array( 'date_created', 'date_created_gmt', 'date_modified', 'date_modified_gmt' ) as $field ) {
			$this->assertArrayNotHasKey( $field, $result['product'] );
			$this->assertArrayNotHasKey( $field, $query['products'][0] );
		}
		$this->assertSame( 'Filtered date update', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should preserve the timezone and timestamp of filtered date objects.
	 */
	public function test_order_output_preserves_filtered_date_object(): void {
		update_option( 'timezone_string', 'Europe/Amsterdam' );
		$order                     = wc_create_order();
		$this->created_order_ids[] = $order->get_id();
		$date                      = new \WC_DateTime( '2026-07-15T12:00:00', new \DateTimeZone( 'Asia/Kathmandu' ) );
		$timestamp                 = $date->getTimestamp();
		add_filter(
			'woocommerce_order_get_date_created',
			static function () use ( $date ) {
				return $date;
			}
		);

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'id' => $order->get_id() ) );

		$this->assertNotWPError( $result );
		$this->assertSame( '2026-07-15T08:15:00+02:00', $result['orders'][0]['date_created'] );
		$this->assertSame( '2026-07-15T06:15:00+00:00', $result['orders'][0]['date_created_gmt'] );
		$this->assertSame( 'Asia/Kathmandu', $date->getTimezone()->getName() );
		$this->assertSame( $timestamp, $date->getTimestamp() );
	}

	/**
	 * @testdox Should return valid product dates with matching store and UTC timestamps.
	 * @dataProvider provider_output_date_timezones
	 * @param string $timezone Store timezone.
	 * @param string $created Creation date in store time.
	 * @param string $expected Expected date with offset.
	 * @param string $expected_gmt Expected UTC date.
	 */
	public function test_product_output_dates_have_offsets( string $timezone, string $created, string $expected, string $expected_gmt ): void {
		update_option( 'timezone_string', $timezone );
		update_option( 'gmt_offset', 5.75 );
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_date_created( $created );
		$product->save();
		$this->created_product_ids[] = $product->get_id();
		$modified                    = new \WC_DateTime( $created, wp_timezone() );
		add_filter(
			'woocommerce_product_get_date_modified',
			static function () use ( $modified ) {
				return $modified;
			}
		);

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Date output regression',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'Date output regression', wc_get_product( $product->get_id() )->get_name() );
		foreach ( array( 'date_created', 'date_modified' ) as $field ) {
			$date       = $result['product'][ $field ];
			$date_gmt   = $result['product'][ $field . '_gmt' ];
			$parsed     = \DateTimeImmutable::createFromFormat( DATE_ATOM, $date );
			$parsed_gmt = \DateTimeImmutable::createFromFormat( DATE_ATOM, $date_gmt );

			$this->assertSame( $expected, $date );
			$this->assertSame( $expected_gmt, $date_gmt );
			$this->assertInstanceOf( \DateTimeImmutable::class, $parsed );
			$this->assertInstanceOf( \DateTimeImmutable::class, $parsed_gmt );
			$this->assertSame( $date, $parsed->format( DATE_ATOM ) );
			$this->assertSame( $date_gmt, $parsed_gmt->format( DATE_ATOM ) );
			$this->assertStringEndsWith( '+00:00', $date_gmt );
			$this->assertSame( $parsed->getTimestamp(), $parsed_gmt->getTimestamp() );
		}
	}

	/**
	 * @testdox Should return order dates with matching store and UTC timestamps.
	 * @dataProvider provider_output_date_timezones
	 * @param string $timezone Store timezone.
	 * @param string $created Creation date in store time.
	 * @param string $expected Expected date with offset.
	 * @param string $expected_gmt Expected UTC date.
	 */
	public function test_order_output_dates_have_offsets( string $timezone, string $created, string $expected, string $expected_gmt ): void {
		update_option( 'timezone_string', $timezone );
		update_option( 'gmt_offset', 5.75 );
		$order = wc_create_order();
		$order->set_date_created( $created );
		$order->set_date_modified( $created );
		$order->save();
		$this->created_order_ids[] = $order->get_id();
		$this->set_order_modified_date_for_query_test( $order, $created );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'id' => $order->get_id() ) );

		$this->assertNotWPError( $result );
		foreach ( array( 'date_created', 'date_modified' ) as $field ) {
			$this->assertSame( $expected, $result['orders'][0][ $field ] );
			$this->assertSame( $expected_gmt, $result['orders'][0][ $field . '_gmt' ] );
			$this->assertSame( strtotime( $expected ), strtotime( $result['orders'][0][ $field . '_gmt' ] ) );
		}
	}

	/**
	 * Store timezones and dates, including daylight saving and fractional offsets.
	 *
	 * @return array
	 */
	public function provider_output_date_timezones(): array {
		return array(
			'UTC'        => array( 'UTC', '2026-07-15T12:00:00', '2026-07-15T12:00:00+00:00', '2026-07-15T12:00:00+00:00' ),
			'summer'     => array( 'Europe/Amsterdam', '2026-07-15T12:00:00', '2026-07-15T12:00:00+02:00', '2026-07-15T10:00:00+00:00' ),
			'winter'     => array( 'Europe/Amsterdam', '2026-01-15T12:00:00', '2026-01-15T12:00:00+01:00', '2026-01-15T11:00:00+00:00' ),
			'negative'   => array( 'America/New_York', '2026-07-15T12:00:00', '2026-07-15T12:00:00-04:00', '2026-07-15T16:00:00+00:00' ),
			'fractional' => array( '', '2026-07-15T12:00:00', '2026-07-15T12:00:00+05:45', '2026-07-15T06:15:00+00:00' ),
		);
	}

	/**
	 * @testdox Should return fractional order quantities when the store supports them.
	 * @testWith [false]
	 *           [true]
	 * @param bool $preserve_type Whether the stock filter preserves the input type.
	 */
	public function test_order_output_allows_fractional_quantities( bool $preserve_type ): void {
		$filter = static function ( $amount ) use ( $preserve_type ) {
			return $preserve_type ? $amount : (float) $amount;
		};
		$this->unregister_domain_abilities();
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', $filter );

		try {
			$this->register_domain_abilities();
			$order                     = \WC_Helper_Order::create_order();
			$this->created_order_ids[] = $order->get_id();
			foreach ( $order->get_items() as $item ) {
				$item->set_quantity( 1.5 );
				$item->save();
			}

			$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
				array(
					'id'                 => $order->get_id(),
					'include_line_items' => true,
				)
			);
			$this->assertNotWPError( $result );
			$this->assertSame( 1.5, $result['orders'][0]['line_items'][0]['quantity'] );
		} finally {
			remove_filter( 'woocommerce_stock_amount', $filter );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}
	}

	/**
	 * @testdox Should omit empty external product fields and preserve configured values.
	 */
	public function test_external_product_output_omits_empty_fields(): void {
		$product = new \WC_Product_External();
		$product->set_name( 'External product' );
		$product->save();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) );
		$this->assertNotWPError( $result );
		$this->assertArrayNotHasKey( 'external_url', $result['products'][0] );
		$this->assertArrayNotHasKey( 'button_text', $result['products'][0] );

		$product->set_product_url( 'https://example.com/buy' );
		$product->set_button_text( 'Buy elsewhere' );
		$product->save();
		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) );
		$this->assertNotWPError( $result );
		$this->assertSame( 'https://example.com/buy', $result['products'][0]['external_url'] );
		$this->assertSame( 'Buy elsewhere', $result['products'][0]['button_text'] );
	}

	/**
	 * @testdox Should retain the saved product response when reloading fails.
	 */
	public function test_saved_product_response_falls_back_when_reload_fails(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();
		wp_delete_post( $id, true );
		$this->assertFalse( wc_get_product( $id ) );

		$formatter = new class() {
			use \Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
			use \Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait {
				format_saved_product_for_response as public format;
			}
		};
		$result    = $formatter::format( $product );

		$this->assertSame( $id, $result['id'] );
		$this->assertSame( $product->get_name(), $result['name'] );
		$this->assertTrue( rest_validate_value_from_schema( $result, wp_get_ability( 'woocommerce/product-create' )->get_output_schema()['properties']['product'] ) );
	}

	/**
	 * @testdox Should retain the saved order response when reloading fails.
	 */
	public function test_saved_order_response_falls_back_when_reload_fails(): void {
		$order = \WC_Helper_Order::create_order();
		$id    = $order->get_id();
		wc_get_order( $id )->delete( true );
		$this->assertFalse( wc_get_order( $id ) );

		$formatter = new class() {
			use \Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
			use \Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait {
				format_saved_order_for_response as public format;
			}
		};
		$result    = $formatter::format( $order );

		$this->assertSame( $id, $result['id'] );
		$this->assertSame( $order->get_status(), $result['status'] );
		$this->assertTrue( rest_validate_value_from_schema( $result, wp_get_ability( 'woocommerce/order-add-note' )->get_output_schema()['properties']['order'] ) );
	}

	/**
	 * @testdox Should return saved product values and dates after a write.
	 * @testWith ["product-create"]
	 *           ["product-update"]
	 *
	 * @param string $ability_name Product write ability.
	 */
	public function test_product_write_returns_saved_state( string $ability_name ): void {
		$input = array( 'name' => 'Saved name' );
		if ( 'product-update' === $ability_name ) {
			$input['id'] = \WC_Helper_Product::create_simple_product()->get_id();
		}
		$change_in_memory = static function ( $product ) {
			$product->set_name( 'Unsaved name' );
		};
		add_action( 'woocommerce_after_product_object_save', $change_in_memory );
		try {
			$result = wp_get_ability( 'woocommerce/' . $ability_name )->execute( $input );
		} finally {
			remove_action( 'woocommerce_after_product_object_save', $change_in_memory );
		}

		$this->assertNotWPError( $result );
		$this->assertSame( 'Saved name', $result['product']['name'] );
		foreach ( array( 'date_created', 'date_created_gmt', 'date_modified', 'date_modified_gmt' ) as $field ) {
			$this->assertNotEmpty( $result['product'][ $field ] ?? null, $field );
		}
	}

	/**
	 * @testdox Should return saved order values after a status change or note.
	 * @testWith ["order-update-status"]
	 *           ["order-add-note"]
	 *
	 * @param string $ability_name Order write ability.
	 */
	public function test_order_write_returns_saved_state( string $ability_name ): void {
		$order = wc_create_order( array( 'status' => 'pending' ) );
		$order->set_payment_method( 'bacs' );
		$order->save();
		$change_in_memory = static function ( $value, $subject ) {
			$subject->set_payment_method( 'unsaved' );
			return $value;
		};
		$hook             = 'order-update-status' === $ability_name ? 'woocommerce_valid_order_statuses_for_payment' : 'woocommerce_order_note_added';
		add_filter( $hook, $change_in_memory, PHP_INT_MAX, 2 );
		$input = array( 'id' => $order->get_id() );
		if ( 'order-update-status' === $ability_name ) {
			$input['status'] = 'on-hold';
		} else {
			$input['note'] = 'Test note';
		}
		try {
			$result = wp_get_ability( 'woocommerce/' . $ability_name )->execute( $input );
		} finally {
			remove_filter( $hook, $change_in_memory, PHP_INT_MAX );
		}

		$this->assertNotWPError( $result );
		$this->assertSame( 'bacs', $result['order']['payment_method'] );
	}

	/**
	 * @testdox Should omit absent product values while keeping zero and false.
	 */
	public function test_product_output_omits_null_values(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_sale_price( '' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();
		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) )['products'][0];

		foreach ( array( 'external_url', 'button_text' ) as $field ) {
			$this->assertArrayNotHasKey( $field, $result );
		}
		$this->assertSame( 0, $result['stock_quantity'] );
		$this->assertFalse( $result['virtual'] );
		$this->assertSame( '', $result['sale_price'] );

		$product->set_manage_stock( false );
		$product->set_stock_quantity( '' );
		$product->save();
		$this->assertArrayNotHasKey( 'stock_quantity', wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) )['products'][0] );
	}

	/**
	 * @testdox Should reject unexpected null output instead of hiding the invalid field.
	 * @testWith ["product", "woocommerce_product_get_name", "name"]
	 *           ["order", "woocommerce_order_get_payment_method", "payment_method"]
	 *
	 * @param string $resource_name Resource to query.
	 * @param string $hook     Getter filter to return null.
	 * @param string $field    Invalid output field.
	 */
	public function test_query_rejects_unexpected_null_output( string $resource_name, string $hook, string $field ): void {
		$object = 'product' === $resource_name ? \WC_Helper_Product::create_simple_product() : \WC_Helper_Order::create_order();
		add_filter( $hook, '__return_null' );

		try {
			$result = wp_get_ability( 'woocommerce/' . $resource_name . 's-query' )->execute( array( 'id' => $object->get_id() ) );
		} finally {
			remove_filter( $hook, '__return_null' );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_output', $result->get_error_code() );
		$this->assertStringContainsString( $field, $result->get_error_message() );
	}

	/**
	 * @testdox Should omit non-date filter values without failing an order note write.
	 * @testWith ["2026-07-15T10:00:00"]
	 *           [1784109600]
	 *           ["1784109600"]
	 *           ["invalid date"]
	 *           [[]]
	 *           [false]
	 *           [null]
	 * @param mixed $date Filtered date.
	 */
	public function test_order_output_handles_filtered_dates( $date ): void {
		$order  = \WC_Helper_Order::create_order();
		$filter = static function () use ( $date ) {
			return $date;
		};
		add_filter( 'woocommerce_order_get_date_created', $filter );
		add_filter( 'woocommerce_order_get_date_modified', $filter );

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Filtered date note',
			)
		);
		$this->assertNotWPError( $result );
		$query = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'id' => $order->get_id() ) );
		$this->assertNotWPError( $query );
		foreach ( array( 'date_created', 'date_created_gmt', 'date_modified', 'date_modified_gmt' ) as $field ) {
			$this->assertArrayNotHasKey( $field, $result['order'] );
			$this->assertArrayNotHasKey( $field, $query['orders'][0] );
		}
		$this->assertSame( 'Filtered date note', wc_get_order_note( $result['note_id'] )->content );
	}

	/**
	 * @testdox Should omit an absent order note date while preserving false and empty strings.
	 */
	public function test_order_note_output_omits_absent_date(): void {
		$order = \WC_Helper_Order::create_order();
		$order->add_order_note( 'Internal note.' );
		$omit_date = static function ( $note ) {
			$note['date_created'] = null;
			$note['added_by']     = '';
			return $note;
		};
		add_filter( 'woocommerce_get_order_note', $omit_date );

		try {
			$result = wp_get_ability( 'woocommerce/order-notes-query' )->execute( array( 'id' => $order->get_id() ) );
		} finally {
			remove_filter( 'woocommerce_get_order_note', $omit_date );
		}

		$this->assertNotWPError( $result );
		$this->assertNotEmpty( $result['notes'] );
		foreach ( $result['notes'] as $note ) {
			$this->assertArrayNotHasKey( 'date_created', $note );
			$this->assertSame( '', $note['author'] );
			$this->assertFalse( $note['customer_note'] );
		}
	}

	/**
	 * @testdox Should give each Core output field a single type.
	 */
	public function test_core_output_schemas_have_single_types(): void {
		$check = function ( array $schema ) use ( &$check ): void {
			if ( isset( $schema['type'] ) ) {
				$this->assertIsString( $schema['type'] );
				$this->assertNotSame( 'null', $schema['type'] );
			}
			$this->assertNotContains( null, $schema['enum'] ?? array() );
			foreach ( $schema['properties'] ?? array() as $name => $property ) {
				if ( 'extensions' !== $name ) {
					$check( $property );
				}
			}
			foreach ( array( 'items', 'additionalProperties' ) as $keyword ) {
				if ( is_array( $schema[ $keyword ] ?? null ) ) {
					$check( $schema[ $keyword ] );
				}
			}
			foreach ( array( 'anyOf', 'oneOf' ) as $keyword ) {
				foreach ( $schema[ $keyword ] ?? array() as $branch ) {
					$check( $branch );
				}
			}
		};
		foreach ( self::CANONICAL_ABILITY_IDS as $name ) {
			$check( wp_get_ability( $name )->get_output_schema() );
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
		$this->assertSame( $product->get_id(), $result['id'] );
		$this->assertSame( 'deleted', $result['status'] );
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
		$this->assertSame( 'deleted', $result['status'] );
		$this->assertNull( get_post( $variation_id ) );
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
		$this->assertSame( 'trash', $result['status'] );
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
		$this->assertArrayNotHasKey( 'permalink', $result['products'][0] );
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
	 * @testdox Should change addresses and the customer note of a completed order, keep other fields and the status, and leave addresses out of the output.
	 */
	public function test_order_update_changes_addresses_and_customer_note(): void {
		$order = $this->create_order_for_update( 'completed' );
		$order->set_shipping_address_1( 'Keep this street' );
		$order->save();
		$total = $order->get_total();

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'            => $order->get_id(),
				'billing'       => array(
					'city'  => 'Porto',
					'email' => 'new@example.com',
				),
				'shipping'      => array(
					'country'  => 'PT',
					'state'    => '',
					'postcode' => '1000-001',
					'city'     => 'Lisbon',
				),
				'customer_note' => 'Leave at the door.',
			)
		);

		$this->assertIsArray( $result );
		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 'Porto', $saved->get_billing_city() );
		$this->assertSame( 'new@example.com', $saved->get_billing_email() );
		$this->assertSame( 'Lisbon', $saved->get_shipping_city() );
		$this->assertSame( 'Keep this street', $saved->get_shipping_address_1() );
		$this->assertSame( 'Leave at the door.', $saved->get_customer_note() );
		$this->assertSame( 'completed', $saved->get_status() );
		$this->assertSame( $total, $saved->get_total() );
		$this->assertSame( 'Lisbon', $result['order']['shipping']['city'] );
		$this->assertArrayNotHasKey( 'billing', $result['order'] );
		$this->assertArrayNotHasKey( 'customer_note', $result['order'] );
	}

	/**
	 * @testdox Should refuse an invalid order update with a code in the convention, and save nothing.
	 *
	 * @dataProvider provider_invalid_order_updates
	 *
	 * @param array  $input Input without the order ID.
	 * @param string $code  Expected error code.
	 * @param string $hint  Text that the message must have.
	 */
	public function test_order_update_refuses_invalid_input( array $input, string $code, string $hint ): void {
		$order = $this->create_order_for_update( 'pending' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_city( 'Before' );
		$order->save();

		$result = wp_get_ability( 'woocommerce/order-update' )->execute( array( 'id' => $order->get_id() ) + $input );

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertStringContainsString( $hint, $result->get_error_message() );
		$this->assertSame( 'Before', wc_get_order( $order->get_id() )->get_shipping_city() );
	}

	/**
	 * @testdox Should return every address and line refusal of an order update in one message, and save nothing.
	 */
	public function test_order_update_returns_every_refusal_in_one_message(): void {
		$order = $this->create_order_for_update( 'pending' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_city( 'Before' );
		$order->save();

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'           => $order->get_id(),
				'billing'      => array( 'email' => 'not-an-email' ),
				'shipping'     => array(
					'city'     => 'After',
					'postcode' => 'not a zip',
				),
				'remove_items' => array( 999999 ),
				'line_items'   => array( array( 'quantity' => 2 ), array( 'quantity' => 3 ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame(
			array(
				'woocommerce_ability_order_billing_email_invalid',
				'woocommerce_ability_order_shipping_postcode_invalid',
				'woocommerce_ability_order_line_not_found',
				'woocommerce_ability_order_line_item_target_invalid',
			),
			$result->get_error_codes()
		);
		$message = $result->get_error_message();
		foreach ( array( 'valid address', 'format of that country', 'include_line_items', 'product_id' ) as $hint ) {
			$this->assertStringContainsString( $hint, $message );
		}
		$this->assertSame( 1, substr_count( $message, 'Each entry in line_items' ), 'A repeated refusal shows once in the message.' );
		$this->assertSame( 'Before', wc_get_order( $order->get_id() )->get_shipping_city() );
	}

	/**
	 * @testdox Should format a sent postcode, and keep a saved postcode that the update does not send.
	 */
	public function test_order_update_formats_only_a_sent_postcode(): void {
		$order = $this->create_order_for_update( 'pending' );
		$order->set_shipping_country( 'GB' );
		$order->set_shipping_postcode( 'sw1a1aa' );
		$order->save();
		$ability = wp_get_ability( 'woocommerce/order-update' );

		$result = $ability->execute(
			array(
				'id'       => $order->get_id(),
				'shipping' => array( 'city' => 'London' ),
			)
		);
		$this->assertNotWPError( $result );
		$saved = wc_get_order( $order->get_id() );
		$kept  = $saved->get_shipping_postcode();
		$this->assertSame( 'London', $saved->get_shipping_city() );
		$ability->execute(
			array(
				'id'       => $order->get_id(),
				'shipping' => array( 'postcode' => 'ec1a1bb' ),
			)
		);

		$this->assertSame( 'sw1a1aa', $kept );
		$this->assertSame( 'EC1A 1BB', wc_get_order( $order->get_id() )->get_shipping_postcode() );
	}

	/**
	 * Invalid inputs for the order update ability.
	 *
	 * @return array<string, array{0: array, 1: string, 2: string}>
	 */
	public function provider_invalid_order_updates(): array {
		return array(
			'no fields'           => array( array(), 'woocommerce_ability_order_no_fields', 'Send at least one' ),
			'country'             => array(
				array(
					'shipping' => array(
						'city'    => 'After',
						'country' => 'XX',
					),
				),
				'woocommerce_ability_order_shipping_country_invalid',
				'ISO 3166-1',
			),
			'state kept from old' => array(
				array(
					'shipping' => array(
						'city'    => 'After',
						'country' => 'CA',
					),
				),
				'woocommerce_ability_order_shipping_state_invalid',
				'ON',
			),
			'postcode'            => array(
				array(
					'shipping' => array(
						'city'     => 'After',
						'postcode' => 'not a zip',
					),
				),
				'woocommerce_ability_order_shipping_postcode_invalid',
				'format of that country',
			),
			'billing email'       => array(
				array(
					'shipping' => array( 'city' => 'After' ),
					'billing'  => array( 'email' => 'not-an-email' ),
				),
				'woocommerce_ability_order_billing_email_invalid',
				'valid address',
			),
			'unknown line'        => array(
				array(
					'shipping'     => array( 'city' => 'After' ),
					'remove_items' => array( 999999 ),
				),
				'woocommerce_ability_order_line_not_found',
				'include_line_items',
			),
			'no line target'      => array(
				array(
					'shipping'   => array( 'city' => 'After' ),
					'line_items' => array( array( 'quantity' => 2 ) ),
				),
				'woocommerce_ability_order_line_item_target_invalid',
				'product_id',
			),
			'unknown product'     => array(
				array(
					'shipping'   => array( 'city' => 'After' ),
					'line_items' => array( array( 'product_id' => 999999 ) ),
				),
				'woocommerce_ability_order_product_not_found',
				'woocommerce/products-query',
			),
			'bad shipping total'  => array(
				array(
					'shipping'       => array( 'city' => 'After' ),
					'shipping_lines' => array(
						array(
							'method_title' => 'Courier',
							'total'        => 'free',
						),
					),
				),
				'woocommerce_ability_order_shipping_line_total_invalid',
				'9.99',
			),
			'no method title'     => array(
				array(
					'shipping'       => array( 'city' => 'After' ),
					'shipping_lines' => array( array( 'total' => '1.00' ) ),
				),
				'woocommerce_ability_order_shipping_line_method_title_required',
				'method_title',
			),
		);
	}

	/**
	 * @testdox Should add, change and remove product lines, recalculate totals, and save the order once.
	 */
	public function test_order_update_writes_product_lines(): void {
		$order   = $this->create_order_for_update( 'pending' );
		$kept    = current( $order->get_items() );
		$removed = new \WC_Order_Item_Product();
		$removed->set_props(
			array(
				'name'     => 'Removed',
				'quantity' => 1,
				'subtotal' => 50,
				'total'    => 50,
			)
		);
		$order->add_item( $removed );
		$order->calculate_totals( false );
		$order->save();
		$product = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 30 ) );
		$saves   = 0;
		$count   = static function () use ( &$saves ) {
			++$saves;
		};
		add_action( 'woocommerce_update_order', $count );
		$old_quantity = $kept->get_quantity();
		$old_total    = (float) $kept->get_total();

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'           => $order->get_id(),
				'line_items'   => array(
					array(
						'id'       => $kept->get_id(),
						'quantity' => $old_quantity * 2,
					),
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'remove_items' => array( $removed->get_id() ),
			)
		);
		remove_action( 'woocommerce_update_order', $count );

		$this->assertIsArray( $result );
		$this->assertSame( 3, $saves );
		$saved = wc_get_order( $order->get_id() );
		$items = array_values( $saved->get_items() );
		$this->assertCount( 2, $items );
		$this->assertSame( $old_quantity * 2, $items[0]->get_quantity() );
		$this->assertEqualsWithDelta( $old_total * 2, (float) $items[0]->get_total(), 0.001 );
		$this->assertSame( $product->get_id(), $items[1]->get_product_id() );
		$this->assertEqualsWithDelta( 60.0, (float) $items[1]->get_total(), 0.001 );
		$expected = $old_total * 2 + 60 + (float) $saved->get_shipping_total();
		$this->assertEqualsWithDelta( $expected, (float) $saved->get_total(), 0.001 );
		$this->assertSame( $items[1]->get_id(), $result['order']['line_items'][1]['id'] );
	}

	/**
	 * @testdox Should add, change and remove shipping lines, include them in the output, and return them from the orders query.
	 */
	public function test_order_update_writes_shipping_lines(): void {
		$order       = $this->create_order_for_update( 'pending' );
		$existing_id = array_key_first( $order->get_shipping_methods() );

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array(
						'method_id'    => 'courier',
						'method_title' => 'Courier',
						'total'        => '7.50',
					),
					array(
						'method_id'    => 'pickup',
						'method_title' => 'Pickup',
						'total'        => '2.50',
					),
				),
				'remove_items'   => array( $existing_id ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result['order']['shipping_lines'] );
		$saved = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 10.0, (float) $saved->get_shipping_total(), 0.001 );
		$this->assertArrayNotHasKey( $existing_id, $saved->get_shipping_methods() );

		$change_id = $result['order']['shipping_lines'][1]['id'];
		$result    = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array(
						'id'    => $change_id,
						'total' => '3.00',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertEqualsWithDelta( 10.5, (float) wc_get_order( $order->get_id() )->get_shipping_total(), 0.001 );

		$query = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'            => $order->get_id(),
				'include_lines' => true,
			)
		);
		$this->assertSame( 'Pickup', $query['orders'][0]['shipping_lines'][1]['method_title'] );
	}

	/**
	 * @testdox Should refuse a line change on an order status that does not allow editing, and name the fix.
	 */
	public function test_order_update_refuses_line_change_on_order_that_is_not_editable(): void {
		$order       = $this->create_order_for_update( 'completed' );
		$shipping_id = array_key_first( $order->get_shipping_methods() );

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'           => $order->get_id(),
				'remove_items' => array( $shipping_id ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_not_editable', $result->get_error_code() );
		$this->assertStringContainsString( 'woocommerce/order-update-status', $result->get_error_message() );
		$this->assertArrayHasKey( $shipping_id, wc_get_order( $order->get_id() )->get_shipping_methods() );
	}

	/**
	 * @testdox Should refuse a line ID of another type or another order.
	 */
	public function test_order_update_refuses_line_of_another_type_or_order(): void {
		$order = $this->create_order_for_update( 'pending' );
		$other = $this->create_order_for_update( 'pending' );
		$fee   = new \WC_Order_Item_Fee();
		$fee->set_total( 5 );
		$order->add_item( $fee );
		$order->save();

		foreach ( array( $fee->get_id(), array_key_first( $other->get_items() ) ) as $item_id ) {
			$result = wp_get_ability( 'woocommerce/order-update' )->execute(
				array(
					'id'           => $order->get_id(),
					'remove_items' => array( $item_id ),
				)
			);

			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_order_line_not_found', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should apply coupons again to two added products.
	 */
	public function test_order_update_applies_coupons_to_added_products(): void {
		$order  = $this->create_order_for_update( 'pending' );
		$coupon = \WC_Helper_Coupon::create_coupon(
			'tenpercent',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '10',
			)
		);
		$order->apply_coupon( $coupon );
		$order->save();
		$discount_before = (float) $order->get_discount_total();
		$first           = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 100 ) );
		$second          = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 200 ) );

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array( 'product_id' => $first->get_id() ),
					array( 'product_id' => $second->get_id() ),
				),
			)
		);

		$this->assertIsArray( $result );
		$saved = wc_get_order( $order->get_id() );
		$items = array_values( $saved->get_items() );
		$this->assertEqualsWithDelta( 90.0, (float) $items[ count( $items ) - 2 ]->get_total(), 0.001 );
		$this->assertEqualsWithDelta( 180.0, (float) $items[ count( $items ) - 1 ]->get_total(), 0.001 );
		$this->assertEqualsWithDelta( $discount_before + 30, (float) $saved->get_discount_total(), 0.001 );
	}

	/**
	 * @testdox Should change product stock when a line of an order with reduced stock is added, changes quantity, or is removed.
	 */
	public function test_order_update_adjusts_stock_of_changed_and_removed_lines(): void {
		$changed = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
		$removed = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
		$added   = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
		$order   = new \WC_Order();
		$order->add_product( $changed, 2 );
		$order->add_product( $removed, 3 );
		$order->set_status( 'on-hold' );
		$order->save();
		wc_reduce_stock_levels( $order->get_id() );
		$order = wc_get_order( $order->get_id() );
		$items = array_values( $order->get_items() );

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'           => $order->get_id(),
				'line_items'   => array(
					array(
						'id'       => $items[0]->get_id(),
						'quantity' => 5,
					),
					array(
						'product_id' => $added->get_id(),
						'quantity'   => 4,
					),
				),
				'remove_items' => array( $items[1]->get_id() ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 5, wc_get_product( $changed->get_id() )->get_stock_quantity() );
		$this->assertSame( 10, wc_get_product( $removed->get_id() )->get_stock_quantity() );
		$this->assertSame( 6, wc_get_product( $added->get_id() )->get_stock_quantity() );
		$this->assertCount( 2, wc_get_order( $order->get_id() )->get_items() );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertSame(
			sprintf(
				'Adjusted stock: %1$s &ndash; 7&rarr;10, %2$s (8&rarr;5), %3$s (10&rarr;6)',
				$removed->get_name(),
				$changed->get_name(),
				$added->get_name()
			),
			$notes[0]->content
		);

		wc_get_order( $order->get_id() )->update_status( 'cancelled' );

		$this->assertSame( 10, wc_get_product( $changed->get_id() )->get_stock_quantity() );
		$this->assertSame( 10, wc_get_product( $added->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should send the named email to the billing email and keep the status.
	 *
	 * @dataProvider provider_order_emails
	 *
	 * @param string $email  Email name.
	 * @param string $status Order status.
	 */
	public function test_order_send_email_sends_named_email( string $email, string $status ): void {
		$order = $this->create_order_for_update( $status );
		$order->set_billing_email( 'customer@example.com' );
		$order->save();
		$mails   = array();
		$capture = static function ( $short_circuit, $atts ) use ( &$mails ) {
			$mails[] = $atts;
			return true;
		};
		add_filter( 'pre_wp_mail', $capture, 10, 2 );

		$result = wp_get_ability( 'woocommerce/order-send-email' )->execute(
			array(
				'id'    => $order->get_id(),
				'email' => $email,
			)
		);
		remove_filter( 'pre_wp_mail', $capture );

		$this->assertSame(
			array(
				'order_id' => $order->get_id(),
				'email'    => $email,
			),
			$result
		);
		$this->assertCount( 1, $mails );
		$this->assertSame( 'customer@example.com', $mails[0]['to'] );
		$this->assertSame( $status, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * The emails that the order send email ability sends.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_order_emails(): array {
		return array(
			'invoice'    => array( 'invoice', 'pending' ),
			'processing' => array( 'processing', 'processing' ),
			'completed'  => array( 'completed', 'completed' ),
		);
	}

	/**
	 * @testdox Should refuse to send an order email that does not apply, and send nothing.
	 */
	public function test_order_send_email_refusals(): void {
		$mails   = 0;
		$capture = static function () use ( &$mails ) {
			++$mails;
			return true;
		};
		add_filter( 'pre_wp_mail', $capture );
		$ability = wp_get_ability( 'woocommerce/order-send-email' );
		$order   = $this->create_order_for_update( 'pending' );

		$mismatch = $ability->execute(
			array(
				'id'    => $order->get_id(),
				'email' => 'completed',
			)
		);

		add_filter( 'woocommerce_order_get_billing_email', '__return_empty_string' );
		$no_recipient = $ability->execute(
			array(
				'id'    => $order->get_id(),
				'email' => 'invoice',
			)
		);

		remove_filter( 'woocommerce_order_get_billing_email', '__return_empty_string' );

		$order = $this->create_order_for_update( 'processing' );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
		$disabled = $ability->execute(
			array(
				'id'    => $order->get_id(),
				'email' => 'processing',
			)
		);
		remove_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
		remove_filter( 'pre_wp_mail', $capture );

		$this->assertSame( 'woocommerce_ability_order_email_status_mismatch', $mismatch->get_error_code() );
		$this->assertSame( 'woocommerce_ability_order_email_no_recipient', $no_recipient->get_error_code() );
		$this->assertSame( 'woocommerce_ability_order_email_disabled', $disabled->get_error_code() );
		$this->assertSame( 0, $mails );

		add_filter( 'pre_wp_mail', '__return_false' );
		$failed = $ability->execute(
			array(
				'id'    => $order->get_id(),
				'email' => 'invoice',
			)
		);
		remove_filter( 'pre_wp_mail', '__return_false' );

		$this->assertSame( 'woocommerce_ability_order_email_send_failed', $failed->get_error_code() );
	}

	/**
	 * Create an order with a product line and a shipping line, for the order write tests.
	 *
	 * @param string $status Order status.
	 * @return \WC_Order
	 */
	private function create_order_for_update( string $status ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( $status );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		return wc_get_order( $order->get_id() );
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
	 * @testdox Should omit absent billing email values.
	 */
	public function test_orders_query_omits_absent_billing_email(): void {
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
		$this->assertArrayNotHasKey( 'billing_email', $result['orders'][0] );
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
	 * @testdox Should apply date_after and date_before with a time in the store timezone.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_filters_created_date_in_store_timezone( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		update_option( 'timezone_string', 'Europe/Amsterdam' );

		$email = wp_unique_id( 'abilities-order-created-timezone-' ) . '@example.com';
		$early = $this->create_order_for_query_sorting( $email, '2025-07-15T09:00:00', '2025-07-15T09:00:00' );
		$late  = $this->create_order_for_query_sorting( $email, '2025-07-15T11:00:00', '2025-07-15T11:00:00' );

		$after = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_after'    => '2025-07-15T10:00:00',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $after );
		$this->assertSame( array( $late->get_id() ), array_column( $after['orders'], 'id' ), 'date_after 10:00 should return only the order created at 11:00 store time.' );

		$before = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_before'   => '2025-07-15T10:00:00',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $before );
		$this->assertSame( array( $early->get_id() ), array_column( $before['orders'], 'id' ), 'date_before 10:00 should return only the order created at 09:00 store time.' );

		$after_utc = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_after'    => '2025-07-15T08:00:00Z',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $after_utc );
		$this->assertSame( array( $late->get_id() ), array_column( $after_utc['orders'], 'id' ), 'date_after 08:00 UTC (10:00 store time) should return only the order created at 11:00 store time.' );

		$after_millis_utc = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_after'    => '2025-07-15T08:00:00.000Z',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $after_millis_utc );
		$this->assertSame( array( $late->get_id() ), array_column( $after_millis_utc['orders'], 'id' ), 'date_after 08:00 UTC with fractional seconds (the form JavaScript toISOString() produces) should match the plain UTC value.' );

		$after_short_offset = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_after'    => '2025-07-15T10:00:00+02',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $after_short_offset );
		$this->assertSame( array( $late->get_id() ), array_column( $after_short_offset['orders'], 'id' ), 'date_after 10:00 with a +02 offset (08:00 UTC) should return only the order created at 11:00 store time.' );

		$range = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => $email,
				'date_after'    => '2025-07-15T08:00:00',
				'date_before'   => '2025-07-15T10:00:00',
				'per_page'      => 2,
			)
		);

		$this->assertNotWPError( $range );
		$this->assertSame( array( $early->get_id() ), array_column( $range['orders'], 'id' ), 'date_after 08:00 together with date_before 10:00 store time should return only the order created at 09:00.' );
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
	 * @testdox Should apply modified_before with a UTC offset in the store timezone.
	 *
	 * @dataProvider provider_order_storage_engines
	 *
	 * @param bool $hpos_enabled Whether HPOS/COT should be enabled for the test.
	 */
	public function test_orders_query_filters_modified_date_in_store_timezone( bool $hpos_enabled ): void {
		$this->set_order_storage_for_test( $hpos_enabled );

		update_option( 'timezone_string', 'Europe/Amsterdam' );

		$email = wp_unique_id( 'abilities-order-modified-timezone-' ) . '@example.com';
		$early = $this->create_order_for_query_sorting( $email, '2025-07-15T09:00:00', '2025-07-15T10:00:00' );
		$late  = $this->create_order_for_query_sorting( $email, '2025-07-15T09:00:00', '2025-07-15T12:00:00' );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email'   => $email,
				'modified_before' => '2025-07-15T09:00:00.000Z',
				'per_page'        => 2,
			)
		);

		$this->assertNotWPError( $result );
		$ids = array_column( $result['orders'], 'id' );
		$this->assertContains( $early->get_id(), $ids, 'modified_before 09:00 UTC with fractional seconds should return the order modified at 10:00 store time (08:00 UTC).' );
		$this->assertNotContains( $late->get_id(), $ids, 'modified_before 09:00 UTC with fractional seconds should exclude the order modified at 12:00 store time (10:00 UTC).' );
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
	 * @testdox Should never return the API credentials of a configured gateway.
	 */
	public function test_payment_gateways_query_omits_gateway_credentials(): void {
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load'          => 'yes',
				'enabled'               => 'yes',
				'title'                 => 'PayPal',
				'api_username'          => 'live-api-username',
				'api_password'          => 'live-api-password',
				'api_signature'         => 'live-api-signature',
				'sandbox_api_username'  => 'sandbox-api-username',
				'sandbox_api_password'  => 'sandbox-api-password',
				'sandbox_api_signature' => 'sandbox-api-signature',
				'identity_token'        => 'pdt-identity-token',
			)
		);

		try {
			self::reload_payment_gateways();

			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'paypal' ) );
		} finally {
			delete_option( 'woocommerce_paypal_settings' );
			self::reload_payment_gateways();
		}

		$this->assertNotWPError( $result );
		$this->assertCount( 1, $result['gateways'] );
		$this->assertSame(
			array( 'id', 'title', 'method_title', 'description', 'enabled' ),
			array_keys( $result['gateways'][0] )
		);
		$this->assertTrue( $result['gateways'][0]['enabled'] );

		$encoded = wp_json_encode( $result );
		foreach ( array( 'api-username', 'api-password', 'api-signature', 'pdt-identity-token' ) as $secret ) {
			$this->assertStringNotContainsString( $secret, $encoded, "Output should not contain the {$secret} setting." );
		}
	}

	/**
	 * @testdox Should list every loaded gateway, enabled or not, with a boolean enabled flag.
	 */
	public function test_payment_gateways_query_lists_loaded_gateways(): void {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes' ) );
		update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );

		try {
			self::reload_payment_gateways();

			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array() );
		} finally {
			delete_option( 'woocommerce_bacs_settings' );
			delete_option( 'woocommerce_cheque_settings' );
			self::reload_payment_gateways();
		}

		$this->assertNotWPError( $result );

		$enabled_by_id = array_column( $result['gateways'], 'enabled', 'id' );
		$this->assertTrue( $enabled_by_id['bacs'] );
		$this->assertFalse( $enabled_by_id['cheque'] );
		$this->assertArrayHasKey( 'cod', $enabled_by_id );
		$this->assertSame( 'Direct bank transfer', array_column( $result['gateways'], 'method_title', 'id' )['bacs'] );
		$this->assertSame( 1, $result['total_pages'] );
		$this->assertSame( count( $result['gateways'] ), $result['per_page'] );
	}

	/**
	 * @testdox Should read gateway titles and descriptions without running the checkout filters.
	 */
	public function test_payment_gateways_query_skips_checkout_title_and_description_filters(): void {
		$filter = static function () {
			return 'filtered';
		};
		add_filter( 'woocommerce_gateway_title', $filter );
		add_filter( 'woocommerce_gateway_description', $filter );

		try {
			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'bacs' ) );
		} finally {
			remove_filter( 'woocommerce_gateway_title', $filter );
			remove_filter( 'woocommerce_gateway_description', $filter );
		}

		$this->assertNotWPError( $result );
		$this->assertNotSame( 'filtered', $result['gateways'][0]['title'] );
		$this->assertNotSame( 'filtered', $result['gateways'][0]['description'] );
	}

	/**
	 * @testdox Should fall back to the saved title and description when a gateway leaves the properties unset.
	 */
	public function test_payment_gateways_query_falls_back_to_saved_title_and_description(): void {
		$gateway = new class() extends \WC_Payment_Gateway {
			/**
			 * Leaves title and description unset, like gateways that read them from settings in their getters.
			 */
			public function __construct() {
				$this->id          = 'settings_only_gateway';
				$this->form_fields = array(
					'title'       => array( 'type' => 'text' ),
					'description' => array( 'type' => 'textarea' ),
					'api_key'     => array( 'type' => 'password' ),
				);
			}
		};
		update_option(
			'woocommerce_settings_only_gateway_settings',
			array(
				'enabled'     => 'yes',
				'title'       => 'Saved title',
				'description' => 'Saved description',
				'api_key'     => 'saved-api-key',
			)
		);
		$register = static function ( $gateways ) use ( $gateway ) {
			$gateways[] = $gateway;
			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $register );

		try {
			self::reload_payment_gateways();

			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'settings_only_gateway' ) );
		} finally {
			remove_filter( 'woocommerce_payment_gateways', $register );
			delete_option( 'woocommerce_settings_only_gateway_settings' );
			self::reload_payment_gateways();
		}

		$this->assertNotWPError( $result );
		$this->assertSame( 'Saved title', $result['gateways'][0]['title'] );
		$this->assertSame( 'Saved description', $result['gateways'][0]['description'] );
		$this->assertStringNotContainsString( 'saved-api-key', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should report a gateway as enabled only when checkout would, that is when enabled is exactly yes.
	 */
	public function test_payment_gateways_query_reports_enabled_like_checkout(): void {
		$gateway  = new class() extends \WC_Payment_Gateway {
			/**
			 * Assigns a truthy value other than yes, which checkout treats as disabled.
			 */
			public function __construct() {
				$this->id      = 'truthy_enabled_gateway';
				$this->title   = 'Truthy';
				$this->enabled = 'true';
			}
		};
		$register = static function ( $gateways ) use ( $gateway ) {
			$gateways[] = $gateway;
			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $register );

		try {
			self::reload_payment_gateways();

			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'truthy_enabled_gateway' ) );
		} finally {
			remove_filter( 'woocommerce_payment_gateways', $register );
			self::reload_payment_gateways();
		}

		$this->assertNotWPError( $result );
		$this->assertFalse( $result['gateways'][0]['enabled'] );
	}

	/**
	 * @testdox Should list gateways in the merchant's checkout order.
	 */
	public function test_payment_gateways_query_follows_checkout_order(): void {
		update_option(
			'woocommerce_gateway_order',
			array(
				'cod'    => 0,
				'cheque' => 1,
				'bacs'   => 2,
			)
		);

		try {
			self::reload_payment_gateways();

			$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array() );
		} finally {
			delete_option( 'woocommerce_gateway_order' );
			self::reload_payment_gateways();
		}

		$this->assertNotWPError( $result );
		$this->assertSame(
			array( 'cod', 'cheque', 'bacs' ),
			array_values( array_intersect( array_column( $result['gateways'], 'id' ), array( 'bacs', 'cheque', 'cod' ) ) )
		);
	}

	/**
	 * @testdox Should close the gateway item schema, so no settings field can be added to the output.
	 */
	public function test_payment_gateways_query_item_schema_is_closed(): void {
		$items = wp_get_ability( 'woocommerce/payment-gateways-query' )->get_output_schema()['properties']['gateways']['items'];

		$this->assertFalse( $items['additionalProperties'] );
	}

	/**
	 * @testdox Should let a shop manager query payment gateways.
	 */
	public function test_payment_gateways_query_allows_shop_manager(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );

		$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'bacs' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( 'bacs', $result['gateways'][0]['id'] );
	}

	/**
	 * @testdox Should return a not-found error for an unknown payment gateway ID.
	 */
	public function test_payment_gateways_query_returns_not_found_for_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/payment-gateways-query' )->execute( array( 'id' => 'no-such-gateway' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_payment_gateway_not_found', $result->get_error_code() );
	}

	/**
	 * Rebuild the loaded payment gateways from the current options, since init() only appends.
	 */
	private static function reload_payment_gateways(): void {
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();
	}

	/**
	 * Register the WooCommerce ability category for this test.
	 */
	private function register_woocommerce_category(): void {
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
			if ( wp_has_ability( $ability_id ) ) {
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
