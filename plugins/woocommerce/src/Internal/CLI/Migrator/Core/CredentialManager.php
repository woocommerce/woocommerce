<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

use WP_CLI;

/**
 * Manages platform credentials.
 */
class CredentialManager {
	/**
	 * Retrieves the stored credentials for a given platform.
	 *
	 * @param string $platform_slug The slug for the platform.
	 *
	 * @return array|null An associative array of credentials, or null if not found.
	 */
	public function get_credentials( string $platform_slug ): ?array {
		$option_name      = "wc_migrator_credentials_{$platform_slug}";
		$credentials_json = get_option( $option_name, false );
		if ( ! $credentials_json ) {
			return null;
		}

		$credentials = json_decode( $credentials_json, true );

		return is_array( $credentials ) ? $credentials : null;
	}

	/**
	 * Checks if credentials exist for a given platform.
	 *
	 * @param string $platform_slug The slug for the platform.
	 *
	 * @return bool True if credentials exist, false otherwise.
	 */
	public function has_credentials( string $platform_slug ): bool {
		$credentials = $this->get_credentials( $platform_slug );

		return ! empty( $credentials );
	}

	/**
	 * Prompts the user for credentials via the command line.
	 *
	 * @param array $fields An associative array of fields to prompt for.
	 *
	 * @return array The collected credentials.
	 */
	public function prompt_for_credentials( array $fields ): array {
		$credentials = array();
		foreach ( $fields as $key => $prompt ) {
			$credentials[ $key ] = $this->readline( $prompt . ' ' );
		}

		return $credentials;
	}

	/**
	 * Saves credentials to the database for a given platform.
	 *
	 * @param string $platform_slug The slug for the platform.
	 * @param array  $credentials   An associative array of credentials.
	 */
	public function save_credentials( string $platform_slug, array $credentials ): void {
		$option_name = "wc_migrator_credentials_{$platform_slug}";
		update_option( $option_name, wp_json_encode( $credentials ) );
	}

	/**
	 * Deletes credentials from the database for a given platform.
	 *
	 * @param string $platform_slug The slug for the platform.
	 */
	public function delete_credentials( string $platform_slug ): void {
		$option_name = "wc_migrator_credentials_{$platform_slug}";
		delete_option( $option_name );
	}

	/**
	 * Reads a line from STDIN.
	 *
	 * A backward-compatible wrapper for WP_CLI::readline().
	 *
	 * @param string $prompt The prompt to show to the user.
	 *
	 * @return string
	 */
	private function readline( string $prompt ): string {
		if ( method_exists( 'WP_CLI', 'readline' ) ) {
			return WP_CLI::readline( $prompt );
		}

		WP_CLI::line( $prompt );
		return trim( fgets( STDIN ) );
	}
}
