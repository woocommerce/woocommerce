<?php

/**
 * Plugin Name: Register Product Collection Tester
 * Description: A plugin to test the registerProductCollection function from WooCommerce Blocks.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 *
 * @package register-product-collection-tester
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
		plugins_url('register-product-collection-tester/index.js', __FILE__),
		array('wp-element', 'wp-blocks', 'wp-i18n', 'wp-components', 'wp-editor', 'wc-blocks'),
		filemtime(plugin_dir_path(__FILE__) . 'register-product-collection-tester/index.js'),
		true
	);
}

add_action('enqueue_block_editor_assets', 'register_product_collections_script');
