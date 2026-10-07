<?php
/**
 * AbilityFieldsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFields;

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
	 * @testdox Should return the registered field values under extensions, and leave out an object whose fields read null.
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
	}
}
