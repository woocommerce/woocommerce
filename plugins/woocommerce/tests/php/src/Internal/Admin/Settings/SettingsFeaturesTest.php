<?php
/**
 * Settings features tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
use WC_Unit_Test_Case;

/**
 * Tests for the features exported through shared admin settings.
 */
class SettingsFeaturesTest extends WC_Unit_Test_Case {

	/**
	 * It excludes retired WC Admin compatibility flags from shared settings.
	 */
	public function test_get_features_excludes_retired_wc_admin_compatibility_flags(): void {
		$features = ( new Settings() )->get_features();

		$this->assertArrayNotHasKey( 'analytics', $features );
		$this->assertArrayNotHasKey( 'remote-inbox-notifications', $features );
		$this->assertArrayNotHasKey( 'launch-your-store', $features );
	}

	/**
	 * It keeps active feature engine flags in shared settings.
	 */
	public function test_get_features_keeps_active_feature_engine_flags(): void {
		$features = ( new Settings() )->get_features();

		$this->assertArrayHasKey( 'cart_checkout_blocks', $features );
		$this->assertArrayHasKey( 'is_enabled', $features['cart_checkout_blocks'] );
		$this->assertArrayHasKey( 'is_experimental', $features['cart_checkout_blocks'] );
	}
}
