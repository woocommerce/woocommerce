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
	public function test_get_id() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download    = new WC_Customer_Download();
		$download->set_user_id( $customer_id );
		$download->set_order_id( 1 );
		$download->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $customer_id );
		$object->set_user_ip_address( '1.2.3.4' );
		$id = $object->save();
		$this->assertEquals( $id, $object->get_id() );
	}

	/**
	 * Test: get_timestamp
	 */
	public function test_get_timestamp() {
		$object = new WC_Customer_Download_Log();
		$set_to = time();

		// Convert timestamp to WC_DateTime using ISO 8601 for PHP 5.2 compat.
		$dt_str       = date( 'c', $set_to );
		$wc_timestamp = new WC_DateTime( $dt_str );

		$object->set_timestamp( $set_to );
		$this->assertEquals( $wc_timestamp, $object->get_timestamp() );
	}

	/**
	 * Test: get_permission_id
	 */
	public function test_get_permission_id() {
		$object = new WC_Customer_Download_Log();
		$set_to = 10;
		$object->set_permission_id( $set_to );
		$this->assertEquals( $set_to, $object->get_permission_id() );
	}

	/**
	 * Test: get_user_id
	 */
	public function test_get_user_id() {
		$object = new WC_Customer_Download_Log();
		$set_to = 10;
		$object->set_user_id( $set_to );
		$this->assertEquals( $set_to, $object->get_user_id() );
	}

	/**
	 * Test: get_user_ip_address
	 */
	public function test_get_user_ip_address() {
		$object = new WC_Customer_Download_Log();
		$set_to = '1.2.3.4';
		$object->set_user_ip_address( $set_to );
		$this->assertEquals( $set_to, $object->get_user_ip_address() );
	}

	/**
	 * Test creating a new download log manually.
	 */
	public function test_create_download_log() {
		// First create a download permission to test against.
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$download_1 = new WC_Customer_Download();
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->save();

		// Create download log.
		$timestamp = time();

		// Convert timestamp to WC_DateTime using ISO 8601 for PHP 5.2 compat.
		$dt_str       = date( 'c', $timestamp );
		$wc_timestamp = new WC_DateTime( $dt_str );

		$download_log = new WC_Customer_Download_Log();
		$download_log->set_timestamp( $timestamp );
		$download_log->set_permission_id( $download_1->get_id() );
		$download_log->set_user_id( $customer_id_2 );
		$download_log->set_user_ip_address( '1.2.3.4' );
		$download_log->save();

		// Pull the download log back from data store.
		$db_download_log = new WC_Customer_Download_Log( $download_log->get_id() );

		// Check that created log matches data from data store.
		$this->assertNotEquals( 0, $db_download_log->get_id(), 'New download log ID not set to non-zero value.' );
		$this->assertEquals( $wc_timestamp, $db_download_log->get_timestamp(), 'New download log timestamp set incorrectly.' );
		$this->assertEquals( $download_1->get_id(), $db_download_log->get_permission_id(), 'New download log permission ID set incorrectly.' );
		$this->assertEquals( $customer_id_2, $db_download_log->get_user_id(), 'New download log user ID set incorrectly.' );
		$this->assertEquals( '1.2.3.4', $db_download_log->get_user_ip_address(), 'New download log IP address set incorrectly.' );
	}

	/**
	 * Test creating a new download log automatically from customer download.
	 */
	public function test_track_download() {
		// First create a download permission to test against.
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );

		$download_1 = new WC_Customer_Download();
		$download_1->set_user_id( $customer_id_1 );
		$download_1->set_order_id( 1 );
		$download_1->set_downloads_remaining( 10 );
		$download_1->save();

		$ip_address = '1.2.3.4';

		// Initially download count should be zero, and remaining should be 10.
		$this->assertEquals( 0, $download_1->get_download_count(), 'New permission download count should be zero.' );
		$this->assertEquals( 10, $download_1->get_downloads_remaining(), 'New permission downloads remaining should be 10.' );

		// Track the download in logs and change remaining/counts.
		$download_1->track_download( $customer_id_2, $ip_address );

		// Ensure download count iterates properly.
		$this->assertEquals( 1, $download_1->get_download_count(), 'After download, permission download count should be 1.' );
		$this->assertEquals( 9, $download_1->get_downloads_remaining(), 'After download, permission downloads remaining should be 9.' );

		// Make sure download log was recorded properly.
		$data_store    = WC_Data_Store::load( 'customer-download-log' );
		$download_logs = $data_store->get_download_logs(
			array(
				'permission_id' => $download_1->get_id(),
			)
		);

		$this->assertEquals( 1, count( $download_logs ), 'After single download, permission should have one download log in database.' );

		$download_log = current( $download_logs );

		// Ensure log contains appropriate data for the user, etc.
		$this->assertNotEquals( 0, $download_log->get_id(), 'Tracked download log ID should not be zero.' );
		$this->assertEquals( $download_1->get_id(), $download_log->get_permission_id(), 'Tracked download log permission ID did not match.' );
		$this->assertEquals( $customer_id_2, $download_log->get_user_id(), 'Tracked download log user ID did not match.' );
		$this->assertEquals( $ip_address, $download_log->get_user_ip_address(), 'Tracked download log IP address did not match.' );
	}
}
