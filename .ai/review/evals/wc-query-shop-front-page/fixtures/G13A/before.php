<?php
/* Scenario context: The shop is the configured static front page. Existing ID-resolved requests have empty pagename; slug-resolved GET / has page_id=0,pagename=shop. WP_Query retains pagename as a page constraint unless cleared; product query must not retain it. */
function configure_shop_query( $q, $shop_id, $shop_slug ) {
    if ( ! $q->is_main_query() ) { return; }
    if ( $q->get( 'page_id' ) === $shop_id ) {
        $q->set( 'page_id', 0 );
        $q->set( 'post_type', 'product' );
    }
}
