<?php
/* Scenario context: Anonymous download endpoint authorizes solely by a secret opaque token string; successful response contains a protected downloadable URL. Legacy token generator permits hexadecimal strings such as 0e830400451993494058024219903391. That fixture token is a PHP numeric-string zero; attacker does not know it and tries another zero-valued numeric string. Token provisioning is outside this snippet and already complete. */
add_action( 'wp_ajax_nopriv_wc_download_token', 'wc_download_token' );
function wc_download_token() {
    $token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
    $stored = get_option( 'wc_download_token', '' );
    if ( ! is_string( $stored ) || '' === $stored || ! hash_equals( $stored, $token ) ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'download_url' => wc_private_download_url() ) );
}
