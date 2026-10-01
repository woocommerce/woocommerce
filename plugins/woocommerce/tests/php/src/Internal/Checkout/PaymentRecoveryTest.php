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
			'Checkout could not be completed after the order moved past the payment step. The order keeps the status the gateway set: Call to a member function push_order() on null',
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
}
