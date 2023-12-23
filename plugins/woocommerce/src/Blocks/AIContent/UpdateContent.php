<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use WP_Error;

/**
 * UpdateContent class.
 */
class UpdateContent {

	/**
	 * Ensure that images are provided for assignment to products and patterns.
	 *
	 * @param array|WP_Error $images  The array of images.
	 * @param Connection     $ai_connection  The AI connection.
	 * @param string         $token  The JWT token. $token
	 * @param string         $business_description The business description.
	 *
	 * @return array|int|mixed|string|WP_Error
	 */
	protected function verify_images( $images, $ai_connection, $token, $business_description ) {
		if ( ! is_wp_error( $images ) && ! empty( $images['images'] ) && ! empty( $images['search_term'] ) ) {
			return $images;
		}

		$images = ( new Pexels() )->get_images( $ai_connection, $token, $business_description );

		if ( is_wp_error( $images ) ) {
			return $images;
		}

		if ( empty( $images['images'] ) || empty( $images['search_term'] ) ) {
			return new WP_Error( 'images_not_found', __( 'No images provided for generating AI content.', 'woocommerce' ) );
		}

		return $images;
	}

	/**
	 * Adjust the size of images for optimal performance on products and patterns.
	 *
	 * @param string $image_url The image URL.
	 * @param string $usage_type The usage type of the image. Either 'products' or 'patterns'.
	 *
	 * @return string
	 */
	protected function adjust_image_size( $image_url, $usage_type ) {
		$parsed_url = wp_parse_url( $image_url );

		if ( ! isset( $parsed_url['query'] ) ) {
			return $image_url;
		}

		$width = 'products' === $usage_type ? 250 : 500;

		parse_str( $parsed_url['query'], $query_params );

		unset( $query_params['h'], $query_params['w'] );
		$query_params['w'] = $width;
		$url               = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];

		return add_query_arg( $query_params, $url );
	}
}
