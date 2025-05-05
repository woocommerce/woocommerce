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
