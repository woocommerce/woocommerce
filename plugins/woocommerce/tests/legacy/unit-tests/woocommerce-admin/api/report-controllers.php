<?php
/**
 * REST Controller Tests
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.6.4
 */

/**
 * REST Controller Tests Class
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.6.4
 */
class WC_Admin_Tests_API_Report_Controllers extends WC_REST_Unit_Test_Case {
	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( $this->user );
	}

	/**
	 * Ensure that every REST controller works with their defaults.
	 */
	public function test_default_params() {
		// These controllers intentionally missing:
		// - `WC_Admin_REST_Admin_Note_Action_Controller`
		// - `WC_Admin_REST_Setting_Options_Controller`
		// - `WC_Admin_REST_Data_Download_Ips_Controller`
		// - `WC_Admin_REST_Product_Variations_Controller`
		// - `WC_Admin_REST_Reports_Import_Controller`
		// because they don't have defaults for required params or a get_items() method.
		$endpoints = array(
			'/wc-analytics/admin/notes',
			'/wc-analytics/coupons',
			'/wc-analytics/customers',
			'/wc-analytics/data',
			'/wc-analytics/data/countries',
			'/wc-analytics/leaderboards',
			'/wc-analytics/orders',
			'/wc-analytics/products',
			'/wc-analytics/products/categories',
			'/wc-analytics/reports',
			'/wc-analytics/reports/products',
			'/wc-analytics/reports/variations',
			'/wc-analytics/reports/products/stats',
			'/wc-analytics/reports/revenue/stats',
			'/wc-analytics/reports/orders',
			'/wc-analytics/reports/orders/stats',
			'/wc-analytics/reports/categories',
			'/wc-analytics/reports/taxes',
			'/wc-analytics/reports/taxes/stats',
			'/wc-analytics/reports/coupons',
			'/wc-analytics/reports/coupons/stats',
			'/wc-analytics/reports/stock',
			'/wc-analytics/reports/stock/stats',
			'/wc-analytics/reports/downloads',
			'/wc-analytics/reports/downloads/stats',
			'/wc-analytics/reports/customers',
			'/wc-analytics/reports/customers/stats',
			'/wc-analytics/taxes',
			'/wc-analytics/reports/performance-indicators',
		);

		foreach ( $endpoints as $endpoint ) {
			$request  = new WP_REST_Request( 'GET', $endpoint );
			$response = $this->server->dispatch( $request );

			// Surface any errors for easier debugging.
			if ( is_wp_error( $response ) ) {
				$this->fail( $response->get_error_message() );
			}

			$this->assertTrue( is_array( $response->get_data() ) );
		}
	}
}
