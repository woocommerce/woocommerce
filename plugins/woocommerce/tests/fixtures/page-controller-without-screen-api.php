<?php
/**
 * Isolated bootstrap fixture for PageController current screen detection.
 *
 * @package Automattic\WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\PageController;

$wp_load_path = $argv[1];

$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';

ob_start();
require $wp_load_path;

if ( ! class_exists( PageController::class ) ) {
	require dirname( __DIR__, 2 ) . '/woocommerce.php';
}

ob_end_clean();

echo wp_json_encode(
	array(
		'get_current_screen_exists' => function_exists( 'get_current_screen' ),
		'screen_id'                 => PageController::get_instance()->get_current_screen_id(),
	)
);
