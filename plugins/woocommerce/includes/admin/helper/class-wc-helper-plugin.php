<?php
/**
 * Woo Marketplace plugin manager.
 *
 * @class WC_Helper_Updater
 * @package WooCommerce\Admin\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin Class
 *
 * Contains the logic to manage the Woo Marketplace plugin.
 */
class WC_Helper_Plugin {
	const WOO_MARKETPLACE_PLUGIN_MAIN_FILE = 'woo-marketplace/woo-marketplace.php';

	/**
	 * Check if the marketplace plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		return is_plugin_active_for_network( self::WOO_MARKETPLACE_PLUGIN_MAIN_FILE ) || is_plugin_active( self::WOO_MARKETPLACE_PLUGIN_MAIN_FILE );
	}

	/**
	 * Check if the marketplace plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed() {
		return file_exists( WP_PLUGIN_DIR . '/' . self::WOO_MARKETPLACE_PLUGIN_MAIN_FILE );
	}
}
