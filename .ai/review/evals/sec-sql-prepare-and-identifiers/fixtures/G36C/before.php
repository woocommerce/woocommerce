<?php
/* Scenario context: Public endpoint deliberately adds publication-date sorting. post_date is a real wp_posts column. Exact strict allowlist remains in the same function; all other strings fall back to ID. */
add_action( 'wp_ajax_nopriv_wc_catalog_sort', 'wc_catalog_sort' );
function wc_catalog_sort() {
    global $wpdb;
    $requested = isset( $_GET['orderby'] ) && is_string( $_GET['orderby'] ) ? wp_unslash( $_GET['orderby'] ) : 'ID';
    $orderby = in_array( $requested, array( 'ID', 'post_title' ), true ) ? $requested : 'ID';
    wp_send_json( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY $orderby LIMIT 10" ) );
}
