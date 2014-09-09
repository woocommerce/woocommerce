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
		add_action( 'woocommerce_language_pack_updater_check', array( $this, 'has_available_update' ) );
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
		$version = get_option( 'woocommerce_language_pack_version', array( '0', get_locale() ) );

		if ( 'en_US' !== get_locale() && ( ! is_array( $version ) || version_compare( $version[0], WC_VERSION, '<' ) || $version[1] !== get_locale() ) ) {
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
		$notices = get_option( 'woocommerce_admin_notices', array() );
		if ( false === array_search( 'translation_upgrade', $notices ) ) {
			$notices[] = 'translation_upgrade';

			update_option( 'woocommerce_admin_notices', $notices );
		}
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
			// Update the language pack version
			update_option( 'woocommerce_language_pack_version', array( WC_VERSION , get_locale() ) );

			// Remove the translation upgrade notice
			$notices = get_option( 'woocommerce_admin_notices', array() );
			$notices = array_diff( $notices, array( 'translation_upgrade' ) );
			update_option( 'woocommerce_admin_notices', $notices );
		}

		return $reply;
	}

}

new WC_Language_Pack_Upgrader();
