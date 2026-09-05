<?php
/**
 * Reports whether the Blocks E2E request lock is active for this request.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

header( 'Content-Type: text/plain; charset=UTF-8' );

if (
	! isset( $GLOBALS['woocommerce_blocks_e2e_database_request_lock_handle'] ) ||
	! is_resource( $GLOBALS['woocommerce_blocks_e2e_database_request_lock_handle'] )
) {
	http_response_code( 500 );
	echo "WooCommerce Blocks E2E database request lock is inactive.\n";
	exit;
}

echo "WooCommerce Blocks E2E database request lock active.\n";
