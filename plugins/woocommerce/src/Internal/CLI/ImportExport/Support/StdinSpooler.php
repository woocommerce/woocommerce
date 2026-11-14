<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\ImportExport\Support;

use Automattic\WooCommerce\Internal\Admin\ImportExport\TempDirectoryGuard;
use InvalidArgumentException;

/**
 * Spools STDIN to a temporary CSV file so an importer can seek within it.
 *
 * @since 11.4.0
 */
final class StdinSpooler {

	/**
	 * Spool STDIN to a temporary file.
	 *
	 * The caller owns the returned file and is responsible for deleting it.
	 *
	 * @param string $prefix Prefix for the temporary file name.
	 *
	 * @return string Path to the temporary file.
	 *
	 * @throws InvalidArgumentException When the temporary file cannot be created or written.
	 */
	public function spool( string $prefix ): string {
		$temp_file = wp_tempnam( $prefix );

		if ( ! $temp_file ) {
			throw new InvalidArgumentException( 'Unable to create a temporary file for the STDIN input.' );
		}

		if ( TempDirectoryGuard::is_inside_web_root( dirname( $temp_file ) ) ) {
			wp_delete_file( $temp_file );
			throw new InvalidArgumentException( 'The temporary directory resolves inside the web root. Set WP_TEMP_DIR to a location outside it.' );
		}

		// wp_tempnam() gives a .tmp name and the importer only opens .csv or .txt files, so rename it.
		$csv_file = $temp_file . '.csv';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- local CLI temp file, WP_Filesystem is not initialised in CLI.
		if ( ! rename( $temp_file, $csv_file ) ) {
			wp_delete_file( $temp_file );
			throw new InvalidArgumentException( 'Unable to create a temporary file for the STDIN input.' );
		}

		$temp_file = $csv_file;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- no WP_Filesystem wrapper for chmod() exists.
		chmod( $temp_file, 0600 );

		$source = fopen( 'php://stdin', 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- STDIN is not part of the WP filesystem.

		if ( false === $source ) {
			wp_delete_file( $temp_file );
			throw new InvalidArgumentException( 'Unable to read STDIN.' );
		}

		$destination = fopen( $temp_file, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- writing a local CLI temp file.

		if ( false === $destination ) {
			fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			wp_delete_file( $temp_file );
			throw new InvalidArgumentException( 'Unable to open the temporary file for writing.' );
		}

		try {
			$copied = self::copy_stream_to_stream( $source, $destination );
		} finally {
			fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $destination ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		if ( ! $copied ) {
			wp_delete_file( $temp_file );
			throw new InvalidArgumentException( 'Unable to copy STDIN to the temporary file.' );
		}

		return $temp_file;
	}

	/**
	 * Copy every byte from a readable stream to a writable stream.
	 *
	 * @param resource $source      The readable stream to copy from.
	 * @param resource $destination The writable stream to copy to.
	 *
	 * @return bool Whether the entire source stream was copied successfully.
	 */
	public static function copy_stream_to_stream( $source, $destination ): bool {
		$bytes_copied = stream_copy_to_stream( $source, $destination );

		return false !== $bytes_copied && feof( $source );
	}
}
