<?php

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Tests for the WC_User class.
 */
class WC_User_Functions_Tests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Setup COT.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * Clean COT specific things.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->clean_up_cot_setup();
	}

	/**
	 * Test wc_get_customer_order_count. Borrowed from `WC_Tests_Customer_Functions` class for COT.
	 */
	public function test_hpos_wc_customer_bought_product() {
		$this->toggle_cot_feature_and_usage( true );
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );
		$product_1     = new WC_Product_Simple();
		$product_1->save();
		$product_id_1 = $product_1->get_id();
		$product_2    = new WC_Product_Simple();
		$product_2->save();
		$product_id_2 = $product_2->get_id();

		$order_1 = WC_Helper_Order::create_order( $customer_id_1, $product_1 );
		$order_1->set_billing_email( 'test@example.com' );
		$order_1->set_status( 'completed' );
		$order_1->save();
		$order_2 = WC_Helper_Order::create_order( $customer_id_2, $product_2 );
		$order_2->set_billing_email( 'test2@example.com' );
		$order_2->set_status( 'completed' );
		$order_2->save();
		$order_3 = WC_Helper_Order::create_order( $customer_id_1, $product_2 );
		$order_3->set_billing_email( 'test@example.com' );
		$order_3->set_status( 'pending' );
		$order_3->save();
		$order_4 = wc_create_order();
		$order_4->add_product( $product_1 );
		$order_4->set_status( 'completed' );
		$order_4->save();

		$this->assertTrue( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_1 ) );
		$this->assertTrue( wc_customer_bought_product( '', $customer_id_1, $product_id_1 ) );
		$this->assertTrue( wc_customer_bought_product( 'test@example.com', 0, $product_id_1 ) );
		$this->assertFalse( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_2 ) );
		$this->assertFalse( wc_customer_bought_product( 'test2@example.com', $customer_id_2, $product_id_1 ) );
	}

	/**
	 * Test test_wc_get_customer_available_downloads_for_partial_refunds.
	 *
	 * @since 9.3
	 */
	public function test_wc_get_customer_available_downloads_for_partial_refunds(): void {
		/** @var Download_Directories $download_directories */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );
		$download_directories->add_approved_directory( 'https://always.trusted' );

		$test_file = 'https://always.trusted/123.pdf';

		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$prod_download = new WC_Product_Download();
		$prod_download->set_file( $test_file );
		$prod_download->set_id( 1 );

		$prod_download2 = new WC_Product_Download();
		$prod_download2->set_file( $test_file );
		$prod_download2->set_id( 2 );

		$product = new WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->save();

		$product2 = new WC_Product_Simple();
		$product2->set_regular_price( 20 );
		$product2->set_downloadable( 'yes' );
		$product2->set_downloads( array( $prod_download2 ) );
		$product2->save();

		$order = new WC_Order();
		$order->set_customer_id( $customer_id );

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_order_id( $order->get_id() );
		$item->set_total( 10 );
		$item->save();
		$order->add_item( $item );

		$item2 = new WC_Order_Item_Product();
		$item2->set_product( $product2 );
		$item2->set_order_id( $order->get_id() );
		$item->set_total( 20 );
		$item2->save();
		$order->add_item( $item2 );

		$order->set_total( 30 ); // 10 + 20
		$order->set_status( 'completed' );
		$order->save();

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$item->get_id() => array(
					'qty'          => 1,
					'refund_total' => 10,
				),
			),
		);

		wc_create_refund( $args );

		$downloads = wc_get_customer_available_downloads( $customer_id );
		$this->assertEquals( 1, count( $downloads ) );

		$download = current( $downloads );
		$this->assertEquals( $prod_download2->get_id(), $download['download_id'] );
		$this->assertEquals( $order->get_id(), $download['order_id'] );
		$this->assertEquals( $product2->get_id(), $download['product_id'] );
	}
}
