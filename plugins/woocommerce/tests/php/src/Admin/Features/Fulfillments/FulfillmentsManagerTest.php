<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\ShippingProviderMock;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentsManagerTest extends \WC_Unit_Test_Case {

	/**
	 * @var FulfillmentsManager
	 */
	private FulfillmentsManager $manager;

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->manager = wc_get_container()->get( FulfillmentsManager::class );
		$this->manager->register();
	}

	/**
	 * Test hooks.
	 */
	public function test_hooks() {
		$this->assertNotFalse( has_filter( 'woocommerce_fulfillment_translate_meta_key', array( $this->manager, 'translate_fulfillment_meta_key' ) ) );
	}

	/**
	 * Test the translate_fulfillment_meta_key method.
	 */
	public function test_translate_fulfillment_meta_key() {
		// Test with a known meta key.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'fulfillment_status' );
		$this->assertEquals( __( 'Fulfillment Status', 'woocommerce' ), $translated_key );

		// Test with an unknown meta key.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'unknown_meta_key' );
		$this->assertEquals( 'unknown_meta_key', $translated_key );
	}

	/**
	 * Test extending the translation of a fulfillment meta key.
	 */
	public function test_extend_translate_fulfillment_meta_key() {
		// Extend the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		// Test the extended translation.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the filter for translating fulfillment meta keys works correctly.
	 */
	public function test_translate_fulfillment_meta_key_with_filter() {

		// Add a filter to modify the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		/**
		 * Filter to translate fulfillment meta keys.
		 *
		 * @since 10.1.0
		 */
		$translated_key = apply_filters( 'woocommerce_fulfillment_translate_meta_key', 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the initial shipping providers are loaded correctly.
	 */
	public function test_get_initial_shipping_providers() {
		/**
		 * Filter to get initial shipping providers
		 *
		 * @since 10.1.0
		 */
		$shipping_providers = apply_filters( 'woocommerce_fulfillment_shipping_providers', array() );
		// Check if the shipping providers are loaded correctly.
		$this->assertIsArray( $shipping_providers );
		$this->assertNotEmpty( $shipping_providers );
	}

	/**
	 * Test that the initial shipping providers can be extended.
	 */
	public function test_extend_initial_shipping_providers() {
		// Extend the shipping providers.
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers['custom_provider'] = array(
					'label' => __( 'Custom Provider', 'woocommerce' ),
					'icon'  => 'custom-icon',
					'value' => 'custom_provider',
				);
				return $providers;
			}
		);

		/**
		 * Filter to get initial shipping providers.
		 *
		 * @since 10.1.0
		 */
		$shipping_providers = apply_filters( 'woocommerce_fulfillment_shipping_providers', array() );

		// Check if the custom provider is included.
		$this->assertArrayHasKey( 'custom_provider', $shipping_providers );
		$this->assertIsArray( $shipping_providers['custom_provider'] );
		$this->assertArrayHasKey( 'label', $shipping_providers['custom_provider'] );
		$this->assertEquals( __( 'Custom Provider', 'woocommerce' ), $shipping_providers['custom_provider']['label'] );
	}

	/**
	 * Test that the fulfillment status hooks are initialized correctly.
	 */
	public function test_init_fulfillment_status_hooks() {
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_create', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_update', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_delete', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
	}

	/**
	 * Test that the fulfillment status is updated on fulfillment creation.
	 */
	public function test_update_order_fulfillment_status_on_fulfillment_updates() {
		$fulfillments = array();
		$product      = \WC_Helper_Product::create_simple_product();
		$order        = OrderHelper::create_order( get_current_user_id(), $product );
		$this->assertEmpty( $order->get_meta( '_fulfillment_status' ) );

		$create_count_before = did_action( 'woocommerce_fulfillment_after_create' );
		$fulfillments[]      = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $product->get_id(),
						'qty'     => 1,
					),
				),
			)
		);
		$this->assertGreaterThan( $create_count_before, did_action( 'woocommerce_fulfillment_after_create' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'unfulfilled', $order->get_meta( '_fulfillment_status', true ) );

		$update_count_before = did_action( 'woocommerce_fulfillment_after_update' );
		$fulfillments[0]->set_status( 'fulfilled' );
		$fulfillments[0]->save();

		$this->assertGreaterThan( $update_count_before, did_action( 'woocommerce_fulfillment_after_update' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'partially_fulfilled', $order->get_meta( '_fulfillment_status' ) );

		$delete_count_before = did_action( 'woocommerce_fulfillment_after_delete' );
		$fulfillments[0]->delete();
		$this->assertGreaterThan( $delete_count_before, did_action( 'woocommerce_fulfillment_after_delete' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( '', $order->get_meta( '_fulfillment_status' ) );
	}

	/**
	 * Test that the tracking number can be parsed correctly.
	 */
	public function test_try_parse_tracking_number_nominal() {
		$tracking_number = '1234567890';

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container     = wc_get_container();
		$mock_provider = $this->getMockBuilder( ShippingProviderMock::class )->onlyMethods( array( 'try_parse_tracking_number' ) )->getMock();
		$container->replace( ShippingProviderMock::class, $mock_provider );
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers = array(
					'custom_provider' => ShippingProviderMock::class,
				);
				return $providers;
			}
		);

		$mock_provider->expects( $this->once() )
			->method( 'try_parse_tracking_number' )
			->willReturn(
				array(
					'url' => 'https://example.com/track?number=' . $tracking_number,
				)
			);

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( $tracking_number, $parsed_number['tracking_number'] );
		$this->assertEquals( 'mock_shipping_provider', $parsed_number['shipping_provider'] );
		$this->assertEquals( 'https://example.com/track?number=' . $tracking_number, $parsed_number['tracking_url'] );
	}

	/**
	 * Test tracking number parsing without any matches.
	 */
	public function test_try_parse_tracking_number_no_match() {
		$tracking_number = '1234567890';

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container     = wc_get_container();
		$mock_provider = $this->getMockBuilder( ShippingProviderMock::class )->onlyMethods( array( 'try_parse_tracking_number' ) )->getMock();
		$container->replace( ShippingProviderMock::class, $mock_provider );
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers = array(
					'custom_provider' => ShippingProviderMock::class,
				);
				return $providers;
			}
		);

		$mock_provider->expects( $this->once() )
			->method( 'try_parse_tracking_number' )
			->willReturn( null );

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( array(), $parsed_number );
	}

	/**
	 * @testdox Should register order deletion hooks.
	 */
	public function test_order_deletion_hooks_registered(): void {
		$this->assertNotFalse( has_action( 'woocommerce_before_delete_order', array( $this->manager, 'delete_order_fulfillments' ) ) );
		$this->assertNotFalse( has_action( 'before_delete_post', array( $this->manager, 'delete_order_fulfillments' ) ) );
	}

	/**
	 * @testdox Should delete fulfillments when an order is permanently deleted.
	 */
	public function test_delete_order_fulfillments_on_order_deletion(): void {
		global $wpdb;

		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => WC_Order::class,
				'entity_id'   => $order->get_id(),
				'status'      => 'unfulfilled',
			),
			array(
				'_items' => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
			)
		);

		$order_id = $order->get_id();

		$fulfillments_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %s",
				WC_Order::class,
				$order_id
			)
		);
		$this->assertSame( '1', $fulfillments_before, 'Fulfillment should exist before deletion' );

		$order->delete( true );

		$fulfillments_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %d",
				WC_Order::class,
				$order_id
			)
		);
		$this->assertSame( '0', $fulfillments_after, 'Fulfillments should be deleted after order deletion' );
	}

	/**
	 * Test tracking number parsing without any shipping providers.
	 */
	public function test_try_parse_tracking_number_no_providers() {
		$tracking_number = '1234567890';

		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers = array();
				return $providers;
			}
		);

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( array(), $parsed_number );
	}
}
