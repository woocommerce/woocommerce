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
 * @since 11.2.0
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
			$batch_ids = $this->normalize_ids( array_slice( $product_ids, $offset, self::BATCH_SIZE ) );

			if ( ! $batch_ids ) {
				continue;
			}

			$this->process_batch( $batch_ids, $mode, $product_cache, $flush_shared_groups );
		}
	}

	/**
	 * Reduce a batch of data-store rows to unique, positive product ids.
	 *
	 * The default data store returns ids as strings, and a replaced store may return
	 * products or post objects. Anything that is not a whole positive number is dropped
	 * rather than cast, so a malformed row cannot resolve to an unrelated post.
	 *
	 * @param array $entries Data-store rows.
	 * @return int[] Unique product ids, in their original order.
	 */
	private function normalize_ids( array $entries ): array {
		$ids = array();

		foreach ( $entries as $entry ) {
			if ( $entry instanceof WC_Product ) {
				$id = $entry->get_id();
			} elseif ( is_object( $entry ) ) {
				$id = $this->to_whole_positive_int( $entry->ID ?? null );
			} else {
				$id = $this->to_whole_positive_int( $entry );
			}

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Convert a value to a positive integer only when it already is one.
	 *
	 * @param mixed $value Value to convert.
	 * @return int The value as an integer, or 0 when it is not a whole positive number.
	 */
	private function to_whole_positive_int( $value ): int {
		if ( is_int( $value ) ) {
			return max( $value, 0 );
		}

		if ( is_float( $value ) && floor( $value ) === $value ) {
			return max( (int) $value, 0 );
		}

		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return (int) $value;
		}

		return 0;
	}

	/**
	 * Prime, process, and release one batch.
	 *
	 * @param int[]             $batch_ids           Product ids in this batch.
	 * @param string            $mode                'start' or 'end'.
	 * @param ProductCache|null $product_cache       The product object cache, when the feature is enabled.
	 * @param bool              $flush_shared_groups Whether the products and term-queries groups may be flushed.
	 */
	private function process_batch( array $batch_ids, string $mode, ?ProductCache $product_cache, bool $flush_shared_groups ): void {
		_prime_post_caches( $batch_ids );

		// Capture post types before product saves evict the primed posts.
		$post_types = array_values( array_unique( array_filter( array_map( 'get_post_type', $batch_ids ) ) ) );

		foreach ( $batch_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				// Only the price changes, so this does not reschedule the sale event.
				wc_apply_sale_state_for_product( $product, $mode );
			}

			$this->product_util->delete_product_specific_transients( $product ? $product : $product_id );
		}

		$this->release_batch_caches( $batch_ids, $post_types, $product_cache, $flush_shared_groups );
	}

	/**
	 * Release the object cache entries a processed batch left behind.
	 *
	 * Only this batch's entries are deleted. clean_post_cache() would invalidate wider
	 * cache state that the batch did not populate, and clean_object_term_cache() accepts
	 * a single object type while a batch can hold both products and variations.
	 *
	 * @param int[]             $batch_ids           Product ids in this batch.
	 * @param string[]          $post_types          Post types found in this batch.
	 * @param ProductCache|null $product_cache       The product object cache, when the feature is enabled.
	 * @param bool              $flush_shared_groups Whether the products and term-queries groups may be flushed.
	 */
	private function release_batch_caches( array $batch_ids, array $post_types, ?ProductCache $product_cache, bool $flush_shared_groups ): void {
		wp_cache_delete_multiple( $batch_ids, 'posts' );
		wp_cache_delete_multiple( $batch_ids, 'post_meta' );

		$taxonomies = array();
		foreach ( $post_types as $post_type ) {
			$taxonomies = array_merge( $taxonomies, get_object_taxonomies( $post_type ) );
		}

		foreach ( array_unique( $taxonomies ) as $taxonomy ) {
			wp_cache_delete_multiple( $batch_ids, "{$taxonomy}_relationships" );
		}

		// Remove entries reloaded while clearing product transients.
		if ( $product_cache ) {
			foreach ( $batch_ids as $product_id ) {
				$product_cache->remove( $product_id );
			}
		}

		if ( $flush_shared_groups ) {
			wp_cache_flush_group( 'products' );
			wp_cache_flush_group( 'term-queries' );
		} elseif ( wp_cache_supports( 'flush_runtime' ) ) {
			// A persistent cache keeps a request-local copy of everything it serves, so the
			// entries above that cannot be deleted by id would accumulate for the whole
			// backlog. Drop the in-memory copy only; the shared backend is untouched.
			wp_cache_flush_runtime();
		}
	}
}
