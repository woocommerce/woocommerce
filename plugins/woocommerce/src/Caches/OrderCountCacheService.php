<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use WC_Order;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A service class to help with updates to the aggregate orders cache.
 */
class OrderCountCacheService {

	/**
	 * OrderCountCache instance.
	 *
	 * @var OrderCountCache
	 */
	private $order_count_cache;

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

		$this->order_count_cache->decrement( $order->get_type(), $this->get_prefixed_status( $previous_status ) );
		$this->order_count_cache->increment( $order->get_type(), $this->get_prefixed_status( $next_status ) );
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
