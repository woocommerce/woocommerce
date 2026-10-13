<?php
/* Scenario context: Runs on every product-list render; extensions can increase listing size without a fixed upper bound. IDs are unique product IDs. Returned rows and missing-ID behavior are unchanged. */
function wc_listing_totals( $ids ) {
    global $wpdb;
    $ids = array_map( 'absint', $ids );
    if ( ! $ids ) { return array(); }
    $rows = $wpdb->get_results( "SELECT product_id, stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN (" . implode( ',', $ids ) . ')', ARRAY_A );
    return array_column( $rows, 'stock_quantity', 'product_id' );
}
