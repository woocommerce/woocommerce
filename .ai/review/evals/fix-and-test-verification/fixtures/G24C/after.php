<?php
/* Scenario context: Refactor-only PR with no claimed behavior change. Input $paged is a validated integer. WordPress reads the unchanged paged argument. */
function wc_catalog_page_args( $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 12, 'paged' => ( $paged < 1 ? 1 : $paged ) );
}
function wc_catalog_page( $paged ) { return new WP_Query( wc_catalog_page_args( $paged ) ); }
