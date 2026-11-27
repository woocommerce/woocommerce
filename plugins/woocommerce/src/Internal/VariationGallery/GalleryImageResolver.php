<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Choose the image order for a selected variation.
 *
 * @internal
 */
final class GalleryImageResolver {

	/**
	 * Missing attachment IDs for the current site's post cache version.
	 *
	 * @var array<int, bool>
	 */
	private static $missing_attachment_ids = array();

	/**
	 * Site and post-cache version associated with the missing IDs.
	 *
	 * @var string
	 */
	private static $post_cache_version = '';

	/**
	 * Get a valid variation main image, excluding the automatic parent fallback.
	 *
	 * @since 11.5.0
	 *
	 * @param \WC_Product_Variation $variation The selected variation.
	 * @return int Featured image ID, or zero when no variation image is available.
	 */
	public static function get_variation_featured_image_id( \WC_Product_Variation $variation ): int {
		$image_id = $variation->get_image_id();
		$image_id = is_numeric( $image_id ) ? (int) $image_id : 0;
		if ( ! $variation->get_image_id( 'edit' ) ) {
			$parent_data     = $variation->get_parent_data();
			$parent_image_id = (int) ( $parent_data['image_id'] ?? 0 );
			if ( $image_id === $parent_image_id ) {
				return 0;
			}
		}

		return self::has_image_preview( $image_id ) ? $image_id : 0;
	}

	/**
	 * Find the first gallery attachment with an image or generated preview.
	 *
	 * @since 11.5.0
	 *
	 * @param array $gallery_ids Gallery values, possibly supplied by a filter.
	 * @return int Image ID, or zero when no usable photo is available.
	 */
	public static function get_first_gallery_image_id( array $gallery_ids ): int {
		$image_ids = array();
		foreach ( $gallery_ids as $image_id ) {
			if ( ( is_int( $image_id ) || ( is_string( $image_id ) && ctype_digit( $image_id ) ) ) && (int) $image_id > 0 ) {
				$image_ids[] = (int) $image_id;
			}
		}
		if ( empty( $image_ids ) ) {
			return 0;
		}
		$missing_ids = self::get_missing_attachment_ids();
		$image_ids   = array_filter( ArrayUtil::unique_truthy_values( $image_ids ), static fn( $id ) => ! isset( $missing_ids[ $id ] ) );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}
		foreach ( $image_ids as $image_id ) {
			if ( self::has_image_preview( $image_id ) ) {
				return $image_id;
			}
		}
		return 0;
	}

	/**
	 * Check whether an attachment is an image or has a generated image preview.
	 *
	 * @since 11.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool Whether the attachment can be displayed in the image gallery.
	 */
	public static function has_image_preview( int $attachment_id ): bool {
		if ( $attachment_id <= 0 ) {
			return false;
		}
		$missing_ids = self::get_missing_attachment_ids();
		if ( isset( $missing_ids[ $attachment_id ] ) ) {
			return false;
		}
		if ( ! get_post( $attachment_id ) ) {
			self::$missing_attachment_ids[ $attachment_id ] = true;
			return false;
		}
		return wp_attachment_is_image( $attachment_id ) || (bool) wp_get_attachment_image_src( $attachment_id, 'full' );
	}

	/**
	 * Reuse missing-post results until post data changes, including on another site.
	 *
	 * @return array<int, bool> Missing attachment IDs.
	 */
	private static function get_missing_attachment_ids(): array {
		$version = get_current_blog_id() . ':' . wp_cache_get_last_changed( 'posts' );
		if ( self::$post_cache_version !== $version ) {
			self::$post_cache_version     = $version;
			self::$missing_attachment_ids = array();
		}
		return self::$missing_attachment_ids;
	}

	/**
	 * Treat the first gallery-only photo as featured, as the editor does on save.
	 *
	 * @since 11.5.0
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

		// A main photo repeated in the gallery does not count as an extra photo.
		$gallery_ids     = array_values( array_diff( $gallery_ids, array( $featured_id ) ) );
		$extra_image_ids = $gallery_ids ? $gallery_ids : $parent_gallery_ids;
		$image_ids       = array_merge( array( $featured_id ), $extra_image_ids );

		return ArrayUtil::unique_truthy_values( $image_ids );
	}
}
