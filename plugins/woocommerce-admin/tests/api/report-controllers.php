<?php
/**
 * REST Controller Tests
 *
 * @package WooCommerce\Tests\API
 * @since 3.6.4
 */

/**
 * REST Controller Tests Class
 *
 * @package WooCommerce\Tests\API
 * @since 3.6.4
 */
class WC_Tests_API_Report_Controllers extends WP_UnitTestCase {
	/**
	 * Ensure that every REST controller works with their defaults.
	 */
	public function test_default_params() {
		$controller_classes = array(
			'WC_Admin_REST_Admin_Notes_Controller',
			'WC_Admin_REST_Admin_Note_Action_Controller',
			'WC_Admin_REST_Coupons_Controller',
			'WC_Admin_REST_Customers_Controller',
			'WC_Admin_REST_Data_Controller',
			'WC_Admin_REST_Data_Countries_Controller',
			'WC_Admin_REST_Data_Download_Ips_Controller',
			'WC_Admin_REST_Leaderboards_Controller',
			'WC_Admin_REST_Orders_Controller',
			'WC_Admin_REST_Products_Controller',
			'WC_Admin_REST_Product_Categories_Controller',
			'WC_Admin_REST_Product_Variations_Controller',
			'WC_Admin_REST_Product_Reviews_Controller',
			'WC_Admin_REST_Product_Variations_Controller',
			'WC_Admin_REST_Reports_Controller',
			'WC_Admin_REST_Setting_Options_Controller',
			'WC_Admin_REST_Reports_Import_Controller',
			'WC_Admin_REST_Reports_Products_Controller',
			'WC_Admin_REST_Reports_Variations_Controller',
			'WC_Admin_REST_Reports_Products_Stats_Controller',
			'WC_Admin_REST_Reports_Revenue_Stats_Controller',
			'WC_Admin_REST_Reports_Orders_Controller',
			'WC_Admin_REST_Reports_Orders_Stats_Controller',
			'WC_Admin_REST_Reports_Categories_Controller',
			'WC_Admin_REST_Reports_Taxes_Controller',
			'WC_Admin_REST_Reports_Taxes_Stats_Controller',
			'WC_Admin_REST_Reports_Coupons_Controller',
			'WC_Admin_REST_Reports_Coupons_Stats_Controller',
			'WC_Admin_REST_Reports_Stock_Controller',
			'WC_Admin_REST_Reports_Stock_Stats_Controller',
			'WC_Admin_REST_Reports_Downloads_Controller',
			'WC_Admin_REST_Reports_Downloads_Stats_Controller',
			'WC_Admin_REST_Reports_Customers_Controller',
			'WC_Admin_REST_Reports_Customers_Stats_Controller',
			'WC_Admin_REST_Taxes_Controller',
			'WC_Admin_REST_Onboarding_Levels_Controller',
			'WC_Admin_REST_Onboarding_Profile_Controller',
			'WC_Admin_REST_Onboarding_Plugins_Controller',
			'WC_Admin_REST_Reports_Performance_Indicators_Controller',
		);

		// Force REST controllers to load.
		do_action( 'rest_api_init' );

		foreach ( $controller_classes as $controller_class ) {
			$controller = new $controller_class();
			$response   = $controller->get_items( new WP_REST_Request() );

			$this->assertTrue( is_array( $response ) );
		}
	}
}
