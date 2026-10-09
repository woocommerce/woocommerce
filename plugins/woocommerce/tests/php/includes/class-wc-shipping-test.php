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
	 * A destination the store does not ship to cannot be delivered, but it can still be collected,
	 * so the shopper is left with collection rather than with nothing.
	 *
	 * @testdox A destination the store does not ship to leaves collection as the only option.
	 */
	public function test_an_unshippable_destination_leaves_collection_as_the_only_option(): void {
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'GB' ) );

		$rates = $this->rates_offered_to( 'US' );

		$this->assertSame( array( 'local_pickup:1' ), array_keys( $rates ), 'Only what the shopper can collect should be offered.' );
	}

	/**
	 * @testdox A destination the store does ship to is offered delivery as well as collection.
	 */
	public function test_a_shippable_destination_is_offered_delivery_too(): void {
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );

		$rates = $this->rates_offered_to( 'US' );

		$this->assertSame( array( 'flat_rate:1', 'local_pickup:1' ), array_keys( $rates ), 'A destination the store ships to should be offered both.' );
	}

	/**
	 * A package with nowhere named yet cannot be proven unshippable, so the shopper keeps every
	 * option while they are still typing.
	 *
	 * @testdox A package with no destination country is offered delivery as well as collection.
	 */
	public function test_a_package_with_no_destination_country_is_offered_delivery_too(): void {
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'GB' ) );

		$rates = $this->rates_offered_to( '' );

		$this->assertSame( array( 'flat_rate:1', 'local_pickup:1' ), array_keys( $rates ), 'Nothing has been ruled out yet, so nothing should be withheld.' );
	}

	/**
	 * Ask for the rates a package bound for the given country is offered.
	 *
	 * @param string $country Destination country.
	 * @return array Rates keyed by rate id.
	 */
	private function rates_offered_to( string $country ): array {
		$methods = array( new WC_Shipping_Flat_Rate( 1 ), new WC_Shipping_Local_Pickup( 1 ) );
		$hook    = fn () => $methods;

		// Rates are cached in the session against a hash of the package, and the ship-to setting is
		// not part of that hash, so two calls with the same package would otherwise read the first
		// call's answer.
		WC()->session->set( 'shipping_for_package_0', null );

		add_action( 'woocommerce_shipping_methods', $hook );

		try {
			$package = $this->sut->calculate_shipping_for_package(
				array(
					'contents'      => array(),
					'contents_cost' => 10,
					'destination'   => array(
						'country'  => $country,
						'state'    => 'CA',
						'postcode' => '00000',
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_shipping_methods', $hook );
		}

		return $package['rates'];
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
	 * @testdox The shipping packages filter runs on every call unless caching is explicitly enabled.
	 */
	public function test_calculate_shipping_does_not_memoize_packages_filter_by_default(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );

		$packages     = array( $this->get_package_hash_test_package() );
		$filter_calls = 0;
		$filter       = function ( $shipping_packages ) use ( &$filter_calls ) {
			++$filter_calls;
			return $shipping_packages;
		};

		add_filter( 'woocommerce_shipping_packages', $filter );

		$this->sut->calculate_shipping( $packages );
		$this->sut->calculate_shipping( $packages );
		$this->sut->calculate_shipping( $packages );

		$this->assertSame( 3, $filter_calls, 'The public filter should retain its existing timing by default.' );
	}

	/**
	 * @testdox Package calculation exposes the packages completed so far through get_packages().
	 */
	public function test_calculate_shipping_exposes_in_progress_packages(): void {
		$observed_packages = array();
		$packages          = array(
			'first'  => array(
				'package_id' => 'first',
				'rates'      => array(),
			),
			'second' => array(
				'package_id' => 'second',
				'rates'      => array(),
			),
		);
		$this->sut         = $this->getMockBuilder( WC_Shipping::class )
			->onlyMethods( array( 'calculate_shipping_for_package' ) )
			->getMock();
		$this->sut->method( 'calculate_shipping_for_package' )
			->willReturnCallback(
				function ( $package ) use ( &$observed_packages ) {
					$observed_packages[] = $this->sut->get_packages();
					return $package;
				}
			);

		$this->sut->calculate_shipping( $packages );

		$this->assertSame(
			array(
				array(),
				array( 'first' => $packages['first'] ),
			),
			$observed_packages,
			'Callbacks should see only packages completed before the current package.'
		);
	}

	/**
	 * @testdox The shipping packages filter result is memoized when caching is explicitly enabled.
	 */
	public function test_calculate_shipping_memoizes_packages_filter_when_enabled(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );

		$packages     = array( $this->get_package_hash_test_package() );
		$filter_calls = 0;
		$filter       = function ( $shipping_packages ) use ( &$filter_calls ) {
			++$filter_calls;
			$shipping_packages['reorganized'] = true;
			return $shipping_packages;
		};

		add_filter( 'woocommerce_shipping_packages_cache_enabled', '__return_true' );
		add_filter( 'woocommerce_shipping_packages', $filter );

		$first  = $this->sut->calculate_shipping( $packages );
		$second = $this->sut->calculate_shipping( $packages );
		$third  = $this->sut->calculate_shipping( $packages );

		$this->assertSame( 1, $filter_calls, 'The filter should run once for repeated identical calls.' );
		$this->assertArrayHasKey( 'reorganized', $first, 'The first call should return the filtered packages.' );
		$this->assertSame( $first, $second, 'The memoized result should preserve the filter output.' );
		$this->assertSame( $first, $third, 'The memoized result should remain stable.' );

		$packages[0]['destination']['country'] = 'GB';
		$packages[0]['destination']['state']   = '';
		$this->sut->calculate_shipping( $packages );

		$this->assertSame( 2, $filter_calls, 'Material package changes should invalidate the memo.' );

		$this->sut->reset_shipping();
		$this->sut->calculate_shipping( $packages );

		$this->assertSame( 3, $filter_calls, 'Resetting shipping should invalidate the memo.' );
	}

	/**
	 * @testdox An empty shipping packages filter result is memoized when caching is enabled.
	 */
	public function test_calculate_shipping_memoizes_empty_packages_filter_result_when_enabled(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );

		$packages     = array( $this->get_package_hash_test_package() );
		$filter_calls = 0;
		$filter       = function () use ( &$filter_calls ) {
			++$filter_calls;
			return array();
		};

		add_filter( 'woocommerce_shipping_packages_cache_enabled', '__return_true' );
		add_filter( 'woocommerce_shipping_packages', $filter );

		$first  = $this->sut->calculate_shipping( $packages );
		$second = $this->sut->calculate_shipping( $packages );

		$this->assertSame( 1, $filter_calls, 'An empty filtered result should still be cached.' );
		$this->assertSame( array(), $first, 'The first call should return the empty filtered result.' );
		$this->assertSame( $first, $second, 'The empty result should be returned from the memo.' );
	}

	/**
	 * @testdox Changing a package key invalidates the shipping packages filter memo.
	 */
	public function test_calculate_shipping_invalidates_filter_memo_when_package_key_changes(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );

		$package      = $this->get_package_hash_test_package();
		$filter_calls = 0;
		$filter       = function ( $shipping_packages ) use ( &$filter_calls ) {
			++$filter_calls;
			return $shipping_packages;
		};

		add_filter( 'woocommerce_shipping_packages_cache_enabled', '__return_true' );
		add_filter( 'woocommerce_shipping_packages', $filter );

		$this->sut->calculate_shipping( array( 0 => $package ) );
		$result = $this->sut->calculate_shipping( array( 'replacement' => $package ) );

		$this->assertSame( 2, $filter_calls, 'Changing the package key should invalidate the memo.' );
		$this->assertArrayHasKey( 'replacement', $result, 'The returned packages should use the current key.' );
		$this->assertArrayNotHasKey( 0, $result, 'The previous package key should not be returned.' );
	}

	/**
	 * @testdox Changing non-rate package metadata invalidates the shipping packages filter memo.
	 */
	public function test_calculate_shipping_invalidates_filter_memo_when_non_rate_metadata_changes(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );

		$packages     = array( $this->get_package_hash_test_package() );
		$filter_calls = 0;
		$filter       = function ( $shipping_packages ) use ( &$filter_calls ) {
			++$filter_calls;
			return $shipping_packages;
		};

		add_filter( 'woocommerce_shipping_packages_cache_enabled', '__return_true' );
		add_filter( 'woocommerce_shipping_packages', $filter );

		$this->sut->calculate_shipping( $packages );
		$packages[0]['package_name'] = 'Replacement package';
		$this->sut->calculate_shipping( $packages );

		$this->assertSame( 2, $filter_calls, 'Filter-visible metadata should invalidate the memo.' );
	}

	/**
	 * @testdox Shipping debug mode bypasses the shipping packages filter memo.
	 */
	public function test_calculate_shipping_does_not_memoize_in_debug_mode(): void {
		$packages     = array( $this->get_package_hash_test_package() );
		$filter_calls = 0;
		$filter       = function ( $shipping_packages ) use ( &$filter_calls ) {
			++$filter_calls;
			return $shipping_packages;
		};

		add_filter( 'woocommerce_shipping_packages_cache_enabled', '__return_true' );
		add_filter( 'woocommerce_shipping_packages', $filter );

		$this->sut->calculate_shipping( $packages );
		$this->sut->calculate_shipping( $packages );
		$this->sut->calculate_shipping( $packages );

		$this->assertSame( 3, $filter_calls, 'Debug mode should run the filter on every call.' );
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
