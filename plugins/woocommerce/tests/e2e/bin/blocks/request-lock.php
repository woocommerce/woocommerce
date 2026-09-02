<?php
/**
 * Prevents Blocks E2E PHP requests from overlapping database snapshots.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

$woocommerce_blocks_e2e_database_request_lock_handle = @fopen( '/var/www/html/.woocommerce-blocks-e2e-db.lock', 'r+' );

if (
	false === $woocommerce_blocks_e2e_database_request_lock_handle ||
	! flock( $woocommerce_blocks_e2e_database_request_lock_handle, LOCK_SH | LOCK_NB )
) {
	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo "WooCommerce Blocks E2E database snapshot in progress.\n";
	exit;
}

$GLOBALS['woocommerce_blocks_e2e_database_request_lock_handle'] = $woocommerce_blocks_e2e_database_request_lock_handle;
