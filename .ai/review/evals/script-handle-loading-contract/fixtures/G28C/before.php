<?php
/* Optional help script is included in this release’s assets. */
function wc_register_checkout_assets() {
    wp_register_script( 'woocommerce-tokenization-form', plugins_url( 'assets/js/frontend/tokenization-form.js', WC_PLUGIN_FILE ), array( 'jquery' ), WC()->version, false );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );

add_action( 'wp_enqueue_scripts', static function() { wp_enqueue_script( 'gateway-ui', plugins_url( 'gateway-ui.js', __FILE__ ), array( 'jquery', 'woocommerce-tokenization-form' ), '1.0', false ); }, 20 );
