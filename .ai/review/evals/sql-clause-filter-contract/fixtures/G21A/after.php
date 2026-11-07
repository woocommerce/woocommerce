<?php
/* Catalog-query callbacks; extensions can register posts_clauses callbacks. */
function wc_catalog_clauses( $clauses ) {
    global $wpdb;
    $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup lookup ON {$wpdb->posts}.ID = lookup.product_id";
    return $clauses;
}
add_filter( 'posts_clauses', 'wc_catalog_clauses', 10 );
function extension_stock_sort( $clauses ) {
    $clauses['orderby'] = 'wc_product_meta_lookup.stock_quantity DESC';
    return $clauses;
}
add_filter( 'posts_clauses', 'extension_stock_sort', 20 );
