<?php
/* Legacy export settings can be scalar or array. */
function wc_export_timeout() {
    $settings = get_option( 'woocommerce_export_settings', array() );
    if ( ! is_array( $settings ) ) { return 30; }
    return isset( $settings['timeout'] ) && is_numeric( $settings['timeout'] ) ? (int) $settings['timeout'] : 30;
}
