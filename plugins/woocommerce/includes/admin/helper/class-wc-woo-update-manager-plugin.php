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
	const WOO_UPDATE_MANAGER_DOWNLOAD_URL     = 'https://woo.com/products/woo-update-manager/download/';

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
		/**
		 * Filter the id of the Woo Update Manager plugin.
		 *
		 * @since 8.7.0
		 */
		$woo_update_manager_plugin_id = apply_filters( 'woo_update_manager_plugin_id', 18734003334043 );

		$install_url = WC_Helper::get_install_base_url() . $woo_update_manager_plugin_id . '/';

		return WC_Helper_API::add_auth_parameters( $install_url );
	}

	/**
	 * Show a notice on the plugins management page to install the Woo Update Manager plugin.
	 *
	 * @return void
	 */
	public static function show_woo_update_manager_install_notice(): void {
		if ( self::is_plugin_installed() ) {
			return;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		if ( self::install_admin_notice_dismissed() ) {
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-woo-updater-not-installed.php';
	}

	/**
	 * Check if the installation notice has been dismissed.
	 *
	 * @return bool
	 */
	protected static function install_admin_notice_dismissed(): bool {
		return get_user_meta( get_current_user_id(), 'dismissed_woo_updater_not_installed_notice', true );
	}
}

WC_Woo_Update_Manager_Plugin::load();
