<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Payments\Integrations;

use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use WC_Cache_Helper;
use WC_Gateway_PAL;
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

		// Ensure shipping is enabled (previous tests may have disabled it).
		update_option( 'woocommerce_ship_to_countries', 'all' );
		update_option( 'woocommerce_calc_shipping', 'yes' );

		// Clean up any existing filters.
		remove_all_filters( 'woocommerce_shipping_methods' );
		remove_all_filters( 'woocommerce_package_rates' );

		// Clear shipping method cache.
		WC()->shipping()->unregister_shipping_methods();

		// Empty and reset cart.
		WC()->cart->empty_cart();
		WC()->session->set( 'chosen_shipping_methods', array() );

		// Clear shipping package cache from session.
		// The Checkout test leaves cached shipping rates in the session which prevents
		// our test shipping methods from being calculated.
		WC()->session->set( 'shipping_for_package_0', null );

		// Invalidate shipping cache transient to force recalculation.
		\WC_Cache_Helper::invalidate_cache_group( 'shipping' );

		// Save the previous payment gateways.
		$this->previous_payment_gateways = WC()->payment_gateways;

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

		// Force reset WC payment gateways to pick up the new settings.
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways                     = new WC_Payment_Gateways();
		WC()->payment_gateways()->init();
	}

	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_pay-at-location_settings' );

		remove_all_filters( 'woocommerce_shipping_methods' );
		remove_all_filters( 'woocommerce_package_rates' );

		// Clear shipping method cache.
		WC()->shipping()->unregister_shipping_methods();

		// Restore previous payment gateways.
		WC()->payment_gateways = $this->previous_payment_gateways;
		parent::tearDown();
	}

	/**
	 * Test that Pay at Location gateway is available when a shipping method that supports local-pickup is chosen.
	 *
	 * @return void
	 */
	public function test_available_gateways_for_shipping_methods() {
		// Create test methods.
		$pickup_method  = new Test_Local_Pickup_Shipping_Method();
		$regular_method = new Test_Regular_Shipping_Method();

		// Register the methods temporarily with WooCommerce.
		add_filter(
			'woocommerce_shipping_methods',
			function ( $methods ) use ( $pickup_method, $regular_method ) {
				$methods['test_pickup_method']  = $pickup_method;
				$methods['test_regular_method'] = $regular_method;
				return $methods;
			}
		);
		WC()->shipping()->load_shipping_methods();
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		// Make a new cart.
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		// Test with local pickup method.
		WC()->session->set( 'chosen_shipping_methods', array( 'test_pickup_rate:1' ) );
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		$this->assertArrayHasKey(
			WC_Gateway_PAL::ID,
			$available_gateways,
			'Pay at Location should be available with local pickup'
		);

		// Test with regular shipping method.
		// Some previous test may have made products virtual, so ensure we have a physical product.
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id() );

		// Re-add the package_rates filter in case it was removed by empty_cart() or other WooCommerce internals.
		add_filter(
			'woocommerce_package_rates',
			function ( $rates, $package ) use ( $pickup_method, $regular_method ) {
				$pickup_rates  = $pickup_method->get_rates_for_package( $package );
				$regular_rates = $regular_method->get_rates_for_package( $package );
				return array_merge( $rates, $pickup_rates, $regular_rates );
			},
			10,
			2
		);

		// Clear the shipping cache and cart shipping methods to force recalculation.
		WC()->session->set( 'shipping_for_package_0', null );
		WC()->cart->shipping_methods = array();
		WC()->session->set( 'chosen_shipping_methods', array( 'test_regular_rate:1' ) );
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$this->assertArrayNotHasKey(
			WC_Gateway_PAL::ID,
			$available_gateways,
			'Pay at Location should NOT be available with regular shipping'
		);

		// Clean up.
		remove_all_filters( 'woocommerce_shipping_methods' );
		remove_all_filters( 'woocommerce_package_rates' );
		delete_option( 'woocommerce_flat_rate_settings' );
		delete_option( 'woocommerce_flat_rate' );
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
