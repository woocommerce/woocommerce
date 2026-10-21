<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve storefront images without changing the variation's saved fields.
 */
final class GalleryImageResolver {

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
		$gallery_ids = array_values( array_unique( $gallery_ids ) );

		if ( ! $featured_id && ! empty( $gallery_ids ) ) {
			$featured_id = array_shift( $gallery_ids );
		}

		if ( ! $featured_id ) {
			$image_ids = array_merge( array( $parent_featured_id ), $parent_gallery_ids );
			$image_ids = array_values( array_unique( array_filter( $image_ids ) ) );
			return $image_ids ? $image_ids : array( 0 );
		}

		return array_values( array_unique( array_merge( array( $featured_id ), $gallery_ids ? $gallery_ids : $parent_gallery_ids ) ) );
	}
}
