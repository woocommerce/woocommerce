<?php

namespace Automattic\WooCommerce\Blocks\Verticals;

/**
 * Verticals API client.
 */
class Client {
	const ENDPOINT = 'https://public-api.wordpress.com/wpcom/v2/site-verticals';

	/**
	 * Make a request to the Verticals API.
	 *
	 * @param string $url The endpoint URL.
	 *
	 * @return array|\WP_Error The response body, or WP_Error if the request failed.
	 */
	private function request( string $url ) {
		$response = wp_remote_get( $url );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		$error_data = array();
		if ( is_wp_error( $response ) ) {
			$error_data['code']    = $response->get_error_code();
			$error_data['message'] = $response->get_error_message();
		}

		if ( 200 !== $response_code ) {
			$error_data['status'] = $response_code;
			if ( isset( $response_body['message'] ) ) {
				$error_data['message'] = $response_body['message'];
			}
			if ( isset( $response_body['code'] ) ) {
				$error_data['code'] = $response_body['code'];
			}
		}

		if ( ! empty( $error_data ) ) {
			return new \WP_Error( 'verticals_api_error', __( 'Request to the Verticals API failed.', 'woo-gutenberg-products-block' ), $error_data );
		}

		return $response_body;
	}

	/**
	 * Returns a list of verticals that have images.
	 *
	 * @return array|\WP_Error Array of verticals, or WP_Error if the request failed.
	 */
	public function get_verticals() {
		$response = $this->request( self::ENDPOINT );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array_filter(
			$response,
			function ( $vertical ) {
				return $vertical['has_vertical_images'];
			}
		);
	}

	/**
	 * Returns the list of images for the given vertical ID.
	 *
	 * @param int $vertical_id The vertical ID.
	 *
	 * @return array|\WP_Error Array of images, or WP_Error if the request failed.
	 */
	public function get_vertical_images( int $vertical_id ) {
		return $this->request( self::ENDPOINT . '/' . $vertical_id . '/images' );
	}
}
