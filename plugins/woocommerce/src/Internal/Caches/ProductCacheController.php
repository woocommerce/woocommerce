<?php
/**
 * ProductCacheController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Controller for product caching functionality.
 *
 * @since 10.5.0
 */
class ProductCacheController {

	/**
	 * Feature flag name for product instance caching.
	 *
	 * @since 10.5.0
	 *
	 * @var string
	 */
	public const FEATURE_NAME = 'product_instance_caching';

	/**
	 * The product cache instance.
	 *
	 * @since 10.5.0
	 *
	 * @var ProductCache
	 */
	private ProductCache $product_cache;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 *
	 * @param ProductCache $product_cache The product cache instance.
	 *
	 * @return void
	 */
	final public function init( ProductCache $product_cache ): void {
		$this->product_cache = $product_cache;

		// Mark cache group as non-persistent immediately to ensure it's set
		// regardless of when this controller is instantiated relative to other hooks.
		$this->set_product_cache_group_as_non_persistent();

		// Defer feature check to 'init' to avoid triggering translations too early.
		add_action( 'init', array( $this, 'on_init' ), 0 );
	}

	/**
	 * Check feature flag and register hooks on WordPress init.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	public function on_init(): void {
		if ( ! FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) ) {
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Register the cache invalidation hooks.
	 *
	 * This method is separated from on_init() to allow tests to call it directly
	 * after enabling the feature flag.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Handle direct WordPress post updates (bypassing CRUD).
		add_action( 'clean_post_cache', array( $this, 'invalidate_product_cache_on_clean' ), 10, 2 );

		// Handle post meta updates (third-party plugins updating via postmeta API).
		add_action( 'updated_post_meta', array( $this, 'invalidate_product_cache_by_meta' ), 10, 2 );
		add_action( 'added_post_meta', array( $this, 'invalidate_product_cache_by_meta' ), 10, 2 );
		add_action( 'deleted_post_meta', array( $this, 'invalidate_product_cache_by_meta' ), 10, 2 );

		// Handle direct stock/sales updates (which uses direct SQL and cache manipulation, bypassing standard meta hooks)
		// In the future, update WC_Product_Data_Store_CPT::update_product_stock() and
		// update_product_sales() to trigger standard WordPress updated_post_meta hooks instead
		// of requiring specific hooks here.
		add_action( 'woocommerce_updated_product_stock', array( $this, 'invalidate_product_cache' ), 10, 1 );
		add_action( 'woocommerce_updated_product_sales', array( $this, 'invalidate_product_cache' ), 10, 1 );
	}

	/**
	 * Set the `product_objects` cache group as non-persistent.
	 *
	 * With product instance caching enabled, products are cached in-memory during a request
	 * rather than being persisted to external cache backends.  If WC_Data::__sleep()/::__wakeup() methods are eventually
	 * removed or changed so that the entire object is stored instead of just the ID, this should be revisited and evaluated
	 * performance impact.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	public function set_product_cache_group_as_non_persistent(): void {
		wp_cache_add_non_persistent_groups( array( $this->product_cache->get_object_type() ) );
	}

	/**
	 * Invalidate the product cache when the post cache is cleaned.
	 *
	 * @since 10.5.0
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function invalidate_product_cache_on_clean( $post_id, $post ): void {
		$post_id = (int) $post_id;
		/**
		 * It's important not to trigger get_post() during this callback as some extensions may attempt to clean cache
		 * prior to updating the database and a call to get_post() would cause the post to be added back to cache before the update.
		 */
		if ( ! ( $post instanceof \WP_Post ) || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->product_cache->remove( $post_id );
	}

	/**
	 * Invalidate the product cache for a given post ID if it's a product or product variation.
	 *
	 * @since 10.5.0
	 *
	 * @param int $post_id The post ID to check and invalidate.
	 *
	 * @return void
	 */
	public function invalidate_product_cache( $post_id ): void {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );
		if ( ! $post_type || ! in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->product_cache->remove( $post_id );
	}

	/**
	 * Invalidate the product cache when post meta is updated.
	 *
	 * @since 10.5.0
	 *
	 * @param int $meta_id   The ID of the metadata entry.
	 * @param int $object_id The ID of the object the metadata is for.
	 *
	 * @return void
	 */
	public function invalidate_product_cache_by_meta( $meta_id, $object_id ): void {
		$object_id = (int) $object_id;
		if ( in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ), true ) ) {
			$this->invalidate_product_cache( $object_id );
		}
	}
}
