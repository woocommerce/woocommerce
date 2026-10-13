<?php
/* Tokenization asset registration and an installed gateway’s frontend dependency. */
function wc_register_checkout_assets() {
    wp_register_script( 'wc-tokenization-ui', plugins_url( 'assets/js/frontend/tokenization-form.js', WC_PLUGIN_FILE ), array( 'jquery' ), WC()->version, false );
    wp_register_script( 'woocommerce-tokenization-form', false, array( 'wc-tokenization-ui' ), WC()->version, false );
    wp_localize_script( 'wc-tokenization-ui', 'wc_tokenization_form_params', array( 'is_registration_required' => WC()->checkout()->is_registration_required(), 'is_logged_in' => is_user_logged_in() ) );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );

add_action( 'wp_enqueue_scripts', static function() { wp_enqueue_script( 'gateway-ui', plugins_url( 'gateway-ui.js', __FILE__ ), array( 'jquery', 'woocommerce-tokenization-form' ), '1.0', false ); }, 20 );
