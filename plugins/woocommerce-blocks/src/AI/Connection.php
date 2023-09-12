<?php

namespace Automattic\WooCommerce\Blocks\AI;

use Automattic\Jetpack\Connection\Client;
use Jetpack_Options;

/**
 * Class Connection
 */
class Connection {

	/**
	 * The post request.
	 *
	 * @param string $token The JWT token.
	 * @param string $prompt The prompt to send to the API.
	 * @param int    $timeout The timeout for the request.
	 *
	 * @return mixed
	 */
	public function fetch_ai_response( $token, $prompt, $timeout = 15 ) {
		if ( $token instanceof \WP_Error ) {
			return $token;
		}

		$response = wp_remote_post(
			'https://public-api.wordpress.com/wpcom/v2/text-completion',
			array(
				'body'    =>
					array(
						'feature' => 'woocommerce_blocks_patterns',
						'prompt'  => $prompt,
						'token'   => $token,
					),
				'timeout' => $timeout,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( $response->get_error_code(), esc_html__( 'Failed to connect with the AI endpoint: try again later.', 'woo-gutenberg-products-block' ), $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

	/**
	 * Return the site ID.
	 *
	 * @return integer|\WP_Error The site ID or a WP_Error object.
	 */
	public function get_site_id() {
		if ( ! class_exists( Jetpack_Options::class ) ) {
			return new \WP_Error( 'site-id-error', esc_html__( 'Failed to fetch the site ID: try again later.', 'woo-gutenberg-products-block' ) );
		}

		$site_id = Jetpack_Options::get_option( 'id' );

		if ( ! $site_id ) {
			return new \WP_Error( 'site-id-error', esc_html__( 'Failed to fetch the site ID: The site is not registered.', 'woo-gutenberg-products-block' ) );
		}

		return $site_id;
	}

	/**
	 * Fetch the JWT token.
	 *
	 * @param integer $site_id The site ID.
	 *
	 * @return string|\WP_Error The JWT token or a WP_Error object.
	 */
	public function get_jwt_token( $site_id ) {
		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		$request = Client::wpcom_json_api_request_as_user(
			sprintf( '/sites/%d/jetpack-openai-query/jwt', $site_id ),
			'2',
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			)
		);

		$response = json_decode( wp_remote_retrieve_body( $request ) );

		if ( $response instanceof \WP_Error ) {
			return new \WP_Error( $response->get_error_code(), esc_html__( 'Failed to generate the JWT token', 'woo-gutenberg-products-block' ), $response->get_error_message() );
		}

		if ( ! isset( $response->token ) ) {
			return new \WP_Error( 'failed-to-retrieve-jwt-token', esc_html__( 'Failed to retrieve the JWT token: Try again later.', 'woo-gutenberg-products-block' ) );
		}

		return $response->token;
	}
}
