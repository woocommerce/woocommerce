<?php
/**
 * Order functions tests
 *
 * @package WooCommerce\Tests\Order.
 */

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Order_Functions_Test
 */
class WC_Order_Functions_Test extends \WC_Unit_Test_Case {
	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

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
		$order->set_status( OrderInternalStatus::PROCESSING );
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

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::CANCELLED );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::COMPLETED );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::REFUNDED );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::PROCESSING );
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

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::CANCELLED );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PENDING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::FAILED );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::COMPLETED );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::REFUNDED );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
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
	 * Test getting total refunded for an item with and without refunds.
	 */
	public function test_get_total_refunded_for_item() {
		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 99.99 );
		$product->save();

		// Create an order with the product.
		$order = new WC_Order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'total'    => 199.98,
			)
		);
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		// Get the item ID.
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Test that by default there is no refund.
		$this->assertEquals( 0, $order->get_total_refunded_for_item( $item_id ) );

		// Create first partial refund for 1 item.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 49.99,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0.5,
						'refund_total' => 49.99,
					),
				),
			)
		);

		// Verify the refunded amount for the item after first refund.
		$this->assertEquals( 49.99, $order->get_total_refunded_for_item( $item_id ) );

		// Create second partial refund for remaining amount.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 149.99,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1.5,
						'refund_total' => 149.99,
					),
				),
			)
		);

		// Verify the total refunded amount for the item after both refunds.
		$this->assertEquals( 199.98, $order->get_total_refunded_for_item( $item_id ) );
	}

	/**
	 * Test that creating a full refund with free items triggers fully refunded action.
	 */
	public function test_full_refund_with_free_items() {
		// Create a paid product.
		$paid_product = WC_Helper_Product::create_simple_product();
		$paid_product->set_regular_price( 10 );
		$paid_product->save();

		// Create a free product.
		$free_product = WC_Helper_Product::create_simple_product();
		$free_product->set_regular_price( 0 );
		$free_product->save();

		// Create an order with both products.
		$order = new WC_Order();

		// Add paid product.
		$paid_item = new WC_Order_Item_Product();
		$paid_item->set_props(
			array(
				'product'  => $paid_product,
				'quantity' => 1,
				'total'    => 10,
			)
		);
		$order->add_item( $paid_item );

		// Add free product.
		$free_item = new WC_Order_Item_Product();
		$free_item->set_props(
			array(
				'product'  => $free_product,
				'quantity' => 1,
				'total'    => 0,
			)
		);
		$order->add_item( $free_item );

		$order->calculate_totals();
		$order->save();

		// Track if fully refunded action was triggered.
		$fully_refunded_triggered = false;
		add_action(
			'woocommerce_order_fully_refunded',
			function () use ( &$fully_refunded_triggered ) {
				$fully_refunded_triggered = true;
			}
		);

		// Track if partially refunded action was triggered.
		$partially_refunded_triggered = false;
		add_action(
			'woocommerce_order_partially_refunded',
			function () use ( &$partially_refunded_triggered ) {
				$partially_refunded_triggered = true;
			}
		);

		// Create a full refund.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => $order->get_total(),
				'reason'   => 'Testing refund with free items',
			)
		);

		$this->assertNotWPError( $refund, 'Refund should be created successfully' );

		// Verify that fully refunded action was triggered and partially refunded was not.
		$this->assertTrue( $fully_refunded_triggered, 'Fully refunded action should be triggered' );
		$this->assertFalse( $partially_refunded_triggered, 'Partially refunded action should not be triggered' );
	}
}
