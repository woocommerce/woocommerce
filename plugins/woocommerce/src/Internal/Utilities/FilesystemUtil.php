<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WP_Filesystem_Base;
use WP_Filesystem_Direct;

/**
 * FilesystemUtil class.
 */
class FilesystemUtil {

	/**
	 * `.htaccess` directive that prevents directory listing while still allowing direct access to files.
	 */
	public const HTACCESS_ALLOW_FILE_ACCESS = 'Options -Indexes';

	/**
	 * `.htaccess` directive that denies all access to a directory's contents.
	 */
	public const HTACCESS_DENY_ALL = 'deny from all';

	/**
	 * Transient key for tracking FTP filesystem initialization failures.
	 */
	private const FTP_INIT_FAILURE_TRANSIENT = 'wc_ftp_filesystem_init_failed';

	/**
	 * Cooldown period in minutes before retrying a failed FTP connection.
	 */
	private const FTP_INIT_COOLDOWN_MINUTES = 2;

	/**
	 * Memoized direct filesystem. Only ever holds a genuine WP_Filesystem_Direct;
	 * the defensive fallback is never cached so a later call can retry direct.
	 *
	 * @var WP_Filesystem_Base|null
	 */
	private static ?WP_Filesystem_Base $cached_direct_filesystem = null;

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
	 * Get a direct filesystem instance, bypassing the configured FS_METHOD.
	 *
	 * This is appropriate for paths that are guaranteed to be writable by the
	 * web server process (such as anything inside the uploads directory). Using
	 * the configured FS_METHOD for those paths is unnecessary and breaks on
	 * sites where FS_METHOD is set to an FTP-based method without complete
	 * credentials, even though the target paths are directly writable.
	 *
	 * Defensive fallback: if WP_Filesystem_Direct cannot be loaded or instantiated,
	 * fall back to {@see self::get_wp_filesystem()} with a _doing_it_wrong notice.
	 *
	 * @since 11.0.0
	 *
	 * @return WP_Filesystem_Base Normally a WP_Filesystem_Direct instance.
	 * @throws Exception If both the direct class and the configured FS_METHOD
	 *                   filesystem fail to initialize (the fallback path).
	 */
	public static function get_wp_filesystem_direct(): WP_Filesystem_Base {
		if ( null !== self::$cached_direct_filesystem ) {
			return self::$cached_direct_filesystem;
		}

		// require_once is a no-op if the class is already loaded, so no class_exists guard is needed.
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		if ( class_exists( WP_Filesystem_Direct::class ) ) {
			try {
				// WP_Filesystem_Direct::chmod()/put_contents() use the FS_CHMOD_* constants when no mode is
				// passed; core only defines them in WP_Filesystem(), which we skip, so mirror them here.
				if ( ! defined( 'FS_CHMOD_DIR' ) ) {
					define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
				}
				if ( ! defined( 'FS_CHMOD_FILE' ) ) {
					define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
				}

				self::$cached_direct_filesystem = new WP_Filesystem_Direct( null );
				return self::$cached_direct_filesystem;
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Fall through to the fallback below.
			}
		}

		_doing_it_wrong(
			__METHOD__,
			esc_html__( 'WP_Filesystem_Direct could not be loaded. Falling back to the configured FS_METHOD; operations on the uploads directory may fail if FS_METHOD is misconfigured.', 'woocommerce' ),
			'11.0.0'
		);

		// Deliberately uncached: the fallback may be a non-direct (FTP) instance, and caching it would pin
		// every later "direct" caller to FS_METHOD; leaving it uncached lets a later call retry direct.
		return self::get_wp_filesystem();
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
	 * Get the content directory's root-relative path (e.g. "/wp-content").
	 *
	 * Resolves the path under which files inside the content directory are addressed,
	 * honoring a relocated WP_CONTENT_DIR. When the content directory lives under
	 * ABSPATH the path is derived from it directly; otherwise (e.g. Bedrock-style
	 * layouts where it sits outside ABSPATH) it is derived from the content URL,
	 * falling back to "/wp-content".
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 *
	 * @return string The content directory's root-relative path, e.g. "/wp-content" or "/app".
	 */
	public static function get_content_directory_relative_path(): string {
		$abspath        = (string) Constants::get_constant( 'ABSPATH' );
		$wp_content_dir = (string) Constants::get_constant( 'WP_CONTENT_DIR' );

		if ( '' !== $abspath && 0 === strpos( $wp_content_dir, $abspath ) ) {
			return '/' . substr( $wp_content_dir, strlen( $abspath ) );
		}

		$content_url_path = wp_parse_url( content_url(), PHP_URL_PATH );
		return is_string( $content_url_path ) ? $content_url_path : '/wp-content';
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
	 * Always uses a direct filesystem for the file operations, since the
	 * caller is responsible for choosing a path that is writable by the web
	 * server process.
	 *
	 * @since 9.3.0
	 *
	 * @param string $path Directory to create.
	 * @param bool   $allow_file_access Whether to allow file access while preventing directory listing. Default false (deny all access).
	 * @throws \Exception In case of error.
	 */
	public static function mkdir_p_not_indexable( string $path, bool $allow_file_access = false ): void {
		$wp_fs = self::get_wp_filesystem_direct();

		if ( $wp_fs->is_dir( $path ) ) {
			return;
		}

		if ( ! wp_mkdir_p( $path ) ) {
			throw new \Exception( esc_html( sprintf( 'Could not create directory: %s.', wp_basename( $path ) ) ) );
		}

		$htaccess_content = $allow_file_access ? self::HTACCESS_ALLOW_FILE_ACCESS : self::HTACCESS_DENY_ALL;

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
			$is_ftp = in_array( $method, array( 'ftpext', 'ftpsockets' ), true );

			if ( $is_ftp && get_transient( self::FTP_INIT_FAILURE_TRANSIENT ) ) {
				return false;
			}

			// See https://core.trac.wordpress.org/changeset/56341.
			ob_start();
			$credentials = request_filesystem_credentials( '' );
			ob_end_clean();

			$initialized = $credentials && WP_Filesystem( $credentials );

			if ( $is_ftp ) {
				if ( ! $initialized ) {
					// A fixed cooldown is used instead of exponential backoff since this handles a non-critical
					// edge case (broken FTP filesystem during logging) that most sites will never encounter.
					set_transient( self::FTP_INIT_FAILURE_TRANSIENT, true, self::FTP_INIT_COOLDOWN_MINUTES * MINUTE_IN_SECONDS );
					error_log( sprintf( 'WooCommerce: FTP filesystem connection failed. Please check your FTP credentials. Retrying in %d minutes.', self::FTP_INIT_COOLDOWN_MINUTES ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				} else {
					delete_transient( self::FTP_INIT_FAILURE_TRANSIENT );
				}
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
	 * Uses a direct filesystem instance rather than honoring FS_METHOD: this
	 * check only inspects paths under ABSPATH or the uploads directory, both
	 * of which the web server can read directly.
	 *
	 * @param string $path The path to validate.
	 * @throws \Exception If the file path is not a valid upload path.
	 */
	public static function validate_upload_file_path( string $path ): void {
		$wp_filesystem = self::get_wp_filesystem_direct();

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
