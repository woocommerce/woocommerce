<?php
/**
 * Product media gallery utilities.
 *
 * @package Automattic\WooCommerce\Internal\ProductGallery
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductGallery;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for product media gallery data.
 */
class ProductMediaGallery {

	/**
	 * Product meta key used for beta product video gallery storage.
	 */
	private const VIDEO_GALLERY_META_KEY = '_wc_video_gallery';

	/**
	 * The feature id used by `FeaturesController` (Settings -> Advanced -> Features).
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
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
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
				'resolve_video_posters' => true,
				'deduplicate'           => false,
			)
		);

		$context               = is_string( $args['context'] ) ? $args['context'] : 'view';
		$include_product_image = (bool) $args['include_product_image'];
		$media_items           = self::get_product_image_media_items( $product, $include_product_image, $context );

		if ( self::is_feature_enabled() ) {
			$video_gallery = self::normalize_video_gallery_items(
				self::get_stored_video_gallery_items( $product )
			);

			if ( ! empty( $video_gallery ) ) {
				$media_items = self::merge_positioned_video_gallery_items(
					$media_items,
					$video_gallery,
					self::get_video_position_offset( $product, $include_product_image, $context )
				);
			}
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
	 * Get media gallery items for classic product gallery templates.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param WC_Product $product Product object.
	 * @return array
	 *
	 * @since 11.0.0
	 */
	public static function get_product_media_gallery_items_for_display( WC_Product $product ): array {
		$media_items = self::get_product_media_gallery_items(
			$product,
			array(
				'include_placeholder' => true,
				'deduplicate'         => true,
			)
		);

		self::prime_attachment_caches( $media_items, true );

		return $media_items;
	}

	/**
	 * Get video gallery items stored for a product.
	 *
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public static function get_stored_video_gallery_items( WC_Product $product ): array {
		$value = $product->get_id()
			? get_post_meta( $product->get_id(), self::VIDEO_GALLERY_META_KEY, true )
			: $product->get_meta( self::VIDEO_GALLERY_META_KEY, true, 'edit' );

		return self::decode_video_gallery_meta( $value );
	}

	/**
	 * Copy stored video gallery items between products.
	 *
	 * @param WC_Product $source_product Source product.
	 * @param WC_Product $target_product Target product.
	 * @return array Stored video gallery items.
	 */
	public static function copy_stored_video_gallery_items( WC_Product $source_product, WC_Product $target_product ): array {
		return self::set_stored_video_gallery_items(
			$target_product,
			self::get_stored_video_gallery_items( $source_product )
		);
	}

	/**
	 * Normalize and store video gallery items for a product.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $video_gallery Video gallery data.
	 * @return array Stored video gallery items.
	 */
	public static function set_stored_video_gallery_items(
		WC_Product $product,
		array $video_gallery
	): array {
		$video_gallery = self::normalize_video_gallery_items( $video_gallery );

		self::update_video_gallery_meta( $product, $video_gallery );

		return $video_gallery;
	}

	/**
	 * Normalize media gallery items.
	 *
	 * @param array $media_gallery Media gallery data.
	 * @return array
	 */
	public static function normalize_media_gallery_items( array $media_gallery ): array {
		$items = array();

		foreach ( $media_gallery as $index => $item ) {
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

			$video_item = self::normalize_video_gallery_item( $item, absint( $index ) );

			if ( ! empty( $video_item ) ) {
				$items[] = self::get_video_media_item( $video_item );
			}
		}

		return $items;
	}

	/**
	 * Get positioned video gallery items from a mixed media gallery.
	 *
	 * The mixed gallery excludes the featured product image. Positions are
	 * stored as 0-based indexes in that gallery and shifted later only when the
	 * composed media gallery includes the featured image.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return array
	 */
	public static function get_positioned_video_gallery_items_from_media_gallery( array $media_gallery ): array {
		$video_gallery = array();

		foreach ( array_values( $media_gallery ) as $position => $media_item ) {
			if ( ! is_array( $media_item ) || 'video' !== ( $media_item['media_type'] ?? '' ) ) {
				continue;
			}

			$media_item['position'] = $position;
			$video_gallery[]        = $media_item;
		}

		return $video_gallery;
	}

	/**
	 * Normalize video gallery items.
	 *
	 * @param array $video_gallery Video gallery data.
	 * @return array
	 */
	public static function normalize_video_gallery_items( array $video_gallery ): array {
		$items = array();

		foreach ( $video_gallery as $fallback_position => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item = self::normalize_video_gallery_item( $item, absint( $fallback_position ) );

			if ( ! empty( $item ) ) {
				$items[] = $item;
			}
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
	 * Get HTML for a classic product gallery video.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param array $media_item Product media gallery item.
	 * @param bool  $main_video Whether this is the main gallery item.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_gallery_video_html( array $media_item, bool $main_video = false ): string {
		$attachment_id = isset( $media_item['id'] ) ? absint( $media_item['id'] ) : 0;
		$video_src     = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';

		if ( ! $video_src ) {
			return '';
		}

		/**
		 * Filters whether the single product gallery flexslider is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $enabled Whether flexslider is enabled.
		 */
		$flexslider        = (bool) apply_filters(
			'woocommerce_single_product_flexslider_enabled',
			get_theme_support( 'wc-product-gallery-slider' )
		);
		$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
		/**
		 * Filters the product gallery thumbnail image size.
		 *
		 * @since 3.3.2
		 *
		 * @param string|array $size Image size.
		 */
		$thumbnail_size = apply_filters(
			'woocommerce_gallery_thumbnail_size',
			array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] )
		);
		/**
		 * Filters the product gallery image size.
		 *
		 * @since 3.3.2
		 *
		 * @param string|array $size Image size.
		 */
		$image_size = apply_filters(
			'woocommerce_gallery_image_size',
			$flexslider || $main_video ? 'woocommerce_single' : $thumbnail_size
		);
		/**
		 * Filters the product gallery full image size.
		 *
		 * @since 3.3.2
		 *
		 * @param string|array $size Image size.
		 */
		$full_size = apply_filters(
			'woocommerce_gallery_full_size',
			/**
			 * Filters the product thumbnails large image size.
			 *
			 * @since 3.0.0
			 *
			 * @param string|array $size Image size.
			 */
			apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' )
		);

		$poster_id        = self::get_video_poster_id( $media_item );
		$thumbnail_src    = $poster_id ? wp_get_attachment_image_src( $poster_id, $thumbnail_size ) : false;
		$thumbnail_srcset = $poster_id ? wp_get_attachment_image_srcset( $poster_id, $thumbnail_size ) : false;
		$thumbnail_sizes  = $poster_id ? wp_get_attachment_image_sizes( $poster_id, $thumbnail_size ) : false;
		$poster_src       = $poster_id ? wp_get_attachment_image_src( $poster_id, $image_size ) : false;
		$full_src         = $poster_id ? wp_get_attachment_image_src( $poster_id, $full_size ) : false;
		$alt_text         = $poster_id
			? trim( wp_strip_all_tags( get_post_meta( $poster_id, '_wp_attachment_image_alt', true ) ) )
			: '';

		if ( empty( $alt_text ) ) {
			$video_title = get_post_field( 'post_title', $attachment_id );
			$alt_text    = $video_title
				? sprintf(
					/* translators: %s is the video title. */
					__( 'Video: %s', 'woocommerce' ),
					$video_title
				)
				: '';
		}

		$full_width       = isset( $full_src[1] ) ? absint( $full_src[1] ) : 1000;
		$full_height      = isset( $full_src[2] ) ? absint( $full_src[2] ) : 1000;
		$full_src_url     = isset( $full_src[0] ) ? $full_src[0] : wc_placeholder_img_src( 'woocommerce_single' );
		$video_attributes = array(
			'autoplay'                => 'autoplay',
			'class'                   => $main_video ? 'wp-post-video wp-post-image' : 'wp-post-video',
			'src'                     => $video_src,
			'loop'                    => 'loop',
			'muted'                   => 'muted',
			'playsinline'             => 'playsinline',
			'preload'                 => 'metadata',
			'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
			'data-src'                => $full_src_url,
			'data-large_image'        => $full_src_url,
			'data-large_image_width'  => $full_width,
			'data-large_image_height' => $full_height,
			'aria-label'              => $alt_text,
		);

		if ( false !== $poster_src && isset( $poster_src[0] ) ) {
			$video_attributes['poster'] = $poster_src[0];
		}

		$thumbnail           = isset( $thumbnail_src[0] )
			? $thumbnail_src[0]
			: wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
		$thumbnail_video_src = isset( $thumbnail_src[0] ) ? '' : $video_src;

		return sprintf(
			'<div data-thumb="%1$s" data-thumb-alt="%2$s" data-thumb-srcset="%3$s" data-thumb-sizes="%4$s" data-thumb-video-src="%5$s" ' .
			'class="woocommerce-product-gallery__image woocommerce-product-gallery__video"><a href="%6$s"><video %7$s></video></a></div>',
			esc_url( $thumbnail ),
			esc_attr( $alt_text ),
			esc_attr( $thumbnail_srcset ? $thumbnail_srcset : '' ),
			esc_attr( $thumbnail_sizes ? $thumbnail_sizes : '' ),
			esc_url( $thumbnail_video_src ),
			esc_url( $video_src ),
			wc_implode_html_attributes(
				array_filter(
					$video_attributes,
					static function ( $value ) {
						return '' !== $value;
					}
				)
			)
		);
	}

