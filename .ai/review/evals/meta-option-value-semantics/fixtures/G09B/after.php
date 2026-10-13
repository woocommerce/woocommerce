<?php
/* Export destination configuration. */
function wc_save_export_settings( $url, $enabled ) {
    update_option( 'woocommerce_export_settings', array( 'enabled' => $enabled ) );
}
function wc_export_destination() {
    $settings = get_option( 'woocommerce_export_settings', array() );
    return $settings['url'] ?? '';
}
