<?php
/**
 * WC_Customer_Download tests file.
 *
 * @package WooCommerce\Tests\Customer
 */

/**
 * Class WC_Customer_Download.
 *
 * @since 3.3.0
 * @package WooCommerce\Tests\Customer
 */
class WC_Tests_Customer_Download extends WC_Unit_Test_Case {

	/**
	 * Test WC_Customer_Download_Data_Store::delete()
	 */
	public function test_delete() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download    = new WC_Customer_Download();
		$download->set_user_id( $customer_id );
		$download->set_order_id( 1 );
		$download->save();
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete( $download );
		$this->assertEquals( 0, $download->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_id()
	 */
	public function test_delete_by_id() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download    = new WC_Customer_Download();
		$download->set_user_id( $customer_id );
		$download->set_order_id( 1 );
		$download->save();
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete_by_id( $download->get_id() );
		$this->assertEquals( 0, $data_store->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_download_id()
	 */
	public function test_delete_by_download_id() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download    = new WC_Customer_Download();
		$download->set_user_id( $customer_id );
		$download->set_order_id( 1 );
		$download->save();
		$download_id = $download->get_download_id();
		$data_store  = WC_Data_Store::load( 'customer-download' );
		$downloads   = $data_store->get_downloads_for_customer( $customer_id );
		$this->assertInstanceOf( 'StdClass', $downloads[0] );
		$data_store->delete_by_download_id( $download_id );
		$downloads = $data_store->get_downloads_for_customer( $customer_id );
		$this->assertEquals( array(), $downloads );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::get_downloads()
	 */
	public function test_get_downloads() {
		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$download_1  = new WC_Customer_Download();
		$download_1->set_user_id( $customer_id );
		$download_1->set_user_email( 'test@example.com' );
		$download_1->set_order_id( 1 );
		$download_1->save();

		$download_2 = new WC_Customer_Download();
		$download_2->set_user_id( $customer_id );
		$download_2->set_user_email( 'test@example.com' );
		$download_2->set_order_id( 1 );
		$download_2->save();

		$data_store = WC_Data_Store::load( 'customer-download' );
		$downloads  = $data_store->get_downloads( array( 'user_email' => 'test@example.com' ) );
		$this->assertEquals( 2, count( $downloads ) );
		$this->assertTrue( $downloads[0] instanceof WC_Customer_Download );
		$this->assertTrue( $downloads[1] instanceof WC_Customer_Download );

		$downloads = $data_store->get_downloads( array( 'user_email' => 'test2@example.com' ) );
		$this->assertEquals( array(), $downloads );

		$expected_result = array( $download_1->get_id(), $download_2->get_id() );
		$downloads       = $data_store->get_downloads(
			array(
				'user_email' => 'test@example.com',
				'return'     => 'ids',
			)
		);
		$this->assertEquals( $expected_result, $downloads );
	}
}
