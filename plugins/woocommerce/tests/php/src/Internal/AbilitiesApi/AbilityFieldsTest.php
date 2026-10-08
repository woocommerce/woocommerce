<?php
/**
 * AbilityFieldsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFields;
use Automattic\WooCommerce\Abilities\AbilityProductTypes;

/**
 * Extension fields in the output of the product and order abilities.
 */
class AbilityFieldsTest extends \WC_Unit_Test_Case {

	private const ABILITY_IDS = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

	private const CODE_SCHEMA = array(
		'type'  => 'string',
		'title' => 'Test code',
	);

	/**
	 * Shared administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

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
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( self::$administrator_id );
		AbilitiesLoader::init();

		if ( ! wp_has_ability_category( 'woocommerce' ) ) {
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
		}

		AbilityFields::register(
			'product',
			'test_code',
			array(
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		AbilityFields::register(
			'order',
			'test_note',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		AbilityFields::register(
			'order_item',
			'test_gift',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order_Item $item ) {
					return 'item-' . $item->get_id();
				},
			)
		);

		$this->set_feature( true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		$this->unregister_abilities();
		$this->reset_fields();
		remove_filter( 'woocommerce_product_class', array( $this, 'membership_product_class' ), 10 );
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should return the registered field values under extensions, and leave out a null that the schema does not allow.
	 */
	public function test_product_reads_return_extension_values(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with code' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen without code' ) );

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) )['products'];
		$products = array_column( $products, null, 'id' );
		$updated  = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $with_code->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( array( 'test_code' => 'A1' ), $products[ $with_code->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $without_code->get_id() ] );
		$this->assertSame( array( 'test_code' => 'A1' ), $updated['product']['extensions'] );
	}

	/**
	 * @testdox Should return order and line item field values under extensions.
	 */
	public function test_order_reads_return_extension_values(): void {
		$order = \WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );

		$listed = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'][0];
		$noted  = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Hi',
			)
		);

		$this->assertSame( array( 'test_note' => 'order-' . $listed['id'] ), $listed['extensions'] );
		$this->assertSame( array( 'test_gift' => 'item-' . $item->get_id() ), $listed['line_items'][0]['extensions'] );
		$this->assertSame( array( 'test_note' => 'order-' . $order->get_id() ), $noted['order']['extensions'] );
	}

	/**
	 * @testdox Should describe the registered fields in the output schemas.
	 */
	public function test_output_schemas_describe_extension_fields(): void {
		$products = wp_get_ability( 'woocommerce/products-query' )->get_output_schema()['properties']['products']['items']['properties'];
		$product  = wp_get_ability( 'woocommerce/product-update' )->get_output_schema()['properties']['product']['properties'];
		$order    = wp_get_ability( 'woocommerce/orders-query' )->get_output_schema()['properties']['orders']['items']['properties'];

		$this->assertSame( self::CODE_SCHEMA, $products['extensions']['properties']['test_code'] );
		$this->assertSame( self::CODE_SCHEMA, $product['extensions']['properties']['test_code'] );
		$this->assertSame( array( 'type' => 'string' ), $order['extensions']['properties']['test_note'] );
		$this->assertSame( array( 'type' => 'string' ), $order['line_items']['items']['properties']['extensions']['properties']['test_gift'] );
	}

	/**
	 * @testdox Should list and return products of a type that an extension adds.
	 */
	public function test_products_of_a_type_an_extension_adds_are_listed_and_returned(): void {
		add_filter( 'woocommerce_product_class', array( $this, 'membership_product_class' ), 10, 2 );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen membership' ) );
		wp_set_object_terms( $product->get_id(), 'membership', 'product_type' );
		wp_cache_flush();
		$product = wc_get_product( $product->get_id() );
		$product->update_meta_data( '_test_code', 'M1' );
		$product->save();
		$this->set_feature( true );

		$query  = wp_get_ability( 'woocommerce/products-query' );
		$listed = $query->execute( array( 'search' => 'Pen membership' ) );
		$by_id  = $query->execute( array( 'id' => $product->get_id() ) );

		$this->assertInstanceOf( TestMembershipProduct::class, $product );
		$this->assertContains( 'membership', $query->get_output_schema()['properties']['products']['items']['properties']['type']['enum'] );
		$this->assertSame( 'membership', $listed['products'][0]['type'] );
		$this->assertSame( array( 'test_code' => 'M1' ), $listed['products'][0]['extensions'] );
		$this->assertSame( 'membership', $by_id['products'][0]['type'] );
	}

	/**
	 * @testdox Should create and update products of a product type alias that an extension registers.
	 */
	public function test_registered_product_type_alias_creates_and_updates_products(): void {
		$this->register_membership_alias();
		$existing = $this->create_membership_product( 'Gold membership' );

		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'               => 'Silver membership',
				'product_type_alias' => 'membership',
				'regular_price'      => '30',
			)
		);
		$priced  = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $existing->get_id(),
				'product_type_alias' => 'membership',
				'regular_price'      => '45',
			)
		);
		$renamed = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $existing->get_id(),
				'name' => 'Platinum membership',
			)
		);
		wp_cache_flush();
		$saved = wc_get_product( $existing->get_id() );

		$this->assertNotWPError( $created );
		$this->assertInstanceOf( TestMembershipProduct::class, wc_get_product( $created['product']['id'] ) );
		$this->assertSame( '30', $created['product']['regular_price'] );
		$this->assertNotWPError( $priced );
		$this->assertNotWPError( $renamed );
		$this->assertInstanceOf( TestMembershipProduct::class, $saved );
		$this->assertSame( '45', $saved->get_regular_price() );
		$this->assertSame( 'Platinum membership', $saved->get_name() );
	}

	/**
	 * @testdox Should find the products of a registered alias in a query by that alias.
	 */
	public function test_query_by_registered_alias_finds_its_products(): void {
		$this->register_membership_alias();
		$membership = $this->create_membership_product( 'Query membership' );
		\WC_Helper_Product::create_simple_product( true, array( 'name' => 'Query pen' ) );

		$result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'search'             => 'Query',
				'product_type_alias' => 'membership',
			)
		);

		$this->assertSame( array( $membership->get_id() ), array_column( $result['products'], 'id' ) );
	}

	/**
	 * @testdox Should refuse a field that the registered alias does not accept.
	 */
	public function test_registered_alias_refuses_fields_it_does_not_accept(): void {
		$this->register_membership_alias();
		$product = $this->create_membership_product( 'Gold membership' );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'membership',
				'stock_quantity'     => 5,
			)
		);

		$this->assertWPError( $result );
	}

	/**
	 * @testdox Should ignore a registered alias with the feature off.
	 */
	public function test_registered_alias_is_ignored_with_the_feature_off(): void {
		$this->register_membership_alias();
		$product = $this->create_membership_product( 'Gold membership' );
		$this->set_feature( false );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Platinum membership',
			)
		);
		$schema = wp_json_encode( wp_get_ability( 'woocommerce/product-create' )->get_input_schema() );

		$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
		$this->assertStringNotContainsString( '"membership"', $schema );
	}

	/**
	 * @testdox Should refuse to register an alias that Core already has, or a config without a type and fields.
	 */
	public function test_register_refuses_a_core_alias_and_an_incomplete_config(): void {
		$this->setExpectedIncorrectUsage( AbilityProductTypes::class . '::register' );
		AbilityProductTypes::register(
			'physical',
			array(
				'wc_type' => 'membership',
				'fields'  => array( 'name' ),
			)
		);
		AbilityProductTypes::register( 'membership', array( 'fields' => array( 'name' ) ) );

		$aliases = AbilityProductTypes::get_all();

		$this->assertSame( array( 'physical', 'virtual', 'digital', 'affiliate', 'grouped' ), array_keys( $aliases ) );
		$this->assertSame( 'simple', $aliases['physical']['wc_type'] );
	}

	/**
	 * Register the membership alias and the abilities again, so their schemas list it.
	 */
	private function register_membership_alias(): void {
		AbilityProductTypes::register(
			'membership',
			array(
				'wc_type'       => 'membership',
				'fields'        => array( 'name', 'regular_price', 'sale_price' ),
				'product_props' => array( 'virtual' => true ),
			)
		);
	}

	/**
	 * A saved product of the membership type. It registers the abilities again, so the output schema lists the type.
	 *
	 * @param string $name Product name.
	 * @return \WC_Product
	 */
	private function create_membership_product( string $name ): \WC_Product {
		add_filter( 'woocommerce_product_class', array( $this, 'membership_product_class' ), 10, 2 );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => $name ) );
		wp_set_object_terms( $product->get_id(), 'membership', 'product_type' );
		wp_cache_flush();
		$this->set_feature( true );
		return wc_get_product( $product->get_id() );
	}

	/**
	 * Use TestMembershipProduct for the membership product type.
	 *
	 * @internal
	 *
	 * @param string $classname    Product class.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function membership_product_class( $classname, $product_type ) {
		return 'membership' === $product_type ? TestMembershipProduct::class : $classname;
	}

	/**
	 * @testdox Should not change the abilities with the feature off.
	 */
	public function test_output_does_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();
		$order = \WC_Helper_Order::create_order();

		$query    = wp_get_ability( 'woocommerce/products-query' );
		$products = $query->execute( array( 'search' => 'Pen' ) )['products'];
		$orders   = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'];

		$this->assertArrayNotHasKey( 'extensions', $query->get_output_schema()['properties']['products']['items']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $products[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0]['line_items'][0] );
		$this->assertSame( $order->get_id(), $orders[0]['id'] );
	}

	/**
	 * @testdox Should refuse a field without a schema.
	 */
	public function test_field_without_schema_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );

		AbilityFields::register( 'product', 'test_bad', array( 'get_callback' => '__return_true' ) );

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * @testdox Should refuse a field whose get_callback is not callable.
	 */
	public function test_field_with_uncallable_get_callback_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );

		AbilityFields::register(
			'product',
			'test_bad',
			array(
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => 'not_a_function',
			)
		);

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * @testdox Should leave out a field whose get_callback throws, and keep the other fields.
	 */
	public function test_field_that_throws_is_left_out(): void {
		AbilityFields::register(
			'order',
			'test_broken',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function () {
					throw new \RuntimeException( 'Broken' );
				},
			)
		);
		$order = \WC_Helper_Order::create_order();

		$output = AbilityFields::add_to_output( array(), 'order', $order );

		$this->assertSame( array( 'test_note' => 'order-' . $order->get_id() ), $output['extensions'] );
	}

	/**
	 * @testdox Should cast a value that matches the field schema to the schema type.
	 */
	public function test_field_value_is_cast_to_its_schema_type(): void {
		AbilityFields::register(
			'order',
			'test_count',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn() => '12',
			)
		);
		$order = \WC_Helper_Order::create_order();

		$output = AbilityFields::add_to_output( array(), 'order', $order );

		$this->assertSame( 12, $output['extensions']['test_count'] );
	}

	/**
	 * @testdox Should leave out, log and report a value that does not match the field schema, and keep the output valid.
	 */
	public function test_field_value_that_does_not_match_its_schema_is_left_out(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );
		AbilityFields::register(
			'product',
			'test_count',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static function () {
					return 'many';
				},
			)
		);
		$this->set_feature( true );
		$reported = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$reported ) {
				$reported[] = $message;
			},
			10,
			2
		);
		$first  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen one' ) );
		$second = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen two' ) );
		$first->update_meta_data( '_test_code', 'A1' );
		$first->save();

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) );

		$this->assertIsArray( $products );
		$products = array_column( $products['products'], null, 'id' );
		$this->assertSame( array( 'test_code' => 'A1' ), $products[ $first->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $second->get_id() ] );
		$this->assertCount( 1, preg_grep( '/"test_count" of "product"/', $reported ) );
	}

	/**
	 * @testdox Should return a null field value under extensions when the schema allows it.
	 */
	public function test_field_that_returns_null_is_kept(): void {
		AbilityFields::register(
			'order',
			'test_empty',
			array(
				'schema'       => array( 'type' => array( 'string', 'null' ) ),
				'get_callback' => '__return_null',
			)
		);
		$order = \WC_Helper_Order::create_order();

		$output = AbilityFields::add_to_output( array(), 'order', $order );

		$this->assertSame(
			array(
				'test_note'  => 'order-' . $order->get_id(),
				'test_empty' => null,
			),
			$output['extensions']
		);
	}

	/**
	 * @testdox Should describe and return the product fields that two separate registrations add.
	 */
	public function test_product_fields_from_two_registrations_are_described_and_returned(): void {
		AbilityFields::register(
			'product',
			'test_badge',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Product $product ) {
					return 'badge-' . $product->get_id();
				},
			)
		);
		$this->set_feature( true );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with badge' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();

		$schema = wp_get_ability( 'woocommerce/products-query' )->get_output_schema()['properties']['products']['items']['properties'];
		$listed = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen with badge' ) )['products'][0];

		$this->assertSame( self::CODE_SCHEMA, $schema['extensions']['properties']['test_code'] );
		$this->assertSame( array( 'type' => 'string' ), $schema['extensions']['properties']['test_badge'] );
		$this->assertSame(
			array(
				'test_code'  => 'A1',
				'test_badge' => 'badge-' . $product->get_id(),
			),
			$listed['extensions']
		);
	}

	/**
	 * Set the feature and register the abilities again.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );

		$this->unregister_abilities();
		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister the core abilities.
	 */
	private function unregister_abilities(): void {
		foreach ( self::ABILITY_IDS as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Drop every registered field.
	 */
	private function reset_fields(): void {
		$fields = new \ReflectionProperty( AbilityFields::class, 'fields' );
		$fields->setAccessible( true );
		$fields->setValue( null, array() );
		$reported = new \ReflectionProperty( AbilityFields::class, 'reported' );
		$reported->setAccessible( true );
		$reported->setValue( null, array() );
		$aliases = new \ReflectionProperty( AbilityProductTypes::class, 'aliases' );
		$aliases->setAccessible( true );
		$aliases->setValue( null, array() );
	}
}
