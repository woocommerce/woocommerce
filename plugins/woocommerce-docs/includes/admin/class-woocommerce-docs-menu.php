<?php
/**
 * WooCommerce Docs Menu
 *
 * Set up UI and menus for the WooCommerce Docs plugin.
 *
 * @class       WooCommerce_Docs_Menu
 * @version     1.0.0
 * @package     WooCommerce_Docs
 */

/**
 * Aclass to set up menus for this WordPress plugin
 */
class WooCommerce_Docs_Menu {

	/**
	 * Define the constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
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
		// Include the admin page template. TODO: add this file.
		include_once plugin_dir_path( __FILE__ ) . 'views/admin.php';
	}
}

