<?php
/**
 * MCP API Key Manager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Simple API Key Manager for MCP authentication.
 * 
 * Manages a single API key for MCP server access.
 */
class ApiKeyManager {

	/**
	 * Option name for storing the API key.
	 */
	private const OPTION_NAME = 'woocommerce_mcp_api_key';

	/**
	 * Get the current API key, generating one if it doesn't exist.
	 *
	 * @return string The API key.
	 */
	public static function get_api_key(): string {
		$api_key = get_option( self::OPTION_NAME );
		
		if ( empty( $api_key ) ) {
			$api_key = self::generate_new_key();
		}
		
		return $api_key;
	}

	/**
	 * Generate a new API key and store it.
	 *
	 * @return string The generated API key.
	 */
	private static function generate_new_key(): string {
		$api_key = 'wc_mcp_' . wp_generate_password( 32, false );
		update_option( self::OPTION_NAME, $api_key );
		
		return $api_key;
	}

	/**
	 * Check if an API key exists.
	 *
	 * @return bool Whether an API key exists.
	 */
	public static function has_api_key(): bool {
		return ! empty( get_option( self::OPTION_NAME ) );
	}

	/**
	 * Validate an API key against the stored key.
	 *
	 * @param string $api_key API key to validate.
	 * @return bool Whether API key is valid.
	 */
	public static function validate_api_key( string $api_key ): bool {
		$stored_key = get_option( self::OPTION_NAME );
		return ! empty( $stored_key ) && hash_equals( $stored_key, $api_key );
	}

	/**
	 * Delete the stored API key.
	 *
	 * @return bool Whether the key was successfully deleted.
	 */
	public static function delete_api_key(): bool {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Regenerate the API key (delete old and create new).
	 *
	 * @return string The new API key.
	 */
	public static function regenerate_api_key(): string {
		self::delete_api_key();
		return self::generate_new_key();
	}
}