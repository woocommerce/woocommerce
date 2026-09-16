<?php
/**
 * ScheduledSaleBatchProcessor class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Product;

/**
 * Starts or ends scheduled sales for a list of products, a batch at a time.
 *
 * Loading the whole backlog at once can exhaust memory before a single product is
 * saved, so each batch is primed, processed, and then released from the object cache
 * before the next one is loaded.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
class ScheduledSaleBatchProcessor {

	/**
	 * How many products are loaded and processed at a time.
	 *
	 * @var int
	 */
	public const BATCH_SIZE = 50;

	/**
	 * The product utility.
	 *
	 * @var ProductUtil
	 */
	private ProductUtil $product_util;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 *
	 * @param ProductUtil $product_util The product utility.
	 */
	final public function init( ProductUtil $product_util ): void {
		$this->product_util = $product_util;
	}

	/**
	 * Apply a sale state to every product in the list, one batch at a time.
	 *
	 * @param (int|string|float|WC_Product|object)[] $product_ids Product references, as returned by the product data store.
	 * @param string                                 $mode        'start' or 'end'.
	 */
	public function process( array $product_ids, string $mode ): void {
		// product_objects entries are released by id, which reaches a real wp_cache_delete()
		// on every cache backend. The products and term-queries groups cannot be addressed
		// by product id, so they are only flushed when the cache is request-local; a shared
		// external cache only has its in-memory copy dropped.
		$product_cache = FeaturesUtil::feature_is_enabled( ProductCacheController::FEATURE_NAME )
			? wc_get_container()->get( ProductCache::class )
			: null;

		$flush_shared_groups = wp_cache_supports( 'flush_group' ) && ! wp_using_ext_object_cache();

		$total = count( $product_ids );

		// Sliced per iteration: array_chunk() would build every batch before the first runs.
		for ( $offset = 0; $offset < $total; $offset += self::BATCH_SIZE ) {
			$batch = new ScheduledSaleBatch(
				array_slice( $product_ids, $offset, self::BATCH_SIZE ),
				$mode,
				$this->product_util,
				$product_cache,
				$flush_shared_groups
			);
			$batch->process();
		}
	}
}
