<?php
/* Scenario context: Synthetic production fixture. WC_New_Export_Controller is new in this release. During update a footer integration runs rest_api_init with old classmap still active; controller cannot autoload then. */
function wc_register_export_routes() {
    if ( class_exists( 'WC_New_Export_Controller' ) ) {
        $controller = new WC_New_Export_Controller();
        $controller->register_routes();
    }
}
add_action( 'rest_api_init', 'wc_register_export_routes' );
