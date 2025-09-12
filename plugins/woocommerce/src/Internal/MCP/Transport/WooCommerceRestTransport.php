<?php
/**
 * WooCommerce MCP REST Transport with API validation.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP\Transport;

use Automattic\WooCommerce\Internal\MCP\ApiKeyManager;
use WP\MCP\Transport\Http\RestTransport;
use WP_REST_Request;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce MCP REST Transport class.
 * 
 * Extends the base RestTransport with WooCommerce-specific API key validation.
 * Overrides the check_permission method to accept a request parameter for validation.
 */
class WooCommerceRestTransport extends RestTransport {

	/**
	 * Check if the user has permission to access the MCP API.
	 *
	 * @param WP_REST_Request|null $request The REST request object.
	 * @return bool|\WP_Error True if allowed, WP_Error if not.
	 */
	public function check_permission( $request = null ) {
		// Get request object if not provided
		if ( ! $request ) {
			$request = rest_get_server()->get_request();
		}

		// Check for API key in header
		$api_key = $request ? $request->get_header( 'X-MCP-API-Key' ) : null;

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_api_key',
				'API key required in X-MCP-API-Key header',
				array( 'status' => 401 )
			);
		}

		// Validate API key
		if ( ! $this->validate_api_key( $api_key ) ) {
			return new \WP_Error(
				'invalid_api_key',
				'Invalid API key',
				array( 'status' => 403 )
			);
		}

		/**
		 * TEMPORARY: Set current user to admin for MCP API requests.
		 * This ensures abilities have proper permissions when called via MCP.
		 * 
		 * TODO: Implement proper user association with API keys.
		 * Future implementation should:
		 * - Associate each API key with a specific WordPress user
		 * - Set the current user based on the API key used
		 * - Allow configurable permissions per API key
		 * 
		 * @since WooCommerce MCP Integration
		 */
		$admin_users = get_users( array(
			'role'   => 'administrator',
			'number' => 1,
		) );

		if ( ! empty( $admin_users ) ) {
			wp_set_current_user( $admin_users[0]->ID );
		}

		return true;
	}

	/**
	 * Validate API key against stored key.
	 *
	 * @param string $api_key API key to validate.
	 * @return bool Whether API key is valid.
	 */
	private function validate_api_key( string $api_key ): bool {
		return ApiKeyManager::validate_api_key( $api_key );
	}
}