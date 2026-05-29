<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\PosCheckoutRequirements;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for PosCheckoutRequirements.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PosCheckoutRequirements
 */
class PosCheckoutRequirementsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox requires_email returns false for an empty order.
	 */
	public function test_requires_email_false_for_empty_order(): void {
		$order = new WC_Order();
		$order->save();

		$this->assertFalse( PosCheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
	}

	/**
	 * @testdox requires_email returns false when the order only has non-downloadable products.
	 */
	public function test_requires_email_false_for_simple_products(): void {
		$product = WC_Helper_Product::create_simple_product();

		$order = new WC_Order();
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->save();

		$this->assertFalse( PosCheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox requires_email returns true when any line item is downloadable.
	 */
	public function test_requires_email_true_for_downloadable_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_downloadable( true );
		$product->save();

		$order = new WC_Order();
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->save();

		$this->assertTrue( PosCheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox requires_email returns true when one of many items is downloadable.
	 */
	public function test_requires_email_true_when_mixed_cart_includes_downloadable(): void {
		$simple       = WC_Helper_Product::create_simple_product();
		$downloadable = WC_Helper_Product::create_simple_product();
		$downloadable->set_downloadable( true );
		$downloadable->save();

		$order = new WC_Order();
		foreach ( array( $simple, $downloadable ) as $product ) {
			$item = new WC_Order_Item_Product();
			$item->set_product( $product );
			$item->set_quantity( 1 );
			$order->add_item( $item );
		}
		$order->save();

		$this->assertTrue( PosCheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
		$simple->delete( true );
		$downloadable->delete( true );
	}
}
