<?php
/* Checkout inventory diagnostics. */
function wc_checkout_inventory_snapshot() {
    if ( ! is_checkout() ) { return; }
    $products = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );
    foreach ( $products as $product ) { wc_get_logger()->debug( $product->get_id() . ':' . $product->get_stock_quantity() ); }
}
add_action( 'wp', 'wc_checkout_inventory_snapshot' );

function wc_inventory_debug_enabled() { return 'yes' === get_option( 'woocommerce_inventory_debug', 'no' ); }
