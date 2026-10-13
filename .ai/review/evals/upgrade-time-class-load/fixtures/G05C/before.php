<?php
/* Scenario context: Synthetic production fixture. New enum BASE value is base. Old in-memory classmap cannot load enum during update. class_exists returns false in that request. */
function wc_upgrade_footer_settings() {
    return array( 'default_customer_address' => 'base' );
}
add_action( 'admin_print_footer_scripts', 'wc_upgrade_footer_settings' );
