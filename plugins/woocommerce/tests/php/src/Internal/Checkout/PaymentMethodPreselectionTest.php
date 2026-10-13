<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use Automattic\WooCommerce\Internal\Checkout\PaymentMethodPreselection;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentMethodPreselection class.
 */
class PaymentMethodPreselectionTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preselect a payment method when the option has never been saved.
	 */
	public function test_is_enabled_by_default(): void {
		delete_option( PaymentMethodPreselection::OPTION_NAME );

		$this->assertTrue( PaymentMethodPreselection::is_enabled(), 'Existing stores must keep the current preselection behavior' );
	}

	/**
	 * @testdox Should follow the saved option value.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $option_value The saved option value.
	 * @param bool   $expected     Whether preselection should be on.
	 */
	public function test_is_enabled_follows_the_option( string $option_value, bool $expected ): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, $option_value );

		$this->assertSame( $expected, PaymentMethodPreselection::is_enabled() );
	}

	/**
	 * @testdox Should forget the shopper's choice once the cart is emptied when preselection is off, so the next purchase starts from nothing.
	 */
	public function test_emptying_the_cart_forgets_the_chosen_payment_method_when_preselection_is_off(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		try {
			WC()->cart->empty_cart();

			$this->assertNull( WC()->session->get( 'chosen_payment_method' ) );
		} finally {
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * @testdox Should keep the shopper's choice after the cart is emptied when preselection is on, as before.
	 */
	public function test_emptying_the_cart_keeps_the_chosen_payment_method_when_preselection_is_on(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'yes' );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		try {
			WC()->cart->empty_cart();

			$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
		} finally {
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * @testdox Should forget the shopper's choice when they remove the last item from the cart and preselection is off.
	 */
	public function test_removing_the_last_item_forgets_the_chosen_payment_method_when_preselection_is_off(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );
		$first_key  = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		$second_key = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		try {
			WC()->cart->remove_cart_item( $first_key );
			$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ), 'The choice stays while the cart still has items' );

			WC()->cart->remove_cart_item( $second_key );
			$this->assertNull( WC()->session->get( 'chosen_payment_method' ) );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * @testdox Should keep the shopper's choice when they remove the last item from the cart and preselection is on, as before.
	 */
	public function test_removing_the_last_item_keeps_the_chosen_payment_method_when_preselection_is_on(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'yes' );
		$key = WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
		WC()->session->set( 'chosen_payment_method', 'cod' );

		try {
			WC()->cart->remove_cart_item( $key );

			$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->set( 'chosen_payment_method', null );
		}
	}
}
