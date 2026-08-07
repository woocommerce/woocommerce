<?php
/**
 * Cleanup variations after a global product attribute is deleted.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Product_Variation;

/**
 * Removes deleted global product attributes from variations and trashes variations left without attributes.
 *
 * @internal
 *
 * @since 11.1.0
 */
class GlobalAttributeVariationCleanup implements RegisterHooksInterface {

	/**
	 * Action Scheduler hook for cleaning up variations after a global attribute is deleted.
	 */
	private const CLEANUP_ACTION = 'wc_cleanup_variations_for_deleted_attribute';

	/**
	 * Default number of variations to clean up in one action.
	 */
	private const DEFAULT_BATCH_SIZE = 50;

	/**
	 * Product object cache.
	 *
	 * @var ProductCache
	 */
	private ProductCache $product_cache;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param ProductCache $product_cache Product object cache.
	 * @return void
	 */
	final public function init( ProductCache $product_cache ): void {
		$this->product_cache = $product_cache;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 *
	 * @since 11.1.0
	 */
	public function register(): void {
		// Priority 50 to make sure this runs after WooCommerce attribute migrations.
		add_action( 'woocommerce_attribute_deleted', array( $this, 'handle_woocommerce_attribute_deleted' ), 10, 3 );
		add_action( self::CLEANUP_ACTION, array( $this, 'handle_wc_cleanup_variations_for_deleted_attribute' ), 10, 2 );
	}

	/**
	 * Schedule variation cleanup after a global attribute is deleted.
	 *
	 * @internal
	 *
	 * @param int    $attribute_id Attribute ID.
	 * @param string $name         Attribute name.
	 * @param string $taxonomy     Attribute taxonomy name.
	 * @return void
	 */
	public function handle_woocommerce_attribute_deleted( $attribute_id, $name, $taxonomy ): void {
		if ( ! taxonomy_is_product_attribute( $taxonomy ) ) {
			return;
		}

		$this->schedule_cleanup( $taxonomy, 0 );
	}

	/**
	 * Clean up one batch of variations that use the deleted global attribute.
	 *
	 * @internal
	 *
	 * @param string $taxonomy          Deleted attribute taxonomy name.
	 * @param int    $last_processed_id Last processed variation ID.
	 * @return void
	 */
	public function handle_wc_cleanup_variations_for_deleted_attribute( $taxonomy, $last_processed_id = 0 ): void {
		global $wpdb;

		if ( ! taxonomy_is_product_attribute( $taxonomy ) ) {
			return;
		}

		$taxonomy = wc_attribute_taxonomy_name( wc_attribute_taxonomy_slug( $taxonomy ) );
		if ( wc_attribute_taxonomy_id_by_name( $taxonomy ) ) {
			wc_get_logger()->warning(
				'Variation cleanup for a deleted global attribute was stopped because the attribute taxonomy exists again.',
				array(
					'source'   => 'woocommerce-variations',
					'taxonomy' => $taxonomy,
				)
			);
			return;
		}

		/**
		 * Filters the number of variations processed per deleted global attribute cleanup batch.
		 *
		 * @param int    $batch_size Number of variations to process. Default 50.
		 * @param string $taxonomy   Deleted attribute taxonomy name.
		 *
		 * @since 11.1.0
		 */
		$batch_size         = apply_filters( 'woocommerce_cleanup_variations_for_deleted_attribute_batch_size', self::DEFAULT_BATCH_SIZE, $taxonomy );
		$batch_size         = is_numeric( $batch_size ) ? max( 1, absint( $batch_size ) ) : self::DEFAULT_BATCH_SIZE;
		$attribute_meta_key = 'attribute_' . $taxonomy;
		$last_processed_id  = absint( $last_processed_id );
		$variation_ids      = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT variations.ID
				FROM {$wpdb->posts} variations
				INNER JOIN {$wpdb->postmeta} deleted_attribute
					ON deleted_attribute.post_id = variations.ID
					AND deleted_attribute.meta_key = %s
				WHERE variations.post_type = 'product_variation'
					AND variations.post_status <> %s
					AND variations.ID > %d
				GROUP BY variations.ID
				ORDER BY variations.ID ASC
				LIMIT %d",
				$attribute_meta_key,
				ProductStatus::TRASH,
				$last_processed_id,
				$batch_size + 1
			)
		);

		$variation_ids = array_map( 'absint', $variation_ids );
		if ( empty( $variation_ids ) ) {
			return;
		}

		$has_more = count( $variation_ids ) > $batch_size;
		if ( $has_more ) {
			array_pop( $variation_ids );
		}

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product_Variation || ProductStatus::TRASH === $variation->get_status() ) {
				continue;
			}

			if ( ! delete_post_meta( $variation_id, $attribute_meta_key ) ) {
				wc_get_logger()->warning(
					sprintf(
						'Failed to delete meta key %s for variation ID %d',
						$attribute_meta_key,
						$variation_id
					),
					array(
						'source'   => 'global-attribute-variation-cleanup',
						'taxonomy' => $taxonomy,
					)
				);
				continue;
			}

			$this->product_cache->remove( $variation_id );

			// Reload the variation to reflect the direct metadata change.
			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product_Variation || ProductStatus::TRASH === $variation->get_status() ) {
				continue;
			}

			// Check whether the variation has additional attributes and trash it if none remain.
			$attribute_meta_keys = array_filter(
				array_keys( get_post_meta( $variation_id ) ),
				static fn( $meta_key ) => is_string( $meta_key ) && 0 === strpos( $meta_key, 'attribute_' )
			);

			if ( empty( $attribute_meta_keys ) ) {
				$variation->delete();
			} else {
				// Refresh the variation title and attribute summary after removing the attribute meta.
				$variation->save();
			}
		}

		if ( $has_more ) {
			$this->schedule_cleanup( $taxonomy, (int) end( $variation_ids ) );
		}
	}

	/**
	 * Schedule the next deleted global attribute variation cleanup batch.
	 *
	 * @param string $taxonomy          Deleted attribute taxonomy name.
	 * @param int    $last_processed_id Last processed variation ID.
	 * @return void
	 */
	private function schedule_cleanup( string $taxonomy, $last_processed_id ): void {
		$args = array( $taxonomy, absint( $last_processed_id ) );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			if ( ! as_next_scheduled_action( self::CLEANUP_ACTION, $args, 'woocommerce' ) ) {
				as_schedule_single_action( time() + 1, self::CLEANUP_ACTION, $args, 'woocommerce' );
			}
			return;
		}

		wc_get_logger()->warning(
			'Action Scheduler unavailable for deleted global attribute variation cleanup.',
			array(
				'source'   => 'global-attribute-variation-cleanup',
				'taxonomy' => $taxonomy,
			)
		);
	}
}
