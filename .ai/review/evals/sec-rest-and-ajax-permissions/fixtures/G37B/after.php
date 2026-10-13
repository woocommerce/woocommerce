<?php
/* Scenario context: Logged-in store managers can reset the store label. Their session cookies accompany a top-level navigation to this same-site AJAX URL. Attacker can induce a manager to follow that URL without knowing any nonce. Capability check remains, and wp_verify_nonce returns false rather than aborting. */
add_action( 'wp_ajax_wc_reset_store_label', 'wc_reset_store_label' );
function wc_reset_store_label() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( array(), 403 ); return; }
    $nonce = isset( $_REQUEST['_ajax_nonce'] ) && is_string( $_REQUEST['_ajax_nonce'] ) ? $_REQUEST['_ajax_nonce'] : '';
    wp_verify_nonce( $nonce, 'wc-reset-store-label' );
    update_option( 'wc_store_label', '' );
    wp_send_json_success();
}
