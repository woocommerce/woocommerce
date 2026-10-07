<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Checkout\PaymentRecovery;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentRecovery class.
 */
class PaymentRecoveryTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * @testdox Should treat every status a gateway cannot leave an order awaiting payment in as past payment.
	 *
	 * @testWith ["processing", true]
	 *           ["completed", true]
	 *           ["on-hold", true]
	 *           ["pending", false]
	 *           ["failed", false]
	 *           ["checkout-draft", false]
	 *           ["cancelled", false]
	 *           ["refunded", false]
	 *
	 * @param string $status   Status to leave the order in.
	 * @param bool   $expected Whether the order counts as moved past payment.
	 */
	public function test_order_moved_past_payment_decides_by_status( string $status, bool $expected ): void {
		$order = new WC_Order();
		$order->set_status( $status );
		$order->save();

		$this->assertSame(
			$expected,
			PaymentRecovery::order_moved_past_payment( $order ),
			sprintf( 'An order in %s should%s count as moved past payment.', $status, $expected ? '' : ' not' )
		);
	}

	/**
	 * @testdox Should leave an order awaiting payment when the site declares its status payable.
	 */
	public function test_order_moved_past_payment_respects_the_payable_statuses_filter(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::ON_HOLD );
		$order->save();

		$declare_on_hold_payable = function ( $statuses ) {
			$statuses[] = OrderStatus::ON_HOLD;
			return $statuses;
		};

		add_filter( 'woocommerce_valid_order_statuses_for_payment', $declare_on_hold_payable );

		try {
			$this->assertFalse(
				PaymentRecovery::order_moved_past_payment( $order ),
				'A status the site declares payable is still awaiting payment.'
			);
		} finally {
			remove_filter( 'woocommerce_valid_order_statuses_for_payment', $declare_on_hold_payable );
		}
	}

	/**
	 * @testdox Should read the order again so a status the gateway set on its own instance is seen.
	 */
	public function test_refresh_order_returns_the_stored_order(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$stale = wc_get_order( $order->get_id() );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$refreshed = PaymentRecovery::refresh_order( $stale );

		$this->assertSame( OrderStatus::PROCESSING, $refreshed->get_status(), 'The refreshed order carries the stored status.' );
	}

	/**
	 * @testdox Should hand back the order it was given when the order can no longer be read.
	 */
	public function test_refresh_order_falls_back_to_the_given_order(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$order->delete( true );

		$this->assertSame( $order, PaymentRecovery::refresh_order( $order ), 'A deleted order is handed back unchanged.' );
	}

	/**
	 * @testdox Should remember the order sent to the gateway under a key payment_complete() leaves alone.
	 */
	public function test_the_order_sent_to_gateway_outlives_payment_complete(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		WC()->session->set( 'order_awaiting_payment', $order->get_id() );

		PaymentRecovery::remember_order_sent_to_gateway( $order->get_id() );
		$order->payment_complete( 'txn_1' );

		$this->assertFalse( WC()->session->get( 'order_awaiting_payment' ), 'payment_complete() clears the older pointer, which is the gap this key closes.' );
		$this->assertSame( $order->get_id(), WC()->session->get( 'order_sent_to_gateway' ), 'The order sent to the gateway is still known after payment_complete().' );
	}

	/**
	 * @testdox Should forget the order sent to the gateway once the cart is emptied.
	 */
	public function test_emptying_the_cart_forgets_the_order_sent_to_gateway(): void {
		$order = new WC_Order();
		$order->save();
		PaymentRecovery::remember_order_sent_to_gateway( $order->get_id() );

		WC()->cart->empty_cart();

		$this->assertEmpty( WC()->session->get( 'order_sent_to_gateway' ), 'An emptied cart has nothing left to place twice.' );
	}

	/**
	 * @testdox Should find the order sent to the gateway only once it moved past payment.
	 *
	 * @testWith ["processing", true]
	 *           ["on-hold", true]
	 *           ["pending", false]
	 *           ["failed", false]
	 *
	 * @param string $status   Status the order is left in.
	 * @param bool   $is_found Whether the order should be found as moved past payment.
	 */
	public function test_session_order_moved_past_payment_decides_by_status( string $status, bool $is_found ): void {
		$order = $this->order_for_the_current_cart( $status );
		PaymentRecovery::remember_order_sent_to_gateway( $order->get_id() );

		$found = PaymentRecovery::get_session_order_moved_past_payment();

		$this->assertSame( $is_found ? $order->get_id() : null, $found ? $found->get_id() : null, sprintf( 'An order in %s should%s be found.', $status, $is_found ? '' : ' not' ) );
	}

	/**
	 * @testdox Should not find the order sent to the gateway once the cart holds something else.
	 */
	public function test_session_order_moved_past_payment_ignores_a_different_cart(): void {
		$order = $this->order_for_the_current_cart( OrderStatus::PROCESSING );
		PaymentRecovery::remember_order_sent_to_gateway( $order->get_id() );

		WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );

		$this->assertNull( PaymentRecovery::get_session_order_moved_past_payment(), 'A changed cart is a new purchase, not a repeat of the old one.' );
	}

	/**
	 * @testdox Should fall back to the order the caller names when nothing was remembered.
	 */
	public function test_session_order_moved_past_payment_falls_back_to_the_given_order(): void {
		$order = $this->order_for_the_current_cart( OrderStatus::PROCESSING );

		$found = PaymentRecovery::get_session_order_moved_past_payment( $order->get_id() );

		$this->assertSame( $order->get_id(), $found ? $found->get_id() : null, 'A session written before the key existed still points at the order.' );
	}

	/**
	 * @testdox Should note on the order that a repeat submit was answered without a second order.
	 */
	public function test_record_repeat_submit_notes_the_order(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		PaymentRecovery::record_repeat_submit( $order );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertStringContainsString( 'No second order was created', $notes[0]->content, 'The merchant should see that the shopper submitted again and was not charged twice.' );
	}

	/**
	 * @testdox Should log the failure under the caller's source and note it on the order.
	 */
	public function test_record_failure_after_payment_logs_and_notes_the_failure(): void {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$error = new \Error( 'Call to a member function push_order() on null' );

		PaymentRecovery::record_failure_after_payment( $order, $error, 'checkout' );

		$this->assertLogged( 'error', sprintf( 'Checkout for order #%d failed after it moved past the payment step', $order->get_id() ) );
		$this->assertLogged( 'error', 'push_order() on null' );
		$this->assertLogged( 'error', __FILE__ );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->assertStringContainsString(
			'Checkout could not be completed after the order moved past the payment step. The order keeps the status the gateway set. Error: Call to a member function push_order() on null',
			$notes[0]->content,
			'The merchant is told what failed and why the order kept its status.'
		);
	}

	/**
	 * @testdox Should empty the cart the gateway left behind when it still holds this order.
	 */
	public function test_record_failure_after_payment_empties_a_cart_that_matches_the_order(): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();

		PaymentRecovery::record_failure_after_payment( $order, new \Error( 'boom' ), 'checkout' );

		$this->assertTrue( WC()->cart->is_empty(), 'The cart that produced the order is emptied.' );
	}

	/**
	 * @testdox Should leave a cart holding something else alone, as a second tab would.
	 */
	public function test_record_failure_after_payment_leaves_an_unrelated_cart_alone(): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_cart_hash( 'a-different-cart' );
		$order->save();

		PaymentRecovery::record_failure_after_payment( $order, new \Error( 'boom' ), 'checkout' );

		$this->assertFalse( WC()->cart->is_empty(), 'A cart holding another order is left as it is.' );
	}

	/**
	 * Fills the cart with one product and saves an order in the given status that the cart produced.
	 *
	 * @param string $status Status to save the order in.
	 * @return WC_Order
	 */
	private function order_for_the_current_cart( string $status ): WC_Order {
		WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );

		$order = new WC_Order();
		$order->set_status( $status );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();

		return $order;
	}
}
