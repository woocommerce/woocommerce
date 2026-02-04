<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Order version string invalidation handler.
 *
 * This class provides an 'invalidate' method that will invalidate
 * the version string for a given order, which in turn invalidates
 * any cached REST API responses containing that order.
 */
class OrdersVersionStringInvalidator {

	/**
	 * Stores the customer ID of orders before they are saved.
	 * Used to detect customer changes that require list invalidation.
	 *
	 * @var array<int, int> Order ID => Customer ID
	 */
	private array $pre_save_customer_ids = array();

	/**
	 * Initialize the invalidator and register hooks.
	 *
	 * Hooks are only registered when both conditions are met:
	 * - The REST API caching feature is enabled
	 * - The backend caching setting is active
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	final public function init(): void {
		// We can't use FeaturesController::feature_is_enabled at this point
		// (before the 'init' action is triggered) because that would cause
		// "Translation loading for the woocommerce domain was triggered too early" warnings.
		if ( 'yes' !== get_option( 'woocommerce_feature_rest_api_caching_enabled' ) ) {
			return;
		}

		if ( 'yes' === get_option( 'woocommerce_rest_api_enable_backend_caching', 'no' ) ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register all order-related hooks.
	 *
	 * Only WooCommerce hooks are registered (not WordPress post hooks) since these always fire
	 * when an order is created/updated/deleted via the WooCommerce APIs, regardless of HPOS
	 * being active or not.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Hook to capture customer ID before save for change detection.
		add_action( 'woocommerce_before_order_object_save', array( $this, 'handle_before_order_save' ), 10, 1 );

		// WooCommerce CRUD hooks for orders.
		add_action( 'woocommerce_new_order', array( $this, 'handle_woocommerce_new_order' ), 10, 2 );
		add_action( 'woocommerce_update_order', array( $this, 'handle_woocommerce_update_order' ), 10, 2 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'handle_woocommerce_before_delete_order' ), 10, 2 );
		add_action( 'woocommerce_trash_order', array( $this, 'handle_woocommerce_trash_order' ), 10, 1 );
		add_action( 'woocommerce_untrash_order', array( $this, 'handle_woocommerce_untrash_order' ), 10, 2 );

		// Status change hook.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_woocommerce_order_status_changed' ), 10, 4 );

		// Refund hooks.
		add_action( 'woocommerce_order_refunded', array( $this, 'handle_woocommerce_order_refunded' ), 10, 2 );
		add_action( 'woocommerce_refund_deleted', array( $this, 'handle_woocommerce_refund_deleted' ), 10, 2 );
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Handle the woocommerce_before_order_object_save hook.
	 *
	 * Captures the customer ID before save to detect changes.
	 *
	 * @param \WC_Order $order The order being saved.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_before_order_save( $order ): void {
		if ( ! $order instanceof \WC_Order || 'shop_order' !== $order->get_type() ) {
			return;
		}

		$order_id = $order->get_id();
		if ( $order_id > 0 ) {
			$this->pre_save_customer_ids[ $order_id ] = (int) $order->get_data()['customer_id'];
		}
	}

	/**
	 * Handle the woocommerce_new_order hook.
	 *
	 * @param int       $order_id The order ID.
	 * @param \WC_Order $order    The order object.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_new_order( $order_id, $order ): void {
		$this->invalidate( (int) $order_id );
		$this->invalidate_orders_list();
	}

	/**
	 * Handle the woocommerce_update_order hook.
	 *
	 * @param int       $order_id The order ID.
	 * @param \WC_Order $order    The order object.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_update_order( $order_id, $order ): void {
		$order_id = (int) $order_id;
		$this->invalidate( $order_id );

		if ( $this->did_customer_change( $order_id, $order ) ) {
			$this->invalidate_orders_list();
		}

		unset( $this->pre_save_customer_ids[ $order_id ] );
	}

	/**
	 * Check if the customer ID changed during the update.
	 *
	 * @param int       $order_id The order ID.
	 * @param \WC_Order $order    The order object (after save).
	 *
	 * @return bool True if customer changed.
	 */
	private function did_customer_change( int $order_id, $order ): bool {
		if ( ! isset( $this->pre_save_customer_ids[ $order_id ] ) ) {
			return false;
		}

		$old_customer_id = $this->pre_save_customer_ids[ $order_id ];
		$new_customer_id = $order instanceof \WC_Order ? (int) $order->get_customer_id() : 0;

		return $old_customer_id !== $new_customer_id;
	}

