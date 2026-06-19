<?php
/**
 * Registration of the `woocommerce/page-content-wrapper` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Registers the `woocommerce/page-content-wrapper` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_page_content_wrapper(): void {
	register_block_type_from_metadata( __DIR__ );
}

add_action( 'init', 'register_block_woocommerce_page_content_wrapper' );
