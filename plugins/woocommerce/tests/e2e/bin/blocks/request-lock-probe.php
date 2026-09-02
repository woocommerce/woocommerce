<?php
/**
 * Probes the Blocks E2E request lock, including PHP shutdown ownership.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

if (
	! isset( $GLOBALS['woocommerce_blocks_e2e_database_request_lock_handle'] ) ||
	! is_resource( $GLOBALS['woocommerce_blocks_e2e_database_request_lock_handle'] )
) {
	http_response_code( 500 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo "WooCommerce Blocks E2E database request lock is inactive.\n";
	exit;
}

$mode = isset( $_GET['mode'] ) && is_string( $_GET['mode'] ) ? $_GET['mode'] : 'health';

if ( 'health' === $mode ) {
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo "WooCommerce Blocks E2E database request lock active.\n";
	return;
}

$event_fifo   = isset( $_GET['event_fifo'] ) && is_string( $_GET['event_fifo'] ) ? $_GET['event_fifo'] : '';
$release_fifo = isset( $_GET['release_fifo'] ) && is_string( $_GET['release_fifo'] ) ? $_GET['release_fifo'] : '';
$event_path   = '#^/var/www/html/\.woocommerce-blocks-e2e-quiescence-[a-zA-Z0-9-]+/events$#D';
$release_path = '#^/var/www/html/\.woocommerce-blocks-e2e-quiescence-[a-zA-Z0-9-]+/reader-release$#D';

if (
	'shutdown' !== $mode ||
	1 !== preg_match( $event_path, $event_fifo ) ||
	1 !== preg_match( $release_path, $release_fifo )
) {
	http_response_code( 400 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo "Invalid WooCommerce Blocks E2E request-lock probe.\n";
	exit;
}

register_shutdown_function(
	static function () use ( $event_fifo, $release_fifo ): void {
		$event_handle = @fopen( $event_fifo, 'w' );
		if ( false === $event_handle ) {
			return;
		}

		fwrite( $event_handle, "reader-shutdown-entered\n" );
		fclose( $event_handle );

		$release_handle = @fopen( $release_fifo, 'r' );
		if ( false === $release_handle ) {
			return;
		}

		$release = fgets( $release_handle );
		fclose( $release_handle );

		if ( "release-reader\n" !== $release ) {
			return;
		}

		$event_handle = @fopen( $event_fifo, 'w' );
		if ( false !== $event_handle ) {
			fwrite( $event_handle, "reader-shutdown-leaving\n" );
			fclose( $event_handle );
		}
	}
);

header( 'Content-Type: text/plain; charset=UTF-8' );
echo "WooCommerce Blocks E2E shutdown probe body complete.\n";
