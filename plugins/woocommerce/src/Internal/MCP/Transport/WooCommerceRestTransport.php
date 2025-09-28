<?php
/**
 * WooCommerce MCP REST Transport with API validation.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP\Transport;

use WP\MCP\Transport\Http\RestTransport;
use WP\MCP\Transport\Infrastructure\McpTransportContext;
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
	 *
	 * @param McpTransportContext $context The transport context.
	 */
	public function __construct( McpTransportContext $context ) {
		parent::__construct( $context );

		// This filter is documented in the check_ability_permission method.
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
		/**
		 * Filter to allow insecure transport for MCP requests.
		 *
		 * @since 10.3.0
		 * @param bool             $allowed Whether to allow insecure transport.
		 * @param \WP_REST_Request $request The REST request object.
		 */
		if ( ! is_ssl() && ! apply_filters( 'woocommerce_mcp_allow_insecure_transport', false, $request ) ) {
			return new \WP_Error(
				'insecure_transport',
				__( 'HTTPS is required for MCP requests.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Get X-MCP-API-Key header.
		$api_key = $request->get_header( 'X-MCP-API-Key' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_api_key',
				__( 'X-MCP-API-Key header required. Format: consumer_key:consumer_secret', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		if ( strpos( $api_key, ':' ) === false ) {
			return new \WP_Error(
				'invalid_api_key',
				__( 'X-MCP-API-Key must be in format consumer_key:consumer_secret', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		list( $consumer_key, $consumer_secret ) = explode( ':', $api_key, 2 );

		// Use our standalone authentication method.
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

		// Hash the consumer key as WooCommerce does.
		$hashed_consumer_key = wc_api_hash( trim( (string) $consumer_key ) );

		// Query the WooCommerce API keys table directly.
		$user_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE consumer_key = %s",
				$hashed_consumer_key
			)
		);

		// Check if user data was found.
		if ( empty( $user_data ) ) {
			return new \WP_Error(
				'authentication_failed',
				__( 'Authentication failed.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Validate consumer secret using hash_equals for timing attack protection.
		if ( ! hash_equals( $user_data->consumer_secret, trim( (string) $consumer_secret ) ) ) {
			return new \WP_Error(
				'authentication_failed',
				__( 'Authentication failed.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Store permissions for tool-level checking.
		self::$current_mcp_permissions = $user_data->permissions;

		// Ensure the user exists before switching context.
		$user = get_user_by( 'id', (int) $user_data->user_id );
		if ( ! $user ) {
			return new \WP_Error(
				'mcp_user_not_found',
				__( 'The user associated with this API key no longer exists.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}
		wp_set_current_user( $user->ID );

		return $user->ID;
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
		// Only check permissions if we have MCP context.
		$permissions = self::get_current_user_permissions();
		if ( null === $permissions ) {
			return $allowed;
		}

		// Check permissions based on method.
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
