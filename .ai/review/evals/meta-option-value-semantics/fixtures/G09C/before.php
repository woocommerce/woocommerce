<?php
/* Scenario context: Synthetic production fixture. Released consumers read named url and enabled keys; new schema_version is additive. No exact-array comparison consumers in this fixture contract. */
function wc_save_export_settings( $url, $enabled ) {
    update_option( 'woocommerce_export_settings', array( 'url' => $url, 'enabled' => $enabled ) );
}
