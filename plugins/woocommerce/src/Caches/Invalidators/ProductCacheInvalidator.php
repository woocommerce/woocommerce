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
		add_action( 'woocommerce_update_product', array( $this, 'on_product_saved' ), 10, 2 );

		add_action( 'delete_post', array( $this, 'on_post_deleted' ), 10, 2 );
		add_action( 'wp_trash_post', array( $this, 'on_post_trashed' ), 10, 1 );
		add_action( 'untrashed_post', array( $this, 'on_post_untrashed' ), 10, 1 );

		add_action( 'woocommerce_new_product_variation', array( $this, 'on_product_variation_created' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'on_product_variation_saved' ), 10, 2 );
		add_action( 'woocommerce_delete_product_variation', array( $this, 'on_product_variation_deleted' ), 10, 1 );
	}

	/**
	 * Handle product being saved (both create and update).
	 *
	 * This hook fires for both new products and product updates.
	 * We determine if it's a new product by checking if the date_created
	 * is very recent (within the last 2 seconds).
	 *
	 * @param int         $product_id The product ID.
	 * @param \WC_Product $product The product object.
	 *
	 * @return void
	 */
	public function on_product_saved( int $product_id, $product ): void {
		$date_created  = $product->get_date_created();
		$date_modified = $product->get_date_modified();
		
		// Determine if this is a new product (created within the last 2 seconds).
		$is_new_product = false;
		if ( $date_created && $date_modified ) {
			$created_timestamp  = $date_created->getTimestamp();
			$modified_timestamp = $date_modified->getTimestamp();
			$is_new_product     = abs( $created_timestamp - $modified_timestamp ) <= 2;
		}

		$operation = $is_new_product ? self::OPERATION_CREATE : self::OPERATION_UPDATE;

		$this->invalidate(
			$product_id,
			$operation,
		);
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
	 * Handle post being trashed.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function on_post_trashed( int $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->invalidate(
			$post_id,
			self::OPERATION_UPDATE,
		);
	}

	/**
	 * Handle post being untrashed.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function on_post_untrashed( int $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->invalidate(
			$post_id,
			self::OPERATION_UPDATE,
		);
	}

	/**
	 * Handle product variation being saved (both create and update).
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 */
	public function on_product_variation_saved( int $variation_id, $variation ): void {
		$this->invalidate(
			$variation_id,
			self::OPERATION_UPDATE,
			array(
				'parent_id' => $variation->get_parent_id(),
			)
		);

		// Also invalidate parent product.
		if ( $parent_id = $variation->get_parent_id() ) {
			$this->invalidate(
				$parent_id,
				self::OPERATION_UPDATE,
				array(
					'variation_id' => $variation_id,
				)
			);
		}
	}

	/**
	 * Handle product variation being saved (both create and update).
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 */
	public function on_product_variation_created( int $variation_id, $variation ): void {
		$this->invalidate(
			$variation_id,
			self::OPERATION_CREATE,
			array(
				'parent_id' => $variation->get_parent_id(),
			)
		);

		// Also invalidate parent product.
		if ( $parent_id = $variation->get_parent_id() ) {
			$this->invalidate(
				$parent_id,
				self::OPERATION_UPDATE,
				array(
					'variation_id' => $variation_id,
				)
			);
		}
	}

	/**
	 * Handle product variation deletion.
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return void
	 */
	public function on_product_variation_deleted( int $variation_id ): void {
		$variation = wc_get_product( $variation_id );
		$parent_id = $variation ? $variation->get_parent_id() : null;

		$this->invalidate(
			$variation_id,
			self::OPERATION_DELETE,
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
					'variation_id' => $variation_id,
				)
			);
		}
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
