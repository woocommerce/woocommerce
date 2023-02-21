<?php
/**
 * Plugin Name: WooCommerce DevHub
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Description: WooCommerce DevHub
 * Version: 0.0.1
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 5.8
 * Tested up to: 6.0
 * WC requires at least: 6.7
 * WC tested up to: 7.0
 * Text Domain: woocommerce-dev-hub
 *
 * @package WC_DevHub
 */

defined( 'ABSPATH' ) || exit;

// Load static site template for web requests going to "/docs" or "/docs/<..>/..."
function load_static_site_template( $original_template ) {
    $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url_parts = parse_url($url);

    if (isset($url_parts['path']) && strpos($url_parts['path'], '/docs') === 0) {
        return dirname(__FILE__) . '/static_site.php';
    }
    return $template;
}

add_filter( 'template_include', 'load_static_site_template' );
