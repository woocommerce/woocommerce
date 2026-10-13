<?php
/* Scenario context: Synthetic production fixture. New enum BASE value is base. Old in-memory classmap cannot load enum during update. class_exists returns false in that request. */
function wc_upgrade_footer_settings() {
    $default = 'base';
    if ( class_exists( '\Automattic\WooCommerce\Enums\CustomerAddress' ) ) {
        $default = \Automattic\WooCommerce\Enums\CustomerAddress::BASE->value;
    }
    return array( 'default_customer_address' => $default );
}
add_action( 'admin_print_footer_scripts', 'wc_upgrade_footer_settings' );
