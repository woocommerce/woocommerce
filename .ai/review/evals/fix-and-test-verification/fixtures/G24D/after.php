<?php
/* Scenario context: PR fixes page-two catalog pagination. Route supplies a validated positive integer $page. Standard WP_Query uses paged. Regression verification seeds 24 published products with stable IDs, calls wc_catalog_page(2), and asserts disjoint result IDs from page 1. */
function wc_catalog_page_args( $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 12, 'paged' => max( 1, $paged ) );
}
function wc_catalog_page( $page ) { return new WP_Query( wc_catalog_page_args( $page ) ); }
