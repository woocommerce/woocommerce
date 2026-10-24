<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Give existing gallery-only variations a featured image in bounded batches.
 */
final class OrphanedGalleryMigration {

	/** Number of variations scanned per batch. */
	private const BATCH_SIZE = 250;

	/** Last scanned variation ID. */
	public const LAST_ID_OPTION = 'woocommerce_orphaned_variation_gallery_last_id';

	/** Timestamp marking completion of the one-time migration. */
	public const COMPLETED_OPTION = 'woocommerce_orphaned_variation_gallery_completed_at';

	/**
	 * Process one batch of variations with gallery photos but no featured image.
	 *
	 * @internal
	 * @since 11.4.0
	 *
	 * @return bool Whether another batch is needed.
	 * @throws \RuntimeException When the batch cannot be read.
	 */
	public static function run(): bool {
		global $wpdb;

		if ( get_option( self::COMPLETED_OPTION ) ) {
			return false;
		}

		// Legacy galleries must reach the core field before this one-time scan.
		if ( ! get_option( Migration::COMPLETED_OPTION ) && Migration::run() ) {
			return true;
		}

		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT gallery.post_id
				FROM {$wpdb->postmeta} AS gallery
				INNER JOIN {$wpdb->posts} AS variation ON variation.ID = gallery.post_id
				WHERE gallery.meta_key = '_product_image_gallery'
					AND gallery.meta_value <> ''
					AND variation.post_type = 'product_variation'
					AND gallery.post_id > %d
					AND NOT EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} AS thumbnail
						WHERE thumbnail.post_id = gallery.post_id
							AND thumbnail.meta_key = '_thumbnail_id'
							AND thumbnail.meta_value NOT IN ('', '0')
					)
				ORDER BY gallery.post_id ASC
				LIMIT %d",
				(int) get_option( self::LAST_ID_OPTION, 0 ),
				self::BATCH_SIZE + 1
			)
		);

		if ( $wpdb->last_error ) {
			throw new \RuntimeException( 'Could not select orphaned variation galleries.' );
		}

		$has_more      = count( $variation_ids ) > self::BATCH_SIZE;
		$variation_ids = array_map( 'intval', array_slice( $variation_ids, 0, self::BATCH_SIZE ) );
		self::prime_batch_caches( $variation_ids );

		foreach ( $variation_ids as $variation_id ) {
			self::promote_first_gallery_image( $variation_id );
		}

		if ( $has_more ) {
			update_option( self::LAST_ID_OPTION, end( $variation_ids ), false );
		} else {
			update_option( self::COMPLETED_OPTION, time(), false );
			delete_option( self::LAST_ID_OPTION );
		}

		return $has_more;
	}

	/**
	 * Load variations, their parents, and gallery attachments into the object cache.
	 *
	 * @param int[] $variation_ids Variation IDs in this batch.
	 */
	private static function prime_batch_caches( array $variation_ids ): void {
		if ( ! $variation_ids ) {
			return;
		}

		_prime_post_caches( $variation_ids );
		$related_post_ids = array();
		foreach ( $variation_ids as $variation_id ) {
			$related_post_ids[] = wp_get_post_parent_id( $variation_id );
			$related_post_ids   = array_merge( $related_post_ids, wp_parse_id_list( get_post_meta( $variation_id, '_product_image_gallery', true ) ) );
		}
		$related_post_ids = ArrayUtil::unique_truthy_values( $related_post_ids );
		if ( $related_post_ids ) {
			_prime_post_caches( $related_post_ids );
		}
	}

	/**
	 * Use the first valid gallery photo as the variation's featured image.
	 *
	 * @param int $variation_id Variation to update.
	 */
	private static function promote_first_gallery_image( int $variation_id ): void {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof \WC_Product_Variation || $variation->get_image_id( 'edit' ) ) {
			return;
		}

		$gallery_ids = $variation->get_gallery_image_ids( 'edit' );
		$valid_ids   = array_values( array_filter( $gallery_ids, 'wp_attachment_is_image' ) );
		if ( ! $valid_ids ) {
			return;
		}

		if ( get_post_meta( $variation_id, '_thumbnail_id', true ) ) {
			return;
		}

		$variation->set_image_id( $valid_ids[0] );
		$variation->set_gallery_image_ids( array_values( array_diff( $gallery_ids, array( $valid_ids[0] ) ) ) );
		LegacyVariationGalleryCompatibility::mark_core_managed( $variation );
		$variation->save();
	}
}
