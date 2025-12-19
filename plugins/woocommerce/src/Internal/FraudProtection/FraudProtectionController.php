<?php
/**
 * FraudProtectionController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Main controller for fraud protection features.
 *
 * This class orchestrates all fraud protection components and ensures
 * zero-impact when the feature flag is disabled.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class FraudProtectionController implements RegisterHooksInterface {

	/**
	 * Features controller instance.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $session_data_collector;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 *
	 * @param FeaturesController   $features_controller     The instance of FeaturesController to use.
	 * @param SessionDataCollector $session_data_collector The instance of SessionDataCollector to use.
	 */
	final public function init( FeaturesController $features_controller, SessionDataCollector $session_data_collector ): void {
		$this->features_controller     = $features_controller;
		$this->session_data_collector = $session_data_collector;
	}

	/**
	 * Hook into WordPress on init.
	 *
	 * @internal
	 */
	public function on_init(): void {
		// Bail if the feature is not enabled.
		if ( ! $this->feature_is_enabled() ) {
			return;
		}

		// Register testing hooks to validate SessionDataCollector.
		// These hooks collect and log session data at key events for debugging.
		// TODO: Remove or refactor these hooks when moving to production.

		// Cart events (traditional + blocks).
		add_action( 'woocommerce_add_to_cart', array( $this, 'log_cart_item_added' ), 10, 6 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'log_cart_item_updated' ), 10, 4 );
		add_action( 'woocommerce_remove_cart_item', array( $this, 'log_cart_item_removed' ), 10, 2 );
		add_action( 'woocommerce_store_api_validate_add_to_cart', array( $this, 'log_blocks_cart_item_added' ), 10, 2 );
		add_action( 'woocommerce_store_api_cart_update_order_from_request', array( $this, 'log_blocks_cart_updated' ), 10, 2 );

		// Checkout events (traditional + blocks).
		add_action( 'woocommerce_before_checkout_form', array( $this, 'log_checkout_started' ), 10, 0 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'log_checkout_order_created' ), 10, 1 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'log_checkout_order_processed' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'log_blocks_checkout_started' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'log_blocks_checkout_order_processed' ), 10, 1 );

		// Payment events.
		add_action( 'woocommerce_payment_complete', array( $this, 'log_payment_complete' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'log_payment_failed' ), 10, 1 );

		// Customer/session events.
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'log_customer_updated' ), 10, 2 );
	}

	/**
	 * Check if fraud protection feature is enabled.
	 *
	 * This method can be used by other fraud protection classes to check
	 * the feature flag status.
	 *
	 * @return bool True if enabled.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'fraud_protection' );
	}

	/**
	 * Cart item added event handler.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product ID.
	 * @param int    $quantity      Quantity added.
	 * @param int    $variation_id  Variation ID.
	 * @param array  $variation     Variation data.
	 * @param array  $cart_item_data Cart item data.
	 */
	public function log_cart_item_added( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ): void {
		$this->collect_and_log_session_data(
			'cart_item_added',
			array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			)
		);
	}

	/**
	 * Cart item quantity updated event handler.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity      New quantity.
	 * @param int    $old_quantity  Old quantity.
	 * @param object $cart          Cart object.
	 */
	public function log_cart_item_updated( $cart_item_key, $quantity, $old_quantity, $cart ): void {
		$this->collect_and_log_session_data(
			'cart_item_updated',
			array(
				'quantity'     => $quantity,
				'old_quantity' => $old_quantity,
			)
		);
	}

	/**
	 * Cart item removed event handler.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart          Cart object.
	 */
	public function log_cart_item_removed( $cart_item_key, $cart ): void {
		$this->collect_and_log_session_data( 'cart_item_removed', array() );
	}

	/**
	 * Blocks cart item added event handler (Store API).
	 *
	 * @param object $product Product object.
	 * @param object $request Request object.
	 */
	public function log_blocks_cart_item_added( $product, $request ): void {
		$product_id = $product->get_id();
		$this->collect_and_log_session_data(
			'blocks_cart_item_added',
			array( 'product_id' => $product_id )
		);
	}

	/**
	 * Blocks cart updated event handler (Store API).
	 *
	 * @param object $draft_order Draft order object.
	 * @param object $request     Request object.
	 */
	public function log_blocks_cart_updated( $draft_order, $request ): void {
		$this->collect_and_log_session_data( 'blocks_cart_updated', array() );
	}

	/**
	 * Checkout started event handler (traditional checkout).
	 */
	public function log_checkout_started(): void {
		$this->collect_and_log_session_data( 'checkout_started', array() );
	}

	/**
	 * Blocks checkout started event handler (Store API).
	 *
	 * @param object $order Order object.
	 */
	public function log_blocks_checkout_started( $order ): void {
		$this->collect_and_log_session_data(
			'blocks_checkout_started',
			array( 'order_id' => $order->get_id() )
		);
	}

	/**
	 * Checkout order created event handler.
	 *
	 * @param int $order_id Order ID.
	 */
	public function log_checkout_order_created( $order_id ): void {
		$this->collect_and_log_session_data(
			'checkout_order_created',
			array( 'order_id' => $order_id )
		);
	}

	/**
	 * Checkout order processed event handler (traditional checkout).
	 *
	 * @param int $order_id Order ID.
	 */
	public function log_checkout_order_processed( $order_id ): void {
		$this->collect_and_log_session_data(
			'checkout_order_processed',
			array( 'order_id' => $order_id )
		);
	}

	/**
	 * Blocks checkout order processed event handler (Store API).
	 *
	 * @param object $order Order object.
	 */
	public function log_blocks_checkout_order_processed( $order ): void {
		$this->collect_and_log_session_data(
			'blocks_checkout_order_processed',
			array( 'order_id' => $order->get_id() )
		);
	}

	/**
	 * Payment complete event handler.
	 *
	 * @param int $order_id Order ID.
	 */
	public function log_payment_complete( $order_id ): void {
		$this->collect_and_log_session_data(
			'payment_complete',
			array( 'order_id' => $order_id )
		);
	}

	/**
	 * Payment failed event handler.
	 *
	 * @param int $order_id Order ID.
	 */
	public function log_payment_failed( $order_id ): void {
		$this->collect_and_log_session_data(
			'payment_failed',
			array( 'order_id' => $order_id )
		);
	}

	/**
	 * Customer updated event handler.
	 *
	 * @param object $customer Customer object.
	 * @param array  $data     Customer data.
	 */
	public function log_customer_updated( $customer, $data ): void {
		$this->collect_and_log_session_data( 'customer_updated', array() );
	}

	/**
	 * Collect session data and log it for debugging.
	 *
	 * Helper method that uses SessionDataCollector to gather data and logs
	 * the result for validation and debugging purposes.
	 *
	 * @param string $event_type Event type identifier.
	 * @param array  $event_data Event-specific context data.
	 */
	private function collect_and_log_session_data( string $event_type, array $event_data ): void {
		try {
			$collected_data = $this->session_data_collector->collect( $event_type, $event_data );

			self::log(
				'debug',
				sprintf( 'Session data collected for event: %s', $event_type ),
				array(
					'event_type' => $event_type,
					'event_data' => $event_data,
					'collected'  => $collected_data,
				)
			);
		} catch ( \Exception $e ) {
			self::log(
				'error',
				sprintf( 'Failed to collect session data for event: %s - %s', $event_type, $e->getMessage() ),
				array(
					'event_type' => $event_type,
					'error'      => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Log helper method for consistent logging across all fraud protection components.
	 *
	 * This static method ensures all fraud protection logs are written with
	 * the same 'woo-fraud-protection' source for easy filtering in WooCommerce logs.
	 *
	 * @param string $level   Log level (emergency, alert, critical, error, warning, notice, info, debug).
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 *
	 * @return void
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		wc_get_logger()->log(
			$level,
			$message,
			array_merge( $context, array( 'source' => 'woo-fraud-protection' ) )
		);
	}
}
