<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySettingsService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySettingsService class.
 */
class MultiCurrencySettingsServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return the WooCommerce plugin file path.
	 */
	public function test_returns_plugin_file_path(): void {
		$service = new MultiCurrencySettingsService();

		$this->assertSame( WC_PLUGIN_FILE, $service->get_plugin_file_path() );
	}

	/**
	 * @testdox Should return the WooCommerce plugin version.
	 */
	public function test_returns_plugin_version(): void {
		$service = new MultiCurrencySettingsService();

		$this->assertSame( WC_VERSION, $service->get_plugin_version() );
	}

	/**
	 * @testdox Should report dev mode from the core-native constant.
	 */
	public function test_reports_dev_mode_from_constant(): void {
		$service = new MultiCurrencySettingsService();

		$this->assertSame( defined( 'WC_STRIPE_DEV_MODE' ) && WC_STRIPE_DEV_MODE, $service->is_dev_mode() );
	}
}
