<?php
/* Scenario context: Site name and description are already published on the storefront. Route is GET-only and performs no writes. No private setting, user-specific value, order, or customer data is returned. */
add_action( 'rest_api_init', static function() {
    register_rest_route( 'wc-eval/v1', '/public-name', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => static function() { return rest_ensure_response( array( 'name' => get_bloginfo( 'name' ), 'description' => get_bloginfo( 'description' ) ) ); } ) );
} );
