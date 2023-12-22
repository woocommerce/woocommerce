<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use Automattic\WooCommerce\Blocks\Images\Pexels;

class UpdateContent {

	protected function verify_images( $images, $ai_connection, $token, $business_description ) {
		if ( ! is_wp_error( $images ) && ! empty( $images['images'] ) && ! empty( $images['search_term'] ) ) {
			return $images;
		}

		$images = ( new Pexels() )->get_images( $ai_connection, $token, $business_description );

		if ( is_wp_error( $images ) ) {
			return $images;
		}

		if ( empty( $images['images'] ) || empty( $images['search_term'] ) ) {
			return new \WP_Error( 'images_not_found', __( 'No images provided for generating AI content.', 'woocommerce' ) );
		}

		return $images;
	}
}
