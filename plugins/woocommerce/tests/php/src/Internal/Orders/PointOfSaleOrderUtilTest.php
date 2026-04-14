<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * PointOfSaleOrderUtil test.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil
 */
class PointOfSaleOrderUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox is_pos_order returns correct value based on created_via property
	 */
	public function test_is_pos_order_returns_value_based_on_created_via_property() {
		$order = new WC_Order();

		$order->set_created_via( 'pos-rest-api' );
		$this->assertTrue( PointOfSaleOrderUtil::is_pos_order( $order ), 'Order created via POS REST API should be identified as POS order' );

		$order->set_created_via( 'checkout' );
		$this->assertFalse( PointOfSaleOrderUtil::is_pos_order( $order ), 'Order created via checkout should not be identified as POS order' );

		$order->set_created_via( 'admin' );
		$order->save();
		$this->assertFalse( PointOfSaleOrderUtil::is_pos_order( $order ), 'Order created via admin should not be identified as POS order' );

		$order->set_created_via( '' );
		$order->save();
		$this->assertFalse( PointOfSaleOrderUtil::is_pos_order( $order ), 'Order with empty created_via should not be identified as POS order' );
	}

	/**
	 * @testdox is_order_paid_at_pos returns true for POS-created orders.
	 */
	public function test_is_order_paid_at_pos_returns_true_for_pos_created_order(): void {
		$order = new WC_Order();
		$order->set_created_via( 'pos-rest-api' );
		$order->save();

		$this->assertTrue( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}

	/**
	 * @testdox is_order_paid_at_pos returns true for POS card reader payment.
	 */
	public function test_is_order_paid_at_pos_returns_true_for_card_reader_payment(): void {
		$order = new WC_Order();
		$order->set_created_via( 'bookings' );
		$order->update_meta_data( '_wcpay_ipp_channel', 'mobile_pos' );
		$order->save();

		$this->assertTrue( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}

	/**
	 * @testdox is_order_paid_at_pos returns true for cash payment at POS.
	 */
	public function test_is_order_paid_at_pos_returns_true_for_cash_payment(): void {
		$order = new WC_Order();
		$order->set_created_via( 'admin' );
		$order->update_meta_data( '_cash_change_amount', '5.00' );
		$order->save();

		$this->assertTrue( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}

	/**
	 * @testdox is_order_paid_at_pos returns true for cash payment with zero change.
	 */
	public function test_is_order_paid_at_pos_returns_true_for_zero_change(): void {
		$order = new WC_Order();
		$order->set_created_via( 'bookings' );
		$order->update_meta_data( '_cash_change_amount', '0' );
		$order->save();

		$this->assertTrue( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}

	/**
	 * @testdox is_order_paid_at_pos returns false for regular web orders.
	 */
	public function test_is_order_paid_at_pos_returns_false_for_regular_order(): void {
		$order = new WC_Order();
		$order->set_created_via( 'checkout' );
		$order->save();

		$this->assertFalse( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}

	/**
	 * @testdox is_order_paid_at_pos returns false for bookings order without POS payment.
	 */
	public function test_is_order_paid_at_pos_returns_false_for_bookings_without_pos(): void {
		$order = new WC_Order();
		$order->set_created_via( 'bookings' );
		$order->save();

		$this->assertFalse( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) );
	}
}
