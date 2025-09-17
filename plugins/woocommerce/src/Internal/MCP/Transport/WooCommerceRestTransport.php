<?php
/**
 * WooCommerce MCP REST Transport with API validation.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP\Transport;

use WP\MCP\Transport\Http\RestTransport;
use WP_REST_Request;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce MCP REST Transport class.
 *
 * Extends the base RestTransport with standalone WooCommerce REST API key authentication.
 * Uses X-MCP-API-Key header with consumer_key:consumer_secret format.
 */
class WooCommerceRestTransport extends RestTransport {

	/**
	 * Validate request using WooCommerce REST API authentication.
	 *
	 * @param WP_REST_Request|null $request The REST request object.
	 * @return bool|\WP_Error True if allowed, WP_Error if not.
	 */
	public function check_permission( $request = null ) {
		return $this->validate_request( $request );
	}

	/**
	 * Validate the MCP request using standalone authentication.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool|\WP_Error True if allowed, WP_Error if not.
	 */
	public function validate_request( \WP_REST_Request $request ) {
		// Get X-MCP-API-Key header
		$api_key = $request->get_header( 'X-MCP-API-Key' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_api_key',
				'X-MCP-API-Key header required. Format: consumer_key:consumer_secret',
				array( 'status' => 401 )
			);
		}

		if ( strpos( $api_key, ':' ) === false ) {
			return new \WP_Error(
				'invalid_api_key',
				'X-MCP-API-Key must be in format consumer_key:consumer_secret',
				array( 'status' => 401 )
			);
		}

		list( $consumer_key, $consumer_secret ) = explode( ':', $api_key, 2 );

		// Use our standalone authentication method
		$result = $this->authenticate( $consumer_key, $consumer_secret );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Authenticate user using consumer key and secret.
	 *
	 * @param string $consumer_key    Consumer key.
	 * @param string $consumer_secret Consumer secret.
	 * @return int|\WP_Error User ID on success, WP_Error on failure.
	 */
	private function authenticate( $consumer_key, $consumer_secret ) {
		global $wpdb;

		// Hash the consumer key as WooCommerce does
		$hashed_consumer_key = wc_api_hash( sanitize_text_field( $consumer_key ) );

		// Query the WooCommerce API keys table directly
		$user_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE consumer_key = %s",
				$hashed_consumer_key
			)
		);

		// Check if user data was found
		if ( empty( $user_data ) ) {
			return new \WP_Error(
				'invalid_consumer_key',
				'Consumer key is invalid.',
				array( 'status' => 401 )
			);
		}

		// Validate consumer secret using hash_equals for timing attack protection
		if ( ! hash_equals( $user_data->consumer_secret, $consumer_secret ) ) {
			return new \WP_Error(
				'invalid_consumer_secret',
				'Consumer secret is invalid.',
				array( 'status' => 401 )
			);
		}

		// Set the current user
		wp_set_current_user( $user_data->user_id );

		return $user_data->user_id;
	}

}