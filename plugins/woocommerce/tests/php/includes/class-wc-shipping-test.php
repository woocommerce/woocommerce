<?php

declare(strict_types=1);

/**
 * Tests for WC_Shipping class.
 */
class WC_Shipping_Test extends WC_Unit_Test_Case {

	/**
	 * @var WC_Shipping The system under test.
	 */
	private $sut;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Shipping();

		update_option( 'woocommerce_shipping_debug_mode', 'yes' );
	}

	/**
	 * Restore options.
	 */
	public function tearDown(): void {
		parent::tearDown();

		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		update_option( 'woocommerce_shipping_hide_rates_when_free', 'no' );
	}

	/**
	 * @testdox shipping methods are hidden based on `woocommerce_shipping_hide_rates_when_free` option.
	 *
	 * @dataProvider provide_test_calculate_shipping_for_hide_rates_when_free
	 *
	 * @param string $option_value Option value for woocommerce_shipping_hide_rates_when_free.
	 * @param array  $shipping_methods Available shipping methods.
	 * @param array  $expected_rates Expected rates.
	 */
	public function test_calculate_shipping_for_hide_rates_when_free( string $option_value, array $shipping_methods, array $expected_rates ) {
		update_option( 'woocommerce_shipping_hide_rates_when_free', $option_value );

		$shipping_methods_hook = fn () => $shipping_methods;

		add_action( 'woocommerce_shipping_methods', $shipping_methods_hook );

		$result = $this->sut->calculate_shipping_for_package(
			array(
				'contents'      => array(),
				'contents_cost' => 10,
				'destination'   => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '00000',
				),
			),
		);

		foreach ( $expected_rates as $rate ) {
			$this->assertArrayHasKey( $rate, $result['rates'] );
		}

		remove_action( 'woocommerce_shipping_methods', $shipping_methods_hook );
	}

	/**
	 * @testdox package rates filter doesn't cause errors when accessing non-existent rates with arithmetic operations
	 *
	 * @dataProvider provide_test_package_rates_filter_error_handling
	 *
	 * @param callable $filter_callback The filter callback to test.
	 * @param string   $description Description of the test case.
	 */
	public function test_package_rates_filter_error_handling( callable $filter_callback, string $description ) {
		$shipping_methods_hook = function () {
			$custom_pickup = new class() extends WC_Shipping_Method {
				/**
				 * Custom pickup shipping method.
				 * @var string
				 */
				public $id = 'custom_pickup';
				/**
				 * Array of features this rate supports.
				 * @var array
				 */
				public $supports = array( 'local-pickup' );

				/**
				 * Get rates for package.
				 * @param array $package package.
				 *
				 * @return WC_Shipping_Rate[]
				 */
				public function get_rates_for_package( $package ) {
					return array( 'pickup_location:0' => new WC_Shipping_Rate( 'pickup_location:0', 'Pickup Location', '5', array(), 'custom_pickup' ) );
				}
			};
			return array( $custom_pickup );
		};

		add_action( 'woocommerce_shipping_methods', $shipping_methods_hook );
		add_filter( 'woocommerce_package_rates', $filter_callback, 10, 2 );

		// This should not throw any errors or warnings.
		$result = $this->sut->calculate_shipping_for_package(
			array(
				'contents'      => array(),
				'contents_cost' => 10,
				'destination'   => array(
					'country'  => 'US',
					'state'    => 'CA',
					'postcode' => '00000',
				),
			),
		);

		// Verify that rates are still returned.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'rates', $result );

		remove_filter( 'woocommerce_package_rates', $filter_callback, 10 );
		remove_action( 'woocommerce_shipping_methods', $shipping_methods_hook );
	}

	/**
	 * Data provider for test_package_rates_filter_error_handling.
	 *
	 * @return array[]
	 */
	public function provide_test_package_rates_filter_error_handling(): array {
		return array(
			'accessing non-existent rate with arithmetic' => array(
				function ( $rates, $package ) {
					// This should not cause an error even if pickup_location:0 doesn't exist in rates.
					if ( isset( $package['rates']['pickup_location:0'] ) ) {
						$new_value = 1 + $package['rates']['pickup_location:0']->cost;
					}
					return $rates;
				},
				'Filter safely checks if rate exists before arithmetic operations',
			),
			'accessing rate cost with empty string'       => array(
				function ( $rates ) {
					// Test that empty cost values don't break arithmetic.
					foreach ( $rates as $rate_id => $rate ) {
						if ( '' === $rate->cost ) {
							$rate->cost = '0';
						}
					}
					return $rates;
				},
				'Filter handles empty cost values in rates',
			),
			'unsafe access that could cause errors'       => array(
				function ( $rates ) {
					// This is the problematic code that the fix should prevent errors for.
					if ( isset( $rates['pickup_location:0'] ) ) {
						$new_value = 1 + $rates['pickup_location:0']->cost;
					}
					return $rates;
				},
				'Filter handles arithmetic operations on rate costs safely',
			),
		);
	}

	/**
	 * Data provider for test_calculate_shipping_for_hide_rates_when_free.
	 *
	 * @return array[]
	 */
	public function provide_test_calculate_shipping_for_hide_rates_when_free(): array {
		$flat_rate     = new WC_Shipping_Flat_Rate( 1 );
		$free_shipping = new WC_Shipping_Free_Shipping( 1 );
		$local_pickup  = new WC_Shipping_Local_Pickup( 1 );

		// phpcs:disable Squiz.Commenting
		$custom_pickup = new class() extends WC_Shipping_Method {
			public $id       = 'custom_pickup';
			public $supports = array( 'local-pickup' );
			public function get_rates_for_package( $package ) {
				return array( 'custom_pickup:1' => new WC_Shipping_Rate( 'custom_pickup:1', 'Pickup Location', 5, array(), 'custom_pickup' ) );
			}
		};
		// phpcs:enable Squiz.Commenting

		return array(
			'hide disabled - show all rates'       => array(
				'no',
				array( $flat_rate, $free_shipping, $local_pickup, $custom_pickup ),
				array( 'flat_rate:1', 'free_shipping:1', 'local_pickup:1', 'custom_pickup:1' ),
			),
			'hide enabled - with free shipping'    => array(
				'yes',
				array( $flat_rate, $free_shipping, $local_pickup, $custom_pickup ),
				array( 'free_shipping:1', 'local_pickup:1', 'custom_pickup:1' ),
			),
			'hide enabled - without free shipping' => array(
				'yes',
				array( $flat_rate, $local_pickup, $custom_pickup ),
				array( 'flat_rate:1', 'local_pickup:1', 'custom_pickup:1' ),
			),
		);
	}
}
