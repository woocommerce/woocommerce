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

// Initialize WordPress test environment using WorDBless (database-less).
\WorDBless\Load::load();
