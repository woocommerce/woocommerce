<?php
/**
 * Class WC_Tests_Customer_Download_Log_Data_Store file.
 *
 * @version  3.5.0
 * @package WooCommerce\Tests\Customer
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tests_Customer_Download_Log_Data_Store class.
 */
class WC_Tests_Customer_Download_Log_Data_Store extends WC_Unit_Test_Case {
	/**
	 * Test WC_Customer_Download_Log_Data_Store::get_download_logs()
	 */
	public function test_get_download_logs() {
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download_1    = new WC_Customer_Download();
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->save();

		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword' );
		$download_2    = new WC_Customer_Download();
		$download_2->set_user_id( $customer_id_2 );
		$download_2->set_order_id( 1 );
		$download_2->save();

		$download_log_1 = new WC_Customer_Download_Log();
		$download_log_1->set_permission_id( $download_1->get_id() );
		$download_log_1->set_user_id( $customer_id_1 );
		$download_log_1->set_user_ip_address( '1.2.3.4' );
		$download_log_1->save();

		$download_log_2 = new WC_Customer_Download_Log();
		$download_log_2->set_permission_id( $download_2->get_id() );
		$download_log_2->set_user_id( $customer_id_2 );
		$download_log_2->set_user_ip_address( '1.2.3.5' );
		$download_log_2->save();

		$data_store = WC_Data_Store::load( 'customer-download-log' );
		$logs       = $data_store->get_download_logs(
			array(
				'return'  => 'ids',
				'orderby' => 'user_id',
				'order'   => 'DESC',
			)
		);

		$expected_result = array( (string) $download_log_2->get_id(), (string) $download_log_1->get_id() );
		$this->assertEquals( $expected_result, $logs );
	}

	/**
	 * @testdox Delete the log entries and also the related permission id.
	 */
	public function test_delete_by_permission_id() {
		global $wpdb;
		// Customer 1.
		$customer = array(
			'id'    => 1,
			'email' => 'test_1@example.com',
		);

		$download_permission = new WC_Customer_Download();
		$download_permission->set_user_id( $customer['id'] );
		$download_permission->set_user_email( $customer['email'] );
		$download_permission->set_order_id( $customer['id'] );
		$download_permission->set_access_granted( '2018-01-22 00:00:00' );
		$p_id = $download_permission->save();

		$download_log = new WC_Customer_Download_Log();
		$download_log->set_permission_id( $p_id );
		$download_log->set_user_id( $customer['id'] );
		$download_log->set_user_ip_address( '1.2.3.4' );
		$download_log->save();

		$download_log_2 = new WC_Customer_Download_Log();
		$download_log_2->set_permission_id( $p_id );
		$download_log_2->set_user_id( $customer['id'] );
		$download_log_2->set_user_ip_address( '1.2.3.4' );
		$download_log_2->save();

		// Check that the download log records are gone.
		$data_store = WC_Data_Store::load( 'customer-download-log' );
		$data_store->delete_by_permission_id( $p_id );
		$this->assertEmpty(
			$data_store->get_download_logs(
				array(
					'return'        => 'ids',
					'permission_id' => $p_id,
				)
			)
		);

		/** Check that the download permission is gone, too.
		 *
		 * I could try to instantiate WC_Customer_Download( $permission ) here as well,
		 * or use the WC_Customer_Download::read(),
		 * but the thrown exception is ambiguous, so opted for a direct query here.
		 */
		$this->assertEquals( 0, $wpdb->query( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d", $p_id ) ) );
	}

	/**
	 * @testdox Delete only log records related to the particular id.
	 */
	public function test_delete_by_permission_id_not_all() {
		global $wpdb;
		// Customer 1.
		$customers   = array();
		$customers[] = array(
			'id'    => 1,
			'email' => 'test_1@example.com',
		);

		// Customer 2.
		$customers[] = array(
			'id'    => 2,
			'email' => 'test_2@example.com',
		);

		$download_permissions = array();
		$dp_ids               = array();
		foreach ( $customers as $customer ) {
			$download_permission = new WC_Customer_Download();
			$download_permission->set_user_id( $customer['id'] );
			$download_permission->set_user_email( $customer['email'] );
			$download_permission->set_order_id( $customer['id'] );
			$download_permission->set_access_granted( '2018-01-22 00:00:00' );
			$dp_ids[]                               = $download_permission->save();
			$download_permissions[ end( $dp_ids ) ] = $download_permission;

		}

		$download_log = new WC_Customer_Download_Log();
		$download_log->set_permission_id( $dp_ids[0] );
		$download_log->set_user_id( $customers[0]['id'] );
		$download_log->set_user_ip_address( '1.2.3.4' );
		$download_log->save();

		$download_log_2 = new WC_Customer_Download_Log();
		$download_log_2->set_permission_id( $dp_ids[1] );
		$download_log_2->set_user_id( $customers[1]['id'] );
		$download_log_2->set_user_ip_address( '1.2.3.5' );
		$download_log_2->save();

		// Check that the download log records are gone for Customer 1...
		$data_store = WC_Data_Store::load( 'customer-download-log' );
		$data_store->delete_by_permission_id( $dp_ids[0] );
		$this->assertEmpty(
			$data_store->get_download_logs(
				array(
					'return'        => 'ids',
					'permission_id' => $dp_ids[0],
				)
			)
		);

		// ...but still present for Customer 2.
		$this->assertEquals(
			array(
				(string) $download_log_2->get_id(),
			),
			$data_store->get_download_logs(
				array(
					'return'        => 'ids',
					'permission_id' => $dp_ids[1],
				)
			)
		);

		// Check that the download permission is gone for Customer 1 and still intact for Customer 2.
		$this->assertEquals( 0, $wpdb->query( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d", $dp_ids[0] ) ) );
		$this->assertEquals( 1, $wpdb->query( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d", $dp_ids[1] ) ) );

		// Clean up the rest.
		$data_store->delete_by_permission_id( $dp_ids[1] );
	}
}
