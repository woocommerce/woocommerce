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
 * @version  2.2.0
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
	protected $repo = 'https://github.com/woothemes/woocommerce-language-packs/raw/v';

	/**
	 * Initialize the language pack upgrader
	 */
	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'upgrader_pre_download', array( $this, 'version_update' ), 10, 2 );
		add_action( 'woocommerce_installed', array( $this, 'has_available_update' ) );
		add_filter( 'admin_init', array( $this, 'manual_language_update' ), 999 );
	}

	/**
	 * Get language package URI.
	 *
	 * @return string
	 */
	public function get_language_package_uri() {
		return $this->repo . WC_VERSION . '/packages/' . get_locale() . '.zip';
	}

	/**
	 * Check for language updates
	 *
	 * @param  object $data Transient update data
	 *
	 * @return object
	 */
	public function check_for_update( $data ) {
		if ( $this->has_available_update() ) {
			$data->translations[] = array(
				'type'       => 'plugin',
				'slug'       => 'woocommerce',
				'language'   => get_locale(),
				'version'    => WC_VERSION,
				'updated'    => date( 'Y-m-d H:i:s' ),
				'package'    => $this->get_language_package_uri(),
				'autoupdate' => 1
			);
		}

		return $data;
	}

	/**
	 * Check if has available translation update
	 *
	 * @return bool
	 */
	public function has_available_update() {
		$locale  = get_locale();

		if ( 'en_US' !== $locale ) {
			return false;
		}

		$version = get_option( 'woocommerce_language_pack_version', array( '0', $locale ) );

		if ( ! is_array( $version ) || version_compare( $version[0], WC_VERSION, '<' ) || $version[1] !== $locale ) {
			if ( $this->check_if_language_pack_exists() ) {
				$this->configure_woocommerce_upgrade_notice();

				return true;
			} else {
				// Updated the woocommerce_language_pack_version to avoid searching translations for this release again
				update_option( 'woocommerce_language_pack_version', array( WC_VERSION , get_locale() ) );
			}
		}

		return false;
	}

	/**
	 * Configure the WooCommerce translation upgrade notice
	 *
	 * @return void
	 */
	public function configure_woocommerce_upgrade_notice() {
		WC_Admin_Notices::add_notice( 'translation_upgrade' );
	}

	/**
	 * Check if language pack exists
	 *
	 * @return bool
	 */
	public function check_if_language_pack_exists() {
		$response = wp_remote_get( $this->get_language_package_uri(), array( 'sslverify' => false, 'timeout' => 60 ) );

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
		if ( $package === $this->get_language_package_uri() ) {
			$this->save_language_version();
		}

		return $reply;
	}

	/**
	 * Save language version
	 *
	 * @return void
	 */
	protected function save_language_version() {
		// Update the language pack version
		update_option( 'woocommerce_language_pack_version', array( WC_VERSION , get_locale() ) );

		// Remove the translation upgrade notice
		$notices = get_option( 'woocommerce_admin_notices', array() );
		$notices = array_diff( $notices, array( 'translation_upgrade' ) );
		update_option( 'woocommerce_admin_notices', $notices );
	}

	/**
	 * Manual language update
	 *
	 * @return void
	 */
	public function manual_language_update() {
		if (
			is_admin()
			&& current_user_can( 'update_plugins' )
			&& isset( $_GET['page'] )
			&& 'wc-status' == $_GET['page']
			&& isset( $_GET['action'] )
			&& 'translation_upgrade' == $_GET['action']
		) {

			$url       = wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=translation_upgrade' ), 'language_update' );
			$tools_url = admin_url( 'admin.php?page=wc-status&tab=tools' );

			if ( ! isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {
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
			$response = wp_remote_get( $this->get_language_package_uri(), array( 'sslverify' => false, 'timeout' => 60 ) );
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

}

new WC_Language_Pack_Upgrader();
