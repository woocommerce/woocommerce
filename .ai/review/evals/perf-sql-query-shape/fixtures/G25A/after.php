<?php
/* Scenario context: Runs on every product-list render; extensions can increase listing size without a fixed upper bound. IDs are unique product IDs. Returned rows and missing-ID behavior are unchanged. */
function wc_listing_totals( $ids ) {
    global $wpdb;
    $out = array();
    foreach ( array_map( 'absint', $ids ) as $id ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $id ), ARRAY_A );
        if ( $row ) { $out[$row['product_id']] = $row['stock_quantity']; }
    }
    return $out;
}
