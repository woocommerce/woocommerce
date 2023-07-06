<?php

/**
 * Class WC_Settings_Tracking_Test.
 */
class WC_Settings_Tracking_Test extends \WC_Unit_Test_Case {
	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-settings-tracking.php';
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$settings_tracking = new WC_Settings_Tracking();
		$settings_tracking->init();
		parent::setUp();
	}

	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		parent::tearDown();
	}

	public function test_settings_view() {
		do_action( 'woocommerce_settings_page_init' );
		$this->assertRecordedTracksEvent( 'wcadmin_settings_view' );
	}

	public function test_settings_change() {
		do_action( 'woocommerce_update_option', array( 'id' => 'some_option' ) );
		do_action( 'update_option', 'some_option', 'old_value', 'new_value' );
		do_action( 'woocommerce_update_options' );
		$this->assertRecordedTracksEvent( 'wcadmin_settings_change' );
	}
}
