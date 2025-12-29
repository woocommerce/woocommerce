<?php
/**
 * CartEventTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks cart events for fraud protection analysis.
 *
 * This class hooks into WooCommerce cart events (add, update, remove, restore)
 * and triggers comprehensive event tracking with full session context. It orchestrates
 * the event tracking by collecting session data and preparing it for the fraud
 * protection service.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CartEventTracker implements RegisterHooksInterface {

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

	/**
	 * Fraud protection controller instance.
	 *
	 * @var FraudProtectionController
	 */
	private FraudProtectionController $fraud_protection_controller;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionDataCollector      $data_collector              The session data collector instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		SessionDataCollector $data_collector,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->data_collector              = $data_collector;
		$this->fraud_protection_controller = $fraud_protection_controller;
	}

	/**
	 * Register cart event hooks.
	 *
	 * Hooks into WooCommerce cart actions to track fraud protection events.
	 * Only registers hooks if the fraud protection feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only register hooks if fraud protection is enabled.
		if ( ! $this->fraud_protection_controller->feature_is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_track_cart_item_added' ), 10, 6 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'handle_track_cart_item_updated' ), 10, 4 );
		add_action( 'woocommerce_remove_cart_item', array( $this, 'handle_track_cart_item_removed' ), 10, 2 );
		add_action( 'woocommerce_restore_cart_item', array( $this, 'handle_track_cart_item_restored' ), 10, 2 );
	}

	/**
	 * Handle cart item added event.
	 *
	 * Triggers fraud protection event tracking when an item is added to the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key  Cart item key.
	 * @param int    $product_id     Product ID.
	 * @param int    $quantity       Quantity added.
	 * @param int    $variation_id   Variation ID.
	 * @param array  $variation      Variation data.
	 * @param array  $cart_item_data Cart item data.
	 * @return void
	 */
	public function handle_track_cart_item_added( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ): void {
		$event_data = $this->build_cart_event_data(
			'item_added',
			$product_id,
			$quantity,
			$variation_id
		);

		$this->track_event( 'cart_item_added', $event_data );
	}

	/**
	 * Handle cart item quantity updated event.
	 *
	 * Triggers fraud protection event tracking when cart item quantity is updated.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity      New quantity.
	 * @param int    $old_quantity  Old quantity.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_updated( $cart_item_key, $quantity, $old_quantity, $cart ): void {
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

		$this->track_event( 'cart_item_updated', $event_data );
	}

	/**
	 * Handle cart item removed event.
	 *
	 * Triggers fraud protection event tracking when an item is removed from the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_removed( $cart_item_key, $cart ): void {
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

		$this->track_event( 'cart_item_removed', $event_data );
	}

	/**
	 * Handle cart item restored event.
	 *
	 * Triggers fraud protection event tracking when a removed item is restored to the cart.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart          Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_restored( $cart_item_key, $cart ): void {
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

		$this->track_event( 'cart_item_restored', $event_data );
	}

	/**
	 * Build cart event-specific data.
	 *
	 * Prepares the cart event data including action type, product details,
	 * and current cart state. This data will be merged with comprehensive
	 * session data during event tracking.
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

	/**
	 * Track fraud protection event with comprehensive session context.
	 *
	 * This method orchestrates the event tracking by:
	 * 1. Collecting comprehensive session data via SessionDataCollector
	 * 2. Merging with event-specific data
	 * 3. Logging the event (will call EventTracker/API client once available)
	 *
	 * The method implements graceful degradation - any errors during tracking
	 * will be logged but will not break the cart functionality.
	 *
	 * @param string $event_type          Event type identifier (e.g., 'cart_item_added').
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	private function track_event( string $event_type, array $event_specific_data ): void {
		try {
			// Collect comprehensive session data.
			$session_data = $this->data_collector->collect( $event_type, $event_specific_data );

			// Once EventTracker/API client is implemented (WOOSUBS-1249), call it here:
			// $event_tracker = wc_get_container()->get( EventTracker::class );
			// $event_tracker->track( $event_type, $session_data );
			//
			// For now, log the event for debugging and verification.
			FraudProtectionController::log(
				'info',
				sprintf(
					'Fraud protection event tracked: %s | Product ID: %s | Quantity: %s | Session ID: %s',
					$event_type,
					$event_specific_data['product_id'] ?? 'N/A',
					$event_specific_data['quantity'] ?? 'N/A',
					$session_data['session']['session_id'] ?? 'N/A'
				),
				array(
					'event_type'   => $event_type,
					'event_data'   => $event_specific_data,
					'session_data' => $session_data,
				)
			);
		} catch ( \Exception $e ) {
			// Gracefully handle errors - fraud protection should never break the cart.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to track fraud protection event: %s | Error: %s',
					$event_type,
					$e->getMessage()
				),
				array(
					'event_type' => $event_type,
					'exception'  => $e,
				)
			);
		}
	}
}
