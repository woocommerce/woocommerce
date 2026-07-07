<?php
/**
 * Webflow Client
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow;

defined( 'ABSPATH' ) || exit;

/**
 * Handles communication with the Webflow v2 REST API.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class WebflowClient {

	/**
	 * Base URL for the Webflow v2 API.
	 *
	 * @var string
	 */
	private const API_BASE = 'https://api.webflow.com/v2';

	/**
	 * How many times to retry on 429 responses before bailing.
	 *
	 * @var int
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Platform credentials.
	 *
	 * @var array
	 */
	private array $credentials;

	/**
	 * Constructor.
	 *
	 * @param array $credentials Platform credentials array, expects 'site_id' and 'access_token'.
	 */
	public function __construct( array $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Makes a GET request to the Webflow REST API.
	 *
	 * @param string $path         The API path relative to API base (e.g., '/sites/{id}/products').
	 * @param array  $query_params Optional query parameters.
	 * @return object|array|\WP_Error Decoded JSON response (object or array) or WP_Error on failure.
	 */
	public function rest_request( string $path, array $query_params = array() ) {
		$credentials = $this->get_credentials();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$endpoint = self::API_BASE . $path;
		if ( ! empty( $query_params ) ) {
			$endpoint = add_query_arg( $query_params, $endpoint );
		}

		$request_args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $credentials['access_token'],
				'Accept'        => 'application/json',
			),
			'timeout' => 60,
		);

		for ( $attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			$response      = wp_remote_request( $endpoint, $request_args );
			$response_code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

			if ( 429 !== $response_code ) {
				return $this->process_response( $response, $path );
			}

			// Rate limited: back off and retry until the budget is exhausted. The final
			// attempt falls through to the retry-budget error below rather than retrying.
			if ( $attempt < self::MAX_RETRIES ) {
				$retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
				$delay       = $retry_after > 0 ? $retry_after : ( 2 ** $attempt );
				$delay       = min( 30, max( 1, $delay ) );
				if ( function_exists( 'sleep' ) ) {
					sleep( $delay );
				}
			}
		}

		return new \WP_Error( 'api_error', "REST request to {$path} exceeded retry budget after repeated 429 responses." );
	}

	/**
	 * Return the configured site ID, or a WP_Error if not set.
	 *
	 * @return string|\WP_Error
	 */
	public function get_site_id() {
		if ( empty( $this->credentials['site_id'] ) ) {
			return new \WP_Error(
				'api_error',
				'Webflow site_id is not configured. Please run: wp wc migrate setup --platform=webflow'
			);
		}

		return (string) $this->credentials['site_id'];
	}

	/**
	 * Validate that credentials are present.
	 *
	 * @return array|\WP_Error Array with 'site_id' and 'access_token' keys, or WP_Error on failure.
	 */
	private function get_credentials() {
		if ( empty( $this->credentials['site_id'] ) || empty( $this->credentials['access_token'] ) ) {
			return new \WP_Error(
				'api_error',
				'Webflow API credentials (site_id, access_token) are not configured. Please run: wp wc migrate setup --platform=webflow'
			);
		}

		return array(
			'site_id'      => (string) $this->credentials['site_id'],
			'access_token' => (string) $this->credentials['access_token'],
		);
	}

	/**
	 * Process the API response.
	 *
	 * @param array|\WP_Error $response The HTTP response.
	 * @param string          $path     The API path (for error context).
	 * @return object|array|\WP_Error Decoded response or WP_Error.
	 */
	private function process_response( $response, string $path ) {
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', 'REST request failed: ' . $response->get_error_message() );
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 300 ) {
			$decoded       = json_decode( $response_body );
			$error_message = is_object( $decoded ) && isset( $decoded->message ) ? $decoded->message : $response_body;
			return new \WP_Error(
				'api_error',
				"REST request to {$path} failed with status code {$response_code}: " . $error_message
			);
		}

		$data = json_decode( $response_body );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'api_error', 'Failed to decode REST JSON response: ' . json_last_error_msg() );
		}

		return $data;
	}
}
