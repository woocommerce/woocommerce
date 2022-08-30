<?php

/**
 * Class WC_REST_System_Status_Tools_V2_Controller.
 */
class WC_REST_System_Status_Tools_V2_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Array of Download objects used for testing.
	 *
	 * @var Array(WC_Customer_Download)
	 */
	private $downloads;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_System_Status_Tools_V2_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Create download permissions and download logs for 'clear_expired_download_permissions' test.
	 * @return array Keys and values to expect in the REST response.
	 */
	private function create_data_for_expired_download_permissions_test() {
		$customer = array(
			'id'    => 1,
			'email' => 'test_1@example.com',
		);

		// Expired permission.
		$download = new WC_Customer_Download();
		$download->set_user_id( $customer['id'] );
		$download->set_user_email( $customer['email'] );
		$download->set_access_granted( '2018-01-22 00:00:00' );
		$download->set_access_expires( '2020-01-01 00:00:00' );
		$d_id              = $download->save();
		$this->downloads[] = $d_id;

		// 2 downloads log for the expired download permission.
		$download_log_1 = new WC_Customer_Download_Log();
		$download_log_1->set_permission_id( $download->get_id() );
		$download_log_1->set_user_id( $customer['id'] );
		$download_log_1->set_user_ip_address( '1.2.3.4' );
		$download_log_1->save();

		$download_log_2 = new WC_Customer_Download_Log();
		$download_log_2->set_permission_id( $download->get_id() );
		$download_log_2->set_user_id( $customer['id'] );
		$download_log_2->set_user_ip_address( '1.2.3.4' );
		$download_log_2->save();

		// Non-expired permission.
		$download = new WC_Customer_Download();
		$download->set_user_id( $customer['id'] );
		$download->set_user_email( $customer['email'] );
		$download->set_access_granted( '2018-01-22 00:00:00' );
		// This will hopefully be always in the future, at least as long as the servers are on Earth?
		$download->set_access_expires( gmdate( 'Y-m-d H:i:s', time() + WEEK_IN_SECONDS ) );
		$d_id              = $download->save();
		$this->downloads[] = $d_id;

		// 1 download log for the non-expired download permission.
		$download_log_3 = new WC_Customer_Download_Log();
		$download_log_3->set_permission_id( $download->get_id() );
		$download_log_3->set_user_id( $customer['id'] );
		$download_log_3->set_user_ip_address( '1.2.3.4' );
		$download_log_3->save();

		return array(
			'success' => true,
			'message' => '1 permissions deleted',
		);
	}

	/**
	 * Clean up db after create_data_for_expired_download_permissions_test.
	 *
	 * @return void
	 */
	private function cleanup_data_for_expired_download_permissions_test() {
		$download = new WC_Customer_Download( $this->downloads[1] );
		$download->delete();
	}

	/**
	 * @testdox Expired download permissions are removed via the respective tool.
	 */
	public function test_execute_tool_clear_expired_download_permissions() {
		wp_set_current_user( $this->user );
		$expected_response_values = $this->create_data_for_expired_download_permissions_test();

		$response = $this->server->dispatch( new WP_REST_Request( 'PUT', '/wc/v2/system_status/tools/clear_expired_download_permissions' ) );

		$this->assertEquals( 200, $response->get_status() );

		$response = $response->get_data();
		foreach ( $expected_response_values as $key => $expected_value ) {
			$this->assertTrue( isset( $response[ $key ] ) );
			$this->assertEquals( $expected_value, $response[ $key ] );

		}

		// Test that the download logs for the permission have been deleted.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $this->downloads[0] ) );

		$this->cleanup_data_for_expired_download_permissions_test();
	}
}
