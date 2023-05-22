<?php
/**
 * Docs_Menu class file.
 *
 * @package  WooCommerce_Docs
 */

namespace WooCommerce_Docs\Admin;

/**
 * A class to set up menus for this WordPress plugin
 */
class Docs_Menu {

	/**
	 * Define the constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Register client-side scripts.
	 */
	public function register_scripts() {
		$script_path       = 'build/wc-docs.js';
		$script_asset_path = WOOCOMMERCE_DOCS_PLUGIN_PATH . '/build/wc-docs.asset.php';
		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_path ),
		);
		$script_url        = WOOCOMMERCE_DOCS_ROOT_URL . $script_path;

		wp_register_script(
			'wc_docs',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_enqueue_script( 'wc_docs' );
	}

	/**
	 * Define the add_admin_menu function
	 */
	public function add_admin_menu() {
		// Add a top-level menu item to the admin menu.
		add_menu_page(
			'WooCommerce Docs',
			'WooCommerce Docs',
			'manage_options',
			'woocommerce-docs',
			array( $this, 'render_admin_page' ),
			'dashicons-media-document',
			6
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Include the admin page template.
		include_once WOOCOMMERCE_DOCS_PLUGIN_PATH . '/includes/views/admin.php';
	}
}

