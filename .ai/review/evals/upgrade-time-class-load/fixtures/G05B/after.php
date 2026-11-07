<?php
// Historical in-place update harness: bootstrap started before the PSR-4 fallback was introduced.
$boot_classmap = array( 'WC_Legacy_Settings' => __DIR__ . '/legacy-settings.php' );
spl_autoload_register( static function ( $class ) use ( $boot_classmap ) {
    if ( isset( $boot_classmap[ $class ] ) ) { require_once $boot_classmap[ $class ]; }
} );
// The updater has replaced files on disk; footer callbacks still run in this request.
function wc_register_export_routes() {
    $controller = new WC_New_Export_Controller();
    $controller->register_routes();
}
add_action( 'rest_api_init', 'wc_register_export_routes' );
add_action( 'admin_print_footer_scripts', static function () { rest_get_server(); } );
