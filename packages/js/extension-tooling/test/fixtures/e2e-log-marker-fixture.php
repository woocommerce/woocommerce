<?php
/**
 * Exercise the WordPress log marker without starting a test site.
 *
 * @package WooCommerce\ExtensionTooling
 */

function add_action( $hook, $callback ) {
	if ( 'init' !== $hook ) {
		throw new RuntimeException( 'The marker must run on init.' );
	}
	$GLOBALS['marker_callback'] = $callback;
}

function wp_unslash( $value ) {
	return stripslashes( $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) );
}

define( 'ABSPATH', __DIR__ . '/' );
require __DIR__ . '/../../bin/e2e-log-marker.php';

foreach ( array(
	array(),
	array( 'unrelated' => 'ignore' ),
	array( 'e2e_log_marker' => array( 'ignore' ) ),
	array( 'e2e_log_marker' => 'REQUEST-42!' ),
) as $query ) {
	$_GET = $query;
	$GLOBALS['marker_callback']();
}
