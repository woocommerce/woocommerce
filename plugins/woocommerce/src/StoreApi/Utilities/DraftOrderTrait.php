<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * DraftOrderTrait
 *
 * Shared functionality for getting and setting draft order IDs from session.
 */
trait DraftOrderTrait {
	/**
	 * Gets draft order data from the customer session.
	 *
	 * @return integer
	 */
	protected function get_draft_order_id() {
		if ( ! wc()->session ) {
			wc()->initialize_session();
		}
		return wc()->session->get( 'store_api_draft_order', 0 );
	}

	/**
	 * Updates draft order data in the customer session.
	 *
	 * @param integer $order_id Draft order ID.
	 */
	protected function set_draft_order_id( $order_id ) {
		if ( ! wc()->session ) {
			wc()->initialize_session();
		}
		wc()->session->set( 'store_api_draft_order', $order_id );
	}

	/**
	 * Uses the draft order ID to return an order object, if valid.
	 *
	 * @return \WC_Order|null;
	 */
	protected function get_draft_order() {
		$draft_order_id = $this->get_draft_order_id();
		$draft_order    = $draft_order_id ? wc_get_order( $draft_order_id ) : false;

		return $this->is_valid_draft_order( $draft_order ) ? $draft_order : null;
	}

	/**
	 * Whether the passed argument is a draft order or an order that is
	 * pending/failed and the cart hasn't changed.
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return boolean Whether the order is valid as a draft order.
	 */
	protected function is_valid_draft_order( $order_object ) {
		if ( ! $order_object instanceof \WC_Order ) {
			return false;
		}

		// Draft orders are okay.
		if ( $order_object->has_status( 'checkout-draft' ) ) {
			return true;
		}

		// Pending and failed orders can be retried if the cart hasn't changed.
		if ( $order_object->needs_payment() && $order_object->has_cart_hash( wc()->cart->get_cart_hash() ) ) {
			return true;
		}

		// needs_payment() is always false for a zero total, so zero-total orders are checked separately.
		if ( $this->is_zero_total_order_awaiting_payment( $order_object ) && $order_object->has_cart_hash( wc()->cart->get_cart_hash() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether a zero-total order is still awaiting payment, such as a fully discounted renewal order.
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return bool
	 */
	private function is_zero_total_order_awaiting_payment( \WC_Order $order_object ): bool {
		if ( $order_object->get_total() > 0 ) {
			return false;
		}

		/**
		 * Filter the valid order statuses for payment.
		 *
		 * The same filter WC_Order::needs_payment() applies, so a zero-total order in a status the site declares payable can be resumed.
		 *
		 * @since 11.4.0
		 *
		 * @param array     $valid_order_statuses Array of valid order statuses for payment.
		 * @param \WC_Order $order                Order object.
		 */
		$payable_statuses = array_filter( (array) apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( OrderStatus::PENDING, OrderStatus::FAILED ), $order_object ) );

		return $order_object->has_status( $payable_statuses );
	}
}
