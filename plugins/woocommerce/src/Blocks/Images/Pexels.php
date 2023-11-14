<?php

namespace Automattic\WooCommerce\Blocks\Images;

use Automattic\WooCommerce\Blocks\AI\Connection;

/**
 * Pexels API client.
 *
 * @internal
 */
class Pexels {

	/**
	 * The Pexels API endpoint.
	 */
	const EXTERNAL_MEDIA_PEXELS_ENDPOINT = '/wpcom/v2/external-media/list/pexels';

	/**
	 * Returns the list of images for the given search criteria.
	 *
	 * @param Connection $ai_connection The AI connection.
	 * @param string     $token The JWT token.
	 * @param string     $business_description The business description.
	 *
	 * @return array|\WP_Error Array of images, or WP_Error if the request failed.
	 */
	public function get_images( $ai_connection, $token, $business_description ) {
		$search_term = $this->define_search_term( $ai_connection, $token, $business_description );

		if ( is_wp_error( $search_term ) ) {
			return $search_term;
		}

		return $this->request( $search_term );
	}

	/**
	 * Define the search term to be used on Pexels using the AI endpoint.
	 *
	 * The search term is a shorter description of the business.
	 *
	 * @param Connection $ai_connection The AI connection.
	 * @param string     $token The JWT token.
	 * @param string     $business_description The business description.
	 *
	 * @return mixed|\WP_Error
	 */
	private function define_search_term( $ai_connection, $token, $business_description ) {
		$prompt = sprintf( 'Based on the description "%s", provide a one-word product description for the store\'s item. Do not include any adjectives or descriptions of the qualities of the product. The returned word should be simple.', $business_description );

		$response = $ai_connection->fetch_ai_response( $token, $prompt );

		if ( is_wp_error( $response ) || ! isset( $response['completion'] ) ) {
			return new \WP_Error( 'search_term_definition_failed', __( 'The search term definition failed.', 'woocommerce' ) );
		}

		return $response['completion'];
	}

	/**
	 * Make a request to the Pexels API.
	 *
	 * @param string $search_term The search term to use.
	 * @param int    $per_page The number of images to return.
	 *
	 * @return array|\WP_Error The response body, or WP_Error if the request failed.
	 */
	private function request( string $search_term, int $per_page = 90 ) {
		$request = new \WP_REST_Request( 'GET', self::EXTERNAL_MEDIA_PEXELS_ENDPOINT );

		$request->set_param( 'search', esc_html( $search_term ) );
		$request->set_param( 'number', $per_page );

		$response      = rest_do_request( $request );
		$response_data = $response->get_data();

		if ( $response->is_error() ) {
			$error_msg = [
				'code' => $response->get_status(),
				'data' => $response_data,
			];

			return new \WP_Error( 'pexels_api_error', __( 'Request to the Pexels API failed.', 'woocommerce' ), $error_msg );
		}

		$response = $response_data['media'] ?? $response_data;

		if ( is_array( $response ) ) {
			shuffle( $response );

			return $response;
		}

		return array();
	}
}
