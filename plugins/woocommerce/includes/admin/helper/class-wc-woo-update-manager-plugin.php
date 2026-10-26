<?php
/**
 * A utility class for Woo Update Manager plugin.
 *
 * @class WC_Woo_Update_Manager_Plugin
 * @package WooCommerce\Admin\Helper
 */

use Automattic\WooCommerce\Admin\PageController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin Class
 *
 * Contains the logic to manage the Woo Update Manager plugin.
 */
class WC_Woo_Update_Manager_Plugin {
	const WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE = 'woo-update-manager/woo-update-manager.php';
	const WOO_UPDATE_MANAGER_DOWNLOAD_URL     = 'https://woocommerce.com/product-download/woo-update-manager';
	const WOO_UPDATE_MANAGER_SLUG             = 'woo-update-manager';

	/**
	 * Domains, and their subdomains, that WooCommerce.com treats as local.
	 */
	private const LOCAL_DOMAINS = array( 'localhost', 'local', 'test', 'lan', 'internal', 'invalid', 'example', 'home', 'localdomain', 'ddev.site', 'lndo.site' );

	/**
	 * Loads the class, runs on init.
	 *
	 * @return void
	 */
	public static function load(): void {
		add_action( 'admin_notices', array( __CLASS__, 'show_woo_update_manager_install_notice' ) );
	}

	/**
	 * Check if the Woo Update Manager plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		return is_plugin_active_for_network( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE ) || is_plugin_active( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Check if the Woo Update Manager plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Generate the URL to install the Woo Update Manager plugin.
	 *
	 * @return string
	 */
	public static function generate_install_url(): string {
		$install_url = WC_Helper::get_install_base_url() . self::WOO_UPDATE_MANAGER_SLUG . '/';

		return WC_Helper_API::add_auth_parameters( $install_url );
	}

	/**
	 * Whether the URL's host is one WooCommerce.com can't reach to push an install: private or reserved IPs,
	 * single-label hosts and local development domains. Matches the check WooCommerce.com runs on its side.
	 *
	 * @param string $url URL to check, usually the home URL.
	 * @return bool
	 */
	public static function is_local_url( string $url ): bool {
		$host = strtolower( trim( (string) wp_parse_url( $url, PHP_URL_HOST ), '[]' ) );

		if ( '' === $host ) {
			return false;
		}

		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}

		if ( false === strpos( $host, '.' ) ) {
			return true;
		}

		foreach ( self::LOCAL_DOMAINS as $domain ) {
			if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the id of the Woo Update Manager plugin.
	 *
	 * @return int
	 */
	public static function get_plugin_slug(): string {
		return self::WOO_UPDATE_MANAGER_SLUG;
	}

	/**
	 * Show a notice on the WC admin pages to install or activate the Woo Update Manager plugin.
	 *
	 * @return void
	 */
	public static function show_woo_update_manager_install_notice(): void {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! WC_Helper::is_site_connected() ) {
			return;
		}

		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		if ( self::is_plugin_installed() && self::is_plugin_active() ) {
			return;
		}

		if ( ! self::is_plugin_installed() ) {

			if ( self::install_admin_notice_dismissed() ) {
				return;
			}

			include __DIR__ . '/views/html-notice-woo-updater-not-installed.php';
			return;
		}

		if ( self::activate_admin_notice_dismissed() ) {
			return;
		}

		include __DIR__ . '/views/html-notice-woo-updater-not-activated.php';
	}

	/**
	 * Check if the installation notice has been dismissed.
	 *
	 * @return bool
	 */
	protected static function install_admin_notice_dismissed(): bool {
		return get_user_meta( get_current_user_id(), 'dismissed_woo_updater_not_installed_notice', true );
	}

	/**
	 * Check if the activation notice has been dismissed.
	 *
	 * @return bool
	 */
	protected static function activate_admin_notice_dismissed(): bool {
		return get_user_meta( get_current_user_id(), 'dismissed_woo_updater_not_activated_notice', true );
	}
}

WC_Woo_Update_Manager_Plugin::load();
