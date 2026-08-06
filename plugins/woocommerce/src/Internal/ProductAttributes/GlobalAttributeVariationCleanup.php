<?php
/**
 * Cleanup variations after a global product attribute is deleted.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Product_Variation;

/**
 * Trashes variations whose only attribute is a deleted global product attribute.
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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_attribute_deleted', array( $this, 'handle_woocommerce_attribute_deleted' ), 5, 3 );
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
		unset( $attribute_id, $name );

		if ( ! is_string( $taxonomy ) || 'pa_' !== substr( $taxonomy, 0, 3 ) ) {
			return;
		}

		$this->schedule_cleanup( $taxonomy, 0 );
	}

	/**
	 * Trash one batch of variations whose only attribute is the deleted global attribute.
	 *
	 * @internal
	 *
	 * @param string $taxonomy          Deleted attribute taxonomy name.
	 * @param int    $last_processed_id Last processed variation ID.
	 * @return void
	 */
	public function handle_wc_cleanup_variations_for_deleted_attribute( $taxonomy, $last_processed_id = 0 ): void {
		global $wpdb;

		if ( ! is_string( $taxonomy ) || 'pa_' !== substr( $taxonomy, 0, 3 ) ) {
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
		 * Filters the number of variations trashed per deleted global attribute cleanup batch.
		 *
		 * @param int    $batch_size Number of variations to trash. Default 50.
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
					AND NOT EXISTS (
						SELECT 1
						FROM {$wpdb->postmeta} other_attribute
						WHERE other_attribute.post_id = variations.ID
							AND other_attribute.meta_key LIKE %s
							AND other_attribute.meta_key <> %s
					)
				GROUP BY variations.ID
				ORDER BY variations.ID ASC
				LIMIT %d",
				$attribute_meta_key,
				ProductStatus::TRASH,
				$last_processed_id,
				$wpdb->esc_like( 'attribute_' ) . '%',
				$attribute_meta_key,
				$batch_size + 1
			)
		);

		$variation_ids = array_map( 'absint', $variation_ids );
		$has_more      = count( $variation_ids ) > $batch_size;
		if ( $has_more ) {
			array_pop( $variation_ids );
		}

		if ( empty( $variation_ids ) ) {
			return;
		}

		_prime_post_caches( $variation_ids, false, true );

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product_Variation || ProductStatus::TRASH === $variation->get_status( 'edit' ) ) {
				continue;
			}

			$variation->delete();
		}

		if ( $has_more ) {
			$this->schedule_cleanup( $taxonomy, end( $variation_ids ) );
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
				'source'   => 'woocommerce-variations',
				'taxonomy' => $taxonomy,
			)
		);
	}
}
