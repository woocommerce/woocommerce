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
}
