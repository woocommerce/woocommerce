<?php
/* Scenario context: Synthetic production fixture. Released option stores url and enabled; extensions and shown reader consume url. Admin save passes validated destination URL. */
function wc_save_export_settings( $url, $enabled ) {
    update_option( 'woocommerce_export_settings', array( 'enabled' => $enabled ) );
}
function wc_export_destination() {
    $settings = get_option( 'woocommerce_export_settings', array() );
    return $settings['url'] ?? '';
}
