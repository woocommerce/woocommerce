<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CheckoutRequirements;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for CheckoutRequirements.
 *
 * @covers \Automattic\WooCommerce\StoreApi\Utilities\CheckoutRequirements
 */
class CheckoutRequirementsTest extends WC_Unit_Test_Case {

	/**
	 * Build an order containing the given products.
	 *
	 * @param \WC_Product ...$products Products to add as line items.
	 * @return WC_Order
	 */
	private function order_with( ...$products ): WC_Order {
		$order = new WC_Order();
		foreach ( $products as $product ) {
			$item = new WC_Order_Item_Product();
			$item->set_product( $product );
			$item->set_quantity( 1 );
			$order->add_item( $item );
		}
		$order->save();
		return $order;
	}

	/**
	 * @testdox requires_email returns false for an empty order.
	 */
	public function test_requires_email_false_for_empty_order(): void {
		$order = $this->order_with();

		$this->assertFalse( CheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
	}

	/**
	 * @testdox requires_email returns false when the order only has non-downloadable products.
	 */
	public function test_requires_email_false_for_simple_products(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->order_with( $product );

		$this->assertFalse( CheckoutRequirements::for_order( $order )->requires_email() );

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

		$order = $this->order_with( $product );

		$this->assertTrue( CheckoutRequirements::for_order( $order )->requires_email() );

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

		$order = $this->order_with( $simple, $downloadable );

		$this->assertTrue( CheckoutRequirements::for_order( $order )->requires_email() );

		$order->delete( true );
		$simple->delete( true );
		$downloadable->delete( true );
	}

	/**
	 * @testdox An extension can force requires_email on via the filter for a cart that otherwise needs no email.
	 */
	public function test_requires_email_filter_lets_extensions_opt_in(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->order_with( $product );

		$this->assertFalse(
			CheckoutRequirements::for_order( $order )->requires_email(),
			'Precondition: a plain simple product must not require an email.'
		);

		add_filter( 'woocommerce_store_api_checkout_requires_email', '__return_true' );
		$this->assertTrue(
			CheckoutRequirements::for_order( $order )->requires_email(),
			'An extension hooking the filter should be able to require an email.'
		);
		remove_filter( 'woocommerce_store_api_checkout_requires_email', '__return_true' );

		$order->delete( true );
		$product->delete( true );
	}
}
