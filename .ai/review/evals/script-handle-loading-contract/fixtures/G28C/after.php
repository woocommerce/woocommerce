<?php
/* Scenario context: Existing wc-tokenization-form public contract stays unchanged. The new tokenization-help.js release asset exists and is an optional internal helper. Nothing enqueues it yet. */
function wc_register_checkout_assets() {
    wp_register_script( 'wc-tokenization-form', plugins_url( 'assets/js/tokenization.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
    wp_register_script( 'wc-tokenization-help', plugins_url( 'assets/js/tokenization-help.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );
