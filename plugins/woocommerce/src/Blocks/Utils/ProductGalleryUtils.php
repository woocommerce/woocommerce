<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;
use Automattic\WooCommerce\Internal\VariationGallery\Package as VariationGalleryPackage;

/**
 * Utility methods used for the Product Gallery block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductGalleryUtils {
	/**
	 * Get all image IDs for the product.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array An array of image IDs.
	 */
	public static function get_all_image_ids( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Invalid product object.', 'woocommerce' ), '9.8.0' );
			return array();
		}

		$gallery_image_ids           = self::get_product_gallery_image_ids( $product );
		$product_variation_image_ids = self::get_product_variation_image_ids( $product );
		$all_image_ids               = array_values( array_map( 'intval', array_unique( array_merge( $gallery_image_ids, $product_variation_image_ids ) ) ) );

		if ( empty( $all_image_ids ) ) {
			return array();
		}

		return $all_image_ids;
	}

	/**
	 * Get the product gallery image data.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery images for.
	 * @param string      $size The size of the image to retrieve.
	 * @return array An array of image data for the product gallery.
	 */
	public static function get_product_gallery_image_data( $product, $size ) {
		$all_image_ids = self::get_all_image_ids( $product );
		return self::get_image_src_data( $all_image_ids, $size, $product->get_title() );
	}

	/**
	 * Get all media items for the product gallery.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array[] An array of media gallery items.
	 */
	public static function get_all_media_items( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Invalid product object.', 'woocommerce' ), '10.9.0' );
			return array();
		}

		$product_media_items         = ProductMediaGallery::get_product_media_gallery_items_for_display( $product );
		$product_variation_image_ids = self::get_product_variation_image_ids( $product );
		$is_placeholder_only         = 1 === count( $product_media_items ) && 0 === (int) ( $product_media_items[0]['id'] ?? 0 );

		// If the media gallery only returned the placeholder, fall back to gallery images.
		if ( $is_placeholder_only && ! empty( $product->get_gallery_image_ids() ) ) {
			$product_media_items = self::get_product_gallery_image_items( $product );
		}

		$existing_ids = array_map( 'strval', self::get_media_ids( $product_media_items ) );

		foreach ( $product_variation_image_ids as $variation_image_id ) {
			if ( in_array( strval( $variation_image_id ), $existing_ids, true ) ) {
				continue;
			}

			$product_media_items[] = array(
				'media_type'  => 'image',
				'source_type' => 'attachment',
				'id'          => absint( $variation_image_id ),
			);
			$existing_ids[]        = strval( $variation_image_id );
		}

		return array_values( $product_media_items );
	}

	/**
	 * Get product gallery media IDs.
	 *
	 * @param array[] $media_items Product gallery media items.
	 * @return int[] Media IDs used by the product gallery frontend store.
	 */
	public static function get_media_ids( $media_items ) {
		return array_values(
			array_map(
				static function ( $media_item ) {
					return isset( $media_item['id'] ) ? (int) $media_item['id'] : 0;
				},
				$media_items
			)
		);
	}

	/**
	 * Get the product gallery media data.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery media for.
	 * @param string      $size The size of the poster/image to retrieve.
	 * @return array[] An array of media data for the product gallery.
	 */
	public static function get_product_gallery_media_data( $product, $size ) {
		$media_items = self::get_all_media_items( $product );
		return self::get_media_src_data( $media_items, $size, $product->get_title() );
	}

	/**
	 * Get the product gallery media count.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery media for.
	 * @return int The number of media items in the product gallery.
	 */
	public static function get_product_gallery_media_count( $product ) {
		$media_items = self::get_all_media_items( $product );
		return count( $media_items );
	}

	/**
	 * Get the image source data.
	 *
	 * @param array  $image_ids The image IDs to retrieve the source data for.
	 * @param string $size The size of the image to retrieve.
	 * @param string $product_title The title of the product used for alt fallback.
	 * @return array An array of image source data.
	 */
	public static function get_image_src_data( $image_ids, $size, $product_title = '' ) {
		$image_src_data = array();

		foreach ( $image_ids as $index => $image_id ) {
			$image_src_data[] = self::get_image_data( $image_id, $size, $product_title, $index );
		}

		return $image_src_data;
	}

	/**
	 * Get media source data.
	 *
	 * @param array[] $media_items Product gallery media items.
	 * @param string  $size The size of the poster/image to retrieve.
	 * @param string  $product_title The title of the product used for alt fallback.
	 * @return array[] An array of media source data.
	 */
	public static function get_media_src_data( $media_items, $size, $product_title = '' ) {
		$media_src_data = array();

		foreach ( $media_items as $index => $media_item ) {
			if ( 'video' === ( $media_item['media_type'] ?? '' ) ) {
				$media_src_data[] = self::get_video_data( $media_item, $size, $product_title, $index );
				continue;
			}

			$media_src_data[] = array_merge(
				self::get_image_data( $media_item['id'] ?? 0, $size, $product_title, $index ),
				array( 'media_type' => 'image' )
			);
		}

		return $media_src_data;
	}

	/**
	 * Get base attributes for product gallery videos.
	 *
	 * @param array  $media Video media data.
	 * @param string $video_location Video location in the product gallery.
	 * @return array Video attributes.
	 */
	public static function get_video_attributes( $media, $video_location ) {
		if ( empty( $media['video_src'] ) ) {
			return array();
		}

		$video_location = in_array( $video_location, array( 'dialog', 'gallery' ), true ) ? $video_location : 'gallery';
		$attrs          = array(
			'aria-label'      => $media['alt'] ?? '',
			'autoplay'        => 'autoplay',
			'data-image-id'   => $media['id'] ?? '',
			'data-wp-context' => sprintf( '{"videoLocation":"%s"}', $video_location ),
			'data-wp-watch'   => 'callbacks.syncVideoPlayback',
			'loop'            => 'loop',
			'muted'           => 'muted',
			'playsinline'     => 'playsinline',
			'preload'         => 'metadata',
			'src'             => $media['video_src'],
		);

		if ( 'dialog' === $video_location ) {
			unset( $attrs['autoplay'] );
			$attrs['data-wp-init--dialog-video-playback'] = 'callbacks.initDialogVideoPlayback';
		}

		if ( ! empty( $media['poster_id'] ) && ! empty( $media['poster_src'] ) ) {
			$attrs['poster'] = $media['poster_src'];
		}

		return $attrs;
	}

	/**
	 * Get product gallery video HTML from attributes.
	 *
	 * @param array $attributes Video attributes.
	 * @return string Video HTML.
	 */
	public static function get_video_html( $attributes ) {
		if ( empty( $attributes ) ) {
			return '';
		}

		return '<video ' . wc_implode_html_attributes(
			array_filter(
				$attributes,
				static function ( $value ) {
					return '' !== $value;
				}
			)
		) . '></video>';
	}

	/**
	 * Get source data for one image.
	 *
	 * @param int|string $image_id The image ID.
	 * @param string     $size The size of the image to retrieve.
	 * @param string     $product_title The title of the product used for alt fallback.
	 * @param int        $index The image index.
	 * @return array Image source data.
	 */
	private static function get_image_data( $image_id, $size, $product_title, $index ) {
		$image_id = (int) $image_id;

		if ( 0 === $image_id ) {
			return array(
				'id'         => 0,
				'media_type' => 'image',
				'src'        => wc_placeholder_img_src(),
				'srcset'     => '',
				'sizes'      => '',
				'alt'        => '',
			);
		}

		$full_src = wp_get_attachment_image_src( $image_id, $size );
		$srcset   = wp_get_attachment_image_srcset( $image_id, $size );
		$sizes    = wp_get_attachment_image_sizes( $image_id, $size );
		$alt      = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		return array(
			'id'         => $image_id,
			'media_type' => 'image',
			'src'        => $full_src ? $full_src[0] : '',
			'srcset'     => $srcset ? $srcset : '',
			'sizes'      => $sizes ? $sizes : '',
			'alt'        => $alt ? $alt : sprintf(
				/* translators: 1: Product title 2: Image number */
				__( '%1$s - Image %2$d', 'woocommerce' ),
				$product_title,
				$index + 1
			),
		);
	}

	/**
	 * Get source data for one video.
	 *
	 * @param array  $media_item Product gallery media item.
	 * @param string $size The size of the poster image to retrieve.
	 * @param string $product_title The title of the product used for alt fallback.
	 * @param int    $index The media index.
	 * @return array Video source data.
	 */
	private static function get_video_data( $media_item, $size, $product_title, $index ) {
		$video_id  = isset( $media_item['id'] ) ? (int) $media_item['id'] : 0;
		$poster_id = ProductMediaGallery::get_video_poster_id( $media_item );
		$poster    = self::get_image_data( $poster_id, $size, $product_title, $index );
		$video_src = $video_id ? wp_get_attachment_url( $video_id ) : ( $media_item['url'] ?? '' );
		$mime_type = $video_id ? get_post_mime_type( $video_id ) : '';
		$settings  = isset( $media_item['settings'] ) && is_array( $media_item['settings'] )
			? $media_item['settings']
			: array();
		$alt       = $poster_id ? get_post_meta( $poster_id, '_wp_attachment_image_alt', true ) : '';

		if ( empty( $alt ) && $video_id ) {
			$video_title = get_post_field( 'post_title', $video_id );
			$alt         = $video_title
				? sprintf(
					/* translators: %s is the video title. */
					__( 'Video: %s', 'woocommerce' ),
					$video_title
				)
				: '';
		}

		if ( empty( $alt ) && $product_title ) {
			$alt = sprintf(
				/* translators: %s is the product title. */
				__( 'Product video: %s', 'woocommerce' ),
				$product_title
			);
		}

		return array_merge(
			$poster,
			array(
				'alt'        => $alt,
				'id'         => $video_id,
				'media_type' => 'video',
				'mime_type'  => $mime_type ? $mime_type : '',
				'poster_id'  => $poster_id,
				'poster_src' => $poster['src'],
				'settings'   => $settings,
				'video_src'  => $video_src ? $video_src : '',
			)
		);
	}

	/**
	 * Get the product gallery image items.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery images for.
	 * @return array[] An array of image media items for the product gallery.
	 */
	private static function get_product_gallery_image_items( $product ) {
		return array_map(
			static function ( $image_id ) {
				return array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => absint( $image_id ),
				);
			},
			self::get_product_gallery_image_ids( $product )
		);
	}

	/**
	 * Get the product variation image data.
	 *
	 * @param \WC_Product $product The product object to retrieve the variation images for.
	 * @return array An array of image data for the product variation images.
	 */
	public static function get_product_variation_image_ids( $product ) {
		$variation_image_ids = array();

		if ( ! $product instanceof \WC_Product ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Invalid product object.', 'woocommerce' ), '9.8.0' );
			return $variation_image_ids;
		}

		try {
			if ( $product->is_type( 'variable' ) ) {
				$variation_gallery_data = self::get_product_variation_gallery_data( $product );

				foreach ( $variation_gallery_data as $variation_data ) {
					$variation_image_ids = array_merge( $variation_image_ids, $variation_data['image_ids'] );
				}
			}
		} catch ( \Exception $e ) {
			// Log the error but continue execution.
			error_log( 'Error getting product variation image IDs: ' . $e->getMessage() );
		}

		$unique_int_ids = array_unique( array_map( 'intval', $variation_image_ids ) );

		return array_values( array_map( 'strval', $unique_int_ids ) );
	}

	/**
	 * Get variation gallery data keyed by variation ID.
	 *
	 * @param \WC_Product $product The product object to retrieve variation gallery data for.
	 * @return array<int, array<string, mixed>> Variation gallery data.
	 */
	public static function get_product_variation_gallery_data( $product ) {
		$variation_gallery_data = array();

		if ( ! $product instanceof \WC_Product ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Invalid product object.', 'woocommerce' ), '10.8.0' );
			return $variation_gallery_data;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return $variation_gallery_data;
		}

		$variations = $product->get_children();
		if ( ! empty( $variations ) ) {
			// Bulk-load posts + postmeta into WP's object cache.
			_prime_post_caches( $variations );
		}

		// 0 is placeholder image ID.
		$parent_featured_id = 0;
		$product_image_id   = (int) $product->get_image_id();
		if ( $product_image_id && wp_attachment_is_image( $product_image_id ) ) {
			$parent_featured_id = $product_image_id;
		}

		$parent_gallery_ids    = array_map( 'intval', $product->get_gallery_image_ids() );
		$parent_gallery_ids    = array_filter( $parent_gallery_ids, 'wp_attachment_is_image' );
		$parent_gallery_extras = array_values( array_diff( $parent_gallery_ids, array( $parent_featured_id ) ) );

		foreach ( $variations as $variation_id ) {
			$variation_id = (int) $variation_id;
			$entry        = self::build_variation_gallery_entry( $variation_id, $parent_featured_id, $parent_gallery_extras );

			if ( null !== $entry ) {
				$variation_gallery_data[ $variation_id ] = $entry;
			}
		}

		return $variation_gallery_data;
	}

	/**
	 * Build the gallery payload for a single variation, or null when the
	 * post isn't a real variation.
	 *
	 * Decision tree (variation chosen):
	 * - no variation images → parent featured + parent gallery
	 * - own featured only → variation featured + parent gallery extras
	 * - own featured + gallery (flag on) → variation images only
	 * - gallery only, no own featured (potential AVI shape) → parent featured + variation gallery
	 *
	 * @param int   $variation_id          Variation post ID.
	 * @param int   $parent_featured_id    Parent product's featured image ID (0 if missing/invalid).
	 * @param int[] $parent_gallery_extras Parent gallery image IDs, with the featured filtered out.
	 * @return array<string, mixed>|null
	 */
	private static function build_variation_gallery_entry( int $variation_id, int $parent_featured_id, array $parent_gallery_extras ): ?array {
		$variation = wc_get_product( $variation_id );

		if ( ! $variation instanceof \WC_Product_Variation ) {
			return null;
		}

		$featured_id    = (int) $variation->get_image_id();
		$featured_valid = $featured_id && wp_attachment_is_image( $featured_id );

		$variation_gallery_ids = array();
		if ( VariationGalleryPackage::is_enabled() ) {
			$variation_gallery_ids = array_map( 'intval', $variation->get_gallery_image_ids() );
			$variation_gallery_ids = array_filter( $variation_gallery_ids, 'wp_attachment_is_image' );
			$variation_gallery_ids = array_values( $variation_gallery_ids );
		}

		// No images from variation - full parent fallback.
		if ( ! $featured_valid && empty( $variation_gallery_ids ) ) {
			$parent_image_ids = array_values(
				array_filter( array_merge( array( $parent_featured_id ), $parent_gallery_extras ) )
			);

			if ( empty( $parent_image_ids ) ) {
				return array(
					'image_id'  => 0,
					'image_ids' => array( 0 ),
				);
			}

			return array(
				'image_id'  => $parent_image_ids[0],
				'image_ids' => $parent_image_ids,
			);
		}

		// Variation has featured image and gallery - full variation gallery.
		if ( ! empty( $variation_gallery_ids ) ) {
			$featured  = $featured_valid ? $featured_id : $variation_gallery_ids[0];
			$image_ids = array_values(
				array_unique( array_merge( array( $featured ), $variation_gallery_ids ) )
			);

			return array(
				'image_id'  => $featured,
				'image_ids' => $image_ids,
			);
		}

		// Variation has only featured image - variation featured and parent gallery.
		$image_ids = array_values(
			array_unique( array_merge( array( $featured_id ), $parent_gallery_extras ) )
		);

		return array(
			'image_id'  => $featured_id,
			'image_ids' => $image_ids,
		);
	}

	/**
	 * Get all image IDs relevant to a variation gallery.
	 *
	 * @param \WC_Product_Variation $variation The variation object.
	 * @return array<int> Variation image IDs.
	 */
	public static function get_variation_gallery_image_ids( \WC_Product_Variation $variation ) {
		$image_ids          = array();
		$variation_image_id = (int) $variation->get_image_id();
		$gallery_image_ids  = array_map( 'intval', $variation->get_gallery_image_ids() );

		if ( $variation_image_id ) {
			$image_ids[] = $variation_image_id;
		}

		if ( ! empty( $gallery_image_ids ) ) {
			$image_ids = array_merge( $image_ids, $gallery_image_ids );
		}

		// Filter out missing/invalid attachments to avoid rendering phantom
		// empty `<li>` wrappers that the visibility watch can't manage.
		$image_ids = array_filter(
			$image_ids,
			function ( $id ) {
				return $id > 0 && wp_attachment_is_image( $id );
			}
		);

		return array_values( array_unique( $image_ids ) );
	}

	/**
	 * Get the product gallery image IDs.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery images for.
	 * @return array An array of unique image IDs for the product gallery.
	 */
	public static function get_product_gallery_image_ids( $product ) {
		$product_image_ids = array();

		// Main product featured image.
		$featured_image_id = $product->get_image_id();

		if ( $featured_image_id ) {
			$product_image_ids[] = $featured_image_id;
		}

		// All other product gallery images.
		$product_gallery_image_ids = $product->get_gallery_image_ids();

		if ( ! empty( $product_gallery_image_ids ) ) {
			// We don't want to show the same image twice, so we have to remove the featured image from the gallery if it's there.
			$product_image_ids = array_unique( array_merge( $product_image_ids, $product_gallery_image_ids ) );
		}

		// If the Product image is not set and there are no gallery images, we need to set it to a placeholder image.
		if ( ! $featured_image_id && empty( $product_gallery_image_ids ) ) {
			$product_image_ids[] = '0';
		}

		foreach ( $product_image_ids as $key => $image_id ) {
			$product_image_ids[ $key ] = strval( $image_id );
		}

		// Reindex array.
		$product_image_ids = array_values( $product_image_ids );

		return $product_image_ids;
	}
}
