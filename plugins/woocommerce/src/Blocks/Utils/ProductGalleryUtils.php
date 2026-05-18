<?php
namespace Automattic\WooCommerce\Blocks\Utils;

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

		$product_media_items         = function_exists( 'wc_get_product_media_gallery_items' )
			? \wc_get_product_media_gallery_items( $product )
			: self::get_product_gallery_image_items( $product );
		$product_variation_image_ids = self::get_product_variation_image_ids( $product );

		if (
			1 === count( $product_media_items ) &&
			0 === (int) ( $product_media_items[0]['id'] ?? 0 ) &&
			! empty( $product->get_gallery_image_ids() )
		) {
			$product_media_items = self::get_product_gallery_image_items( $product );
		}

		if (
			1 === count( $product_media_items ) &&
			0 === (int) ( $product_media_items[0]['id'] ?? 0 ) &&
			! empty( $product_variation_image_ids )
		) {
			$product_media_items = array();
		}

		$existing_ids = array_map(
			static function ( $media_item ) {
				return isset( $media_item['id'] ) ? strval( $media_item['id'] ) : '';
			},
			$product_media_items
		);

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
	 * Get the product gallery image count.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery images for.
	 * @return int The number of images in the product gallery.
	 */
	public static function get_product_gallery_image_count( $product ) {
		$all_image_ids = self::get_all_image_ids( $product );
		return count( $all_image_ids );
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
		$poster_id = function_exists( 'wc_get_gallery_video_poster_id' )
			? \wc_get_gallery_video_poster_id( $media_item )
			: 0;
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
				$variations = $product->get_children();
				if ( ! empty( $variations ) ) {
					_prime_post_caches( $variations );
				}
				foreach ( $variations as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation ) {
						$variation_image_id = $variation->get_image_id();
						if ( ! empty( $variation_image_id ) && ! in_array( strval( $variation_image_id ), $variation_image_ids, true ) ) {
							$variation_image_ids[] = strval( $variation_image_id );
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			// Log the error but continue execution.
			error_log( 'Error getting product variation image IDs: ' . $e->getMessage() );
		}

		return $variation_image_ids;
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
