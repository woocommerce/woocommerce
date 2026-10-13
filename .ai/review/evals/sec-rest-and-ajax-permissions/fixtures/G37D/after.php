<?php
/* Scenario context: The admin UI sends _ajax_nonce for action wc-reset-store-label; legacy clients may send _wpnonce instead. Source precedence remains _ajax_nonce before _wpnonce. Both capability and nonce failure branches terminate before the mutation. Request method is unchanged; a valid nonce proves intent. */
add_action( 'wp_ajax_wc_reset_store_label', 'wc_reset_store_label' );
function wc_reset_store_label() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( array(), 403 ); return; }
    $nonce_result = check_ajax_referer( 'wc-reset-store-label', false, false );
    if ( false === $nonce_result ) { wp_die( -1, 403 ); return; }
    update_option( 'wc_store_label', '' );
    wp_send_json_success();
}
