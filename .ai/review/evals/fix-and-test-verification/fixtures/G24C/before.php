<?php
/* Refactor of catalog pagination; route passes integer page numbers. */
function wc_catalog_page_args( int $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 12, 'paged' => max( 1, $paged ) );
}
function wc_catalog_page( int $paged ) { return new WP_Query( wc_catalog_page_args( $paged ) ); }
