<?php
/**
 * CheckoutEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks checkout events for fraud protection analysis.
 *
 * This class provides methods to track both WooCommerce Blocks (Store API) and traditional
 * shortcode checkout events for fraud protection event dispatching.
 * Event-specific data is passed to the dispatcher which handles session data collection internally.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutEventTracker {

	/**
	 * Fraud protection dispatcher instance.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private FraudProtectionDispatcher $dispatcher;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionDispatcher $dispatcher The fraud protection dispatcher instance.
	 */
	final public function init( FraudProtectionDispatcher $dispatcher ): void {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Track checkout page loaded event.
	 *
	 * Triggers fraud protection event dispatching when the checkout page is initially loaded.
	 * This captures the initial session state before any user interactions.
	 *
	 * @internal
	 * @return void
	 */
	public function track_checkout_page_loaded(): void {
		// Track the page load event. Session data will be collected by the dispatcher.
		$this->dispatcher->dispatch_event( 'checkout_page_loaded', array() );
	}

	/**
	 * Track Store API customer update event (WooCommerce Blocks checkout).
	 *
	 * Triggered when customer information is updated via the Store API endpoint
	 * /wc/store/v1/cart/update-customer during Blocks checkout flow.
	 *
	 * @internal
	 * @return void
	 */
	public function track_blocks_checkout_update(): void {
		// At this point we don't have any payment or shipping data, so we pass an empty array.
		$this->dispatcher->dispatch_event( 'checkout_update', array() );
	}

	/**
	 * Track shortcode checkout field update event.
	 *
	 * Triggered when checkout fields are updated via AJAX (woocommerce_update_order_review).
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function track_shortcode_checkout_field_update(): void {
		$this->dispatcher->dispatch_event( 'checkout_update', array() );
	}

	/**
	 * Track successful order placement.
	 *
	 * Called when an order is successfully placed, with or without payment.
	 * Works for both shortcode and Store API checkout flows.
	 *
	 * @internal
	 *
	 * @param int       $order_id The order ID.
	 * @param \WC_Order $order    The order object.
	 * @return void
	 */
	public function track_order_placed( int $order_id, \WC_Order $order ): void {
		$customer_id = $order->get_customer_id();
		$event_data  = array(
			'order_id'       => $order_id,
			'payment_method' => $order->get_payment_method(),
			'total'          => (float) $order->get_total(),
			'currency'       => $order->get_currency(),
			'customer_id'    => $customer_id ? $customer_id : 'guest',
			'status'         => $order->get_status(),
		);

		$this->dispatcher->dispatch_event( 'order_placed', $event_data );
	}
}
