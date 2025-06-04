<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use WC_Order;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A service class to help with updates to the aggregate orders cache.
 *
 * @internal
 */
class OrderCountCacheService {

	/**
	 * OrderCountCache instance.
	 *
	 * @var OrderCountCache
	 */
	private $order_count_cache;

	/**
	 * Array of order ids with their last transitioned status as key value pairs.
	 *
	 * @var array
	 */
	private $order_statuses = array();

	/**
	 * Array of order ids with their initial status as key value pairs.
	 *
	 * @var array
	 */
	private $initial_order_statuses = array();

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 */
	final public function init() {
		$this->order_count_cache = new OrderCountCache();
		add_action( 'woocommerce_new_order', array( $this, 'update_on_new_order' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_on_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_before_trash_order', array( $this, 'update_on_order_trashed' ), 10, 2 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'update_on_order_deleted' ), 10, 2 );
		add_action( 'woocommerce_refresh_order_count_cache', array( $this, 'refresh_cache' ) );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'schedule_background_actions' ) );
		// This is a temporary fix to ensure the background actions are scheduled.
		// @todo: Remove this once the Action Scheduler package is updated to >= 3.9.3.
		add_action( 'admin_init', array( $this, 'schedule_background_actions' ) );
	}

	/**
	 * Refresh the cache for a given order type.
	 *
	 * @param string $order_type The order type.
	 * @return void
	 */
	public function refresh_cache( $order_type ) {
		$this->order_count_cache->flush( $order_type );
		OrderUtil::get_count_for_type( $order_type );
	}

	/**
	 * Register background caching for each order type.
	 *
	 * @return void
	 */
	public function schedule_background_actions() {
		$order_types = wc_get_order_types( 'order-count' );
		$frequency   = HOUR_IN_SECONDS * 12;
		foreach ( $order_types as $order_type ) {
			as_schedule_recurring_action( time() + $frequency, $frequency, 'woocommerce_refresh_order_count_cache', array( $order_type ), 'count', true );
		}
	}

	/**
	 * Update the cache when a new order is made.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_new_order( $order_id, $order ) {
		if ( ! $this->order_count_cache->is_cached( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) ) ) {
			return;
		}

		// If the order status was updated, we need to increment the order count cache for the
		// initial status that was errantly decremented on order status change.
		if ( isset( $this->initial_order_statuses[ $order_id ] ) ) {
			$this->order_count_cache->increment( $order->get_type(), $this->get_prefixed_status( $this->initial_order_statuses[ $order_id ] ) );
		}

		// If the order status count has already been incremented, we can skip incrementing it again.
		if ( isset( $this->order_statuses[ $order->get_id() ] ) && $this->order_statuses[ $order->get_id() ] === $order->get_status() ) {
			return;
		}

		$this->order_statuses[ $order_id ] = $order->get_status();
		$this->order_count_cache->increment( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) );
	}

	/**
	 * Update the cache when an order is trashed.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_order_trashed( $order_id, $order ) {
		if (
			! $this->order_count_cache->is_cached( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) ) ||
			! $this->order_count_cache->is_cached( $order->get_type(), OrderStatus::TRASH ) ) {
			return;
		}

		$this->order_count_cache->decrement( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) );
		$this->order_count_cache->increment( $order->get_type(), OrderStatus::TRASH );
	}

	/**
	 * Update the cache when an order is deleted.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_order_deleted( $order_id, $order ) {
		if ( ! $this->order_count_cache->is_cached( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) ) ) {
			return;
		}

		$this->order_count_cache->decrement( $order->get_type(), $this->get_prefixed_status( $order->get_status() ) );
	}

	/**
	 * Update the cache whenver an order status changes.
	 *
	 * @param int      $order_id Order id.
	 * @param string   $previous_status the old WooCommerce order status.
	 * @param string   $next_status the new WooCommerce order status.
	 * @param WC_Order $order The order.
	 */
	public function update_on_order_status_changed( $order_id, $previous_status, $next_status, $order ) {
		if (
			! $this->order_count_cache->is_cached( $order->get_type(), $this->get_prefixed_status( $next_status ) ) ||
			! $this->order_count_cache->is_cached( $order->get_type(), $this->get_prefixed_status( $previous_status ) )
		) {
			return;
		}

		// If the order status count has already been incremented, we can skip incrementing it again.
		if ( isset( $this->order_statuses[ $order_id ] ) && $this->order_statuses[ $order_id ] === $next_status ) {
			return;
		}

		$this->order_statuses[ $order_id ] = $next_status;
		$was_decremented                   = $this->order_count_cache->decrement( $order->get_type(), $this->get_prefixed_status( $previous_status ) );
		$this->order_count_cache->increment( $order->get_type(), $this->get_prefixed_status( $next_status ) );

		// Set the initial order status in case this is a new order and the previous status should not be decremented.
		if ( ! isset( $this->initial_order_statuses[ $order_id ] ) && $was_decremented ) {
			$this->initial_order_statuses[ $order_id ] = $previous_status;
		}
	}

	/**
	 * Get the prefixed status.
	 *
	 * @param string $status The status.
	 * @return string
	 */
	private function get_prefixed_status( $status ) {
		$status = 'wc-' . $status;

		$special_statuses = array(
			'wc-' . OrderStatus::AUTO_DRAFT => OrderStatus::AUTO_DRAFT,
			'wc-' . OrderStatus::TRASH      => OrderStatus::TRASH,
		);

		if ( isset( $special_statuses[ $status ] ) ) {
			return $special_statuses[ $status ];
		}

		return $status;
	}
}
