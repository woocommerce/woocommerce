<?php
/**
 * Batched migration of legacy variation gallery meta into core's native prop.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Migrate legacy variation gallery meta into WooCommerce's native variation gallery prop.
 *
 * Variations from the retired Additional Variation Images extension store their galleries
 * in `_wc_additional_variation_images`. Core now reads that meta at runtime as a fallback,
 * but the fallback adds extra postmeta lookups to every unresolved variation gallery read.
 * This runner copies legacy values into `_product_image_gallery` once and marks each
 * variation as finalized so the runtime fallback can stop executing.
 *
 * MIGRATED 10.8.0: legacy meta `_wc_additional_variation_images` is intentionally NOT
 * deleted by this runner. Third-party code (custom themes, exporters, integrations) may
 * still read it directly, and a merchant who later reactivates the legacy extension
 * should not lose their source data. The
 * `_wc_variation_gallery_legacy_fallback_disabled` sentinel is therefore the
 * source-of-truth marker — its presence signals that core has taken authority over the
 * gallery for that variation, regardless of what `_wc_additional_variation_images` still
 * contains.
 */
class Migration {

	/**
	 * Number of variations processed per batch.
	 */
	private const BATCH_SIZE = 250;

	/**
	 * Option name recording when the migration finished.
	 */
	public const COMPLETED_OPTION = 'wc_variation_gallery_migration_completed_at';

	/**
	 * Run one batch of the migration.
	 *
	 * @return bool Whether there are pending migration records.
	 */
	public static function run(): bool {
		global $wpdb;

		$legacy_meta_key      = '_wc_additional_variation_images';
		$core_gallery_meta    = '_product_image_gallery';
		$fallback_disabled    = LegacyVariationGalleryCompatibility::get_core_managed_meta_key();
		$select_variation_ids = static function ( int $limit ) use ( $wpdb, $legacy_meta_key, $fallback_disabled ): array {
			$query = $wpdb->prepare(
				"SELECT legacy.post_id
				FROM {$wpdb->postmeta} AS legacy
				INNER JOIN {$wpdb->posts} AS posts
					ON posts.ID = legacy.post_id
					AND posts.post_type = 'product_variation'
				LEFT JOIN {$wpdb->postmeta} AS disabled
					ON disabled.post_id = legacy.post_id
					AND disabled.meta_key = %s
				WHERE legacy.meta_key = %s
					AND legacy.meta_value <> ''
					AND disabled.post_id IS NULL
				GROUP BY legacy.post_id
				ORDER BY legacy.post_id ASC
				LIMIT %d",
				$fallback_disabled,
				$legacy_meta_key,
				$limit
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
			return array_map( 'intval', $wpdb->get_col( $query ) );
		};

		$variation_ids = $select_variation_ids( self::BATCH_SIZE );

		foreach ( $variation_ids as $variation_id ) {
			$legacy_gallery_image_ids = wp_parse_id_list( get_post_meta( $variation_id, $legacy_meta_key, true ) );
			$core_gallery_image_ids   = wp_parse_id_list( get_post_meta( $variation_id, $core_gallery_meta, true ) );

			if ( empty( $core_gallery_image_ids ) && ! empty( $legacy_gallery_image_ids ) ) {
				update_post_meta( $variation_id, $core_gallery_meta, implode( ',', $legacy_gallery_image_ids ) );
			}

			LegacyVariationGalleryCompatibility::mark_variation_id_core_managed( $variation_id );
		}

		$has_more = ! empty( $select_variation_ids( 1 ) );

		if ( ! $has_more ) {
			update_option( self::COMPLETED_OPTION, (string) time() );

			Telemetry::record_event(
				Telemetry::EVENT_MIGRATION_COMPLETED,
				array(
					'batched_variation_count' => count( $variation_ids ),
				)
			);
		}

		return $has_more;
	}
}
