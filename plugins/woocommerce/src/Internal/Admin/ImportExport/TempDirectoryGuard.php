<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

/**
 * Checks where import and export scratch files would be written.
 *
 * `WP_TEMP_DIR`, or the `WP_CONTENT_DIR` fallback in `get_temp_dir()`, can point at a publicly served path.
 *
 * @since 11.4.0
 */
final class TempDirectoryGuard {

	/**
	 * Check whether a directory resolves inside the WordPress install.
	 *
	 * @param string $directory The directory to check.
	 *
	 * @return bool True when the directory is inside `ABSPATH` or `WP_CONTENT_DIR`.
	 */
	public static function is_inside_web_root( string $directory ): bool {
		$directory = self::resolve( $directory );

		foreach ( array( WP_CONTENT_DIR, ABSPATH ) as $root ) {
			if ( 0 === strpos( $directory, self::resolve( $root ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize a directory path, following symlinks when the path exists.
	 *
	 * @param string $directory The directory path.
	 *
	 * @return string The path with a trailing slash.
	 */
	private static function resolve( string $directory ): string {
		$resolved = realpath( $directory );

		return trailingslashit( wp_normalize_path( false !== $resolved ? $resolved : $directory ) );
	}
}
