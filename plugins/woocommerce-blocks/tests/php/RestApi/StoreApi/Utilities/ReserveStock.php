<?php
/**
 * Utility Tests.
 *
 * @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\RestApi\StoreApi\Utilities;

use PHPUnit\Framework\TestCase;
use \WC_Helper_Order as OrderHelper;
use \WC_Helper_Product as ProductHelper;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\ReserveStock;

/**
 * ReserveStock Utility Tests.
 */
class ReserveStockTests extends TestCase {

	/**
	 * Test that stock is reserved for draft orders.
	 */
	public function test_reserve_stock_for_order() {
		$class = new ReserveStock();

		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock( 10 );
		$product->save();

		$order = OrderHelper::create_order( 1, $product ); // Note this adds 4 to the order.
		$order->set_status( 'checkout-draft' );
		$order->save();

		$result = $class->reserve_stock_for_order( $order );
		$this->assertTrue( $result );
		$this->assertEquals( 4, $this->get_reserved_stock_by_product_id( $product->get_stock_managed_by_id() ) );

		// Repeat.
		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'checkout-draft' );
		$order->save();

		$result = $class->reserve_stock_for_order( $order );
		$this->assertTrue( $result );
		$this->assertEquals( 8, $this->get_reserved_stock_by_product_id( $product->get_stock_managed_by_id() ) );

		// Repeat again - should not be enough stock for this.
		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'checkout-draft' );
		$order->save();

		$result = $class->reserve_stock_for_order( $order );
		$this->assertTrue( is_wp_error( $result ) );
	}

	/**
	 * Helper to get the count of reserved stock.
	 *
	 * @param integer $product_id
	 * @return integer
	 */
	protected function get_reserved_stock_by_product_id( $product_id ) {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( stock_table.`stock_quantity` ) FROM $wpdb->wc_reserved_stock stock_table WHERE stock_table.`product_id` = %d",
				$product_id
			)
		);
	}
}
