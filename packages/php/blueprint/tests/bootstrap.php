<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Starter_Plugin
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

require 'vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
