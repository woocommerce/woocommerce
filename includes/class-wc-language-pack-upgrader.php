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
		add_filter( 'upgrader_post_install', array( $this, 'version_update' ), 999, 2 );
	}

	/**
	 * Get WordPress language
	 *
	 * @return string
	 */
	public static function get_language() {
		if ( defined( 'WPLANG' ) && '' != WPLANG ) {
			return WPLANG;
		}
		return 'en';
	}

	/**
	 * Get language package URI.
	 *
	 * @return string
	 */
	public function get_language_package_uri() {
		return $this->repo . WC_VERSION . '/packages/' . $this->get_language() . '.zip';
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
				'language'   => $this->get_language(),
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
		$version = get_option( 'woocommerce_language_pack_version', '0' );

		if ( version_compare( $version, WC_VERSION, '<' ) && 'en' !== $this->get_language() ) {

			if ( $this->check_if_language_pack_exists() ) {
				$this->configure_woocommerce_upgrade_notice();

				return true;
			} else {
				// Updated the woocommerce_language_pack_version to avoid searching translations for this release again
				update_option( 'woocommerce_language_pack_version', WC_VERSION );
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
	 * @param  bool  $response   Install response (true = success, false = fail)
	 * @param  array $hook_extra Extra arguments passed to hooked filters
	 *
	 * @return bool
	 */
	public function version_update( $response, $hook_extra ) {
		if ( $response ) {
			if (
				( isset( $hook_extra['language_update_type'] ) && 'plugin' == $hook_extra['language_update_type'] )
				&& ( isset( $hook_extra['language_update']->slug ) && 'woocommerce' == $hook_extra['language_update']->slug )
			) {
				// Update the language pack version
				update_option( 'woocommerce_language_pack_version', WC_VERSION );

				// Remove the translation upgrade notice
				$notices = get_option( 'woocommerce_admin_notices', array() );
				$notices = array_diff( $notices, array( 'translation_upgrade' ) );
				update_option( 'woocommerce_admin_notices', $notices );
			}
		}

		return $response;
	}

}

new WC_Language_Pack_Upgrader();
