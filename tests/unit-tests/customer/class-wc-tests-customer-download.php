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
	 * Download object used for testing.
	 *
	 * @var WC_Customer_Download
	 */
	private $download;

	/**
	 * ID of the fake customer used for the tests.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * E-mail of the fake customer used for the tests.
	 *
	 * @var string
	 */
	private $customer_email;

	/**
	 * Tests set up.
	 */
	public function setUp() {
		$this->customer_id    = 1;
		$this->customer_email = 'test@example.com';

		$this->download = new WC_Customer_Download();
		$this->download->set_user_id( $this->customer_id );
		$this->download->set_user_email( $this->customer_email );
		$this->download->set_order_id( 1 );
		$this->download->set_access_granted( '2018-01-22 00:00:00' );
		$this->download->save();
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete()
	 */
	public function test_delete() {
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete( $this->download );
		$this->assertEquals( 0, $this->download->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_id()
	 */
	public function test_delete_by_id() {
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete_by_id( $this->download->get_id() );
		$this->assertEquals( 0, $data_store->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_download_id()
	 */
	public function test_delete_by_download_id() {
		$download_id = $this->download->get_download_id();
		$data_store  = WC_Data_Store::load( 'customer-download' );
		$downloads   = $data_store->get_downloads_for_customer( $this->customer_id );
		$this->assertInstanceOf( 'StdClass', $downloads[0] );
		$data_store->delete_by_download_id( $download_id );
		$downloads = $data_store->get_downloads_for_customer( $this->customer_id );
		$this->assertEquals( array(), $downloads );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::get_downloads()
	 */
	public function test_get_downloads() {
		$download_2 = new WC_Customer_Download();
		$download_2->set_user_id( $this->customer_id );
		$download_2->set_user_email( $this->customer_email );
		$download_2->set_order_id( 2 );
		$download_2->set_access_granted( '2018-01-22 00:00:00' );
		$download_2->save();

		$data_store = WC_Data_Store::load( 'customer-download' );
		$downloads  = $data_store->get_downloads(
			array(
				'user_email' => $this->customer_email,
				'orderby'    => 'order_id',
				'order'      => 'DESC',
			)
		);
		$this->assertEquals( array( $download_2, $this->download ), $downloads );

		$downloads = $data_store->get_downloads( array( 'user_email' => 'test2@example.com' ) );
		$this->assertEquals( array(), $downloads );

		$expected_result = array( $this->download->get_id(), $download_2->get_id() );
		$downloads       = $data_store->get_downloads(
			array(
				'user_email' => $this->customer_email,
				'return'     => 'ids',
			)
		);
		$this->assertEquals( $expected_result, $downloads );

		$expected_result = array(
			array(
				'user_email'    => $this->customer_email,
				'permission_id' => $this->download->get_id(),
			),
			array(
				'user_email'    => $this->customer_email,
				'permission_id' => $download_2->get_id(),
			),
		);
		$downloads       = $data_store->get_downloads(
			array(
				'user_email' => $this->customer_email,
				'return'     => 'permission_id,user_email',
			)
		);
		$this->assertEquals( $expected_result, $downloads );
	}
}
