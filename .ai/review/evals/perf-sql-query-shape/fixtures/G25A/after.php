<?php
/* Product-list stock renderer. IDs are unique product IDs; page size can be changed by extensions. */
function wc_listing_totals( $ids ) {
    global $wpdb;
    $out = array();
    foreach ( array_map( 'absint', $ids ) as $id ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $id ), ARRAY_A );
        if ( $row ) { $out[$row['product_id']] = $row['stock_quantity']; }
    }
    return $out;
}
