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

new \WooCommerce_Docs\Docs_Menu();
