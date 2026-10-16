<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use WC_Unit_Test_Case;

/**
 * Tests for the PaymentMethodPreselection class.
 */
class PaymentMethodPreselectionTest extends WC_Unit_Test_Case {

	/**
	 * Put back the session choice the base class doesn't reset.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			WC()->cart->empty_cart();
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * @testdox Should forget the shopper's choice once the cart is emptied when a callback is attached, so the next purchase starts from the filter.
	 */
	public function test_emptying_the_cart_forgets_the_chosen_payment_method_with_a_callback(): void {
		add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		WC()->cart->empty_cart();

		$this->assertNull( WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * @testdox Should keep the shopper's choice after the cart is emptied without a callback, as before.
	 */
	public function test_emptying_the_cart_keeps_the_chosen_payment_method_without_a_callback(): void {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		WC()->cart->empty_cart();

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * @testdox Should forget the shopper's choice when they remove the last item from the cart and a callback is attached.
	 */
	public function test_removing_the_last_item_forgets_the_chosen_payment_method_with_a_callback(): void {
		add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );
		$first_key  = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		$second_key = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		WC()->cart->remove_cart_item( $first_key );
		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ), 'The choice stays while the cart still has items' );

		WC()->cart->remove_cart_item( $second_key );
		$this->assertNull( WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * @testdox Should keep the shopper's choice when they remove the last item from the cart without a callback, as before.
	 */
	public function test_removing_the_last_item_keeps_the_chosen_payment_method_without_a_callback(): void {
		$key = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		WC()->cart->remove_cart_item( $key );

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}
}
