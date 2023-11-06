<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used for the Product Gallery block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductGalleryUtils {
	const CROP_IMAGE_SIZE_NAME = '_woo_blocks_product_gallery_crop_full';

	/**
	 * When requesting a full-size image, this function may return an array with a single image.
	 * However, when requesting a non-full-size image, it will always return an array with multiple images.
	 * This distinction is based on the image size needed for rendering purposes:
	 * - "Full" size is used for the main product featured image.
	 * - Non-full sizes are used for rendering thumbnails.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $size Image size.
	 * @param array  $attributes Attributes.
	 * @param string $wrapper_class Wrapper class.
	 * @param bool   $crop_images Whether to crop images.
	 * @return array
	 */
	public static function get_product_gallery_images( $post_id, $size = 'full', $attributes = array(), $wrapper_class = '', $crop_images = false ) {
		$product_gallery_images = array();
		$product                = wc_get_product( $post_id );

		if ( $product ) {
			$all_product_gallery_image_ids = self::get_product_gallery_image_ids( $product );

			if ( 'full' === $size || 'full' !== $size && count( $all_product_gallery_image_ids ) > 1 ) {
				$size = $crop_images ? self::CROP_IMAGE_SIZE_NAME : $size;

				foreach ( $all_product_gallery_image_ids as $product_gallery_image_id ) {
					if ( $crop_images ) {
						self::maybe_generate_intermediate_image( $product_gallery_image_id, self::CROP_IMAGE_SIZE_NAME );
					}

					$product_image_html = wp_get_attachment_image(
						$product_gallery_image_id,
						$size,
						false,
						$attributes
					);

					if ( $wrapper_class ) {
						$product_image_html = '<div class="' . $wrapper_class . '">' . $product_image_html . '</div>';
					}

					$product_image_html_processor = new \WP_HTML_Tag_Processor( $product_image_html );
					$product_image_html_processor->next_tag( 'img' );
					$product_image_html_processor->set_attribute(
						'data-wc-context',
						wp_json_encode(
							array(
								'woocommerce' => array(
									'imageId' => $product_gallery_image_id,
								),
							)
						)
					);

					$product_gallery_images[] = $product_image_html_processor->get_updated_html();
				}
			}
		}

		return $product_gallery_images;
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

		return $unique_image_ids;
	}

	/**
	 * Generates the intermediate image sizes only when needed.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size Image size.
	 * @return void
	 */
	public static function maybe_generate_intermediate_image( $attachment_id, $size ) {
		$metadata   = image_get_intermediate_size( $attachment_id, $size );
		$upload_dir = wp_upload_dir();
		$image_path = '';

		if ( $metadata ) {
			$image_path = $upload_dir['basedir'] . '/' . $metadata['path'];
		}

		/*
		 * We need to check both if the size metadata exists and if the file exists.
		 * Sometimes we can have orphaned image file and no metadata or vice versa.
		 */
		if ( $metadata && file_exists( $image_path ) ) {
			return;
		}

		$image_path     = wp_get_original_image_path( $attachment_id );
		$image_metadata = wp_get_attachment_metadata( $attachment_id );

		// If image sizes are not available. Bail.
		if ( ! isset( $image_metadata['width'], $image_metadata['height'] ) ) {
			return;
		}

		/*
		 * We want to take the minimum dimension of the image and
		 * use that size as the crop size for the new image.
		 */
		$min_size                         = min( $image_metadata['width'], $image_metadata['height'] );
		$new_image_metadata               = image_make_intermediate_size( $image_path, $min_size, $min_size, true );
		$image_metadata['sizes'][ $size ] = $new_image_metadata;

		wp_update_attachment_metadata( $attachment_id, $image_metadata );
	}
}
