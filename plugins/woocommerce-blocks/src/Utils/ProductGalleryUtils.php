<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used for the Product Gallery block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductGalleryUtils {

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
	 * @return array
	 */
	public static function get_product_gallery_images( $post_id, $size = 'full', $attributes = array(), $wrapper_class = '' ) {
		$product_gallery_images = array();
		$product                = wc_get_product( $post_id );

		if ( $product ) {
			$all_product_gallery_image_ids = self::get_product_gallery_image_ids( $product );

			if ( 'full' === $size || 'full' !== $size && count( $all_product_gallery_image_ids ) > 1 ) {
				foreach ( $all_product_gallery_image_ids as $product_gallery_image_id ) {
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
}
