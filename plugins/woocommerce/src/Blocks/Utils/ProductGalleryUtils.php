<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used for the Product Gallery block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductGalleryUtils {
	const CROP_IMAGE_SIZE_NAME = '_woo_blocks_product_gallery_crop_full';

	/**
	 * Get the product gallery image data.
	 *
	 * @param \WC_Product $product The product object to retrieve the gallery images for.
	 * @return array An array of image data for the product gallery.
	 */
	public static function get_product_gallery_image_data( $product ) {
		$image_data = array(
			// Image src data.
			'images'    => array(),
			// List of image IDs.
			'image_ids' => array(),
		);

		if ( ! $product instanceof \WC_Product ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Invalid product object.', 'woocommerce' ), '9.8.0' );
			return $image_data;
		}

		$gallery_image_ids           = self::get_product_gallery_image_ids( $product );
		$product_variation_image_ids = self::get_product_variation_image_ids( $product );
		$all_image_ids               = array_values( array_map( 'intval', array_unique( array_merge( $gallery_image_ids, $product_variation_image_ids ) ) ) );

		if ( empty( $all_image_ids ) ) {
			return $image_data;
		}

		$image_data['image_ids'] = $all_image_ids;
		$image_data['images']    = array_combine(
			$all_image_ids,
			self::get_image_src_data( $all_image_ids )
		);

		return $image_data;
	}

	/**
	 * Get the image source data.
	 *
	 * @param array $image_ids The image IDs to retrieve the source data for.
	 * @return array An array of image source data.
	 */
	public static function get_image_src_data( $image_ids ) {
		$image_src_data = array();

		foreach ( $image_ids as $image_id ) {
			if ( 0 === $image_id ) {
				// Handle placeholder image.
				$image_src_data[] = array(
					'id'     => 0,
					'src'    => wc_placeholder_img_src(),
					'srcset' => '',
					'sizes'  => '',
				);
				continue;
			}

			// Get the image source.
			$full_src = wp_get_attachment_image_src( $image_id, 'full' );

			// Get srcset and sizes.
			$srcset = wp_get_attachment_image_srcset( $image_id, 'full' );
			$sizes  = wp_get_attachment_image_sizes( $image_id, 'full' );

			$image_src_data[] = array(
				'id'     => $image_id,
				'src'    => $full_src ? $full_src[0] : '',
				'srcset' => $srcset ? $srcset : '',
				'sizes'  => $sizes ? $sizes : '',
			);
		}

		return $image_src_data;
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
	 * @param \WC_Product $product                      The product object to retrieve the gallery images for.
	 * @param int         $max_number_of_visible_images The maximum number of visible images to return. Defaults to 8.
	 * @param bool        $only_visible                 Whether to return only the visible images. Defaults to false.
	 * @return array An array of unique image IDs for the product gallery.
	 */
	public static function get_product_gallery_image_ids( $product, $max_number_of_visible_images = 8, $only_visible = false ) {
		// Main product featured image.
		$featured_image_id = $product->get_image_id();
		// All other product gallery images.
		$product_gallery_image_ids = $product->get_gallery_image_ids();

		// If the Product image is not set, we need to set it to a placeholder image.
		if ( '' === $featured_image_id ) {
			$featured_image_id = '0';
		}

		// We don't want to show the same image twice, so we have to remove the featured image from the gallery if it's there.
		$unique_image_ids = array_unique(
			array_merge(
				array( $featured_image_id ),
				$product_gallery_image_ids
			)
		);

		foreach ( $unique_image_ids as $key => $image_id ) {
			$unique_image_ids[ $key ] = strval( $image_id );
		}

		if ( count( $unique_image_ids ) > $max_number_of_visible_images && $only_visible ) {
			$unique_image_ids = array_slice( $unique_image_ids, 0, $max_number_of_visible_images );
		}

		// Reindex array.
		$unique_image_ids = array_values( $unique_image_ids );

		return $unique_image_ids;
	}
}
