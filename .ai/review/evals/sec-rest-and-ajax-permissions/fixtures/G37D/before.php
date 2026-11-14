<?php
/* Admin clients submit _ajax_nonce; legacy clients may submit _wpnonce. */
add_action( 'wp_ajax_wc_reset_store_label', 'wc_reset_store_label' );
function wc_reset_store_label() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( array(), 403 ); return; }
    check_ajax_referer( 'wc-reset-store-label' );
    update_option( 'wc_store_label', '' );
    wp_send_json_success();
}
