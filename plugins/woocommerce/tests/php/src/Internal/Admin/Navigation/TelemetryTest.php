<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry;

// phpcs:disable Squiz.Commenting.FunctionComment.Missing -- setUp/tearDown and self-documenting tests need no docblocks.

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry
 */
class TelemetryTest extends \WC_Unit_Test_Case {

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
}
