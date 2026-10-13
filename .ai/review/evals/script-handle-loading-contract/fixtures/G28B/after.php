<?php
/* Scenario context: Released gateway integration docs use wc-tokenization-form as a script dependency. Registration shown is the only registration. Rename is presented as internal cleanup and has no deprecation or compatibility alias; extensions keep the old handle. */
function wc_register_checkout_assets() {
    wp_register_script( 'wc-tokenization-ui', plugins_url( 'assets/js/tokenization.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'wc_register_checkout_assets' );