	/**
	 * Handle the woocommerce_before_delete_order hook.
	 *
	 * @param int       $order_id The order ID.
	 * @param \WC_Order $order    The order object.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_before_delete_order( $order_id, $order ): void {
		$this->invalidate( (int) $order_id );
		$this->invalidate_orders_list();
	}

	/**
	 * Handle the woocommerce_trash_order hook.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_trash_order( $order_id ): void {
		$this->invalidate( (int) $order_id );
		$this->invalidate_orders_list();
	}

	/**
	 * Handle the woocommerce_untrash_order hook.
	 *
	 * @param int    $order_id        The order ID.
	 * @param string $previous_status The previous order status before trashing.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_untrash_order( $order_id, $previous_status ): void {
		$this->invalidate( (int) $order_id );
		$this->invalidate_orders_list();
	}

	/**
	 * Handle the woocommerce_order_status_changed hook.
	 *
	 * Status changes affect which orders appear in status-filtered collection endpoints,
	 * so we always invalidate the orders list.
	 *
	 * @param int       $order_id    The order ID.
	 * @param string    $from_status The old status.
	 * @param string    $to_status   The new status.
	 * @param \WC_Order $order       The order object.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_order_status_changed( $order_id, $from_status, $to_status, $order ): void {
		$this->invalidate( (int) $order_id );
		$this->invalidate_orders_list();
	}

	/**
	 * Handle the woocommerce_order_refunded hook.
	 *
	 * @param int $order_id  The parent order ID.
	 * @param int $refund_id The refund ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_order_refunded( $order_id, $refund_id ): void {
		$order_id  = (int) $order_id;
		$refund_id = (int) $refund_id;

		$this->invalidate( $order_id );
		$this->invalidate_refund( $refund_id );
		$this->invalidate_order_refunds_list( $order_id );
		$this->invalidate_refunds_list();
	}

	/**
	 * Handle the woocommerce_refund_deleted hook.
	 *
	 * @param int $refund_id The refund ID.
	 * @param int $order_id  The parent order ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_refund_deleted( $refund_id, $order_id ): void {
		$order_id  = (int) $order_id;
		$refund_id = (int) $refund_id;

		$this->invalidate( $order_id );
		$this->invalidate_refund( $refund_id );
		$this->invalidate_order_refunds_list( $order_id );
		$this->invalidate_refunds_list();
	}

	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Invalidate an order version string.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 */
	public function invalidate( int $order_id ): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( "order_{$order_id}" );
	}

	/**
	 * Invalidate a refund version string.
	 *
	 * @param int $refund_id The refund ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 */
	public function invalidate_refund( int $refund_id ): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( "refund_{$refund_id}" );
	}

	/**
	 * Invalidate the orders list version string.
	 *
	 * This should be called when orders are created, deleted, change status,
	 * or change customer, as these operations affect collection/list endpoints.
	 *
	 * @return void
	 */
	private function invalidate_orders_list(): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( 'list_orders' );
	}

	/**
	 * Invalidate the refunds list version string.
	 *
	 * This should be called when refunds are created or deleted,
	 * as these operations affect the /refunds collection endpoint.
	 *
	 * @return void
	 */
	private function invalidate_refunds_list(): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( 'list_refunds' );
	}

	/**
	 * Invalidate the refunds list version string for a specific order.
	 *
	 * This should be called when refunds are created or deleted for an order,
	 * as these operations affect the /orders/{id}/refunds collection endpoint.
	 *
	 * @param int $order_id The parent order ID.
	 *
	 * @return void
	 */
	private function invalidate_order_refunds_list( int $order_id ): void {
		if ( $order_id > 0 ) {
			wc_get_container()->get( VersionStringGenerator::class )->delete_version( "list_order_refunds_{$order_id}" );
		}
	}
}
