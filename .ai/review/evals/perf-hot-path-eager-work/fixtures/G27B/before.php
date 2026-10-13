<?php
/* Scenario context: wc_inventory_debug_enabled is false by default. Existing diagnostics intentionally run only when a merchant enables the inventory-debug setting. Logger debug calls format and write records. The shop may have arbitrarily many products. */
function wc_checkout_inventory_snapshot() {
    if ( ! is_checkout() || ! wc_inventory_debug_enabled() ) { return; }
    $products = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );
    foreach ( $products as $product ) { wc_get_logger()->debug( $product->get_id() . ':' . $product->get_stock_quantity() ); }
}
add_action( 'wp', 'wc_checkout_inventory_snapshot' );
