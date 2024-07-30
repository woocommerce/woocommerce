<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Order functions tests
 *
 * @package WooCommerce\Tests\Order.
 */

/**
 * Class WC_Order_Functions_Test
 */
class WC_Order_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test that wc_restock_refunded_items() preserves order item stock metadata.
	 */
	public function test_wc_restock_refunded_items_stock_metadata() {
		// Create a product, with stock management enabled.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		// Place an order for the product, qty 2.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->calculate_totals();

		$checkout = WC_Checkout::instance();
		$order    = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$order->set_status( 'wc-processing' );
		$order->save();

		// Get the line item.
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Force a restock of one item.
		$refunded_items                         = array();
		$refunded_items[ $line_item->get_id() ] = array(
			'qty' => 1,
		);
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 1, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 1, (int) $line_item->get_meta( '_restock_refunded_items', true ) );

		// Force another restock of one item.
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 0, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 2, (int) $line_item->get_meta( '_restock_refunded_items', true ) );
	}

	/**
	 * Test update_total_sales_counts and check total_sales after order reflection.
	 *
	 * Tests the fix for issue #23796
	 */
	public function test_wc_update_total_sales_counts() {

		$product_id = WC_Helper_Product::create_simple_product()->get_id();

		WC()->cart->add_to_cart( $product_id );

		$order_id = WC_Checkout::instance()->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy',
			)
		);

		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		$order = new WC_Order( $order_id );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( 'cancelled' );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( 'completed' );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( 'refunded' );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		// Test trashing the order.
		$order->delete( false );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		// To successfully untrash, we need to grab a new instance of the order.
		wc_get_order( $order_id )->untrash();
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		// Test full deletion of the order (again, we need to grab a new instance of the order).
		wc_get_order( $order_id )->delete( true );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );
	}


	/**
	 * Test wc_update_coupon_usage_counts and check usage_count after order reflection.
	 *
	 * Tests the fix for issue #31245
	 */
	public function test_wc_update_coupon_usage_counts() {
		$coupon   = WC_Helper_Coupon::create_coupon( 'test' );
		$order_id = WC_Checkout::instance()->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy',
			)
		);

		$order = new WC_Order( $order_id );
		$order->apply_coupon( $coupon );

		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'cancelled' );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'pending' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'failed' );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'completed' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'refunded' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		// Test trashing the order.
		$order->delete( false );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		// To successfully untrash, we need to grab a new instance of the order.
		$order = wc_get_order( $order_id );
		$order->untrash();
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );
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
