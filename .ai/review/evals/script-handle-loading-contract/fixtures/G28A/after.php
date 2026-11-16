<?php
/* Tokenization asset registration and an installed gateway’s frontend dependency. */
function wc_register_checkout_assets() {
    if ( ! is_admin() ) { return; }
    wp_register_script( 'woocommerce-tokenization-form', plugins_url( 'assets/js/frontend/tokenization-form.js', WC_PLUGIN_FILE ), array( 'jquery' ), WC()->version, false );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );

add_action( 'wp_enqueue_scripts', static function() { wp_enqueue_script( 'gateway-ui', plugins_url( 'gateway-ui.js', __FILE__ ), array( 'jquery', 'woocommerce-tokenization-form' ), '1.0', false ); }, 20 );
