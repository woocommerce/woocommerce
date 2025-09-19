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
	 * Current MCP user's API key permissions.
	 *
	 * @var string|null
	 */
	private static $current_mcp_permissions = null;

	/**
	 * Constructor.
	 */
	public function __construct( ...$args ) {
		parent::__construct( ...$args );

		// Register permission filter callback
		add_filter( 'woocommerce_check_rest_ability_permissions_for_method', array( $this, 'check_ability_permission' ), 10, 3 );
	}

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
		// Require TLS by default; allow explicit opt-in for non-SSL (e.g., local dev).
		if ( ! is_ssl() && ! apply_filters( 'woocommerce_mcp_allow_insecure_transport', false, $request ) ) {
			return new \WP_Error(
				'insecure_transport',
				__( 'HTTPS is required for MCP requests.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

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

		// Store permissions for tool-level checking
		self::$current_mcp_permissions = $user_data->permissions;

		// Set the current user
		wp_set_current_user( $user_data->user_id );

		return $user_data->user_id;
	}

	/**
	 * Get the current MCP user's API key permissions.
	 *
	 * @return string|null The permissions (read, write, read_write) or null if no MCP context.
	 */
	public static function get_current_user_permissions(): ?string {
		return self::$current_mcp_permissions;
	}

	/**
	 * Check REST ability permissions for HTTP method.
	 *
	 * @param bool   $allowed    Whether the operation is allowed. Default false.
	 * @param string $method     HTTP method (GET, POST, PUT, DELETE).
	 * @param object $controller REST controller instance.
	 * @return bool Whether permission is granted.
	 */
	public function check_ability_permission( $allowed, $method, $controller ) {
		// Only check permissions if we have MCP context
		$permissions = self::get_current_user_permissions();
		if ( $permissions === null ) {
			return $allowed;
		}

		// Check permissions based on method
		switch ( $method ) {
			case 'HEAD':
			case 'GET':
				return ( 'read' === $permissions || 'read_write' === $permissions );
			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				return ( 'write' === $permissions || 'read_write' === $permissions );
			case 'OPTIONS':
				return true;
			default:
				return false;
		}
	}

}