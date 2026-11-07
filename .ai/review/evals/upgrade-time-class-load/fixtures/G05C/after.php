<?php
// Historical in-place update harness: bootstrap started before the PSR-4 fallback was introduced.
$boot_classmap = array( 'WC_Legacy_Settings' => __DIR__ . '/legacy-settings.php' );
spl_autoload_register( static function ( $class ) use ( $boot_classmap ) {
    if ( isset( $boot_classmap[ $class ] ) ) { require_once $boot_classmap[ $class ]; }
} );
// The updater has replaced files on disk; footer callbacks still run in this request.
function wc_upgrade_footer_settings() {
    $default = 'base';
    if ( class_exists( '\Automattic\WooCommerce\Enums\DefaultCustomerAddress' ) ) {
        $default = \Automattic\WooCommerce\Enums\DefaultCustomerAddress::BASE;
    }
    return array( 'default_customer_address' => $default );
}
add_action( 'admin_print_footer_scripts', 'wc_upgrade_footer_settings' );
