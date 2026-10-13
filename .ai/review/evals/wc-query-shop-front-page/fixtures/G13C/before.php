<?php
/* Scenario context: Shop is configured front page; shop_id is positive integer and shop_slug is nonempty. q uses integer page_id and string pagename. ID-resolved path has empty pagename; slug path must clear both page constraints. */
function configure_shop_query( $q, $shop_id, $shop_slug ) {
    if ( ! $q->is_main_query() ) { return; }
    if ( $q->get( 'page_id' ) === $shop_id ) {
        $q->set( 'page_id', 0 );
        $q->set( 'post_type', 'product' );
    }
}
