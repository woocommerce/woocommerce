<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Language Pack Upgrader class
 *
 * Downloads the last language pack.
 *
 * @class    WC_Language_Pack_Upgrader
 * @version  2.4.0
 * @package  WooCommerce/Classes/Language
 * @category Class
 * @author   WooThemes
 */
class WC_Language_Pack_Upgrader {

	/**
	 * Languages repository
	 *
	 * @var string
	 */
	protected static $repo = 'https://github.com/woothemes/woocommerce-language-packs/raw/v';

	/**
	 * Initialize the language pack upgrader
	 */
	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'upgrader_pre_download', array( $this, 'version_update' ), 10, 2 );
		add_action( 'woocommerce_installed', array( __CLASS__, 'has_available_update' ) );
		add_action( 'update_option_WPLANG', array( $this, 'updated_language_option' ), 10, 2 );
		add_filter( 'admin_init', array( $this, 'manual_language_update' ), 10 );
	}

	/**
	 * Get language package URI.
	 *
	 * @return string
	 */
	public static function get_language_package_uri( $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = get_locale();
		}
		return self::$repo . WC_VERSION . '/packages/' . $locale . '.zip';
	}

	/**
	 * Check for language updates
	 *
	 * @param  object $data Transient update data
	 *
	 * @return object
	 */
	public function check_for_update( $data ) {
		if ( self::has_available_update() ) {
			$locale = get_locale();

			$data->translations[] = array(
				'type'       => 'plugin',
				'slug'       => 'woocommerce',
				'language'   => $locale,
				'version'    => WC_VERSION,
				'updated'    => date( 'Y-m-d H:i:s' ),
				'package'    => self::get_language_package_uri( $locale ),
				'autoupdate' => 1
			);
		}

		return $data;
	}

	/**
	 * Triggered when WPLANG is changed
	 *
	 * @param string $old
	 * @param string $new
	 */
	public function updated_language_option( $old, $new ) {
		self::has_available_update( $new );
	}

	/**
	 * Check if has available translation update
	 *
	 * @return bool
	 */
	public static function has_available_update( $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = get_locale();
		}

		if ( 'en_US' === $locale ) {
			return false;
		}

		$version = get_option( 'woocommerce_language_pack_version', array( '0', $locale ) );

		if ( ! is_array( $version ) || version_compare( $version[0], WC_VERSION, '<' ) || $version[1] !== $locale ) {
			if ( self::check_if_language_pack_exists( $locale ) ) {
				self::configure_woocommerce_upgrade_notice();
				return true;
			} else {
				// Updated the woocommerce_language_pack_version to avoid searching translations for this release again
				update_option( 'woocommerce_language_pack_version', array( WC_VERSION, $locale ) );
			}
		}

		return false;
	}

	/**
	 * Configure the WooCommerce translation upgrade notice
	 */
	public static function configure_woocommerce_upgrade_notice() {
		WC_Admin_Notices::add_notice( 'translation_upgrade' );
	}

	/**
	 * Check if language pack exists
	 *
	 * @return bool
	 */
	public static function check_if_language_pack_exists( $locale ) {
		$response = wp_safe_remote_get( self::get_language_package_uri( $locale ), array( 'timeout' => 60 ) );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update the language version in database
	 *
	 * This updates the database while the download the translation package and ensures that not generate download loop
	 * If the installation fails you can redo it in: WooCommerce > Sistem Status > Tools > Force Translation Upgrade
	 *
	 * @param  bool   $reply   Whether to bail without returning the package (default: false)
	 * @param  string $package Package URL
	 *
	 * @return bool
	 */
	public function version_update( $reply, $package ) {
		if ( $package === self::get_language_package_uri() ) {
			$this->save_language_version();
		}

		return $reply;
	}

	/**
	 * Save language version
	 */
	protected function save_language_version() {
		// Update the language pack version
		update_option( 'woocommerce_language_pack_version', array( WC_VERSION, get_locale() ) );

		// Remove the translation upgrade notice
		$notices = get_option( 'woocommerce_admin_notices', array() );
		$notices = array_diff( $notices, array( 'translation_upgrade' ) );
		update_option( 'woocommerce_admin_notices', $notices );
	}

	/**
	 * Manual language update
	 */
	public function manual_language_update() {
		if (
			is_admin()
			&& current_user_can( 'update_plugins' )
			&& isset( $_GET['page'] )
			&& in_array( $_GET['page'], array( 'wc-status', 'wc-setup' ) )
			&& isset( $_GET['action'] )
			&& 'translation_upgrade' == $_GET['action']
		) {
			$page    = 'wc-status&tab=tools';
			$wpnonce = 'debug_action';
			if ( 'wc-setup' == $_GET['page'] ) {
				$page    = 'wc-setup';
				$wpnonce = 'setup_language';
			}

			$url       = wp_nonce_url( admin_url( 'admin.php?page=' . $page . '&action=translation_upgrade' ), 'language_update' );
			$tools_url = admin_url( 'admin.php?page=' . $page );

			if ( ! isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $wpnonce ) ) {
				wp_redirect( add_query_arg( array( 'translation_updated' => 2 ), $tools_url ) );
				exit;
			}

			if ( false === ( $creds = request_filesystem_credentials( $url, '', false, false, null ) ) ) {
				wp_redirect( add_query_arg( array( 'translation_updated' => 3 ), $tools_url ) );
				exit;
			}

			if ( ! WP_Filesystem( $creds ) ) {
				request_filesystem_credentials( $url, '', true, false, null );

				wp_redirect( add_query_arg( array( 'translation_updated' => 3 ), $tools_url ) );
				exit;
			}

			// Download the language pack
			$response = wp_safe_remote_get( self::get_language_package_uri(), array( 'timeout' => 60 ) );
			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				global $wp_filesystem;

				$upload_dir = wp_upload_dir();
				$file       = trailingslashit( $upload_dir['path'] ) . get_locale() . '.zip';

				// Save the zip file
				if ( ! $wp_filesystem->put_contents( $file, $response['body'], FS_CHMOD_FILE ) ) {
					wp_redirect( add_query_arg( array( 'translation_updated' => 3 ), $tools_url ) );
					exit;
				}

				// Unzip the file to wp-content/languages/plugins directory
				$dir   = trailingslashit( WP_LANG_DIR ) . 'plugins/';
				$unzip = unzip_file( $file, $dir );
				if ( true !== $unzip ) {
					wp_redirect( add_query_arg( array( 'translation_updated' => 3 ), $tools_url ) );
					exit;
				}

				// Delete the package file
				$wp_filesystem->delete( $file );

				// Update the language pack version
				$this->save_language_version();

				// Redirect and show a success message
				wp_redirect( add_query_arg( array( 'translation_updated' => 1 ), $tools_url ) );
				exit;
			} else {
				// Don't have a valid package for the current language!
				wp_redirect( add_query_arg( array( 'translation_updated' => 4 ), $tools_url ) );
				exit;
			}
		}
	}

	/**
	 * Language update messages
	 *
	 * @since 2.4.5
	 */
	public static function language_update_messages() {
		switch ( $_GET['translation_updated'] ) {
			case 2 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . __( 'Seems you don\'t have permission to do this!', 'woocommerce' ) . '</p></div>';
				break;
			case 3 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . sprintf( __( 'An authentication error occurred while updating the translation. Please try again or configure your %sUpgrade Constants%s.', 'woocommerce' ), '<a href="http://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants">', '</a>' ) . '</p></div>';
				break;
			case 4 :
				echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . __( 'Sorry but there is no translation available for your language =/', 'woocommerce' ) . '</p></div>';
				break;

			default :
				// Force WordPress find for new updates and hide the WooCommerce translation update
				set_site_transient( 'update_plugins', null );

				echo '<div class="updated"><p>' . __( 'Translations installed/updated successfully!', 'woocommerce' ) . '</p></div>';
				break;
		}
	}
}

new WC_Language_Pack_Upgrader();
