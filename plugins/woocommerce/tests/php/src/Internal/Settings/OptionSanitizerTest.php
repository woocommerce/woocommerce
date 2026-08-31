<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Settings;

use Automattic\WooCommerce\Internal\Settings\OptionSanitizer;
use WC_Unit_Test_Case;

/**
 * Tests for OptionSanitizer.
 */
class OptionSanitizerTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Stock thresholds are sanitized consistently as absolute integers.
	 * @dataProvider stock_threshold_values_provider
	 *
	 * @param string $value    Submitted threshold value.
	 * @param int    $expected Expected sanitized value.
	 */
	public function test_sanitizes_stock_thresholds_as_absolute_integers( string $value, int $expected ): void {
		new OptionSanitizer();

		$low_stock_threshold = apply_filters(
			'woocommerce_admin_settings_sanitize_option_woocommerce_notify_low_stock_amount',
			$value
		);
		$no_stock_threshold  = apply_filters(
			'woocommerce_admin_settings_sanitize_option_woocommerce_notify_no_stock_amount',
			$value
		);

		$this->assertSame( $expected, $low_stock_threshold, 'The low-stock threshold should be stored as an absolute integer.' );
		$this->assertSame( $expected, $no_stock_threshold, 'The out-of-stock threshold should be stored as an absolute integer.' );
	}

	/**
	 * Provides stock threshold values and their expected sanitized forms.
	 *
	 * @return array<string, array{string, int}>
	 */
	public function stock_threshold_values_provider(): array {
		return array(
			'positive integer' => array( '3', 3 ),
			'zero'             => array( '0', 0 ),
			'negative integer' => array( '-2', 2 ),
			'fraction'         => array( '2.9', 2 ),
			'empty value'      => array( '', 0 ),
			'non-numeric text' => array( 'invalid', 0 ),
		);
	}

	/**
	 * @testdox Price separators containing numbers are discarded on any update_option() write path.
	 * @dataProvider price_separator_values_provider
	 *
	 * @param string $option_id    Option being saved.
	 * @param string $stored_value Value stored before the write.
	 * @param mixed  $new_value    Value being written.
	 * @param string $expected     Value expected to be stored afterwards.
	 */
	public function test_discards_price_separators_containing_numbers( string $option_id, string $stored_value, $new_value, string $expected ): void {
		update_option( $option_id, $stored_value );

		update_option( $option_id, $new_value );

		$this->assertSame( $expected, get_option( $option_id ), 'A separator containing a number should never reach the database.' );
	}

	/**
	 * Provides separator writes and the value expected to be stored afterwards.
	 *
	 * @return array<string, array{string, string, mixed, string}>
	 */
	public function price_separator_values_provider(): array {
		return array(
			'space stays'            => array( 'woocommerce_price_thousand_sep', ',', ' ', ' ' ),
			'entity stays'           => array( 'woocommerce_price_thousand_sep', ',', '&#44;', '&#44;' ),
			'digit is discarded'     => array( 'woocommerce_price_thousand_sep', ',', '1', ',' ),
			'fullwidth is discarded' => array( 'woocommerce_price_decimal_sep', '.', '１', '.' ),
			'array is discarded'     => array( 'woocommerce_price_decimal_sep', '.', array( '1' ), '.' ),
		);
	}

	/**
	 * @testdox Price separator sanitization falls back to the default when the stored value is invalid too.
	 */
	public function test_price_separator_falls_back_to_default_when_stored_value_is_invalid(): void {
		$sanitizer = wc_get_container()->get( OptionSanitizer::class );

		// Store an invalid separator directly, standing in for a value saved before the validation existed.
		remove_filter( 'sanitize_option_woocommerce_price_decimal_sep', array( $sanitizer, 'sanitize_price_separator_option' ), 10 );
		update_option( 'woocommerce_price_decimal_sep', '2' );
		add_filter( 'sanitize_option_woocommerce_price_decimal_sep', array( $sanitizer, 'sanitize_price_separator_option' ), 10, 2 );

		update_option( 'woocommerce_price_decimal_sep', '3' );

		$this->assertSame( '.', get_option( 'woocommerce_price_decimal_sep' ), 'An invalid stored value should not be re-saved.' );
	}
}
