<?php
/**
 * Convenience functions for WC_Admin_Page_Controller.
 *
 * @package Woocommerce Admin
 */

/**
 * Register JS-powered WooCommerce Admin Page.
 * Passthrough to WC_Admin_Page_Controller::register_page().
 *
 * @param array $options Options for WC_Admin_Page_Controller::register_page().
 */
function wc_admin_register_page( $options ) {
	$controller = WC_Admin_Page_Controller::get_instance();
	$controller->register_page( $options );
}

/**
 * Is this a WooCommerce Admin Page?
 * Passthrough to WC_Admin_Page_Controller::is_registered_page().
 *
 * @return boolean True if the page is a WooCommerce Admin page.
 */
function wc_admin_is_registered_page() {
	$controller = WC_Admin_Page_Controller::get_instance();
	return $controller->is_registered_page();
}
