<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductViews;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Tracks product page views and stores a running count in post meta.
 *
 * Each product's view count is stored in the `_wc_view_count` post meta key.
 * To avoid inflating counts on rapid refreshes, a WC session flag per product
 * is set for the duration of `woocommerce_product_view_count_interval` seconds
 * (default 30 minutes) before the same visitor's view is counted again.
 *
 * @since 10.8.0
 */
class ProductViewCountTracker implements RegisterHooksInterface {

	/**
	 * Post meta key used to store the view count.
	 */
	public const META_KEY = '_wc_view_count';

	/**
	 * Default de-duplication window in seconds (30 minutes).
	 */
	private const DEFAULT_INTERVAL = 1800;

	/**
	 * Register WordPress hooks.
	 *
	 * @since 10.8.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'track_product_view' ), 20 );
	}

	/**
	 * Increment the view count for the current product page.
	 *
	 * Runs on `template_redirect`. Skips non-product pages, bot/crawler
	 * requests, and repeat views within the configured interval.
	 *
	 * @since 10.8.0
	 * @return void
	 */
	public function track_product_view(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		global $post;

		if ( ! $post || $post->post_type !== 'product' ) {
			return;
		}

		$product_id = (int) $post->ID;

		if ( $this->is_duplicate_view( $product_id ) ) {
			return;
		}

		$this->mark_viewed( $product_id );
		$this->increment_count( $product_id );
	}

	/**
	 * Return the view count for a given product.
	 *
	 * @since 10.8.0
	 * @param int $product_id Product post ID.
	 * @return int
	 */
	public function get_count( int $product_id ): int {
		return (int) get_post_meta( $product_id, self::META_KEY, true );
	}

	/**
	 * Determine whether this visitor already triggered a count for this product
	 * within the de-duplication window.
	 *
	 * @since 10.8.0
	 * @param int $product_id Product post ID.
	 * @return bool
	 */
	private function is_duplicate_view( int $product_id ): bool {
		$session = WC()->session;

		if ( ! $session ) {
			return false;
		}

		$key     = 'wc_product_viewed_' . $product_id;
		$expires = (int) $session->get( $key, 0 );

		return $expires > time();
	}

	/**
	 * Store a session flag so the same visitor is not counted again within the interval.
	 *
	 * @since 10.8.0
	 * @param int $product_id Product post ID.
	 * @return void
	 */
	private function mark_viewed( int $product_id ): void {
		$session = WC()->session;

		if ( ! $session ) {
			return;
		}

		/**
		 * Duration in seconds before the same visitor's view is counted again.
		 *
		 * @since 10.8.0
		 * @param int $interval Interval in seconds. Default 1800 (30 minutes).
		 */
		$interval = (int) apply_filters( 'woocommerce_product_view_count_interval', self::DEFAULT_INTERVAL );
		$key      = 'wc_product_viewed_' . $product_id;

		$session->set( $key, time() + $interval );
	}

	/**
	 * Atomically increment the view count stored in post meta.
	 *
	 * Uses a direct DB query (same pattern as `total_sales`) so concurrent
	 * requests cannot overwrite each other.
	 *
	 * @since 10.8.0
	 * @param int $product_id Product post ID.
	 * @return void
	 */
	private function increment_count( int $product_id ): void {
		global $wpdb;

		// Ensure the meta row exists before the atomic UPDATE.
		add_post_meta( $product_id, self::META_KEY, 0, true );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = meta_value + 1
				 WHERE post_id  = %d
				   AND meta_key = %s",
				$product_id,
				self::META_KEY
			)
		);

		// Bust the object cache for this meta so subsequent reads are fresh.
		wp_cache_delete( $product_id, 'post_meta' );

		/**
		 * Fires after a product view has been counted.
		 *
		 * @since 10.8.0
		 * @param int $product_id Product post ID.
		 */
		do_action( 'woocommerce_product_view_counted', $product_id );
	}
}
