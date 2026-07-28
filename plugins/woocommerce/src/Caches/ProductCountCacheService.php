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
	 *
	 * @return void
	 */
	final public function init(): void {
		$this->product_count_cache = new ProductCountCache();

		add_action( self::BACKGROUND_EVENT_HOOK, array( $this, 'prime_cache_if_cold' ) );
		add_action( 'action_scheduler_init', array( $this, 'unschedule_background_actions' ) );
	}


	/**
	 * Primes the product count cache for a given post type when it is cold.
	 *
	 * @param string $product_type The product post type.
	 * @return void
	 */
	public function prime_cache_if_cold( $product_type = 'product' ): void {
		// Intentionally left blank so already-scheduled actions complete successfully.
	}


	/**
	 * Unschedule background actions.
	 *
	 * @return void
	 */
	public function unschedule_background_actions(): void {
		as_unschedule_all_actions( self::BACKGROUND_EVENT_HOOK );
	}
}
