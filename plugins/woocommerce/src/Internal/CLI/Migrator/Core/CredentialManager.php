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
	 * Handles the interactive credential setup process for a platform.
	 *
	 * @param string $platform_slug The platform slug to set up credentials for.
	 * @param array  $required_fields An array of field_key => prompt_text for credentials to collect.
	 *
	 * @return void
	 */
	public function setup_credentials( string $platform_slug, array $required_fields ): void {
		if ( empty( $required_fields ) ) {
			WP_CLI::error( 'No credential fields specified for setup.' );
			return;
		}

		WP_CLI::log( 'Configuring credentials for ' . ucfirst( $platform_slug ) . '...' );

		$credentials = $this->prompt_for_credentials( $required_fields );
		$this->save_credentials( $platform_slug, $credentials );
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
