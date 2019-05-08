<?php
/**
 * Convenience functions for WC_Admin_Page_Controller.
 *
 * @package Woocommerce Admin
 */

/**
 * Connect an existing page to WooCommerce Admin.
 * Passthrough to WC_Admin_Page_Controller::connect_page().
 *
 * @param array $options Options for WC_Admin_Page_Controller::connect_page().
 */
function wc_admin_connect_page( $options ) {
	$controller = WC_Admin_Page_Controller::get_instance();
	$controller->connect_page( $options );
}

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
 * Is this page connected to WooCommerce Admin?
 * Passthrough to WC_Admin_Page_Controller::is_connected_page().
 *
 * @return boolean True if the page is connected to WooCommerce Admin.
 */
function wc_admin_is_connected_page() {
	$controller = WC_Admin_Page_Controller::get_instance();
	return $controller->is_connected_page();
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

/**
 * Get breadcrumbs for WooCommerce Admin Page navigation.
 * Passthrough to WC_Admin_Page_Controller::get_breadcrumbs().
 *
 * @return array Navigation pieces (breadcrumbs).
 */
function wc_admin_get_breadcrumbs() {
	$controller = WC_Admin_Page_Controller::get_instance();
	return $controller->get_breadcrumbs();
}
