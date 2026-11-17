<?php
/* Catalog-query callbacks. The catalog_region table has a unique product_id column. */
function wc_catalog_clauses( $clauses ) {
    global $wpdb;
    $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id";
    return $clauses;
}
add_filter( 'posts_clauses', 'wc_catalog_clauses', 10 );
function extension_stock_sort( $clauses ) {
    $clauses['orderby'] = 'wc_product_meta_lookup.stock_quantity DESC';
    return $clauses;
}
add_filter( 'posts_clauses', 'extension_stock_sort', 20 );
function extension_region( $clauses ) {
    global $wpdb;
    $clauses['join'] .= " JOIN {$wpdb->prefix}catalog_region region ON region.product_id={$wpdb->posts}.ID";
    $clauses['where'] .= " AND region.code='EU'";
    return $clauses;
}
add_filter( 'posts_clauses', 'extension_region', 5 );
