<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Products;

/**
 * Coalesces sale event rescheduling across the sale-date meta writes made during a request.
 *
 * A single CRUD save writes `_sale_price_dates_from` and `_sale_price_dates_to` as separate
 * update_post_meta() calls, and bulk importers can touch the same product several times in one
 * request. Rescheduling on every write repeats a full product load plus the Action Scheduler
 * unschedule and schedule calls, and each pass immediately discards the previous pass's result.
 * Product IDs are collected here instead, so the work runs once per product.
 *
 * @internal
 *
 * @since 11.1.0
 */
class SaleEventSchedulingDeferrer {

	/**
	 * Priority for the shutdown hook.
	 *
	 * Deliberately last. Other callbacks on `shutdown` save products and write meta, so flushing
	 * early would queue those writes with nothing left to process them and the rescheduling would
	 * be dropped without a trace. Nothing needs to observe the scheduled actions during shutdown,
	 * so there is no reason to run sooner.
	 */
	private const SHUTDOWN_HOOK_PRIORITY = PHP_INT_MAX;

	/**
	 * Maximum number of distinct products to hold before flushing.
	 *
	 * A long-running request such as a CLI import can touch tens of thousands of products, so the
	 * queue is bounded instead of growing until shutdown. Repeated writes to the same product still
	 * collapse into one entry, so an ordinary save is unaffected by this limit.
	 */
	private const MAX_PENDING_PRODUCTS = 100;

	/**
	 * Product IDs pending rescheduling, as a set of id => true.
	 *
	 * @var array
	 */
	private array $pending_product_ids = array();

	/**
	 * Whether the shutdown flush has been registered.
	 *
	 * @var bool
	 */
	private bool $shutdown_hook_registered = false;

	/**
	 * Record a product ID whose sale events need rescheduling.
	 *
	 * @param int $product_id ID of the product or variation whose sale-date meta changed.
	 * @return void
	 */
	public function queue_product( int $product_id ): void {
		if ( ! $this->shutdown_hook_registered ) {
			add_action( 'shutdown', array( $this, 'handle_shutdown' ), self::SHUTDOWN_HOOK_PRIORITY );
			$this->shutdown_hook_registered = true;
		}

		$this->pending_product_ids[ $product_id ] = true;

		if ( count( $this->pending_product_ids ) >= self::MAX_PENDING_PRODUCTS ) {
			$this->flush();
		}
	}

	/**
	 * Handle the shutdown hook.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_shutdown(): void {
		$this->flush();
	}

	/**
	 * Reschedule sale events for every collected product ID and reset the collection.
	 *
	 * Runs automatically on `shutdown`. CLI importers and tests that need the Action Scheduler
	 * entries in place before the request ends can call this directly after their meta writes.
	 *
	 * @return void
	 */
	public function flush(): void {
		if ( empty( $this->pending_product_ids ) ) {
			return;
		}

		/*
		 * The collection is reset before the rescheduling runs, so that any sale-date meta written
		 * while it is in progress is queued for the next flush instead of being processed twice.
		 */
		$product_ids               = array_keys( $this->pending_product_ids );
		$this->pending_product_ids = array();

		foreach ( $product_ids as $product_id ) {
			wc_maybe_schedule_product_sale_events( $product_id );
		}
	}
}
