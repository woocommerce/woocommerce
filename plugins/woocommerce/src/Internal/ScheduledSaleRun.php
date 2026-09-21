<?php
/**
 * ScheduledSaleRun class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Product;

/**
 * Starts or ends scheduled sales, processing and releasing one batch at a time.
 *
 * @internal Just for internal use.
 * @since 11.3.0
 */
class ScheduledSaleRun {

	/**
	 * How many products are loaded and processed at a time.
	 *
	 * @var int
	 */
	public const BATCH_SIZE = 50;

	/**
	 * Mode for a sale that is starting.
	 *
	 * @var string
	 */
	public const MODE_START = 'start';

	/**
	 * Mode for a sale that is ending.
	 *
	 * @var string
	 */
	public const MODE_END = 'end';

	/**
	 * Unique product IDs in data-store order.
	 *
	 * @var int[]
	 */
	private array $product_ids;

	/**
	 * The sale mode.
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * The product utility.
	 *
	 * @var ProductUtil
	 */
	private ProductUtil $product_util;

	/**
	 * The product object cache, when the feature is enabled.
	 *
	 * @var ProductCache|null
	 */
	private ?ProductCache $product_cache;

	/**
	 * Whether the products and term-queries groups may be flushed.
	 *
	 * @var bool
	 */
	private bool $flush_shared_groups;

	/**
	 * Initialize a scheduled sale run.
	 *
	 * @param mixed[] $entries Product references returned by the data store.
	 * @param string  $mode    One of MODE_START or MODE_END.
	 * @throws \InvalidArgumentException When the sale mode is unsupported.
	 */
	public function __construct( array $entries, string $mode ) {
		if ( ! in_array( $mode, array( self::MODE_START, self::MODE_END ), true ) ) {
			throw new \InvalidArgumentException( 'Scheduled sale mode must be either start or end.' );
		}

		$this->mode        = $mode;
		$this->product_ids = array();

		foreach ( $entries as $entry ) {
			$product_id = $this->normalize_entry( $entry );
			if ( null !== $product_id ) {
				$this->product_ids[ $product_id ] = $product_id;
			}
		}
	}

	/**
	 * Process normalized product IDs in batches.
	 */
	public function process(): void {
		$this->product_util  = wc_get_container()->get( ProductUtil::class );
		$this->product_cache = FeaturesUtil::feature_is_enabled( ProductCacheController::FEATURE_NAME )
			? wc_get_container()->get( ProductCache::class )
			: null;

		// Shared groups can only be flushed when the cache belongs to this request.
		$this->flush_shared_groups = wp_cache_supports( 'flush_group' ) && ! wp_using_ext_object_cache();

		$total = count( $this->product_ids );
		for ( $offset = 0; $offset < $total; $offset += self::BATCH_SIZE ) {
			$this->process_batch( array_slice( $this->product_ids, $offset, self::BATCH_SIZE ) );
		}
	}

	/**
	 * Normalize a data-store row to a positive product ID.
	 *
	 * @param mixed $entry Product reference returned by the data store.
	 * @return int|null Positive product ID, or null for an invalid row.
	 */
	private function normalize_entry( $entry ): ?int {
		if ( $entry instanceof WC_Product ) {
			$entry = $entry->get_id();
		} elseif ( is_object( $entry ) ) {
			$entry = $entry->ID ?? null;
		}

		if ( is_int( $entry ) ) {
			$product_id = $entry;
		} elseif ( is_float( $entry ) && is_finite( $entry ) && floor( $entry ) === $entry ) {
			$product_id = (int) $entry;
		} elseif ( is_string( $entry ) && ctype_digit( $entry ) ) {
			$product_id = (int) $entry;
		} else {
			return null;
		}

		return $product_id > 0 ? $product_id : null;
	}

	/**
	 * Prime, process, and release one batch.
	 *
	 * @param int[] $batch_ids Product ids in this batch.
	 */
	private function process_batch( array $batch_ids ): void {
		_prime_post_caches( $batch_ids );

		// Capture post types before product saves evict the primed posts.
		$post_types = array_values( array_unique( array_filter( array_map( 'get_post_type', $batch_ids ) ) ) );

		foreach ( $batch_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				// Only the price changes, so this does not reschedule the sale event.
				wc_apply_sale_state_for_product( $product, $this->mode );
			}

			$this->product_util->delete_product_specific_transients( $product ? $product : $product_id );
		}

		$this->release_caches( $batch_ids, $post_types );
	}

	/**
	 * Release the object cache entries this batch left behind.
	 *
	 * Only this batch's entries are deleted. clean_post_cache() would invalidate wider
	 * cache state that the batch did not populate, and clean_object_term_cache() accepts
	 * a single object type while a batch can hold both products and variations.
	 *
	 * @param int[]    $batch_ids  Product ids in this batch.
	 * @param string[] $post_types Post types found in this batch.
	 */
	private function release_caches( array $batch_ids, array $post_types ): void {
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
		if ( $this->product_cache ) {
			foreach ( $batch_ids as $product_id ) {
				$this->product_cache->remove( $product_id );
			}
		}

		if ( $this->flush_shared_groups ) {
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
