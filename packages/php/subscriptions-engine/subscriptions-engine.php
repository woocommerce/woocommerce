<?php
/**
 * This file is part of the WooCommerce Subscriptions Engine package.
 *
 * @package WooCommerce\Subscriptions\Engine
 */

/**
 * Plugin Name: Subscriptions Engine
 * Plugin URI: https://woocommerce.com/
 * Description: An empty subscriptions-engine definition file to set up the wp-env test environment.
 * Version: 0.0.1
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Requires at least: 6.7
 * Requires PHP: 7.4
 */

$autoload_entry_point = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload_entry_point ) ) {
	require_once $autoload_entry_point;
}
// When the package is distributed as part of WooCommerce core, it will provide autoloading of necessary dependencies.

if ( class_exists( \WooCommerce\Subscriptions\Engine\Package::class ) ) {
	\WooCommerce\Subscriptions\Engine\Package::init();
}
