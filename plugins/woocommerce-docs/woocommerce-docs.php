<?php
/**
 * Plugin Name: WooCommerce Docs
 * Plugin URI: https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-docs
 * Description: This plugin ingests WooCommerce documentation from multiple remote sources.
 * Version: 1.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 **/
require __DIR__ . '/vendor/autoload.php';

define( 'WOOCOMMERCE_DOCS_ROOT_URL', plugin_dir_url( __FILE__ ) );
const WOOCOMMERCE_DOCS_PLUGIN_PATH = __DIR__;

\WooCommerceDocs\App\Bootstrap::bootstrap();


