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
	 * Transient key for tracking FTP filesystem initialization failures.
	 */
	private const FTP_INIT_FAILURE_TRANSIENT = 'wc_ftp_filesystem_init_failed';

	/**
	 * Cooldown period in minutes before retrying a failed FTP connection.
	 */
	private const FTP_INIT_COOLDOWN_MINUTES = 2;

	/**
	 * Wrapper to retrieve the class instance contained in the $wp_filesystem global, after initializing if necessary.
	 *
	 * @return WP_Filesystem_Base
	 * @throws Exception Thrown when the filesystem fails to initialize.
	 */
	public static function get_wp_filesystem(): WP_Filesystem_Base {
		global $wp_filesystem;

		$initialized = ( $wp_filesystem instanceof WP_Filesystem_Base ) || self::initialize_wp_filesystem();

		if ( ! $initialized || ! self::is_usable_ftp_filesystem( $wp_filesystem ) ) {
			throw new Exception( 'The WordPress filesystem could not be initialized.' );
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
	 * @param bool   $allow_file_access Whether to allow file access while preventing directory listing. Default false (deny all access).
	 * @throws \Exception In case of error.
	 */
	public static function mkdir_p_not_indexable( string $path, bool $allow_file_access = false ): void {
		$wp_fs = self::get_wp_filesystem();

		if ( $wp_fs->is_dir( $path ) ) {
			return;
		}

		if ( ! wp_mkdir_p( $path ) ) {
			throw new \Exception( esc_html( sprintf( 'Could not create directory: %s.', wp_basename( $path ) ) ) );
		}

		$htaccess_content = $allow_file_access ? 'Options -Indexes' : 'deny from all';

		$files = array(
			'.htaccess'  => $htaccess_content,
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
			if ( get_transient( self::FTP_INIT_FAILURE_TRANSIENT ) ) {
				return false;
			}

			// See https://core.trac.wordpress.org/changeset/56341.
			ob_start();
			$credentials = request_filesystem_credentials( '' );
			ob_end_clean();

			$initialized = $credentials && WP_Filesystem( $credentials );

			if ( ! $initialized ) {
				// A fixed cooldown is used instead of exponential backoff since this handles a non-critical
				// edge case (broken FTP filesystem during logging) that most sites will never encounter.
				set_transient( self::FTP_INIT_FAILURE_TRANSIENT, true, self::FTP_INIT_COOLDOWN_MINUTES * MINUTE_IN_SECONDS );
				error_log( sprintf( 'WooCommerce: FTP filesystem connection failed. Please check your FTP credentials. Retrying in %d minutes.', self::FTP_INIT_COOLDOWN_MINUTES ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			} else {
				delete_transient( self::FTP_INIT_FAILURE_TRANSIENT );
			}
		}

		return is_null( $initialized ) ? false : $initialized;
	}

	/**
	 * Check if an FTP-based filesystem instance is usable.
	 *
	 * Checks both the connection resource and the error state. The connection
	 * resource can be null if PHP's max execution time interrupted ftp_connect()
	 * before it completed, leaving the instance in a broken state without errors.
	 *
	 * @param WP_Filesystem_Base $wp_filesystem The filesystem instance to check.
	 * @return bool False if FTP-based and unusable, true otherwise.
	 */
	private static function is_usable_ftp_filesystem( WP_Filesystem_Base $wp_filesystem ): bool {
		$has_broken_state = false;
		$has_errors       = false;

		if ( 'ftpext' === $wp_filesystem->method ) {
			$has_broken_state = empty( $wp_filesystem->link );
			$has_errors       = is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors();
		}

		if ( 'ftpsockets' === $wp_filesystem->method ) {
			$has_broken_state = empty( $wp_filesystem->ftp );
			$has_errors       = is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors();
		}

		return ! $has_broken_state && ! $has_errors;
	}

	/**
	 * Validate that a file path is a valid upload path.
	 *
	 * @param string $path The path to validate.
	 * @throws \Exception If the file path is not a valid upload path.
	 */
	public static function validate_upload_file_path( string $path ): void {
		$wp_filesystem = self::get_wp_filesystem();

		// File must exist and be readable.
		$is_valid_file = $wp_filesystem->is_readable( $path );

		// Check that file is within an allowed location.
		if ( $is_valid_file ) {
			$is_valid_file = self::file_is_in_directory( $path, $wp_filesystem->abspath() );
			if ( ! $is_valid_file ) {
				$upload_dir    = wp_get_upload_dir();
				$is_valid_file = false === $upload_dir['error'] && self::file_is_in_directory( $path, $upload_dir['basedir'] );
			}
		}

		if ( ! $is_valid_file ) {
			throw new \Exception( esc_html__( 'File path is not a valid upload path.', 'woocommerce' ) );
		}
	}

	/**
	 * Check if a given file is inside a given directory.
	 *
	 * @param string $file_path The full path of the file to check.
	 * @param string $directory The path of the directory to check.
	 * @return bool True if the file is inside the directory.
	 */
	private static function file_is_in_directory( string $file_path, string $directory ): bool {
		// Extract protocol if it exists.
		$protocol = '';
		if ( preg_match( '#^([a-z0-9]+://)#i', $file_path, $matches ) ) {
			$protocol  = $matches[1];
			$file_path = preg_replace( '#^[a-z0-9]+://#i', '', $file_path );
		}

		$file_path = (string) new URL( $file_path ); // This resolves '/../' sequences.
		$file_path = preg_replace( '/^file:\\/\\//', $protocol, $file_path );
		$file_path = preg_replace( '/^file:\\/\\//', '', $file_path );

		return 0 === stripos( wp_normalize_path( $file_path ), trailingslashit( wp_normalize_path( $directory ) ) );
	}
}
