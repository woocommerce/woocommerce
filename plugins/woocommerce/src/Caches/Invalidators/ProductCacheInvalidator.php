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
		// Product lifecycle hooks.
		add_action( 'woocommerce_new_product', array( $this, 'on_product_created' ), 10, 2 );
		add_action( 'woocommerce_update_product', array( $this, 'on_product_updated' ), 10, 2 );
		add_action( 'woocommerce_delete_product', array( $this, 'on_product_deleted' ), 10, 2 );
		add_action( 'woocommerce_trash_product', array( $this, 'on_product_trashed' ), 10, 1 );
		add_action( 'woocommerce_untrash_product', array( $this, 'on_product_untrashed' ), 10, 1 );

		// Product variation hooks.
		add_action( 'woocommerce_new_product_variation', array( $this, 'on_product_variation_created' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'on_product_variation_updated' ), 10, 2 );
		add_action( 'woocommerce_delete_product_variation', array( $this, 'on_product_variation_deleted' ), 10, 1 );
	}

	/**
	 * Handle new product creation.
	 *
	 * @param int         $product_id The product ID.
	 * @param \WC_Product $product The product object.
	 *
	 * @return void
	 */
	public function on_product_created( int $product_id, $product ): void {
		$this->invalidate(
			$product_id,
			'create',
			array(
				'product' => $product,
			)
		);
	}

	/**
	 * Handle product update.
	 *
	 * @param int         $product_id The product ID.
	 * @param \WC_Product $product The product object.
	 *
	 * @return void
	 */
	public function on_product_updated( int $product_id, $product ): void {
		$this->invalidate(
			$product_id,
			'update',
			array(
				'product' => $product,
			)
		);
	}

	/**
	 * Handle product deletion.
	 *
	 * @param int              $product_id The product ID.
	 * @param \WC_Product|null $product The product object (may not be available).
	 *
	 * @return void
	 */
	public function on_product_deleted( int $product_id, $product = null ): void {
		$this->invalidate(
			$product_id,
			'delete',
			array(
				'product' => $product,
			)
		);
	}

	/**
	 * Handle product being trashed.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 */
	public function on_product_trashed( int $product_id ): void {
		$this->invalidate(
			$product_id,
			'trash',
			array(
				'product_id' => $product_id,
			)
		);
	}

	/**
	 * Handle product being untrashed.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return void
	 */
	public function on_product_untrashed( int $product_id ): void {
		$this->invalidate(
			$product_id,
			'untrash',
			array(
				'product_id' => $product_id,
			)
		);
	}

	/**
	 * Handle new product variation creation.
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 */
	public function on_product_variation_created( int $variation_id, $variation ): void {
		$this->invalidate(
			$variation_id,
			'create',
			array(
				'product'      => $variation,
				'is_variation' => true,
				'parent_id'    => $variation->get_parent_id(),
			)
		);

		// Also invalidate parent product.
		if ( $parent_id = $variation->get_parent_id() ) {
			$this->invalidate(
				$parent_id,
				'variation_created',
				array(
					'variation_id' => $variation_id,
				)
			);
		}
	}

	/**
	 * Handle product variation update.
	 *
	 * @param int         $variation_id The variation ID.
	 * @param \WC_Product $variation The variation object.
	 *
	 * @return void
	 */
	public function on_product_variation_updated( int $variation_id, $variation ): void {
		$this->invalidate(
			$variation_id,
			'update',
			array(
				'product'      => $variation,
				'is_variation' => true,
				'parent_id'    => $variation->get_parent_id(),
			)
		);

		// Also invalidate parent product.
		if ( $parent_id = $variation->get_parent_id() ) {
			$this->invalidate(
				$parent_id,
				'variation_updated',
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
		// Try to get the parent ID before the variation is fully deleted.
		$variation = wc_get_product( $variation_id );
		$parent_id = $variation ? $variation->get_parent_id() : null;

		$this->invalidate(
			$variation_id,
			'delete',
			array(
				'is_variation' => true,
				'parent_id'    => $parent_id,
			)
		);

		// Also invalidate parent product.
		if ( $parent_id ) {
			$this->invalidate(
				$parent_id,
				'variation_deleted',
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
		 * This action allows other code to respond to product changes and clear
		 * their own caches or update external systems.
		 *
		 * @since 9.5.0
		 *
		 * @param int|string $product_id The product ID.
		 * @param string     $operation The operation that triggered the invalidation
		 *                              (create, update, delete, trash, untrash, variation_*).
		 * @param mixed      $context Optional additional context about the invalidation.
		 *                           May include 'product' (WC_Product object), 'changes' (array),
		 *                           'is_variation' (bool), 'parent_id' (int), 'variation_id' (int).
		 */
		do_action( 'woocommerce_product_cache_invalidated', $product_id, $operation, $context );
	}
}
