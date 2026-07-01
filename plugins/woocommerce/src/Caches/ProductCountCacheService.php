<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Internal\Utilities\ProductUtil;
use WC_Product;
use WP_Post;

/**
 * A service class to help with updates to the aggregate product counts cache.
 *
 * @internal
 */
class ProductCountCacheService {

	public const BACKGROUND_EVENT_HOOK = 'woocommerce_refresh_product_count_cache';

	/**
	 * ProductCountCache instance.
	 *
	 * @var ProductCountCache
	 */
	private ProductCountCache $product_count_cache;

	/**
	 * Array of product IDs with their last transitioned status as key value pairs.
	 * Guarantees idempotency for product status transitions when multiple hooks fire for the same product.
	 *
	 * @var array<int,string>
	 */
	private array $product_statuses = array();

	/**
	 * Array of product IDs with their initial status as key value pairs.
	 * Guarantees idempotency for product status transitions when multiple hooks fire for the same product.
	 *
	 * @var array<int,string>
	 */
	private array $initial_product_statuses = array();

	/**
	 * Set of product IDs currently being created in this request (keyed by ID; detected via old_status='new').
	 *
	 * @var array<int,true>
	 */
	private array $products_in_creation = array();

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->product_count_cache = new ProductCountCache();

		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'schedule_background_actions' ) );
		add_action( self::BACKGROUND_EVENT_HOOK, array( $this, 'prime_cache_if_cold' ) );
		if ( defined( 'WC_PLUGIN_BASENAME' ) ) {
			add_action( 'deactivate_' . WC_PLUGIN_BASENAME, array( $this, 'unschedule_background_actions' ) );
		}

		// transition_post_status owns all mid-lifecycle status changes; woocommerce_new_product corrects for creation-time
		// ephemeral transitions before the final status is committed; before_delete_post closes the lifecycle.
		add_action( 'woocommerce_new_product', array( $this, 'update_on_new_product' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'update_on_product_status_changed' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'update_on_product_deleted' ), 10, 2 );
	}

	/**
	 * Primes the product count cache for a given post type when it is cold.
	 *
	 * @param string $product_type The product post type.
	 * @return void
	 */
	public function prime_cache_if_cold( string $product_type = 'product' ): void {
		// Cache warm-up is only effective when an object cache plugin is active, and the cache entry is missing.
		if ( wp_using_ext_object_cache() && null === $this->product_count_cache->get( $product_type ) ) {
			$this->product_count_cache->flush( $product_type );
			wc_get_container()->get( ProductUtil::class )->get_counts_for_type( $product_type );
		}
	}

	/**
	 * Register background caching for each product type.
	 *
	 * @return void
	 */
	public function schedule_background_actions(): void {
		$frequency = HOUR_IN_SECONDS * 12;
		$timestamp = time() + $frequency;
		as_schedule_recurring_action( $timestamp, $frequency, self::BACKGROUND_EVENT_HOOK, array( 'product' ), 'count', true );
	}

	/**
	 * Unschedules background actions.
	 *
	 * @return void
	 */
	public function unschedule_background_actions(): void {
		WC()->queue()->cancel_all( self::BACKGROUND_EVENT_HOOK );
	}

	/**
	 * Update the cache when a new product is created.
	 *
	 * @param int        $product_id Product ID.
	 * @param WC_Product $product    The product.
	 * @return void
	 */
	public function update_on_new_product( int $product_id, WC_Product $product ): void {
		// transition_post_status already counted this product — reverse any errant decrement from a cold step 1 and stop.
		// In-memory status may diverge from DB after a mid-creation wp_update_post; do not increment here.
		if ( isset( $this->product_statuses[ $product_id ] ) ) {
			$this->maybe_restore_initial_status_count( $product_id );
			unset( $this->products_in_creation[ $product_id ] );
			return;
		}

		// Cache was cold throughout creation — transition_post_status never fired; use in-memory status as the sole count.
		$product_status = $product->get_status();
		if ( $this->product_count_cache->is_cached( 'product', $product_status ) ) {
			$this->product_statuses[ $product_id ] = $product_status;
			$this->product_count_cache->increment( 'product', $product_status );
		}
		unset( $this->products_in_creation[ $product_id ] );
	}

	/**
	 * Update the cache whenever a product status changes.
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The previous post status.
	 * @param WP_Post $post       The post object.
	 *
	 * @return void
	 */
	public function update_on_product_status_changed( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$product_id = $post->ID;

		// WordPress uses 'new' as old_status exclusively on the first transition_post_status of a newly inserted post.
		if ( 'new' === $old_status ) {
			$this->products_in_creation[ $product_id ] = true;
		}

		$is_new_cached = $this->product_count_cache->is_cached( 'product', $new_status );
		$is_old_cached = $this->product_count_cache->is_cached( 'product', $old_status );
		if ( ! $is_new_cached && ! $is_old_cached ) {
			return;
		}

		// If the status count has already been incremented for this product, skip.
		if ( ( $this->product_statuses[ $product_id ] ?? null ) === $new_status ) {
			return;
		}

		$previously_tracked                    = isset( $this->product_statuses[ $product_id ] );
		$this->product_statuses[ $product_id ] = $new_status;
		$was_decremented                       = $is_old_cached && false !== $this->product_count_cache->decrement( 'product', $old_status );
		if ( $is_new_cached ) {
			$this->product_count_cache->increment( 'product', $new_status );
		}

		// Record old status for creation-time correction only; existing-product decrements are correct and must not be reversed.
		// If $previously_tracked, an earlier transition already counted the old status — decrement is legitimate.
		if ( ! $previously_tracked && $was_decremented && ! isset( $this->initial_product_statuses[ $product_id ] ) && isset( $this->products_in_creation[ $product_id ] ) ) {
			$this->initial_product_statuses[ $product_id ] = $old_status;
		} elseif ( ( $this->initial_product_statuses[ $product_id ] ?? null ) === $new_status ) {
			unset( $this->initial_product_statuses[ $product_id ] );
		}
	}

	/**
	 * Update the cache when a product is permanently deleted.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function update_on_product_deleted( int $post_id, WP_Post $post ): void {
		if ( 'product' === $post->post_type ) {
			// Reverse any errant decrement from a mid-creation status transition that update_on_new_product will never get to correct.
			$this->maybe_restore_initial_status_count( $post_id );

			$product_status = $post->post_status;
			if ( $this->product_count_cache->is_cached( 'product', $product_status ) ) {
				$this->product_count_cache->decrement( 'product', $product_status );
			}

			unset( $this->product_statuses[ $post_id ], $this->products_in_creation[ $post_id ] );
		}
	}

	/**
	 * Reverses an errant decrement recorded in initial_product_statuses for a given product, if any.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return void
	 */
	private function maybe_restore_initial_status_count( int $product_id ): void {
		if ( isset( $this->initial_product_statuses[ $product_id ] ) ) {
			$initial_status = $this->initial_product_statuses[ $product_id ];
			unset( $this->initial_product_statuses[ $product_id ] );
			if ( $this->product_count_cache->is_cached( 'product', $initial_status ) ) {
				$this->product_count_cache->increment( 'product', $initial_status );
			}
		}
	}
}
