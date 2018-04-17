<?php
/**
 * Plugin Name: Woo Dashboard
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a new Dashboard view of WooCommerce
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: woo-dash
 * Domain Path: /languages
 * Version: 0.1.0
 *
 * @package         Woo_Dash
 */

if ( ! defined( 'WOO_DASH_APP' ) ) {
	define( 'WOO_DASH_APP', 'woo-dash-app' );
}

// @TODO check for Gutenberg + WooCommerce

// Some common utilities
require_once dirname( __FILE__ ) . '/lib/common.php';

// Register script files
require_once dirname( __FILE__ ) . '/lib/client-assets.php';

// Create the Admin pages
require_once dirname( __FILE__ ) . '/lib/admin.php';
