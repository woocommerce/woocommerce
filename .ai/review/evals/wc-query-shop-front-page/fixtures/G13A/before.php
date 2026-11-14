<?php
/* Reduced pre_get_posts callback for a site with the shop as its static front page. */
function configure_shop_query( $q, $shop_id, $shop_slug ) {
    if ( ! $q->is_main_query() || $q->get( 'page_builder_owns_shop' ) ) { return; }
    if ( $q->get( 'page_id' ) === $shop_id ) {
        $q->set( 'page_id', 0 );
        $q->set( 'post_type', 'product' );
        $q->is_singular = false;
        $q->is_post_type_archive = true;
        $q->is_archive = true;
    }
}

function on_shop_front_query( $q ) {
    if ( 'page' !== get_option( 'show_on_front' ) ) { return; }
    $shop_id = (int) wc_get_page_id( 'shop' );
    if ( $shop_id <= 0 || $shop_id !== (int) get_option( 'page_on_front' ) ) { return; }
    configure_shop_query( $q, $shop_id, (string) get_post_field( 'post_name', $shop_id ) );
}
add_action( 'pre_get_posts', 'on_shop_front_query' );

function mark_page_builder_query( $q ) {
    if ( $q->is_main_query() && 'yes' === get_option( 'page_builder_shop_enabled', 'no' ) ) {
        $q->set( 'page_builder_owns_shop', true );
    }
}
add_action( 'pre_get_posts', 'mark_page_builder_query', 5 );
