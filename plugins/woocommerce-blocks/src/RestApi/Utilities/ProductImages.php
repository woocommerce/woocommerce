<?php
/**
 * Helper class to convert product images to schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * ProductImages class.
 */
class ProductImages {

	/**
	 * Convert product images/attachments to an array.
	 *
	 * @param \WC_Product|\WC_Product_Variation $product Product instance.
	 * @return array Array of images confirming to product schema.
	 */
	public function images_to_array( $product ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$thumbnail = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

			$images[] = array(
				'id'        => $attachment_id,
				'src'       => current( $attachment ),
				'thumbnail' => current( $thumbnail ),
				'srcset'    => wp_get_attachment_image_srcset( $attachment_id, 'full' ),
				'sizes'     => wp_get_attachment_image_sizes( $attachment_id, 'full' ),
				'name'      => get_the_title( $attachment_id ),
				'alt'       => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}
}
