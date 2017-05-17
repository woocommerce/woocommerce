<?php
/**
 * Handles the product CSV importer UI in admin.
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Product_Import Class.
 */
class WC_Admin_Product_Import {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'admin_menu_hide' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=product', __( 'Product Import', 'woocommerce' ), __( 'Import', 'woocommerce' ), 'edit_products', 'product_importer', array( $this, 'admin_screen' ) );
	}

	/**
	 * Hide menu item from view.
	 */
	public function admin_menu_hide() {
		global $submenu;

		if ( isset( $submenu['edit.php?post_type=product'] ) ) {
			foreach ( $submenu['edit.php?post_type=product'] as $key => $menu ) {
				if ( 'product_importer' === $menu[2] ) {
					unset( $submenu['edit.php?post_type=product'][ $key ] );
				}
			}
		}
	}

	/**
	 * Import page UI.
	 */
	public function admin_screen() {

	}
}

new WC_Admin_Product_Import();
