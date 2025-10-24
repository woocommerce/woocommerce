<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches\Invalidators;

use Automattic\WooCommerce\Caches\Invalidators\CacheInvalidatorInterface;

/**
 * Handles product cache invalidation.
 *
 * This class hooks into WooCommerce product lifecycle events and triggers
 * cache invalidation via WordPress actions, allowing other code to respond
 * to product changes.
 *
 * Example usage by consumers:
 * ```php
 * add_action( 'woocommerce_product_cache_invalidated', function( $product_id, $operation, $context ) {
 *     // Clear your custom cache
 *     wp_cache_delete( 'my_cache_' . $product_id );
 * }, 10, 3 );
 * ```
 */
class ProductCacheInvalidator implements CacheInvalidatorInterface {

	/**
	 * Initialize the invalidator and register hooks.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register all product-related hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'save_post_product', array( $this, 'on_product_post_saved' ), 10, 3 );
		add_action( 'save_post_product_variation', array( $this, 'on_variation_post_saved' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'on_post_deleted' ), 10, 2 );
	}

	/**
	 * Handle product being saved (both create and update).
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 * @param bool     $update Whether this is an update or new post.
	 *
	 * @return void
	 */
	public function on_product_post_saved( int $post_id, $post, bool $update ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$operation = $update ? self::OPERATION_UPDATE : self::OPERATION_CREATE;

		$this->invalidate(
			$post_id,
			$operation,
		);
	}

	/**
	 * Handle product variation post being saved (both create and update).
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 * @param bool     $update Whether this is an update or new post.
	 *
	 * @return void
	 */
	public function on_variation_post_saved( int $post_id, $post, bool $update ): void {
		error_log('IS SAVING VARIATION');
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$operation = $update ? self::OPERATION_UPDATE : self::OPERATION_CREATE;
		$parent_id = $post->post_parent;

		$this->invalidate(
			$post_id,
			$operation,
			array(
				'parent_id' => $parent_id,
			)
		);

		// Also invalidate parent product.
		if ( $parent_id ) {
			$this->invalidate(
				$parent_id,
				self::OPERATION_UPDATE,
				array(
					'variation_id' => $post_id,
				)
			);
		}
	}

	/**
	 * Handle post deletion.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function on_post_deleted( int $post_id, $post ): void {
		if ( ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->invalidate(
			$post_id,
			self::OPERATION_DELETE,
		);
	}

	/**
	 * Invalidate product cache and notify listeners via WordPress action.
	 *
	 * @param int|string $product_id The product ID.
	 * @param string     $operation The operation that triggered invalidation.
	 * @param mixed      $context Optional additional context.
	 *
	 * @return void
	 */
	public function invalidate( $product_id, string $operation, $context = null ): void {
		/**
		 * Fires when a product cache is invalidated.
		 *
		 * @since 10.4.0
		 *
		 * @param int|string $product_id The product ID.
		 * @param string     $operation The operation that triggered the invalidation
		 *                              (create, update, delete, trash, untrash, variation_*).
		 * @param mixed      $context Optional additional context about the invalidation.
		 *                           May include:
		 *                           - 'product' (WC_Product object)
		 *                           - 'parent_id' (int) - Parent product ID for variations
		 *                           - 'variation_id' (int) - Variation ID when parent is notified
		 */
		do_action( 'woocommerce_product_cache_invalidated', $product_id, $operation, $context );
	}
}
