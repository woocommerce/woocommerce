<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry
 */
class Telemetry_Test extends \WC_Unit_Test_Case {

	/**
	 * Telemetry instance under test. Stored so tearDown() can unhook its
	 * callbacks and avoid leaking option hooks across tests.
	 *
	 * @var Telemetry|null
	 */
	private $telemetry;

	public function tearDown(): void {
		if ( $this->telemetry ) {
			remove_action( 'update_option_woocommerce_feature_navigation_v2_enabled', array( $this->telemetry, 'on_flag_toggled' ), 10 );
			remove_action( 'add_option_woocommerce_feature_navigation_v2_enabled', array( $this->telemetry, 'on_flag_toggled_first_time' ), 10 );
			$this->telemetry = null;
		}
		delete_option( 'woocommerce_feature_navigation_v2_enabled' );
		parent::tearDown();
	}

	public function test_telemetry_hooks_flag_toggles() {
		$this->telemetry = new Telemetry();

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
		$this->telemetry = new Telemetry();
		// Should be a no-op (and not throw) even when Tracks is disabled in tests.
		$this->telemetry->on_flag_toggled( 'no', 'yes' );
		$this->assertTrue( true, 'on_flag_toggled completed without error' );
	}
}
