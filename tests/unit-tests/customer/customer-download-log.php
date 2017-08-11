<?php

/**
 * Class Customer_Download_Log.
 * @since 3.3.0
 * @package WooCommerce\Tests\Customer
 */
class WC_Tests_Customer_Download_Log extends WC_Unit_Test_Case {

	/**
	 * Test: get_id
	 */
	function test_get_id() {
		$object = new WC_Customer_Download_Log();
		$id = $object->save();
		$this->assertEquals( $id, $object->get_id() );
	}

	/**
	 * Test: get_timestamp
	 */
	function test_get_timestamp() {
		$object = new WC_Customer_Download_Log();
		$set_to = current_time( 'timestamp', true );
		$wc_timestamp = new WC_DateTime;
		$wc_timestamp->setTimestamp( $set_to );
		$object->set_timestamp( $set_to );
		$this->assertEquals( $wc_timestamp, $object->get_timestamp() );
	}

	/**
	 * Test: get_permission_id
	 */
	function test_get_permission_id() {
		$object = new WC_Customer_Download_Log();
		$set_to = 10;
		$object->set_permission_id( $set_to );
		$this->assertEquals( $set_to, $object->get_permission_id() );
	}

	/**
	 * Test: get_user_id
	 */
	function test_get_user_id() {
		$object = new WC_Customer_Download_Log();
		$set_to = 10;
		$object->set_user_id( $set_to );
		$this->assertEquals( $set_to, $object->get_user_id() );
	}

	/**
	 * Test: get_user_ip_address
	 */
	function test_get_user_ip_address() {
		$object = new WC_Customer_Download_Log();
		$set_to = '1.2.3.4';
		$object->set_user_ip_address( $set_to );
		$this->assertEquals( $set_to, $object->get_user_ip_address() );
	}

	/**
	 * Test creating a new download log manually.
	 */
	public function test_create_download_log() {
		// First create a download permission to test against
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$download_1 = new WC_Customer_Download;
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->save();

		// Create download log
		$timestamp = current_time( 'timestamp', true );
		$wc_timestamp = new WC_DateTime;
		$wc_timestamp->setTimestamp( $timestamp );

		$download_log = new WC_Customer_Download_Log;
		$download_log->set_timestamp( $timestamp );
		$download_log->set_permission_id( $download_1->get_id() );
		$download_log->set_user_id( $customer_id_2 );
		$download_log->set_user_ip_address( '1.2.3.4' );
		$download_log->save();

		// Pull the download log back from data store
		$db_download_log = new WC_Customer_Download_Log( $download_log->get_id() );

		// Check that created log matches data from data store
		$this->assertNotEquals( 0, $db_download_log->get_id() );
		$this->assertEquals( $wc_timestamp, $db_download_log->get_timestamp() );
		$this->assertEquals( $download_1->get_id(), $db_download_log->get_permission_id() );
		$this->assertEquals( $customer_id_2, $db_download_log->get_user_id() );
		$this->assertEquals( '1.2.3.4', $db_download_log->get_user_ip_address() );
	}

	/**
	 * Test creating a new download log automatically from customer download.
	 */
	public function test_track_download() {
		// First create a download permission to test against
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$download_1 = new WC_Customer_Download;
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->set_downloads_remaining( 10 );
		$download_1->save();

		$ip_address = '1.2.3.4';

		// Initially download count should be zero, and remaining should be 10
		$this->assertEquals( 0, $download_1->get_download_count() );
		$this->assertEquals( 10, $download_1->get_downloads_remaining() );

		// Track the download in logs and change remaining/counts
		$download_1->track_download( $customer_id_2, $ip_address );

		// Ensure download count iterates properly
		$this->assertEquals( 1, $download_1->get_download_count() );
		$this->assertEquals( 9, $download_1->get_downloads_remaining() );

		// Make sure download log was recorded properly
		$data_store = WC_Data_Store::load( 'customer-download-log' );
		$download_logs = $data_store->get_download_logs( array(
			'permission_id' => $download_1->get_id()
		) );

		$this->assertEquals( 1, count( $download_logs ) );

		$download_log = current( $download_logs );

		// Ensure log contains appropriate data for the user, etc.
		$this->assertNotEquals( 0, $download_log->get_id() );
		$this->assertEquals( $download_1->get_id(), $download_log->get_permission_id() );
		$this->assertEquals( $customer_id_2, $download_log->get_user_id() );
		$this->assertEquals( $ip_address, $download_log->get_user_ip_address() );
	}
}
