<?php
/* Scenario context: Released extensions use wc-tokenization-form as dependency only. The real script creates the same globals as before. No inline scripts or localized data are attached to either handle; the script exposes its own globals without localization. Script footer loading remains unchanged. */
function wc_register_checkout_assets() {
    wp_register_script( 'wc-tokenization-ui', plugins_url( 'assets/js/tokenization.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
    wp_register_script( 'wc-tokenization-form', false, array( 'wc-tokenization-ui' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );
