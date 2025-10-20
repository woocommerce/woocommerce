<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Payments\Integrations;

use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use WC_Gateway_Pay_At_Location;
use WC_Helper_Product;
use WC_Payment_Gateways;
use WC_Shipping_Method;
use WC_Shipping_Rate;
use WC_Unit_Test_Case;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ValidClassName.NotCamelCaps, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName -- this is a test file with multiple test classes.

/**
 * Integration tests for PayAtLocation payment method local pickup requirements
 *
 * @since 10.4.0
 */
class PayAtLocationIntegrationTest extends WC_Unit_Test_Case {


	/**
	 * Previous payment gateways to restore after tests.
	 *
	 * @var $previous_payment_gateways WC_Payment_Gateways Previous payment gateways.
	 */
	public $previous_payment_gateways;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable the Pay at Location gateway.
		update_option(
			'woocommerce_pay-at-location_settings',
			array(
				'enabled'            => 'yes',
				'title'              => 'Pay at location',
				'description'        => 'Pay at our location.',
				'instructions'       => '',
				'enable_for_methods' => array(),
				'enable_for_virtual' => 'yes',
			)
		);
		$this->previous_payment_gateways = WC()->payment_gateways;

		// Reset WC payment gateways to pick up the new settings.
		WC()->payment_gateways = new WC_Payment_Gateways();
	}

	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_pay-at-location_settings' );
		remove_all_filters( 'woocommerce_shipping_methods' );
		WC()->payment_gateways = $this->previous_payment_gateways;
		parent::tearDown();
	}

	/**
	 * Create a test shipping method that supports local-pickup feature.
	 *
	 * @return Test_Local_Pickup_Shipping_Method
	 */
	private function create_local_pickup_method() {
		return new Test_Local_Pickup_Shipping_Method();
	}

	/**
	 * Create a test shipping method that does NOT support local-pickup feature.
	 *
	 * @return Test_Regular_Shipping_Method
	 */
	private function create_regular_shipping_method() {
		return new Test_Regular_Shipping_Method();
	}

	/**
	 * Test that LocalPickupUtils correctly identifies custom methods that support local-pickup feature.
	 */
	public function test_local_pickup_utils_with_custom_methods() {
		// Create test methods.
		$pickup_method  = $this->create_local_pickup_method();
		$regular_method = $this->create_regular_shipping_method();

		// Register the methods temporarily with WooCommerce.
		add_filter(
			'woocommerce_shipping_methods',
			function ( $methods ) use ( $pickup_method, $regular_method ) {
				$methods['test_pickup_method']  = $pickup_method;
				$methods['test_regular_method'] = $regular_method;
				return $methods;
			}
		);

		// Test that LocalPickupUtils recognizes our custom pickup method.
		$this->assertTrue( LocalPickupUtils::is_local_pickup_method( 'test_pickup_method' ) );

		// Test that it doesn't recognize our regular method.
		$this->assertFalse( LocalPickupUtils::is_local_pickup_method( 'test_regular_method' ) );

		// Clean up.
		remove_all_filters( 'woocommerce_shipping_methods' );
	}

	/**
	 * Test that concrete test classes can be instantiated and return expected values.
	 */
	public function test_concrete_test_classes() {
		// Create test methods.
		$pickup_method  = $this->create_local_pickup_method();
		$regular_method = $this->create_regular_shipping_method();

		// Test that the methods have the correct IDs.
		$this->assertEquals( 'test_pickup_method', $pickup_method->id );
		$this->assertEquals( 'test_regular_method', $regular_method->id );

		// Test that the methods have the correct titles.
		$this->assertEquals( 'Test Pickup Method', $pickup_method->method_title );
		$this->assertEquals( 'Test Regular Method', $regular_method->method_title );

		// Test that the pickup method supports local-pickup.
		$this->assertTrue( $pickup_method->supports( 'local-pickup' ) );
		$this->assertFalse( $pickup_method->supports( 'products' ) );

		// Test that the regular method does not support local-pickup.
		$this->assertFalse( $regular_method->supports( 'local-pickup' ) );
		$this->assertTrue( $regular_method->supports( 'products' ) );

		// Test that get_rates_for_package returns expected structure.
		$rates = $pickup_method->get_rates_for_package( array() );
		$this->assertIsArray( $rates );
		$this->assertCount( 1, $rates );
		$this->assertEquals( 'test_pickup_rate_1', $rates['test_pickup_rate:1']->id );
		$this->assertEquals( 'test_pickup_method', $rates['test_pickup_rate:1']->method_id );

		$rates = $regular_method->get_rates_for_package( array() );
		$this->assertIsArray( $rates );
		$this->assertCount( 1, $rates );
		$this->assertEquals( 'test_regular_rate_1', $rates['test_regular_rate:1']->id );
		$this->assertEquals( 'test_regular_method', $rates['test_regular_rate:1']->method_id );

		// Clean up.
		remove_all_filters( 'woocommerce_shipping_methods' );
	}

	/**
	 * Test that Pay at Location gateway is available when a shipping method that supports local-pickup is chosen.
	 *
	 * @return void
	 */
	public function test_available_gateways_for_shipping_methods() {
		// Create test methods.
		$pickup_method  = $this->create_local_pickup_method();
		$regular_method = $this->create_regular_shipping_method();
		// Register the methods temporarily with WooCommerce.
		add_filter(
			'woocommerce_shipping_methods',
			function ( $methods ) use ( $pickup_method, $regular_method ) {
				$methods['test_pickup_method']  = $pickup_method;
				$methods['test_regular_method'] = $regular_method;
				return $methods;
			}
		);

		// Make a new cart.
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		// Test with local pickup method.
		WC()->session->set( 'chosen_shipping_methods', array( 'test_pickup_rate:1' ) );
		WC()->cart->calculate_totals();
		WC()->cart->calculate_shipping();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$this->assertArrayHasKey( WC_Gateway_Pay_At_Location::ID, $available_gateways );

		// Test with local pickup method.
		WC()->session->set( 'chosen_shipping_methods', array( 'test_regular_rate:1' ) );
		WC()->cart->calculate_totals();
		WC()->cart->calculate_shipping();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$this->assertArrayNotHasKey( WC_Gateway_Pay_At_Location::ID, $available_gateways );

		// Clean up.
		remove_all_filters( 'woocommerce_shipping_methods' );
	}
}


/**
 * Test shipping method that supports local-pickup feature.
 */
class Test_Local_Pickup_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		$this->id           = 'test_pickup_method';
		$this->method_title = 'Test Pickup Method';
		$this->supports     = array( 'local-pickup' );
	}

	/**
	 * Get rates for package.
	 *
	 * @param array $package Shipping package.
	 * @return array Array of rates.
	 */
	public function get_rates_for_package( $package ) {
		return array(
			'test_pickup_rate:1' => new WC_Shipping_Rate(
				'test_pickup_rate_1',
				'Test Pickup Rate 1',
				0,
				array(),
				'test_pickup_method'
			),
		);
	}
}

/**
 * Test shipping method that does NOT support local-pickup feature.
 */
class Test_Regular_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		$this->id           = 'test_regular_method';
		$this->method_title = 'Test Regular Method';
		$this->supports     = array( 'products' );
	}

	/**
	 * Get rates for package.
	 *
	 * @param array $package Shipping package.
	 * @return array Array of rates.
	 */
	public function get_rates_for_package( $package ) {
		return array(
			'test_regular_rate:1' => new WC_Shipping_Rate(
				'test_regular_rate_1',
				'Test Regular Rate 1',
				0,
				array(),
				'test_regular_method'
			),
		);
	}
}
