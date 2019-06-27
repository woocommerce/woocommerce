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
class WC_Tests_API_Report_Controllers extends WC_REST_Unit_Test_Case {
	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp() {
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
			'/wc/v4/admin/notes',
			'/wc/v4/coupons',
			'/wc/v4/customers',
			'/wc/v4/data',
			'/wc/v4/data/countries',
			'/wc/v4/leaderboards',
			'/wc/v4/orders',
			'/wc/v4/products',
			'/wc/v4/products/categories',
			'/wc/v4/reports',
			'/wc/v4/reports/products',
			'/wc/v4/reports/variations',
			'/wc/v4/reports/products/stats',
			'/wc/v4/reports/revenue/stats',
			'/wc/v4/reports/orders',
			'/wc/v4/reports/orders/stats',
			'/wc/v4/reports/categories',
			'/wc/v4/reports/taxes',
			'/wc/v4/reports/taxes/stats',
			'/wc/v4/reports/coupons',
			'/wc/v4/reports/coupons/stats',
			'/wc/v4/reports/stock',
			'/wc/v4/reports/stock/stats',
			'/wc/v4/reports/downloads',
			'/wc/v4/reports/downloads/stats',
			'/wc/v4/reports/customers',
			'/wc/v4/reports/customers/stats',
			'/wc/v4/taxes',
			'/wc/v4/reports/performance-indicators',
			'/wc-admin/v1/onboarding/levels',
			'/wc-admin/v1/onboarding/profile',
			'/wc-admin/v1/onboarding/plugins',
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
