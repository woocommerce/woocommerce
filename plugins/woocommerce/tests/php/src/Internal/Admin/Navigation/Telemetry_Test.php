<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry
 */
class Telemetry_Test extends \WC_Unit_Test_Case {

	public function tearDown(): void {
		delete_option( 'woocommerce_feature_navigation_v2_enabled' );
		parent::tearDown();
	}

	public function test_telemetry_hooks_flag_toggles() {
		new Telemetry();

		$this->assertNotFalse(
			has_action( 'update_option_woocommerce_feature_navigation_v2_enabled' ),
			'update hook must be registered so flag flips emit a Tracks event'
		);
		$this->assertNotFalse(
			has_action( 'add_option_woocommerce_feature_navigation_v2_enabled' ),
			'add hook must also be registered (first-time set uses add_option, not update)'
		);
	}

	public function test_telemetry_on_flag_toggled_does_not_error_when_tracks_unavailable() {
		$telemetry = new Telemetry();
		// Should be a no-op (and not throw) even when Tracks is disabled in tests.
		$telemetry->on_flag_toggled( 'no', 'yes' );
		$this->assertTrue( true, 'on_flag_toggled completed without error' );
	}
}
