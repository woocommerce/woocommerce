<?php
/**
 * Bootstrap.
 *
 * @package automattic/woocommerce-analytics
 */

/**
 * Include the composer autoloader.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

// Include WooCommerce mocks before initializing test environment.
require_once __DIR__ . '/mocks/woocommerce-functions.php';
require_once __DIR__ . '/mocks/class-wc-order.php';
require_once __DIR__ . '/mocks/class-wc-tracks.php';

// A @runInSeparateProcess test runs from stdin with an empty SCRIPT_FILENAME, so wp_guess_url() warns on PHP 7.4.
if ( empty( $_SERVER['SCRIPT_FILENAME'] ) ) {
	$_SERVER['SCRIPT_FILENAME'] = __FILE__;
}

// Initialize WordPress test environment using WorDBless (database-less).
\WorDBless\Load::load();

// Depends on WP_REST_Controller, so it must come after WorDBless has loaded core.
require_once __DIR__ . '/mocks/class-wc-rest-controller.php';
