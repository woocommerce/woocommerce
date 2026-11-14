<?php
/* Public catalog count AJAX endpoint. */
add_action( 'wp_ajax_nopriv_wc_catalog_count', 'wc_catalog_count' );
function wc_catalog_count() {
    global $wpdb;
    $slug = isset( $_GET['slug'] ) && is_string( $_GET['slug'] ) ? wp_unslash( $_GET['slug'] ) : '';
    $sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_name=%s", $slug );
    wp_send_json( (int) $wpdb->get_var( $sql ) );
}
