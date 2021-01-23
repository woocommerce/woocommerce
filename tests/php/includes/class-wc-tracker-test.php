<?php
/**
 * Unit tests for the WC_Tracker class.
 *
 * @package WooCommerce\Tests\WC_Tracker.
 */

/**
 * Class WC_Tracker_Test
 */
class WC_Tracker_Test extends \WC_Unit_Test_Case {

    /**
     * Utility method to add filter on woocommerce_admin_disabled
     */
    public function disable_woocommerce_admin() {
        return true;
    }

	/**
	 * Test the tracking of wc_admin being disabled via filter.
	 */
	public function test_wc_admin_disabled_get_tracking_data() {
		$posted_data = null;
		add_filter(
			'pre_http_request',
			function( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return true;
			}, 3, 10
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data = json_decode( $posted_data['body'], true );

        // Test the default case of no filter for set for woocommerce_admin_disabled.
        $this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'no', $tracking_data['wc_admin_disabled'] );

		// Test the case for woocommerce_admin_disabled filter returning true.
        add_filter( 'wc_admin_disabled', $this->disable_woocommerce_admin() );
        
        // Bypass the 1h cooldown period so we can invoke send_tracking_data again.
		add_filter(
			'woocommerce_tracker_last_send_time',
			function( $time ) { return $time - 10000; }
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data_disabled_wc_admin = json_decode( $posted_data['body'], true );
        $this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data_disabled_wc_admin );
		$this->assertEquals( 'yes', $tracking_data['wc_admin_disabled'] );
        remove_filter( 'wc_admin_disabled', $this->disable_woocommerce_admin() );
	}

}
