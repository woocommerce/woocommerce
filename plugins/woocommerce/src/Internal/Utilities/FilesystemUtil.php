<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WP_Filesystem_Base;

/**
 * FilesystemUtil class.
 */
class FilesystemUtil {
	/**
	 * Wrapper to retrieve the class instance contained in the $wp_filesystem global, after initializing if necessary.
	 *
	 * @return WP_Filesystem_Base
	 * @throws Exception Thrown when the filesystem fails to initialize.
	 */
	public static function get_wp_filesystem(): WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			$initialized = self::initialize_wp_filesystem();

			if ( false === $initialized ) {
				throw new Exception( 'The WordPress filesystem could not be initialized.' );
			}
		}

		return $wp_filesystem;
	}

	/**
	 * Get the WP filesystem method, with a fallback to 'direct' if no FS_METHOD constant exists and there are not FTP related options/credentials set.
	 *
	 * @return string|false The name of the WP filesystem method to use.
	 */
	public static function get_wp_filesystem_method_or_direct() {
		$proxy = wc_get_container()->get( LegacyProxy::class );
		if ( ! self::constant_exists( 'FS_METHOD' ) && false === $proxy->call_function( 'get_option', 'ftp_credentials' ) && ! self::constant_exists( 'FTP_HOST' ) ) {
			return 'direct';
		}

		$method = $proxy->call_function( 'get_filesystem_method' );
		if ( $method ) {
			return $method;
		}

		return 'direct';
	}

	/**
	 * Check if a constant exists and is not null.
	 *
	 * @param string $name Constant name.
	 * @return bool True if the constant exists and its value is not null.
	 */
	private static function constant_exists( string $name ): bool {
		return Constants::is_defined( $name ) && ! is_null( Constants::get_constant( $name ) );
	}

	/**
	 * Recursively creates a directory (if it doesn't exist) and adds an empty index.html and a .htaccess to prevent
	 * directory listing.
	 *
	 * @since 9.3.0
	 *
	 * @param string $path Directory to create.
	 * @throws \Exception In case of error.
	 */
	public static function mkdir_p_not_indexable( string $path ): void {
		$wp_fs = self::get_wp_filesystem();

		if ( $wp_fs->is_dir( $path ) ) {
			return;
		}

		if ( ! wp_mkdir_p( $path ) ) {
			throw new \Exception( esc_html( sprintf( 'Could not create directory: %s.', wp_basename( $path ) ) ) );
		}

		$files = array(
			'.htaccess'  => 'deny from all',
			'index.html' => '',
		);

		foreach ( $files as $name => $content ) {
			$wp_fs->put_contents( trailingslashit( $path ) . $name, $content );
		}
	}

	/**
	 * Wrapper to initialize the WP filesystem with defined credentials if they are available.
	 *
	 * @return bool True if the $wp_filesystem global was successfully initialized.
	 */
	protected static function initialize_wp_filesystem(): bool {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$method      = self::get_wp_filesystem_method_or_direct();
		$initialized = false;

		if ( 'direct' === $method ) {
			$initialized = WP_Filesystem();
		} elseif ( false !== $method ) {
			// See https://core.trac.wordpress.org/changeset/56341.
			ob_start();
			$credentials = request_filesystem_credentials( '' );
			ob_end_clean();

			$initialized = $credentials && WP_Filesystem( $credentials );
		}

		return is_null( $initialized ) ? false : $initialized;
	}
}
