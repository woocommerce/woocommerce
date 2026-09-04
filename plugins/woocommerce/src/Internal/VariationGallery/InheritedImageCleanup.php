<?php
/**
 * Batched cleanup of variation featured images frozen by the inherited-image save bug.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Remove variation `_thumbnail_id` values that duplicate the parent product's
 * featured image, restoring dynamic inheritance.
 *
 * Saving a variation through the classic editor used to persist the parent's
 * featured image onto the variation (see ClassicVariationGalleryAdmin). While
 * the stored ID still equals the parent's current featured image, deleting it
 * is display-neutral and re-enables inheritance. Values that have diverged are
 * left untouched: they are indistinguishable from a deliberate merchant choice.
 *
 * Runs once. Merchants who intentionally select the parent's image after this
 * cleanup keep it, so this must never be re-scheduled.
 */
class InheritedImageCleanup {

	/**
	 * Number of variations processed per batch.
	 */
	private const BATCH_SIZE = 250;

	/**
	 * Option name recording when the cleanup finished.
	 */
	public const COMPLETED_OPTION = 'wc_variation_gallery_inherited_image_cleanup_completed_at';

	/**
	 * Option name accumulating the number of cleaned variations across batches.
	 */
	public const CLEANED_COUNT_OPTION = 'wc_variation_gallery_inherited_image_cleanup_count';

	/**
	 * Run one batch of the cleanup.
	 *
	 * @return bool Whether there are pending records.
	 */
	public static function run(): bool {
		global $wpdb;

		if ( get_option( self::COMPLETED_OPTION ) ) {
			return false;
		}

		$select_variation_ids = static function ( int $limit ) use ( $wpdb ): array {
			$query = $wpdb->prepare(
				"SELECT variation.ID
				FROM {$wpdb->posts} AS variation
				INNER JOIN {$wpdb->postmeta} AS variation_thumb
					ON variation_thumb.post_id = variation.ID
					AND variation_thumb.meta_key = '_thumbnail_id'
				INNER JOIN {$wpdb->postmeta} AS parent_thumb
					ON parent_thumb.post_id = variation.post_parent
					AND parent_thumb.meta_key = '_thumbnail_id'
				WHERE variation.post_type = 'product_variation'
					AND variation_thumb.meta_value <> ''
					AND variation_thumb.meta_value = parent_thumb.meta_value
				ORDER BY variation.ID ASC
				LIMIT %d",
				$limit
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
			return array_map( 'intval', $wpdb->get_col( $query ) );
		};

		$variation_ids = $select_variation_ids( self::BATCH_SIZE );

		foreach ( $variation_ids as $variation_id ) {
			delete_post_meta( $variation_id, '_thumbnail_id' );
		}

		if ( ! empty( $variation_ids ) ) {
			update_option( self::CLEANED_COUNT_OPTION, (int) get_option( self::CLEANED_COUNT_OPTION, 0 ) + count( $variation_ids ), false );
		}

		$has_more = ! empty( $select_variation_ids( 1 ) );

		// Guard against duplicate completion events if this runner is invoked twice.
		if ( ! $has_more && ! get_option( self::COMPLETED_OPTION ) ) {
			update_option( self::COMPLETED_OPTION, time() );
			Telemetry::record_event(
				Telemetry::EVENT_INHERITED_IMAGE_CLEANUP_COMPLETED,
				array( 'cleaned_count' => (int) get_option( self::CLEANED_COUNT_OPTION, 0 ) )
			);
		}

		return $has_more;
	}
}
