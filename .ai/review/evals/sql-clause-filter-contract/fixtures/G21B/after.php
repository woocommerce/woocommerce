<?php
/* Scenario context: Focused source for a product-only query pipeline. Released extension documentation permits posts_clauses callbacks at priority 20 to reference the wc_product_meta_lookup alias installed at priority 10. The shown extension is active. These callbacks run only for this catalog query. An earlier priority-5 extension adds JOIN wp_catalog_region region ON region.product_id=wp_posts.ID and WHERE region.code='EU'. wp_catalog_region has at most one row per product. Those clauses arrive in $clauses. */
function wc_catalog_clauses( $clauses ) {
    global $wpdb;
    $clauses['join'] = " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id";
    return $clauses;
}
add_filter( 'posts_clauses', 'wc_catalog_clauses', 10 );
function extension_stock_sort( $clauses ) {
    $clauses['orderby'] = 'wc_product_meta_lookup.stock_quantity DESC';
    return $clauses;
}
add_filter( 'posts_clauses', 'extension_stock_sort', 20 );
