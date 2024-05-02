<?php
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
		$refunded_items = array();
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

		$coupon = WC_Helper_Coupon::create_coupon( 'test' );
		$order_id = WC_Checkout::instance()->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy',
			)
		);
		
		$order = new WC_Order( $order_id );
		$order->apply_coupon($coupon);
		
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );
	
		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'cancelled' );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'pending' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'failed' );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'completed' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'refunded' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		$order->update_status( 'processing' );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );

		// Test trashing the order.
		$order->delete( false );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon('test') )->get_usage_count() );

		// To successfully untrash, we need to grab a new instance of the order.
		$order = wc_get_order( $order_id );
		$order->untrash();
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon('test') )->get_usage_count() );
	}

}
