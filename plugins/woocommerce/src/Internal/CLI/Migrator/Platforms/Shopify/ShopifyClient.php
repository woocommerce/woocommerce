<?php
/**
 * Shopify Client
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify;

/**
 * Handles communication with the Shopify REST API.
 */
class ShopifyClient {

	/**
	 * Platform credentials.
	 *
	 * @var array
	 */
	private array $credentials;

	/**
	 * Constructor.
	 *
	 * @param array $credentials Platform credentials array.
	 */
	public function __construct( array $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Makes a request to the Shopify REST API.
	 *
	 * @param string $path         The API path (e.g., '/products/count.json').
	 * @param array  $query_params Optional query parameters.
	 * @param string $method       HTTP method (GET, POST, PUT, DELETE).
	 * @param array  $body         Request body for POST/PUT.
	 * @return object|\WP_Error Decoded JSON response object or WP_Error on failure.
	 */
	public function rest_request( string $path, array $query_params = array(), string $method = 'GET', array $body = array() ) {
		$credentials = $this->get_credentials();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$rest_endpoint = $this->build_rest_url( $credentials['domain'], $path, $query_params );
		$request_args  = $this->build_request_args( $credentials['access_token'], $method, $body );

		$response = wp_remote_request( $rest_endpoint, $request_args );

		return $this->process_response( $response, $path );
	}

	/**
	 * Makes a request to the Shopify GraphQL API.
	 *
	 * @param string $query     The GraphQL query string.
	 * @param array  $variables The variables for the query.
	 * @return object|\WP_Error Decoded JSON response data or WP_Error on failure.
	 */
	public function graphql_request( string $query, array $variables = array() ) {
		$credentials = $this->get_credentials();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$graphql_endpoint = $this->build_graphql_url( $credentials['domain'] );
		$request_args     = $this->build_graphql_request_args( $credentials['access_token'], $query, $variables );

		$response = wp_remote_request( $graphql_endpoint, $request_args );

		return $this->process_graphql_response( $response );
	}

	/**
	 * Get Shopify API credentials.
	 *
	 * @return array|\WP_Error Array with 'domain' and 'access_token' keys, or WP_Error on failure.
	 */
	private function get_credentials() {
		if ( empty( $this->credentials['shop_url'] ) || empty( $this->credentials['access_token'] ) ) {
			return new \WP_Error(
				'api_error',
				'Shopify API credentials (shop_url, access_token) are not configured. Please run: wp wc migrate setup'
			);
		}

		// Map the stored credential keys to the expected format.
		return array(
			'domain'       => $this->credentials['shop_url'],
			'access_token' => $this->credentials['access_token'],
		);
	}

	/**
	 * Build the REST API URL.
	 *
	 * @param string $domain       The Shopify domain.
	 * @param string $path         The API path.
	 * @param array  $query_params Query parameters.
	 * @return string The complete API URL.
	 */
	private function build_rest_url( string $domain, string $path, array $query_params ): string {
		// Ensure the domain has the protocol.
		if ( ! preg_match( '~^https?://~i', $domain ) ) {
			$domain = 'https://' . $domain;
		}

		$shop_url = untrailingslashit( $domain );
		// Use the latest stable API version.
		$api_version   = '2025-04';
		$rest_endpoint = "{$shop_url}/admin/api/{$api_version}{$path}";

		if ( ! empty( $query_params ) ) {
			$rest_endpoint = add_query_arg( $query_params, $rest_endpoint );
		}

		return $rest_endpoint;
	}

	/**
	 * Build the request arguments.
	 *
	 * @param string $access_token The Shopify access token.
	 * @param string $method       HTTP method.
	 * @param array  $body         Request body.
	 * @return array Request arguments for wp_remote_request.
	 */
	private function build_request_args( string $access_token, string $method, array $body ): array {
		$request_args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type'           => 'application/json',
				'X-Shopify-Access-Token' => $access_token,
			),
			'timeout' => 60,
		);

		if ( ! empty( $body ) && ( 'POST' === $method || 'PUT' === $method ) ) {
			$request_args['body'] = wp_json_encode( $body );
		}

		return $request_args;
	}

	/**
	 * Process the API response.
	 *
	 * @param array|WP_Error $response The HTTP response.
	 * @param string         $path     The API path for error reporting.
	 * @return object|\WP_Error Decoded response or WP_Error.
	 */
	private function process_response( $response, string $path ) {
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', 'REST request failed: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 300 ) {
			$error_details = json_decode( $response_body );
			$error_message = isset( $error_details->errors ) ? wp_json_encode( $error_details->errors ) : $response_body;
			return new \WP_Error(
				'api_error',
				"REST request to {$path} failed with status code {$response_code}: " . $error_message
			);
		}

		$data = json_decode( $response_body );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'api_error', 'Failed to decode REST JSON response: ' . json_last_error_msg() );
		}

		return $data;
	}

	/**
	 * Build the GraphQL API URL.
	 *
	 * @param string $domain The Shopify domain.
	 * @return string The complete GraphQL API URL.
	 */
	private function build_graphql_url( string $domain ): string {
		// Ensure the domain has the protocol.
		if ( ! preg_match( '~^https?://~i', $domain ) ) {
			$domain = 'https://' . $domain;
		}

		$shop_url = untrailingslashit( $domain );
		// Use the same API version as REST.
		$api_version = '2025-04';
		return "{$shop_url}/admin/api/{$api_version}/graphql.json";
	}

	/**
	 * Build the request arguments for GraphQL requests.
	 *
	 * @param string $access_token The Shopify access token.
	 * @param string $query        The GraphQL query.
	 * @param array  $variables    The GraphQL variables.
	 * @return array Request arguments for wp_remote_request.
	 *
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 */
	private function build_graphql_request_args( string $access_token, string $query, array $variables ): array {
		$request_body = compact( 'query', 'variables' );

		return array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'           => 'application/json',
				'X-Shopify-Access-Token' => $access_token,
			),
			'body'    => wp_json_encode( $request_body ),
			'timeout' => 60,
		);
	}

	/**
	 * Process the GraphQL API response.
	 *
	 * @param array|\WP_Error $response The HTTP response.
	 * @return object|\WP_Error Decoded response data or WP_Error.
	 */
	private function process_graphql_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', 'GraphQL request failed: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 300 ) {
			$error_details = json_decode( $response_body );
			$error_message = isset( $error_details->errors ) ? wp_json_encode( $error_details->errors ) : $response_body;
			return new \WP_Error(
				'api_error',
				"GraphQL request failed with status code {$response_code}: " . $error_message
			);
		}

		$data = json_decode( $response_body );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'api_error', 'Failed to decode GraphQL JSON response: ' . json_last_error_msg() );
		}

		// Check for GraphQL-specific errors.
		if ( ! empty( $data->errors ) ) {
			return new \WP_Error( 'graphql_error', 'GraphQL API returned errors: ' . wp_json_encode( $data->errors ) );
		}

		if ( empty( $data->data ) ) {
			return new \WP_Error( 'api_error', 'GraphQL response missing "data" field.' );
		}

		return $data->data;
	}
}
