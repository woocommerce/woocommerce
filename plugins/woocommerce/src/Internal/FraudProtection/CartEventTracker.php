<?php
/**
 * CartEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks cart events for fraud protection analysis.
 *
 * This class provides methods to track cart events (add, update, remove, restore)
 * for fraud protection event dispatching. Event-specific data is passed
 * to the dispatcher which handles session data collection internally.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CartEventTracker {

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
	 * Track cart page loaded event.
	 *
	 * Triggers fraud protection event dispatching when the cart page is initially loaded.
	 * This captures the initial session state before any user interactions.
	 *
	 * @internal
	 * @return void
	 */
	public function track_cart_page_loaded(): void {
		// Track the page load event. Session data will be collected by the dispatcher.
		$this->dispatcher->dispatch_event( 'cart_page_loaded', array() );
	}

	/**
	 * Track cart item added event.
	 *
	 * Triggers fraud protection event dispatching when an item is added to the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key  Cart item key.
	 * @param int    $product_id     Product ID.
	 * @param int    $quantity       Quantity added.
	 * @param int    $variation_id   Variation ID.
	 * @return void
	 */
	public function track_cart_item_added( $cart_item_key, $product_id, $quantity, $variation_id ): void {
		$event_data = $this->build_cart_event_data(
			'item_added',
			$product_id,
			$quantity,
			$variation_id
		);

		// Trigger event dispatching.
		$this->dispatcher->dispatch_event( 'cart_item_added', $event_data );
	}

	/**
	 * Track cart item quantity updated event.
	 *
	 * Triggers fraud protection event dispatching when cart item quantity is updated.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity      New quantity.
	 * @param int    $old_quantity  Old quantity.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function track_cart_item_updated( $cart_item_key, $quantity, $old_quantity, $cart ): void {
		$cart_item = $cart->cart_contents[ $cart_item_key ] ?? null;

		if ( (int) $quantity === (int) $old_quantity || ! $cart_item ) {
			return;
		}

		$product_id   = $cart_item['product_id'] ?? 0;
		$variation_id = $cart_item['variation_id'] ?? 0;

		$event_data = $this->build_cart_event_data(
			'item_updated',
			$product_id,
			(int) $quantity,
			$variation_id
		);

		// Add old quantity for context.
		$event_data['old_quantity'] = (int) $old_quantity;

		// Trigger event dispatching.
		$this->dispatcher->dispatch_event( 'cart_item_updated', $event_data );
	}

	/**
	 * Track cart item removed event.
	 *
	 * Triggers fraud protection event dispatching when an item is removed from the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function track_cart_item_removed( $cart_item_key, $cart ): void {
		$cart_item = $cart->removed_cart_contents[ $cart_item_key ] ?? null;

		if ( ! $cart_item ) {
			return;
		}

		$product_id   = $cart_item['product_id'] ?? 0;
		$variation_id = $cart_item['variation_id'] ?? 0;
		$quantity     = $cart_item['quantity'] ?? 0;

		$event_data = $this->build_cart_event_data(
			'item_removed',
			$product_id,
			$quantity,
			$variation_id
		);

		// Trigger event dispatching.
		$this->dispatcher->dispatch_event( 'cart_item_removed', $event_data );
	}

	/**
	 * Track cart item restored event.
	 *
	 * Triggers fraud protection event dispatching when a removed item is restored to the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function track_cart_item_restored( $cart_item_key, $cart ): void {
		$cart_item = $cart->cart_contents[ $cart_item_key ] ?? null;

		if ( ! $cart_item ) {
			return;
		}

		$product_id   = $cart_item['product_id'] ?? 0;
		$variation_id = $cart_item['variation_id'] ?? 0;
		$quantity     = $cart_item['quantity'] ?? 0;

		$event_data = $this->build_cart_event_data(
			'item_restored',
			$product_id,
			$quantity,
			$variation_id
		);

		// Trigger event dispatching.
		$this->dispatcher->dispatch_event( 'cart_item_restored', $event_data );
	}

	/**
	 * Build cart event-specific data.
	 *
	 * Prepares the cart event data including action type, product details,
	 * and current cart state. This data will be merged with comprehensive
	 * session data during event dispatching.
	 *
	 * @param string $action       Action type (item_added, item_updated, item_removed, item_restored).
	 * @param int    $product_id   Product ID.
	 * @param int    $quantity     Quantity.
	 * @param int    $variation_id Variation ID.
	 * @return array Cart event data.
	 */
	private function build_cart_event_data( string $action, int $product_id, int $quantity, int $variation_id ): array {
		$cart_item_count = 0;

		// Get current cart item count if cart is available.
		if ( WC()->cart instanceof \WC_Cart ) {
			$cart_item_count = WC()->cart->get_cart_contents_count();
		}

		return array(
			'action'          => $action,
			'product_id'      => $product_id,
			'quantity'        => $quantity,
			'variation_id'    => $variation_id,
			'cart_item_count' => $cart_item_count,
		);
	}
}
