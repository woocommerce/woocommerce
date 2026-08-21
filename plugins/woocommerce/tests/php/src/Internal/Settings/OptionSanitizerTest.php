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
	 */
	public function test_sanitizes_stock_thresholds_as_absolute_integers(): void {
		new OptionSanitizer();

		$low_stock_threshold = apply_filters(
			'woocommerce_admin_settings_sanitize_option_woocommerce_notify_low_stock_amount',
			'-2'
		);
		$no_stock_threshold  = apply_filters(
			'woocommerce_admin_settings_sanitize_option_woocommerce_notify_no_stock_amount',
			''
		);

		$this->assertSame( 2, $low_stock_threshold, 'The low-stock threshold should be stored as an absolute integer.' );
		$this->assertSame( 0, $no_stock_threshold, 'The out-of-stock threshold should be stored as an absolute integer.' );
	}
}