	/**
	 * Merge positioned video items into product image media items.
	 *
	 * Video positions are 0-based indexes in the final mixed gallery, excluding
	 * the featured product image. The positions are not anchors to the image-only
	 * gallery; they already describe where videos should land after images and
	 * earlier videos are composed together.
	 *
	 * Example:
	 * - Images: array( 'image A', 'image B', 'image C' )
	 * - Videos: array(
	 *     array( 'id' => 10, 'position' => 1 ),
	 *     array( 'id' => 11, 'position' => 2 ),
	 *   )
	 * - Outcome: array( 'image A', 'video 10', 'video 11', 'image B', 'image C' )
	 *
	 * @param array $media_items     Product image media items.
	 * @param array $video_gallery   Stored video gallery items.
	 * @param int   $position_offset Offset applied when featured image is included.
	 * @return array
	 */
	private static function merge_positioned_video_gallery_items( array $media_items, array $video_gallery, int $position_offset ): array {
		foreach ( $video_gallery as $index => $video_item ) {
			$video_gallery[ $index ]['_sort_order'] = $index;
		}

		usort(
			$video_gallery,
			static function ( $first, $second ) {
				return array(
					$first['position'] ?? 0,
					$first['_sort_order'] ?? 0,
				) <=> array(
					$second['position'] ?? 0,
					$second['_sort_order'] ?? 0,
				);
			}
		);

		foreach ( $video_gallery as $video_item ) {
			unset( $video_item['_sort_order'] );

			$position = isset( $video_item['position'] ) ? absint( $video_item['position'] ) : count( $media_items );
			$position = min( count( $media_items ), $position + $position_offset );

			array_splice( $media_items, $position, 0, array( self::get_video_media_item( $video_item ) ) );
		}

		return $media_items;
	}

	/**
	 * Get the positional offset used when composing videos into product media.
	 *
	 * @param WC_Product $product               Product object.
	 * @param bool       $include_product_image Whether the product image is included.
	 * @param string     $context               Product read context.
	 * @return int
	 */
	private static function get_video_position_offset( WC_Product $product, bool $include_product_image, string $context ): int {
		return $include_product_image && absint( $product->get_image_id( $context ) ) ? 1 : 0;
	}

	/**
	 * Normalize one video gallery item.
	 *
	 * @param array $item                 Video gallery item.
	 * @param int   $fallback_position    Position used when item has no position.
	 * @return array
	 */
	private static function normalize_video_gallery_item( array $item, int $fallback_position ): array {
		$media_type    = isset( $item['media_type'] ) ? sanitize_key( $item['media_type'] ) : 'video';
		$source_type   = isset( $item['source_type'] ) ? sanitize_key( $item['source_type'] ) : 'attachment';
		$attachment_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;

		if ( 'video' !== $media_type || 'attachment' !== $source_type || ! $attachment_id ) {
			return array();
		}

		$mime_type = get_post_mime_type( $attachment_id );

		if ( ! is_string( $mime_type ) || 0 !== strpos( $mime_type, 'video/' ) ) {
			return array();
		}

		$video_item = array();
		$poster_id  = isset( $item['poster_id'] ) ? absint( $item['poster_id'] ) : 0;

		$video_item['source_type'] = 'attachment';
		$video_item['id']          = $attachment_id;
		$video_item['position']    = isset( $item['position'] ) ? absint( $item['position'] ) : $fallback_position;

		if (
			$poster_id &&
			(
				wp_attachment_is_image( $poster_id ) ||
				0 === strpos( (string) get_post_mime_type( $poster_id ), 'image/' )
			)
		) {
			$video_item['poster_id'] = $poster_id;
		}

		return $video_item;
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

			if ( 'attachment' === ( $item['source_type'] ?? 'attachment' ) && ! empty( $item['id'] ) ) {
				$attachment_ids[] = absint( $item['id'] );
			}

			if (
				$include_posters &&
				(
					! isset( $item['media_type'] ) ||
					'video' === $item['media_type']
				)
			) {
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
	 * Get a normalized video media item.
	 *
	 * @param array $video_item Stored video gallery item.
	 * @return array
	 */
	private static function get_video_media_item( array $video_item ): array {
		unset( $video_item['position'] );

		$video_item['media_type']  = 'video';
		$video_item['source_type'] = 'attachment';

		return $video_item;
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

	/**
	 * Decode video gallery post meta.
	 *
	 * @param mixed $value Raw video gallery meta value.
	 * @return array
	 */
	private static function decode_video_gallery_meta( $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Update video gallery post meta.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $video_gallery Video gallery items.
	 * @return void
	 */
	private static function update_video_gallery_meta( WC_Product $product, array $video_gallery ): void {
		if ( empty( $video_gallery ) ) {
			if ( $product->get_id() ) {
				delete_post_meta( $product->get_id(), self::VIDEO_GALLERY_META_KEY );
			} else {
				$product->delete_meta_data( self::VIDEO_GALLERY_META_KEY );
			}
			return;
		}

		$value = wp_json_encode( $video_gallery );

		if ( false === $value ) {
			return;
		}

		if ( $product->get_id() ) {
			update_post_meta( $product->get_id(), self::VIDEO_GALLERY_META_KEY, wp_slash( $value ) );
		} else {
			$product->update_meta_data( self::VIDEO_GALLERY_META_KEY, $value );
		}
	}
}
