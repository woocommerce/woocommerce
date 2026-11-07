<?php
/* Public storefront name and description endpoint. */
add_action( 'rest_api_init', static function() {
    register_rest_route( 'wc-eval/v1', '/public-name', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => static function() { return rest_ensure_response( array( 'name' => get_bloginfo( 'name' ) ) ); } ) );
} );
