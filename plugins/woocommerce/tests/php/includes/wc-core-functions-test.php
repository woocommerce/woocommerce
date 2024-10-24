<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * Core functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

/**
 * Class WC_Core_Functions_Test
 */
class WC_Core_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test wc_ascii_uasort_comparison() function.
	 */
	public function test_wc_ascii_uasort_comparison() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		uasort( $sorted_values, 'wc_ascii_uasort_comparison' );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * Test wc_asort_by_locale() function.
	 */
	public function test_wc_asort_by_locale() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		wc_asort_by_locale( $sorted_values );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * @testdDox wc_get_rounding_precision returns the value of wc_get_price_decimals()+2, but with a minimum of WC_ROUNDING_PRECISION (6)
	 *
	 * @testWith [0, 6]
	 *           [2, 6]
	 *           [4, 6]
	 *           [5, 7]
	 *           [6, 8]
	 *           [7, 9]
	 *
	 * @param int $decimals Value returned by wc_get_price_decimals().
	 * @param int $expected Expected value returned by the function.
	 */
	public function test_wc_get_rounding_precision( $decimals, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_get_rounding_precision();
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testDox wc_add_number_precision moves the decimal point to the right as many places as wc_get_price_decimals() says, and (optionally) properly rounds the result.
	 *
	 * @testWith [2, 1.23456789, false, 123.456789]
	 *           [2, 1.23456789, true, 123.4568]
	 *           [4, 1.23456789, false, 12345.6789]
	 *           [4, 1.23456789, true, 12345.68]
	 *           [5, 1.23456789, false, 123456.789]
	 *           [5, 1.23456789, true, 123456.79]
	 *           [2, null, false, 0]
	 *           [2, null, true, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param bool  $round Whether to round the result or not.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_add_number_precision( $decimals, $value, $round, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_add_number_precision( $value, $round );
		$this->assertFloatEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testWith [2, 123.4567, 1.234567]
	 *           [5, 123.4567, 0.001234567]
	 *           [2, null, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_remove_number_precision( $decimals, $value, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function () use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_remove_number_precision( $value );
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * Test wc_help_tip() function.
	 */
	public function test_wc_help_tip_strips_html() {
		$expected = '<span class="woocommerce-help-tip" tabindex="0" aria-label="Strong text regular text" data-tip="&lt;strong&gt;Strong text&lt;/strong&gt; regular text"></span>';
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', false ) );
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', true ) );
	}

	/**
	 * Test wc_get_customer_default_location() function.
	 */
	public function test_wc_get_customer_default_location() {
		/**
		 * Test with none of the options set. In this case the location should be empty.
		 *
		 * woocommerce_default_country is set to 'US:CA' by default unless it was defined during setup.
		 */
		delete_option( 'woocommerce_default_customer_address' );
		delete_option( 'woocommerce_default_country' );
		delete_option( 'woocommerce_allowed_countries' );
		delete_option( 'woocommerce_specific_allowed_countries' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'US', $result['country'] );
		$this->assertEquals( 'CA', $result['state'] );

		// Test with a default address defined during setup. This country has states.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( 'LS', $result['state'] );

		// Test with a default address defined during setup. This country has no states.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'GB' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'GB', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with default address, but specific countries set. Address is allowed.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'DE', 'AT', 'CH' ) );
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( 'LS', $result['state'] );

		// Test with default address, but specific countries set. Address is not allowed.
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'DE:LS' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		$result = wc_get_customer_default_location();
		$this->assertEquals( '', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with no default address.
		update_option( 'woocommerce_default_customer_address', '' );
		update_option( 'woocommerce_default_country', 'GB' );
		$result = wc_get_customer_default_location();
		$this->assertEquals( '', $result['country'] );
		$this->assertEquals( '', $result['state'] );

		// Test with geolocation.
		update_option( 'woocommerce_default_customer_address', 'geolocation' );
		update_option( 'woocommerce_default_country', 'GB' );
		delete_option( 'woocommerce_allowed_countries' );
		delete_option( 'woocommerce_specific_allowed_countries' );
		add_filter(
			'woocommerce_geolocate_ip',
			function () {
				return 'FR';
			},
			10
		);
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'FR', $result['country'] );
		$this->assertEquals( '', $result['state'] );
		remove_all_filters( 'woocommerce_geolocate_ip' );

		// Test with geolocation but geolocated country is not allowed.
		update_option( 'woocommerce_default_customer_address', 'geolocation' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		add_filter(
			'woocommerce_geolocate_ip',
			function () {
				return 'FR';
			},
			10
		);
		$result = wc_get_customer_default_location();
		$this->assertEquals( 'GB', $result['country'] );
		$this->assertEquals( '', $result['state'] );
		remove_all_filters( 'woocommerce_geolocate_ip' );
	}
}
