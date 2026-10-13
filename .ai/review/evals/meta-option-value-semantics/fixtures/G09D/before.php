<?php
/* Scenario context: Synthetic production fixture. Legacy imports can store false,string or array; timeout contract is numeric value or default30. Function shown is complete. */
function wc_export_timeout() {
    $settings = get_option( 'woocommerce_export_settings', array() );
    if ( ! is_array( $settings ) ) { return 30; }
    return isset( $settings['timeout'] ) && is_numeric( $settings['timeout'] ) ? (int) $settings['timeout'] : 30;
}
