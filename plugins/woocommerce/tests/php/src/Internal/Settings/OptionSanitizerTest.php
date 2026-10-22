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
}
