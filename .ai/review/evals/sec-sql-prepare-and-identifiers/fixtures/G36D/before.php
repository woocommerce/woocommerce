<?php
/* Public product-ID lookup; result order is unspecified. */
add_action( 'wp_ajax_nopriv_wc_catalog_ids', 'wc_catalog_ids' );
function wc_catalog_ids() {
    global $wpdb;
    $ids = isset( $_GET['ids'] ) && is_array( $_GET['ids'] ) ? array_filter( $_GET['ids'], 'is_scalar' ) : array();
    $ids = array_map( 'absint', $ids );
    if ( ! $ids ) { wp_send_json( array() ); return; }
    wp_send_json( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND ID IN (" . implode( ',', $ids ) . ')' ) );
}
