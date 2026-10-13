<?php
/* Scenario context: Released gateway integration docs list wc-tokenization-form as a frontend dependency. An extension enqueues gateway-ui with dependencies [jquery,wc-tokenization-form] on classic checkout. The wc-tokenization-form script registration shown is its only registration. */
function wc_register_checkout_assets() {
    wp_register_script( 'wc-tokenization-form', plugins_url( 'assets/js/tokenization.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );
