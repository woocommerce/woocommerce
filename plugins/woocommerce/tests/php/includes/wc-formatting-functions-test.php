<?php
declare( strict_types = 1 );

/**
 * Formatting functions tests
 *
 * @package WooCommerce\Tests\Formatting.
 */

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Formatting_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Resets the static WC_Admin_Settings errors so they do not leak into other tests.
	 */
	public function tearDown(): void {
		$this->wc_admin_settings_errors_property()->setValue( null, array() );

		parent::tearDown();
	}

	/**
	 * Data provider for test_wc_sanitize_coupon_code.
	 *
	 * @return array[]
	 */
	public function data_provider_test_wc_sanitize_coupon_code(): array {
		return array(
			array( 'DUMMYCOUPON', 'DUMMYCOUPON' ),
			array( 'a&amp;a', 'a&a' ),
			array( "test's", "test's" ),
		);
	}

	/**
	 * Test wc_sanitize_coupon_code() function.
	 *
	 * @dataProvider data_provider_test_wc_sanitize_coupon_code
	 *
	 * @param string $assert Expected result.
	 * @param string $input Input for wc_sanitize_coupon_code().
	 */
	public function test_wc_sanitize_coupon_code( string $assert, string $input ) {
		$this->assertSame( $assert, wc_sanitize_coupon_code( $input ) );
	}

	/**
	 * Data provider for test_wc_format_postcode.
	 *
	 * @return array[]
	 * @see WC_Tests_Formatting_Functions::test_wc_format_postcode for US, GB, BR, JP, NL, LV
	 */
	public function data_provider_test_wc_format_postcode(): array {
		$ie = array(
			array( 'D02 AF30', 'D02AF30', 'IE' ),
		);

		$pt = array(
			array( '1000-205', '1000205', 'PT' ),
		);

		$dk = array(
			array( '1234', '1234', 'DK' ),
			array( 'DK-1234', 'DK-1234', 'DK' ),
			array( 'DK-1234', 'dk-1234', 'DK' ),
		);

		$se = array(
			array( '113 52', '11352', 'SE' ),
		);

		$sk = array(
			array( '811 02', '81102', 'SK' ),
			array( 'SK-811 02', 'SK-81102', 'SK' ),
			array( 'SK-811 02', 'sk-81102', 'SK' ),
		);

		$cz = array(
			array( '115 03', '11503', 'CZ' ),
			array( 'CZ-115 03', 'CZ-11503', 'CZ' ),
			array( 'CZ-115 03', 'cz-11503', 'CZ' ),
		);

		return array_merge( $ie, $pt, $dk, $se, $sk, $cz );
	}

	/**
	 * Test wc_format_postcode() function.
	 *
	 * @dataProvider data_provider_test_wc_format_postcode
	 *
	 * @param string $assert Expected result.
	 * @param string $postcode Postcode input for wc_format_postcode().
	 * @param string $country Country input for wc_format_postcode().
	 */
	public function test_wc_format_postcode( string $assert, string $postcode, string $country ) {
		$this->assertSame( $assert, wc_format_postcode( $postcode, $country ), "Test formatting of $postcode postcodes." );
	}

	/**
	 * Data provider for test_wc_format_option_price_separators.
	 *
	 * @return array[]
	 */
	public function data_provider_wc_format_option_price_separators(): array {
		return array(
			'thousand sep: comma'           => array( 'woocommerce_price_thousand_sep', ',', ',', ',', ',', false ),
			'thousand sep: period'          => array( 'woocommerce_price_thousand_sep', '.', '.', '.', '.', false ),
			'thousand sep: space'           => array( 'woocommerce_price_thousand_sep', ',', ',', ' ', ' ', false ),
			'thousand sep: empty'           => array( 'woocommerce_price_thousand_sep', ',', ',', '', '', false ),
			'thousand sep: nbsp entity'     => array( 'woocommerce_price_thousand_sep', ',', ',', '&nbsp;', '&nbsp;', false ),
			'thousand sep: comma entity'    => array( 'woocommerce_price_thousand_sep', ',', ',', '&#44;', '&#044;', false ),
			'thousand sep: zero digit'      => array( 'woocommerce_price_thousand_sep', ',', ',', '0', ',', true ),
			'thousand sep: single digit'    => array( 'woocommerce_price_thousand_sep', ',', ',', '1', ',', true ),
			'thousand sep: digit+symbol'    => array( 'woocommerce_price_thousand_sep', ',', ',', '1,', ',', true ),
			'thousand sep: digit entity'    => array( 'woocommerce_price_thousand_sep', ',', ',', '&#49;', ',', true ),
			'thousand sep: fullwidth digit' => array( 'woocommerce_price_thousand_sep', ',', ',', '１', ',', true ),
			'decimal sep: period'           => array( 'woocommerce_price_decimal_sep', '.', '.', '.', '.', false ),
			'decimal sep: single digit'     => array( 'woocommerce_price_decimal_sep', '.', '.', '2', '.', true ),
			'decimal sep: arabic digit'     => array( 'woocommerce_price_decimal_sep', '.', '.', '٢', '.', true ),
		);
	}

	/**
	 * @testdox wc_format_option_price_separators should reject values containing digits and return the existing option value.
	 *
	 * @dataProvider data_provider_wc_format_option_price_separators
	 *
	 * @param string $option_id        The option being saved.
	 * @param string $stored_value     The value currently stored in the option.
	 * @param string $pre_filter_value The sanitized value passed through earlier filters.
	 * @param string $raw_value        The raw input being saved.
	 * @param string $expected         The value the filter should return.
	 * @param bool   $expect_rejection Whether the input should be rejected.
	 */
	public function test_wc_format_option_price_separators( string $option_id, string $stored_value, string $pre_filter_value, string $raw_value, string $expected, bool $expect_rejection ): void {
		$option = array(
			'id'      => $option_id,
			'default' => ',',
		);

		update_option( $option_id, $stored_value );

		$errors_before = $this->get_wc_admin_settings_errors();
		$result        = wc_format_option_price_separators( $pre_filter_value, $option, $raw_value );
		$errors_after  = $this->get_wc_admin_settings_errors();

		$this->assertSame( $expected, $result );

		if ( $expect_rejection ) {
			$this->assertCount( count( $errors_before ) + 1, $errors_after, 'An error should be added when a numeric separator is rejected.' );
			$this->assertStringContainsString( 'cannot contain numbers', end( $errors_after ), 'Error message should mention numbers.' );
		} else {
			$this->assertCount( count( $errors_before ), $errors_after, 'No error should be added for valid separators.' );
		}
	}

	/**
	 * @testdox wc_format_option_price_separators should reject a non-string raw value.
	 */
	public function test_wc_format_option_price_separators_rejects_non_string_input(): void {
		$option = array(
			'id'      => 'woocommerce_price_thousand_sep',
			'default' => ',',
		);

		update_option( 'woocommerce_price_thousand_sep', ',' );

		$errors_before = $this->get_wc_admin_settings_errors();
		$result        = wc_format_option_price_separators( ',', $option, array( '1' ) );
		$errors_after  = $this->get_wc_admin_settings_errors();

		$this->assertSame( ',', $result, 'An array raw value should be rejected and the stored value returned.' );
		$this->assertCount( count( $errors_before ) + 1, $errors_after, 'An error should be added when the raw value is not a string.' );
	}

	/**
	 * @testdox wc_format_option_price_separators should fall back to the option default when the stored value is invalid too.
	 */
	public function test_wc_format_option_price_separators_falls_back_to_default(): void {
		$option = array(
			'id'      => 'woocommerce_price_thousand_sep',
			'default' => ',',
		);

		update_option( 'woocommerce_price_thousand_sep', '1' );

		$result = wc_format_option_price_separators( '2', $option, '2' );

		$this->assertSame( ',', $result, 'A stored value that is itself invalid should not be re-saved.' );
	}

	/**
	 * @testdox wc_format_option_price_separators should leave the value alone when the field was not submitted.
	 */
	public function test_wc_format_option_price_separators_skips_missing_field(): void {
		$option = array(
			'id'      => 'woocommerce_price_thousand_sep',
			'default' => ',',
		);

		$errors_before = $this->get_wc_admin_settings_errors();
		$result        = wc_format_option_price_separators( null, $option, null );
		$errors_after  = $this->get_wc_admin_settings_errors();

		$this->assertNull( $result, 'A null raw value means the field was not submitted, so nothing should be saved.' );
		$this->assertCount( count( $errors_before ), $errors_after, 'No error should be added for a field that was not submitted.' );
	}

	/**
	 * Reads the private static $errors array from WC_Admin_Settings via reflection.
	 *
	 * @return array
	 */
	private function get_wc_admin_settings_errors(): array {
		return $this->wc_admin_settings_errors_property()->getValue();
	}

	/**
	 * Returns the private static $errors property of WC_Admin_Settings.
	 *
	 * @return ReflectionProperty
	 */
	private function wc_admin_settings_errors_property(): ReflectionProperty {
		$reflection = new \ReflectionClass( WC_Admin_Settings::class );
		$property   = $reflection->getProperty( 'errors' );
		$property->setAccessible( true );
		return $property;
	}

	/**
	 * Test wc_is_stock_amount_integer function.
	 *
	 * @testdox wc_is_stock_amount_integer should return true when stock amounts are integers and false when they are floats.
	 */
	public function test_wc_is_stock_amount_integer() {
		// Remove all filters from woocommerce_stock_amount.
		remove_all_filters( 'woocommerce_stock_amount' );

		// Test with floatval applied to the filter.
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$this->assertFalse( wc_is_stock_amount_integer(), 'Should return false when floatval is applied to stock amount filter.' );

		// Remove floatval filter and add intval filter.
		remove_all_filters( 'woocommerce_stock_amount' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
		$this->assertTrue( wc_is_stock_amount_integer(), 'Should return true when intval is applied to stock amount filter.' );
	}
}
