<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Settings_General.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Settings_General_Test extends WC_Unit_Test_Case {

	/**
	 * Option names used in tests, cleaned up in tearDown().
	 *
	 * @var string[]
	 */
	private array $option_names_to_clean = array();

	/**
	 * Clean up options after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->option_names_to_clean as $option_name ) {
			delete_option( $option_name );
		}
		$this->option_names_to_clean = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should allow saving when thousand and decimal separators are different.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/46047
	 */
	public function test_save_allows_different_separators(): void {
		$this->option_names_to_clean[] = 'woocommerce_price_thousand_sep';
		$this->option_names_to_clean[] = 'woocommerce_price_decimal_sep';

		// Set initial values.
		update_option( 'woocommerce_price_thousand_sep', '.' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		// Simulate form submission with different (but swapped) separators.
		$_POST['woocommerce_price_thousand_sep'] = ',';
		$_POST['woocommerce_price_decimal_sep']  = '.';

		$settings = new WC_Settings_General();
		$settings->save();

		$this->assertEquals( ',', get_option( 'woocommerce_price_thousand_sep' ), 'Thousand separator should be updated to comma' );
		$this->assertEquals( '.', get_option( 'woocommerce_price_decimal_sep' ), 'Decimal separator should be updated to period' );

		unset( $_POST['woocommerce_price_thousand_sep'], $_POST['woocommerce_price_decimal_sep'] );
	}

	/**
	 * @testdox Should revert separators when merchant sets them to the same character.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/46047
	 */
	public function test_save_reverts_same_separators(): void {
		$this->option_names_to_clean[] = 'woocommerce_price_thousand_sep';
		$this->option_names_to_clean[] = 'woocommerce_price_decimal_sep';

		// Set initial values.
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		// Simulate form submission with the SAME separator.
		$_POST['woocommerce_price_thousand_sep'] = '.';
		$_POST['woocommerce_price_decimal_sep']  = '.';

		$settings = new WC_Settings_General();
		$settings->save();

		// Values should be reverted to the previous ones.
		$this->assertEquals( ',', get_option( 'woocommerce_price_thousand_sep' ), 'Thousand separator should be reverted to previous value (comma)' );
		$this->assertEquals( '.', get_option( 'woocommerce_price_decimal_sep' ), 'Decimal separator should be reverted to previous value (period)' );

		unset( $_POST['woocommerce_price_thousand_sep'], $_POST['woocommerce_price_decimal_sep'] );
	}

	/**
	 * @testdox Should allow saving when both separators are empty strings.
	 *
	 * Some merchants may prefer no separators.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/46047
	 */
	public function test_save_allows_both_empty_separators(): void {
		$this->option_names_to_clean[] = 'woocommerce_price_thousand_sep';
		$this->option_names_to_clean[] = 'woocommerce_price_decimal_sep';

		// Set initial values.
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		// Simulate form submission with both empty.
		$_POST['woocommerce_price_thousand_sep'] = '';
		$_POST['woocommerce_price_decimal_sep']  = '';

		$settings = new WC_Settings_General();
		$settings->save();

		$this->assertEquals( '', get_option( 'woocommerce_price_thousand_sep' ), 'Empty thousand separator should be allowed' );
		$this->assertEquals( '', get_option( 'woocommerce_price_decimal_sep' ), 'Empty decimal separator should be allowed' );

		unset( $_POST['woocommerce_price_thousand_sep'], $_POST['woocommerce_price_decimal_sep'] );
	}

	/**
	 * @testdox Should revert separators when both are set to a dot.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/46047
	 */
	public function test_save_reverts_same_dot_separators(): void {
		$this->option_names_to_clean[] = 'woocommerce_price_thousand_sep';
		$this->option_names_to_clean[] = 'woocommerce_price_decimal_sep';

		// Set initial values.
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		// Simulate form submission with both as dot.
		$_POST['woocommerce_price_thousand_sep'] = '.';
		$_POST['woocommerce_price_decimal_sep']  = '.';

		$settings = new WC_Settings_General();
		$settings->save();

		// Values should be reverted.
		$this->assertEquals( ',', get_option( 'woocommerce_price_thousand_sep' ), 'Thousand separator should be reverted to comma' );
		$this->assertEquals( '.', get_option( 'woocommerce_price_decimal_sep' ), 'Decimal separator should be reverted to period' );

		unset( $_POST['woocommerce_price_thousand_sep'], $_POST['woocommerce_price_decimal_sep'] );
	}
}
