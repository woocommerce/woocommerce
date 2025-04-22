<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use WP_Error;

/**
 * ContentProcessor class.
 *
 * Process images for content
 *
 * @internal
 */
class ContentProcessor {

	/**
	 * Summarize the business description to ensure better results are returned by AI.
	 *
	 * @param string     $business_description The business description.
	 * @param Connection $ai_connection  The AI connection.
	 * @param string     $token  The JWT token.
	 * @param integer    $character_limit The character limit for the business description.
	 *
	 * @return mixed|WP_Error
	 */
	public static function summarize_business_description( $business_description, $ai_connection, $token, $character_limit = 150 ) {
		if ( empty( $business_description ) ) {
			return new WP_Error( 'business_description_not_found', __( 'No business description provided for generating AI content.', 'woocommerce' ) );
		}

		if ( strlen( $business_description ) > $character_limit ) {
			$prompt = sprintf( 'You are a professional writer. Read the following business description and write a text with less than %s characters to summarize the products the business is selling: "%s". Make sure you do not add double quotes in your response. Do not add any explanations in the response', $character_limit, $business_description );

			$response = $ai_connection->fetch_ai_response( $token, $prompt, 30 );

			$business_description = $response['completion'] ?? $business_description;
		}

		return $business_description;
	}

	/**
	 * Ensure that images are provided for assignment to products and patterns.
	 *
	 * @param array|WP_Error $images  The array of images.
	 * @param Connection     $ai_connection  The AI connection.
	 * @param string         $token  The JWT token.
	 * @param string         $business_description The business description.
	 *
	 * @return array|int|mixed|string|WP_Error
	 */
	public static function verify_images( $images, $ai_connection, $token, $business_description ) {
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
	public static function adjust_image_size( $image_url, $usage_type ) {
		$parsed_url = wp_parse_url( $image_url );

		if ( ! isset( $parsed_url['query'] ) ) {
			return $image_url;
		}

		$width = 'products' === $usage_type ? 400 : 500;

		parse_str( $parsed_url['query'], $query_params );

		unset( $query_params['h'], $query_params['w'] );
		$query_params['w'] = $width;
		$url               = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];

		return add_query_arg( $query_params, $url );
	}
}
