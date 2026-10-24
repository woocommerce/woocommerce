<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Choose the image order for a selected variation.
 */
final class GalleryImageResolver {

	/**
	 * Get a valid variation featured image, excluding the automatic parent fallback.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Product_Variation $variation The selected variation.
	 * @return int Featured image ID, or zero when no variation image is available.
	 */
	public static function get_variation_featured_image_id( \WC_Product_Variation $variation ): int {
		$image_id = (int) $variation->get_image_id();
		if ( ! $variation->get_image_id( 'edit' ) ) {
			$parent_data     = $variation->get_parent_data();
			$parent_image_id = (int) ( $parent_data['image_id'] ?? 0 );
			if ( $image_id === $parent_image_id ) {
				return 0;
			}
		}

		// Filters can supply a variation image even when none is saved.
		return $image_id && wp_attachment_is_image( $image_id ) ? $image_id : 0;
	}

	/**
	 * Treat the first gallery-only photo as featured, as the editor does on save.
	 *
	 * @since 11.4.0
	 *
	 * @param int   $featured_id          Valid variation featured image, or zero.
	 * @param int[] $gallery_ids          Valid variation gallery images.
	 * @param int   $parent_featured_id   Valid parent featured image, or zero.
	 * @param int[] $parent_gallery_ids   Valid parent gallery extras.
	 * @return int[] Ordered storefront image IDs, or zero for the placeholder.
	 */
	public static function resolve( int $featured_id, array $gallery_ids, int $parent_featured_id, array $parent_gallery_ids ): array {
		$gallery_ids = ArrayUtil::unique_truthy_values( $gallery_ids );

		if ( ! $featured_id && ! empty( $gallery_ids ) ) {
			$featured_id = array_shift( $gallery_ids );
		}

		if ( ! $featured_id ) {
			$image_ids = ArrayUtil::unique_truthy_values( array_merge( array( $parent_featured_id ), $parent_gallery_ids ) );
			return $image_ids ? $image_ids : array( 0 );
		}

		$extra_image_ids = $gallery_ids ? $gallery_ids : $parent_gallery_ids;
		$image_ids       = array_merge( array( $featured_id ), $extra_image_ids );

		return ArrayUtil::unique_truthy_values( $image_ids );
	}
}
