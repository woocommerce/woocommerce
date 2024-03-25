<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\Jetpack\Constants;
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

		$method      = get_filesystem_method();
		$initialized = false;

		if ( 'direct' === $method ) {
			$initialized = WP_Filesystem();
		} elseif ( false !== $method ) {
			$credentials = get_option(
				'ftp_credentials',
				array(
					'hostname' => '',
					'username' => '',
				)
			);

			$ftp_constants = array(
				'hostname'    => 'FTP_HOST',
				'username'    => 'FTP_USER',
				'password'    => 'FTP_PASS',
				'public_key'  => 'FTP_PUBKEY',
				'private_key' => 'FTP_PRIKEY',
			);

			foreach ( $ftp_constants as $key => $constant ) {
				if ( Constants::is_defined( $constant ) ) {
					$credentials[ $key ] = Constants::get_constant( $constant );
				} elseif ( ! isset( $credentials[ $key ] ) ) {
					$credentials[ $key ] = '';
				}
			}

			$initialized = WP_Filesystem( $credentials );
		}

		return is_null( $initialized ) ? false : $initialized;
	}
}
