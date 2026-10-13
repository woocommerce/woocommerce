<?php
/* Refactor of catalog pagination; route passes integer page numbers. */
function wc_catalog_page_args( $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 12, 'paged' => ( $paged < 1 ? 1 : $paged ) );
}
function wc_catalog_page( $paged ) { return new WP_Query( wc_catalog_page_args( $paged ) ); }
