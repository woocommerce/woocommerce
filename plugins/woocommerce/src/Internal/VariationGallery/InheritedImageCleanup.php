<?php
/**
 * Batched cleanup of variation featured images frozen by the inherited-image save bug.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Repair parent images persisted onto variations after Variation Gallery reached 100% rollout.
 *
 * Matching values are removed once to restore dynamic inheritance.
 */
class InheritedImageCleanup {

	/**
	 * Number of variations processed per batch.
	 */
	private const BATCH_SIZE = 250;

	/**
	 * Option name storing progress between batches.
	 */
	private const STATE_OPTION = 'wc_variation_gallery_inherited_image_cleanup_state';

	/**
	 * Option name recording when the cleanup finished.
	 */
	public const COMPLETED_OPTION = 'wc_variation_gallery_inherited_image_cleanup_completed_at';

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

		$state = get_option( self::STATE_OPTION, array() );
		$state = is_array( $state ) ? $state : array();

		$query = $wpdb->prepare(
			"SELECT variation.ID AS variation_id,
				GROUP_CONCAT(DISTINCT variation_thumb.meta_value) AS inherited_image_ids
			FROM {$wpdb->posts} AS variation
			INNER JOIN {$wpdb->postmeta} AS variation_thumb
				ON variation_thumb.post_id = variation.ID
				AND variation_thumb.meta_key = '_thumbnail_id'
			INNER JOIN {$wpdb->postmeta} AS parent_thumb
				ON parent_thumb.post_id = variation.post_parent
				AND parent_thumb.meta_key = '_thumbnail_id'
			WHERE variation.ID > %d
				AND variation.post_type = 'product_variation'
				AND variation_thumb.meta_value <> ''
				AND variation_thumb.meta_value = parent_thumb.meta_value
			GROUP BY variation.ID
			ORDER BY variation.ID ASC
			LIMIT %d",
			(int) ( $state['last_processed_id'] ?? 0 ),
			self::BATCH_SIZE + 1
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		$matching_variations = (array) $wpdb->get_results( $query, ARRAY_A );
		$has_more            = count( $matching_variations ) > self::BATCH_SIZE;
		$matching_variations = array_slice( $matching_variations, 0, self::BATCH_SIZE );

		foreach ( $matching_variations as $matching_variation ) {
			foreach ( wp_parse_id_list( $matching_variation['inherited_image_ids'] ) as $inherited_image_id ) {
				delete_post_meta( (int) $matching_variation['variation_id'], '_thumbnail_id', $inherited_image_id );
			}
		}

		$cleaned_count = (int) ( $state['cleaned_count'] ?? 0 ) + count( $matching_variations );

		if ( $has_more ) {
			$last_processed = end( $matching_variations );
			update_option(
				self::STATE_OPTION,
				array(
					'last_processed_id' => (int) $last_processed['variation_id'],
					'cleaned_count'     => $cleaned_count,
				),
				false
			);

			return true;
		}

		delete_option( self::STATE_OPTION );
		update_option( self::COMPLETED_OPTION, time() );
		Telemetry::record_event(
			Telemetry::EVENT_INHERITED_IMAGE_CLEANUP_COMPLETED,
			array( 'cleaned_count' => $cleaned_count )
		);

		return false;
	}
}
