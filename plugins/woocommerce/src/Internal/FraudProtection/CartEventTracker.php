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
	 * Fraud protection tracker instance.
	 *
	 * @var FraudProtectionTracker
	 */
	private FraudProtectionTracker $tracker;

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
	 * @param FraudProtectionTracker    $tracker                     The fraud protection tracker instance.
	 * @param SessionDataCollector      $data_collector              The session data collector instance.
	 * @param FraudProtectionController $fraud_protection_controller The fraud protection controller instance.
	 */
	final public function init(
		FraudProtectionTracker $tracker,
		SessionDataCollector $data_collector,
		FraudProtectionController $fraud_protection_controller
	): void {
		$this->tracker                     = $tracker;
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

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'cart_item_added', $event_data );
			$this->tracker->track_event( 'cart_item_added', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for cart event: %s | Error: %s',
					'cart_item_added',
					$e->getMessage()
				),
				array(
					'event_type' => 'cart_item_added',
					'exception'  => $e,
				)
			);
		}
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

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'cart_item_updated', $event_data );
			$this->tracker->track_event( 'cart_item_updated', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for cart event: %s | Error: %s',
					'cart_item_updated',
					$e->getMessage()
				),
				array(
					'event_type' => 'cart_item_updated',
					'exception'  => $e,
				)
			);
		}
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

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'cart_item_removed', $event_data );
			$this->tracker->track_event( 'cart_item_removed', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for cart event: %s | Error: %s',
					'cart_item_removed',
					$e->getMessage()
				),
				array(
					'event_type' => 'cart_item_removed',
					'exception'  => $e,
				)
			);
		}
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

		// Collect comprehensive session data.
		try {
			$collected_data = $this->data_collector->collect( 'cart_item_restored', $event_data );
			$this->tracker->track_event( 'cart_item_restored', $collected_data );
		} catch ( \Exception $e ) {
			// Log error but don't break functionality.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for cart event: %s | Error: %s',
					'cart_item_restored',
					$e->getMessage()
				),
				array(
					'event_type' => 'cart_item_restored',
					'exception'  => $e,
				)
			);
		}
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
}
