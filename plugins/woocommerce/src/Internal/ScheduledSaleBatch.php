<?php
/**
 * ScheduledSaleBatch class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;

/**
 * Starts or ends a scheduled sale for one batch of products, then releases the
 * cache entries populated while processing that batch.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
class ScheduledSaleBatch {

	/**
	 * Product ids in this batch.
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
	 * Initialize a scheduled sale batch.
	 *
	 * @param (int|string)[]    $product_ids        Product ids for this batch.
	 * @param string            $mode               'start' or 'end'.
	 * @param ProductUtil       $product_util       The product utility.
	 * @param ProductCache|null $product_cache      The product object cache, when the feature is enabled.
	 * @param bool              $flush_shared_groups Whether the products and term-queries groups may be flushed.
	 * @throws \InvalidArgumentException When the sale mode is unsupported.
	 */
	public function __construct( array $product_ids, string $mode, ProductUtil $product_util, ?ProductCache $product_cache, bool $flush_shared_groups ) {
		if ( ! in_array( $mode, array( 'start', 'end' ), true ) ) {
			throw new \InvalidArgumentException( 'Scheduled sale mode must be either start or end.' );
		}

		$this->product_ids         = $this->normalize_ids( $product_ids );
		$this->mode                = $mode;
		$this->product_util        = $product_util;
		$this->product_cache       = $product_cache;
		$this->flush_shared_groups = $flush_shared_groups;
	}

	/**
	 * Prime, process, and release this batch.
	 */
	public function process(): void {
		if ( ! $this->product_ids ) {
			return;
		}

		_prime_post_caches( $this->product_ids );

		// Capture post types before product saves evict the primed posts.
		$post_types = array_values( array_unique( array_filter( array_map( 'get_post_type', $this->product_ids ) ) ) );

		foreach ( $this->product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				// Only the price changes, so this does not reschedule the sale event.
				wc_apply_sale_state_for_product( $product, $this->mode );
			}

			$this->product_util->delete_product_specific_transients( $product ? $product : $product_id );
		}

		$this->release_caches( $post_types );
	}

	/**
	 * Normalize product ids to unique, positive integers.
	 *
	 * @param (int|string)[] $product_ids Product ids.
	 * @return int[] Unique product ids, in their original order.
	 */
	private function normalize_ids( array $product_ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
	}

	/**
	 * Release the object cache entries this batch left behind.
	 *
	 * Only this batch's entries are deleted. clean_post_cache() would invalidate wider
	 * cache state that the batch did not populate, and clean_object_term_cache() accepts
	 * a single object type while a batch can hold both products and variations.
	 *
	 * @param string[] $post_types Post types found in this batch.
	 */
	private function release_caches( array $post_types ): void {
		wp_cache_delete_multiple( $this->product_ids, 'posts' );
		wp_cache_delete_multiple( $this->product_ids, 'post_meta' );

		$taxonomies = array();
		foreach ( $post_types as $post_type ) {
			$taxonomies = array_merge( $taxonomies, get_object_taxonomies( $post_type ) );
		}

		foreach ( array_unique( $taxonomies ) as $taxonomy ) {
			wp_cache_delete_multiple( $this->product_ids, "{$taxonomy}_relationships" );
		}

		// Remove entries reloaded while clearing product transients.
		if ( $this->product_cache ) {
			foreach ( $this->product_ids as $product_id ) {
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
