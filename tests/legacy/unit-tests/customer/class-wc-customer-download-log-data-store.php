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
}
