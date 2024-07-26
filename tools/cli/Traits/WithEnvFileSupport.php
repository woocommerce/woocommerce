<?php

namespace WooCommerce\Dev\CLI\Traits;

/**
 * Implements methods to provide environment file (.env) support
 * to a command.
 *
 * @package WooCommerce\Dev\CLI\Traits
 */

use RuntimeException;

/**
 * Trait WithEnvFileSupport
 *
 * @package WooCommerce\Dev\CLI\Traits
 */
trait WithEnvFileSupport {
	/**
	 * Reads and exports, by means of calls to the `setenv` function, the contents
	 * of a list of files in the .env format (KEY=VALUE\n).
	 *
	 * @param array<string> $envFiles A list of .env format files to read. Non-existing files will
	 *                             be ignored.
	 *
	 * @throws RuntimeException If one of the environment files cannot be read or there's an issue with its handling.
	 * @since TBD
	 *
	 */
	protected function exportEnvFiles( array $envFiles ): void {
		foreach ( $envFiles as $envFile ) {
			if ( ! ( is_file( $envFile ) && is_readable( $envFile ) ) ) {
				continue;
			}

			if ( ! $envFileHandle = fopen( $envFile, 'rb' ) ) {
				throw new RuntimeException( "Cannot read file {$envFile}." );
			}

			while ( $line = fgets( $envFileHandle ) ) {
				if ( ! preg_match( '/[\w_-]/', $line ) || strpos( $line, '=' ) === false ) {
					// Comment or blob or not a correct line.
					continue;
				}
				$setting = trim( $line );
				[ $key, $value ] = explode( '=', $setting, 2 );
				putenv( $setting );
				if ( isset( $_ENV ) ) {
					$_ENV[ trim( $key ) ] = trim( $value );
				}
			}

			if ( ! fclose( $envFileHandle ) ) {
				throw new RuntimeException( "Cannot close {$envFile} file handle." );
			}
		}
	}
}
