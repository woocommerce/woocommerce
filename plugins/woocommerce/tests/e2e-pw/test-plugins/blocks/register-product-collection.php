<?php
/**
 * Plugin Name: WooCommerce Blocks Test Register Product Collection
 * Description: Used to tests the registerProductCollection function.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-register-product-collection
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Enqueue the JavaScript file.
function register_product_collections_script()
{
	wp_enqueue_script(
		'rpc_register_product_collections',
		plugins_url('register-product-collection.js', __FILE__),
		array('wp-element', 'wp-blocks', 'wp-i18n', 'wp-components', 'wp-editor', 'wc-blocks', 'wc-blocks-registry'),
		filemtime(plugin_dir_path(__FILE__) . 'register-product-collection.js'),
		true
	);
}

add_action('enqueue_block_editor_assets', 'register_product_collections_script');
