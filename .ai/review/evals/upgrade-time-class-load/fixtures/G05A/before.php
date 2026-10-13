<?php
/* Scenario context: Synthetic production fixture. This legacy includes file is first included from its new on-disk contents during the in-place update request before it ends. CustomerAddress enum is introduced by this release; old in-memory classmap lacks it and has no PSR-4 fallback. Fresh requests map it. */
function wc_upgrade_footer_settings() {
    return array( 'default_customer_address' => 'base' );
}
add_action( 'admin_print_footer_scripts', 'wc_upgrade_footer_settings' );
