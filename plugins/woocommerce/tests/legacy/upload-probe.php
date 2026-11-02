<?php
/**
 * Temporary diagnostics for uploads under WordPress 7.2's isolated test directory.
 *
 * Remove together with the `require_once` in bootstrap.php once the cause is known.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Report the state of the upload target, and whether this process can write into it.
 *
 * @param array $file Upload array passed through the prefilter.
 * @return array
 */
function wc_wp72_upload_probe( $file ) {
	static $seen = 0;
	++$seen;

	// Matches what _wp_handle_upload() does next, so the report describes the directory it will use.
	$uploads = wp_upload_dir();
	$path    = isset( $uploads['path'] ) ? (string) $uploads['path'] : '';
	$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
	$tmp     = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

	$describe = function ( $dir ) {
		if ( '' === $dir || ! file_exists( $dir ) ) {
			return 'missing';
		}

		return sprintf(
			'perms=%s owner=%s:%s writable=%s',
			substr( sprintf( '%o', fileperms( $dir ) ), -4 ),
			fileowner( $dir ),
			filegroup( $dir ),
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Temporary diagnostics.
			is_writable( $dir ) ? 'yes' : 'no'
		);
	};

	// The decisive check: can this process actually create a file where the copy is about to land?
	$write_test = 'skipped';
	if ( '' !== $path && file_exists( $path ) ) {
		$probe_file = $path . '/wc-upload-probe-' . $seen . '.txt';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Temporary diagnostics.
		$write_test = false === file_put_contents( $probe_file, 'probe' ) ? 'FAILED' : 'ok';
		if ( 'ok' === $write_test ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Temporary diagnostics.
			unlink( $probe_file );
		}
	}

	$source  = '' === $tmp || ! file_exists( $tmp ) ? 'missing' : 'readable=' . ( is_readable( $tmp ) ? 'yes' : 'no' );
	$anomaly = 'ok' !== $write_test || 'missing' === $source || ! empty( $uploads['error'] );

	if ( $seen > 3 && ! $anomaly ) {
		return $file;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Temporary diagnostics.
	fwrite(
		STDERR,
		sprintf(
			"\nUPLOAD-PROBE #%d %s UPLOADS=%s run_id=%s euid=%s wp_upload_dir_error=%s\n  basedir  %s (%s)\n  path     %s (%s)\n  write    %s\n  tmp_name %s (%s)\n  temp_dir %s\n",
			$seen,
			$anomaly ? 'ANOMALY' : 'baseline',
			defined( 'UPLOADS' ) ? UPLOADS : '(undefined)',
			false === getenv( 'WP_TESTS_UPLOADS_RUN_ID' ) ? '(unset)' : getenv( 'WP_TESTS_UPLOADS_RUN_ID' ),
			function_exists( 'posix_geteuid' ) ? posix_geteuid() : 'unknown',
			empty( $uploads['error'] ) ? 'none' : $uploads['error'],
			$basedir,
			$describe( $basedir ),
			$path,
			$describe( $path ),
			$write_test,
			$tmp,
			$source,
			get_temp_dir()
		)
	);

	return $file;
}

add_filter( 'wp_handle_upload_prefilter', 'wc_wp72_upload_probe' );
add_filter( 'wp_handle_sideload_prefilter', 'wc_wp72_upload_probe' );
