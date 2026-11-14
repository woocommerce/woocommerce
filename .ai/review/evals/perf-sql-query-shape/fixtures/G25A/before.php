<?php
/* Product-list stock renderer. IDs are unique product IDs; page size can be changed by extensions. */
function wc_listing_totals( $ids ) {
    global $wpdb;
    $ids = array_map( 'absint', $ids );
    if ( ! $ids ) { return array(); }
    $rows = $wpdb->get_results( "SELECT product_id, stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN (" . implode( ',', $ids ) . ')', ARRAY_A );
    return array_column( $rows, 'stock_quantity', 'product_id' );
}
