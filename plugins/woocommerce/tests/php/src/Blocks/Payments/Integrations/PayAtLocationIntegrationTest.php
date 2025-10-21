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
		// Add a flat rate shipping method to ensure wc_get_shipping_method_count() > 0.
		// Without this, cart->needs_shipping() always returns false in CI.
		// We use the same approach as the Checkout test (FixtureData).
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => 10,
		);
		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

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
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		// Debug: Verify first test state
		$first_test_methods = WC()->cart->get_shipping_methods();
		$first_test_ids = array_map(
			function( $method ) {
				return $method && is_callable( array( $method, 'get_method_id' ) ) ? $method->get_method_id() : 'unknown';
			},
			$first_test_methods
		);

		// Debug: Log first test state (will print even on success)
		error_log( 'FIRST TEST STATE: Methods: ' . implode( ', ', $first_test_ids ) . ' | Needs shipping: ' . ( WC()->cart->needs_shipping() ? 'YES' : 'NO' ) . ' | Package rates filter: ' . ( has_filter( 'woocommerce_package_rates' ) !== false ? 'YES' : 'NO' ) . ' | wc_shipping_enabled: ' . ( wc_shipping_enabled() ? 'YES' : 'NO' ) . ' | wc_get_shipping_method_count: ' . wc_get_shipping_method_count( true ) );

		$this->assertArrayHasKey(
			WC_Gateway_Pay_At_Location::ID,
			$available_gateways,
			'First test: Pay at Location SHOULD be available with pickup. Methods: ' . implode( ', ', $first_test_ids ) . ' | Needs shipping: ' . ( WC()->cart->needs_shipping() ? 'YES' : 'NO' )
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

		// Debug: Capture comprehensive cart and shipping state for failure analysis
		$shipping_methods = WC()->cart->get_shipping_methods();
		$method_ids       = array_map(
			function( $method ) {
				return $method && is_callable( array( $method, 'get_method_id' ) ) ? $method->get_method_id() : 'unknown';
			},
			$shipping_methods
		);

		// Get cart contents details
		$cart_items = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product      = $cart_item['data'];
			$cart_items[] = array(
				'product_id'   => $product->get_id(),
				'is_virtual'   => $product->is_virtual(),
				'needs_shipping' => $product->needs_shipping(),
			);
		}

		// Get shipping packages to see what WooCommerce thinks should ship
		$packages = WC()->shipping()->get_packages();
		$package_info = array();
		foreach ( $packages as $package ) {
			$package_info[] = array(
				'rates' => array_keys( $package['rates'] ?? array() ),
			);
		}

		$debug_info = array(
			'cart_has_items'        => ! empty( WC()->cart->get_cart() ),
			'cart_needs_shipping'   => WC()->cart->needs_shipping(),
			'cart_item_count'       => WC()->cart->get_cart_contents_count(),
			'cart_items'            => $cart_items,
			'shipping_methods'      => $method_ids,
			'shipping_packages'     => $package_info,
			'chosen_methods'        => WC()->session->get( 'chosen_shipping_methods' ),
			'session_shipping_cache' => WC()->session->get( 'shipping_for_package_0' ) !== null ? 'EXISTS' : 'NULL',
			'has_calculated_shipping' => WC()->cart->has_calculated_shipping,
			'is_pickup_check'       => array_map(
				function( $id ) {
					return array(
						'id'         => $id,
						'is_pickup'  => LocalPickupUtils::is_local_pickup_method( $id ),
					);
				},
				$method_ids
			),
			'woocommerce_shipping_methods_filter' => has_filter( 'woocommerce_shipping_methods' ) !== false ? 'HAS_FILTER' : 'NO_FILTER',
			'woocommerce_package_rates_filter' => has_filter( 'woocommerce_package_rates' ) !== false ? 'HAS_FILTER' : 'NO_FILTER',
			'wc_shipping_enabled' => wc_shipping_enabled(),
			'wc_get_shipping_method_count' => wc_get_shipping_method_count( true ),
		);

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$this->assertArrayNotHasKey(
			WC_Gateway_Pay_At_Location::ID,
			$available_gateways,
			'Pay at Location should NOT be available with regular shipping. Debug: ' . wp_json_encode( $debug_info, JSON_PRETTY_PRINT )
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
