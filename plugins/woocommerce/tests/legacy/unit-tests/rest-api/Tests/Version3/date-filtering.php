<?php
/**
 * Trait for testing the date filtering on controllers that inherit from WC_REST_CRUD_Controller.
 *
 * The class using this trait needs to provide two methods:
 *
 * object get_item_for_date_filtering_tests() --> create (in db) and return an object for testing (with get_id method).
 * string get_endpoint_for_date_filtering_tests() --> get the REST API endpoint for querying items.
 *
 * @package WooCommerce\Tests\API
 */

/**
 * Trait for testing the date filtering on controllers that inherit from WC_REST_CRUD_Controller.
 */
trait DateFilteringForCrudControllers {

	/**
	 * @testdox Items can be filtered by creation or modification date, and the specified dates can be GMT or not.
	 *
	 * @testWith ["after", false, true]
	 *           ["after", true, false]
	 *           ["before", true, true]
	 *           ["before", false, false]
	 *           ["modified_after", false, true]
	 *           ["modified_after", true, false]
	 *           ["modified_before", true, true]
	 *           ["modified_before", false, false]
	 *
	 * @param string $param_name The name of the date parameter to filter by.
	 * @param bool   $filter_by_gmt Whether the dates to filter by are GMT or not.
	 * @param bool   $expected_to_be_returned True if the created item is expected to be included in the response, false otherwise.
	 */
	public function test_filter_by_creation_or_modification_date( $param_name, $filter_by_gmt, $expected_to_be_returned ) {
		global $wpdb;

		wp_set_current_user( $this->user );
		$item_id = $this->get_item_for_date_filtering_tests()->get_id();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			'UPDATE ' . $wpdb->prefix . "posts SET
			post_date = '2000-01-01T12:00:00',
			post_date_gmt = '2000-01-01T10:00:00',
			post_modified = '2000-02-01T12:00:00',
			post_modified_gmt = '2000-02-01T10:00:00'
			WHERE ID = " . $item_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$request = new WP_REST_Request( 'GET', $this->get_endpoint_for_date_filtering_tests() );
		$request->set_query_params(
			array(
				$param_name     => false === strpos( $param_name, 'modified' ) ? '2000-01-01T11:00:00' : '2000-02-01T11:00:00',
				'dates_are_gmt' => $filter_by_gmt ? 'true' : 'false',
			)
		);
		$response       = $this->server->dispatch( $request );
		$response_items = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_to_be_returned ? 1 : 0, count( $response_items ) );
	}

	/**
	 * @testdox Items can be filtered by any combination of more than one date (but all must either be GMT or not be it).
	 *
	 * @testWith ["after", "2000-01-01T11:59:59", "modified_before", "2000-02-01T12:00:01", false, true]
	 *           ["after", "2000-01-01T11:59:59", "modified_before", "2000-02-01T11:59:59", false, false]
	 *           ["before", "2000-01-01T10:00:01", "modified_after", "2000-02-01T09:59:59", true, true]
	 *           ["before", "2000-01-01T09:59:59", "modified_after", "2000-02-01T09:59:59", true, false]
	 *
	 * @param string $first_param_name Name of the first parameter to filter by.
	 * @param string $first_param_value Value of the first parameter to filter by.
	 * @param string $second_param_name Name of the second parameter to filter by.
	 * @param string $second_param_value Value of the second parameter to filter by.
	 * @param bool   $filter_by_gmt Whether the dates to filter by are GMT or not.
	 * @param bool   $expected_to_be_returned True if the created item is expected to be included in the response, false otherwise.
	 */
	public function test_can_filter_by_more_than_one_date( $first_param_name, $first_param_value, $second_param_name, $second_param_value, $filter_by_gmt, $expected_to_be_returned ) {
		global $wpdb;

		wp_set_current_user( $this->user );
		$item_id = $this->get_item_for_date_filtering_tests()->get_id();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			'UPDATE ' . $wpdb->prefix . "posts SET
			post_date = '2000-01-01T12:00:00',
			post_date_gmt = '2000-01-01T10:00:00',
			post_modified = '2000-02-01T12:00:00',
			post_modified_gmt = '2000-02-01T10:00:00'
			WHERE ID = " . $item_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$request = new WP_REST_Request( 'GET', $this->get_endpoint_for_date_filtering_tests() );
		$request->set_query_params(
			array(
				$first_param_name  => $first_param_value,
				$second_param_name => $second_param_value,
				'dates_are_gmt'    => $filter_by_gmt ? 'true' : 'false',
			)
		);
		$response       = $this->server->dispatch( $request );
		$response_items = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_to_be_returned ? 1 : 0, count( $response_items ) );
	}
}
