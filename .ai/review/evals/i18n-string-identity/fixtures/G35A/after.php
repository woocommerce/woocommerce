<?php
function wc_cross_sells_assets() {
    wp_register_script( 'wc-cross-sells', plugins_url( 'assets/cross-sells.js', __FILE__ ), array( 'wp-i18n' ), '1.0', true );
    wp_set_script_translations( 'wc-cross-sell', 'woocommerce' );
    wp_enqueue_script( 'wc-cross-sells' );
}
add_action( 'wp_enqueue_scripts', 'wc_cross_sells_assets' );
