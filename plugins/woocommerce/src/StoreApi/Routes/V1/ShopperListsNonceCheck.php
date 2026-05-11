<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

/**
 * Stopgap CSRF guard for the write-capable shopper-lists routes.
 *
 * Enforces a `wc_store_api` Nonce header on writes and refreshes the
 * client nonce via response headers on every reply. Same shape as the
 * cart's existing flow, scoped to the nonce concern.
 *
 * To be replaced by a reusable Store API-wide nonce trait once that
 * lands on trunk.
 *
 * @internal
 */
trait ShopperListsNonceCheck {
	/**
	 * Override of {@see AbstractRoute::get_response} that enforces the
	 * `wc_store_api` Nonce header on writes and refreshes it on every reply.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		if ( $this->is_write_request( $request ) ) {
			$nonce_check = $this->check_store_api_nonce( $request );
			if ( is_wp_error( $nonce_check ) ) {
				return $this->add_nonce_response_headers( $this->error_to_response( $nonce_check ) );
			}
		}

		$response = parent::get_response( $request );

		return $this->add_nonce_response_headers( $response );
	}

	/**
	 * Whether the request mutates state. Mirrors `AbstractCartRoute::is_update_request`.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return bool
	 */
	private function is_write_request( \WP_REST_Request $request ): bool {
		return in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true );
	}

	/**
	 * Verify the `Nonce` request header against the `wc_store_api` action.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return true|\WP_Error True on success, WP_Error on missing/invalid nonce.
	 */
	private function check_store_api_nonce( \WP_REST_Request $request ) {
		// This filter is documented in src/StoreApi/Routes/V1/AbstractCartRoute.php.
		if ( apply_filters( 'woocommerce_store_api_disable_nonce_check', false ) ) {
			return true;
		}

		$nonce = $request->get_header( 'Nonce' );
		if ( null === $nonce || '' === $nonce ) {
			return new \WP_Error(
				'woocommerce_rest_missing_nonce',
				__( 'Missing the Nonce header. This endpoint requires a valid nonce.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		if ( ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
			return new \WP_Error(
				'woocommerce_rest_invalid_nonce',
				__( 'Nonce is invalid.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Attach a fresh `wc_store_api` nonce to the response.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @return \WP_REST_Response
	 */
	private function add_nonce_response_headers( \WP_REST_Response $response ): \WP_REST_Response {
		$response->header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response->header( 'Nonce-Timestamp', (string) time() );

		return $response;
	}
}
