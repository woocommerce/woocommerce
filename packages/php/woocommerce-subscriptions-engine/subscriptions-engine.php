<?php
/**
 * This file is part of the WooCommerce Subscriptions Engine package.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

/**
 * Plugin Name: WooCommerce Subscriptions Engine
 * Plugin URI: https://woocommerce.com/
 * Description: An empty subscriptions-engine definition file to set up the wp-env test environment.
 * Version: 0.0.1
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Requires at least: 6.7
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

$autoload_entry_point = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload_entry_point ) ) {
	require_once $autoload_entry_point;
}
// When the package is distributed as part of WooCommerce core, it will provide autoloading of necessary dependencies.

if ( class_exists( \Automattic\WooCommerce\SubscriptionsEngine\Package::class ) ) {
	\Automattic\WooCommerce\SubscriptionsEngine\Package::init();
}
