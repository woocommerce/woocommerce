<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutEmailPolicy;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Checkout Email policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutEmailPolicy
 */
class CheckoutEmailPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutEmailPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CheckoutEmailPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_store_api_require_billing_email', array( $this->sut, 'require_when_cart_needs_email' ), 10 );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the cart-aware email rule inside POS context.
	 */
	public function test_register_attaches_filter_in_pos_context(): void {
		Context::set_test_override( true );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_require_billing_email', array( $this->sut, 'require_when_cart_needs_email' ) ) );
	}

	/**
	 * @testdox register() does not attach the filter outside POS context.
	 */
	public function test_register_skips_filter_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertFalse( has_filter( 'woocommerce_store_api_require_billing_email', array( $this->sut, 'require_when_cart_needs_email' ) ) );
	}

	/**
	 * @testdox require_when_cart_needs_email returns false when the order has no items needing an email.
	 */
	public function test_returns_false_when_cart_does_not_need_email(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->make_order_with( $product );

		$this->assertFalse( $this->sut->require_when_cart_needs_email( true, $order ) );

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox require_when_cart_needs_email returns true when the cart contains a downloadable product.
	 */
	public function test_returns_true_when_cart_contains_downloadable(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_downloadable( true );
		$product->save();
		$order = $this->make_order_with( $product );

		$this->assertTrue( $this->sut->require_when_cart_needs_email( true, $order ) );

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox require_when_cart_needs_email respects an upstream filter that already opted out.
	 */
	public function test_respects_upstream_opt_out(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_downloadable( true );
		$product->save();
		$order = $this->make_order_with( $product );

		$this->assertFalse(
			$this->sut->require_when_cart_needs_email( false, $order ),
			'If an upstream filter has already returned false, we must not re-impose the requirement.'
		);

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * Build a one-item order around the given product.
	 *
	 * @param \WC_Product $product Product to add as a single-quantity line item.
	 * @return WC_Order
	 */
	private function make_order_with( $product ): WC_Order {
		$order = new WC_Order();
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->save();
		return $order;
	}
}
