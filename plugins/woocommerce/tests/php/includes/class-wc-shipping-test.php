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
	 * @testdox ignored package fields do not invalidate cached shipping rates
	 *
	 * @dataProvider provide_ignored_package_hash_fields
	 *
	 * @param string $field Package field to mutate.
	 * @param mixed  $value Mutated field value.
	 */
	public function test_calculate_shipping_for_package_ignores_non_rate_fields_in_package_hash( string $field, $value ) {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		WC()->session->__unset( 'shipping_for_package_0' );

		$filter_calls = 0;
		$filter       = $this->get_package_rates_counter( $filter_calls );
		$package      = $this->get_package_hash_test_package();

		add_filter( 'woocommerce_package_rates', $filter, 10 );

		$this->sut->calculate_shipping_for_package( $package );

		$package[ $field ] = $value;

		$this->sut->calculate_shipping_for_package( $package );

		$this->assertSame( 1, $filter_calls );

		remove_filter( 'woocommerce_package_rates', $filter, 10 );
	}

	/**
	 * @testdox material package fields invalidate cached shipping rates
	 *
	 * @dataProvider provide_material_package_hash_fields
	 *
	 * @param callable $mutate_package Package mutation callback.
	 */
	public function test_calculate_shipping_for_package_invalidates_cache_for_material_package_changes( callable $mutate_package ) {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		WC()->session->__unset( 'shipping_for_package_0' );

		$filter_calls = 0;
		$filter       = $this->get_package_rates_counter( $filter_calls );
		$package      = $this->get_package_hash_test_package();

		add_filter( 'woocommerce_package_rates', $filter, 10 );

		$this->sut->calculate_shipping_for_package( $package );

		$mutate_package( $package );

		$this->sut->calculate_shipping_for_package( $package );

		$this->assertSame( 2, $filter_calls );

		remove_filter( 'woocommerce_package_rates', $filter, 10 );
	}

	/**
	 * @testdox unknown package fields invalidate cached shipping rates by default
	 */
	public function test_calculate_shipping_for_package_invalidates_cache_for_unknown_package_fields_by_default() {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		WC()->session->__unset( 'shipping_for_package_0' );

		$filter_calls = 0;
		$filter       = $this->get_package_rates_counter( $filter_calls );
		$package      = $this->get_package_hash_test_package();

		add_filter( 'woocommerce_package_rates', $filter, 10 );

		$this->sut->calculate_shipping_for_package( $package );

		$package['custom_extension_key'] = 'changed';

		$this->sut->calculate_shipping_for_package( $package );

		$this->assertSame( 2, $filter_calls );

		remove_filter( 'woocommerce_package_rates', $filter, 10 );
	}

	/**
	 * @testdox extensions can ignore package fields for the shipping-rate cache hash
	 */
	public function test_calculate_shipping_for_package_allows_extensions_to_ignore_package_hash_fields() {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		WC()->session->__unset( 'shipping_for_package_0' );

		$filter_calls          = 0;
		$filter                = $this->get_package_rates_counter( $filter_calls );
		$ignored_fields_filter = function ( array $ignored_fields ): array {
			$ignored_fields[] = 'custom_extension_key';
			return $ignored_fields;
		};
		$package               = $this->get_package_hash_test_package();

		add_filter( 'woocommerce_package_rates', $filter, 10 );
		add_filter( 'woocommerce_shipping_package_hash_ignored_fields', $ignored_fields_filter );

		$this->sut->calculate_shipping_for_package( $package );

		$package['custom_extension_key'] = 'changed';

		$this->sut->calculate_shipping_for_package( $package );

		$this->assertSame( 1, $filter_calls );

		remove_filter( 'woocommerce_shipping_package_hash_ignored_fields', $ignored_fields_filter );
		remove_filter( 'woocommerce_package_rates', $filter, 10 );
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
	 * Data provider for ignored package hash fields.
	 *
	 * @return array[]
	 */
	public function provide_ignored_package_hash_fields(): array {
		return array(
			'subtotal'      => array( 'subtotal', 20 ),
			'total'         => array( 'total', 20 ),
			'package_id'    => array( 'package_id', 'package-1-changed' ),
			'package_name'  => array( 'package_name', 'Package 1 Changed' ),
			'rates'         => array( 'rates', array( 'prefilled_rate' => new WC_Shipping_Rate( 'prefilled_rate', 'Prefilled Rate', '7.00' ) ) ),
			'package_index' => array( 'package_index', 2 ),
		);
	}

	/**
	 * Data provider for material package hash fields.
	 *
	 * @return array[]
	 */
	public function provide_material_package_hash_fields(): array {
		return array(
			'destination postcode' => array(
				function ( array &$package ): void {
					$package['destination']['postcode'] = '11111';
				},
			),
			'contents cost'        => array(
				function ( array &$package ): void {
					$package['contents_cost'] = 20;
				},
			),
			'cart contents'        => array(
				function ( array &$package ): void {
					$package['contents']['test_item']['quantity'] = 2;
				},
			),
		);
	}

	/**
	 * Get a package rates filter that counts recalculations.
	 *
	 * @param int $filter_calls Filter call count.
	 * @return callable
	 */
	private function get_package_rates_counter( int &$filter_calls ): callable {
		return function ( $rates ) use ( &$filter_calls ) {
			++$filter_calls;
			return $rates;
		};
	}

	/**
	 * Get a package for shipping hash tests.
	 *
	 * @return array
	 */
	private function get_package_hash_test_package(): array {
		return array(
			'contents'      => array(
				'test_item' => array(
					'quantity'          => 1,
					'line_subtotal'     => 10,
					'line_subtotal_tax' => 0,
					'line_total'        => 10,
					'line_tax'          => 0,
					'data'              => new WC_Product_Simple(),
				),
			),
			'contents_cost' => 10,
			'destination'   => array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '00000',
			),
			'package_id'    => 'package-1',
			'package_name'  => 'Package 1',
			'package_index' => 1,
			'subtotal'      => 10,
			'total'         => 10,
			'rates'         => array(),
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
