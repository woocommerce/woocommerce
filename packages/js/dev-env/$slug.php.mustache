<?php
/**
 * Plugin Name: Woo Plugin Setup
 * Plugin URI: https://github.com/psealock/woo-plugin-setup
 * Description: This is an example of an extension template to follow.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: woo-plugin-setup
 * Domain Path: /languages
 * Tested up to: 6.1
 * WC tested up to: 7.1
 * WC requires at least: 5.8
 *
 * Copyright: © 2022 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WOO_PLUGIN_SETUP_PLUGIN_FILE' ) ) {
	define( 'WOO_PLUGIN_SETUP_PLUGIN_FILE', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use WooPluginSetup\Admin\Setup;

/**
 * Woo_Plugin_Setup class.
 */
class Woo_Plugin_Setup {
	/**
	 * This class instance.
	 *
	 * @var \Woo_Plugin_Setup single instance of this class.
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init the plugin once WP is loaded.
	 */
	public function init() {
		// If WooCommerce does not exist, deactivate plugin.
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

		if ( is_admin() ) {
			// Load plugin translations.
			$plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages'; /* Relative to WP_PLUGIN_DIR */
			load_plugin_textdomain( 'woo-plugin-setup', false, $plugin_rel_path );

			new Setup();
		}
	}

	/**
	 * Gets the main instance.
	 *
	 * Ensures only one instance can be loaded.
	 *
	 * @return \Woo_Plugin_Setup
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Woo_Plugin_Setup::instance();
