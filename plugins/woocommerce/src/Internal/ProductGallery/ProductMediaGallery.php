<?php
/**
 * Product media gallery utilities.
 *
 * @package Automattic\WooCommerce\Internal\ProductGallery
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductGallery;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for product media gallery data.
 */
class ProductMediaGallery {

	/**
	 * The feature id used by `FeaturesController` (Settings → Advanced → Features).
	 */
	public const FEATURE_ID = 'product_gallery_videos';

	/**
	 * Option backing the product gallery videos feature toggle.
	 */
	public const ENABLE_OPTION_NAME = 'woocommerce_feature_product_gallery_videos_enabled';

	/**
	 * Check if product gallery videos are enabled.
	 *
	 * @return bool
	 */
	public static function is_feature_enabled(): bool {
		return function_exists( 'wc_product_gallery_videos_enabled' ) && wc_product_gallery_videos_enabled();
	}

	/**
	 * Get ordered media gallery items for a product.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $args    Optional arguments.
	 * @return array
	 */
	public static function get_product_media_gallery_items( WC_Product $product, array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'context'               => 'view',
				'include_product_image' => true,
				'include_placeholder'   => false,
				'feature_enabled'       => self::is_feature_enabled(),
				'validate_attachments'  => false,
				'preserve_video_data'   => true,
				'resolve_video_posters' => true,
				'deduplicate'           => false,
			)
		);

		$context     = is_string( $args['context'] ) ? $args['context'] : 'view';
		$media_items = array();

		if ( $args['feature_enabled'] ) {
			$media_items = self::normalize_media_gallery_items(
				$product->get_media_gallery( $context ),
				(bool) $args['validate_attachments'],
				(bool) $args['preserve_video_data']
			);

			if ( ! empty( $media_items ) ) {
				$media_items = $args['include_product_image']
					? self::maybe_prepend_product_image( $product, $media_items, $context )
					: self::maybe_remove_product_image( $product, $media_items, $context );
			}
		}

		if ( empty( $media_items ) ) {
			$media_items = self::get_product_image_media_items( $product, (bool) $args['include_product_image'], $context );
		}

		if ( $args['deduplicate'] ) {
			$media_items = self::deduplicate_media_items( $media_items );
		}

		if ( $args['resolve_video_posters'] ) {
			$media_items = self::resolve_video_poster_ids( $media_items );
		}

		if ( $args['include_placeholder'] && empty( $media_items ) ) {
			$media_items[] = array(
				'media_type'  => 'image',
				'source_type' => 'placeholder',
				'id'          => 0,
			);
		}

		return array_values( $media_items );
	}

	/**
	 * Normalize media gallery items.
	 *
	 * @param array $media_gallery Media gallery data.
	 * @param bool  $validate_attachments Whether attachment IDs should be type-checked.
	 * @param bool  $preserve_video_data Whether to preserve additional video item keys.
	 * @return array
	 */
	public static function normalize_media_gallery_items(
		array $media_gallery,
		bool $validate_attachments = false,
		bool $preserve_video_data = true
	): array {
		$items = array();

		foreach ( $media_gallery as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$media_type    = isset( $item['media_type'] ) ? sanitize_key( $item['media_type'] ) : '';
			$source_type   = isset( $item['source_type'] ) ? sanitize_key( $item['source_type'] ) : 'attachment';
			$attachment_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;

			if ( 'attachment' !== $source_type || ! $attachment_id ) {
				continue;
			}

			if ( 'image' === $media_type ) {
				if (
					$validate_attachments &&
					! wp_attachment_is_image( $attachment_id ) &&
					0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' )
				) {
					continue;
				}

				$items[] = self::get_image_media_item( $attachment_id );
				continue;
			}

			if ( 'video' !== $media_type ) {
				continue;
			}

			if ( $validate_attachments ) {
				$mime_type = get_post_mime_type( $attachment_id );

				if ( ! is_string( $mime_type ) || 0 !== strpos( $mime_type, 'video/' ) ) {
					continue;
				}
			}

			$video_item = $preserve_video_data ? wc_clean( $item ) : array();
			$video_item = is_array( $video_item ) ? $video_item : array();
			$poster_id  = isset( $item['poster_id'] ) ? absint( $item['poster_id'] ) : 0;

			$video_item['media_type']  = 'video';
			$video_item['source_type'] = 'attachment';
			$video_item['id']          = $attachment_id;

			if (
				$poster_id &&
				(
					! $validate_attachments ||
					wp_attachment_is_image( $poster_id ) ||
					0 === strpos( (string) get_post_mime_type( $poster_id ), 'image/' )
				)
			) {
				$video_item['poster_id'] = $poster_id;
			} else {
				unset( $video_item['poster_id'] );
			}

			$items[] = $video_item;
		}

		return $items;
	}

	/**
	 * Get image media items from product image props.
	 *
	 * @param WC_Product $product               Product object.
	 * @param bool       $include_product_image Whether to include the product image.
	 * @param string     $context               Product read context.
	 * @return array
	 */
	public static function get_product_image_media_items( WC_Product $product, bool $include_product_image = true, string $context = 'view' ): array {
		$attachment_ids = array();

		if ( $include_product_image && $product->get_image_id( $context ) ) {
			$attachment_ids[] = $product->get_image_id( $context );
		}

		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids( $context ) );
		$media_items    = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );

			if ( $attachment_id ) {
				$media_items[] = self::get_image_media_item( $attachment_id );
			}
		}

		return $media_items;
	}

	/**
	 * Move the product image to the front of media gallery data.
	 *
	 * @param WC_Product $product       Product object.
	 * @param array      $media_gallery Media gallery items.
	 * @param string     $context       Product read context.
	 * @return array
	 */
	public static function maybe_prepend_product_image( WC_Product $product, array $media_gallery, string $context = 'view' ): array {
		$product_image_id = absint( $product->get_image_id( $context ) );

		if ( ! $product_image_id ) {
			return $media_gallery;
		}

		foreach ( $media_gallery as $index => $item ) {
			if ( ! is_array( $item ) || ! self::media_item_is_product_image( $item, $product_image_id ) ) {
				continue;
			}

			if ( 0 === $index ) {
				return $media_gallery;
			}

			unset( $media_gallery[ $index ] );
			array_unshift( $media_gallery, self::get_image_media_item( $product_image_id ) );

			return array_values( $media_gallery );
		}

		array_unshift( $media_gallery, self::get_image_media_item( $product_image_id ) );

		return $media_gallery;
	}

	/**
	 * Remove the product image when it is represented as the first gallery item.
	 *
	 * @param WC_Product $product       Product object.
	 * @param array      $media_gallery Media gallery items.
	 * @param string     $context       Product read context.
	 * @return array
	 */
	private static function maybe_remove_product_image( WC_Product $product, array $media_gallery, string $context = 'view' ): array {
		$product_image_id = absint( $product->get_image_id( $context ) );

		if (
			$product_image_id &&
			isset( $media_gallery[0] ) &&
			is_array( $media_gallery[0] ) &&
			self::media_item_is_product_image( $media_gallery[0], $product_image_id )
		) {
			array_shift( $media_gallery );
		}

		return array_values( $media_gallery );
	}

	/**
	 * Check if a media gallery item represents the product image.
	 *
	 * @param array $item             Media gallery item.
	 * @param int   $product_image_id Product image attachment ID.
	 * @return bool
	 */
	private static function media_item_is_product_image( array $item, int $product_image_id ): bool {
		return 'image' === ( $item['media_type'] ?? '' ) && absint( $item['id'] ?? 0 ) === $product_image_id;
	}

	/**
	 * Get image IDs from media gallery items.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return array
	 */
	public static function get_image_ids( array $media_gallery ): array {
		$image_ids = array();

		foreach ( $media_gallery as $item ) {
			if (
				is_array( $item ) &&
				'image' === ( $item['media_type'] ?? '' ) &&
				'attachment' === ( $item['source_type'] ?? 'attachment' ) &&
				! empty( $item['id'] )
			) {
				$image_ids[] = absint( $item['id'] );
			}
		}

		return array_values( array_filter( $image_ids ) );
	}

	/**
	 * Check if media gallery contains video items.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return bool
	 */
	public static function has_videos( array $media_gallery ): bool {
		foreach ( $media_gallery as $item ) {
			if ( is_array( $item ) && 'video' === ( $item['media_type'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the poster attachment ID for a gallery video item.
	 *
	 * @param array $media_item Product media gallery item.
	 * @return int
	 */
	public static function get_video_poster_id( array $media_item ): int {
		$poster_id = isset( $media_item['poster_id'] ) ? absint( $media_item['poster_id'] ) : 0;

		if ( $poster_id ) {
			return $poster_id;
		}

		$attachment_id = isset( $media_item['id'] ) ? absint( $media_item['id'] ) : 0;

		return $attachment_id ? (int) get_post_thumbnail_id( $attachment_id ) : 0;
	}

	/**
	 * Get attachment IDs represented by media gallery items.
	 *
	 * @param array $media_gallery   Media gallery items.
	 * @param bool  $include_posters Whether to include resolved video poster IDs.
	 * @return array
	 */
	private static function get_attachment_ids( array $media_gallery, bool $include_posters = false ): array {
		$attachment_ids = array();

		foreach ( $media_gallery as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( 'attachment' === ( $item['source_type'] ?? '' ) && ! empty( $item['id'] ) ) {
				$attachment_ids[] = absint( $item['id'] );
			}

			if ( $include_posters && 'video' === ( $item['media_type'] ?? '' ) ) {
				$attachment_ids[] = self::get_video_poster_id( $item );
			}
		}

		return array_values( array_unique( array_filter( $attachment_ids ) ) );
	}

	/**
	 * Prime attachment post caches for media gallery items.
	 *
	 * @param array $media_gallery   Media gallery items.
	 * @param bool  $include_posters Whether to include resolved video poster IDs.
	 * @return void
	 */
	public static function prime_attachment_caches( array $media_gallery, bool $include_posters = false ): void {
		$attachment_ids = self::get_attachment_ids( $media_gallery, $include_posters );

		if ( ! empty( $attachment_ids ) ) {
			_prime_post_caches( $attachment_ids );
		}
	}

	/**
	 * Get a normalized image media item.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array
	 */
	private static function get_image_media_item( int $attachment_id ): array {
		return array(
			'media_type'  => 'image',
			'source_type' => 'attachment',
			'id'          => $attachment_id,
		);
	}

	/**
	 * Resolve poster IDs for video items.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return array
	 */
	private static function resolve_video_poster_ids( array $media_gallery ): array {
		foreach ( $media_gallery as $index => $media_item ) {
			if ( ! is_array( $media_item ) || 'video' !== ( $media_item['media_type'] ?? '' ) ) {
				continue;
			}

			$poster_id = self::get_video_poster_id( $media_item );

			if ( $poster_id ) {
				$media_gallery[ $index ]['poster_id'] = $poster_id;
			}
		}

		return $media_gallery;
	}

	/**
	 * Remove duplicate media items by attachment ID.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return array
	 */
	private static function deduplicate_media_items( array $media_gallery ): array {
		$seen_ids = array();
		$items    = array();

		foreach ( $media_gallery as $media_item ) {
			if ( ! is_array( $media_item ) ) {
				continue;
			}

			$attachment_id = isset( $media_item['id'] ) ? absint( $media_item['id'] ) : 0;

			if ( $attachment_id && isset( $seen_ids[ $attachment_id ] ) ) {
				continue;
			}

			if ( $attachment_id ) {
				$seen_ids[ $attachment_id ] = true;
			}

			$items[] = $media_item;
		}

		return $items;
	}
}
