<?php
/* Scenario context: PR claims to repair page-two catalog results. Standard WP_Query consumes paged for pagination. No pre_get_posts callback or custom query-var handler maps catalog_page to paged. Positive integer page input comes from the validated route. */
function wc_catalog_page_args( $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 12, 'catalog_page' => max( 1, $paged ) );
}
function wc_catalog_page( $paged ) { return new WP_Query( wc_catalog_page_args( $paged ) ); }
