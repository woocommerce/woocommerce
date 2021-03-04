<?php
function register_woocommerce_admin_test_helper_rest_route( $route, $callback ) {
    add_action( 'rest_api_init', function() use ( $route, $callback ) {
        register_rest_route(
            'wc-admin-test-helper',
            $route,
            array(
                'methods'  => 'POST',
                'callback' => $callback,
                'permission_callback' => function( $request ) {
                    if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
                        return new \WP_Error(
                            'woocommerce_rest_cannot_edit',
                            __( 'Sorry, you cannot perform this action', 'woocommerce-admin-test-helper' )
                        );
                    }
                    return true;
                },
            )
        );
    } );
}

require( 'admin-notes/delete-all-notes.php' );
require( 'admin-notes/add-note.php' );
require( 'tools/trigger-wca-install.php' );
