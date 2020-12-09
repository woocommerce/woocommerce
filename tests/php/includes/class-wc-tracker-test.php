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
		$tracking_data = WC_Tracker::get_tracking_data();

        // Test the default case of no filter for set for woocommerce_admin_disabled.
        $this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'no', $tracking_data['wc_admin_disabled'] );

		// Test the case for woocommerce_admin_disabled filter returning true.
        add_filter( 'wc_admin_disabled', $this->disable_woocommerce_admin() );
        
        $tracking_data_disabled_wc_admin = WC_Tracker::get_tracking_data();
        $this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data_disabled_wc_admin );
		$this->assertEquals( 'yes', $tracking_data['wc_admin_disabled'] );
        remove_filter( 'wc_admin_disabled', $this->disable_woocommerce_admin() );
	}

}
