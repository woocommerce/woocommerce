<?php
/* Scenario context: WordPress REST route uses ordinary authentication; anonymous requests reach permission_callback. The label is a store-wide option editable only by manage_woocommerce users. No second permission check exists in callback. */
add_action( 'rest_api_init', static function() {
    register_rest_route( 'wc-eval/v1', '/store-label', array(
        'methods' => 'POST',
        'permission_callback' => static function() { return current_user_can( 'manage_woocommerce' ); },
        'callback' => static function( $request ) { $label = $request->get_param( 'label' ); if ( ! is_string( $label ) ) { return new WP_Error( 'invalid_label', 'Invalid label', array( 'status' => 400 ) ); } update_option( 'wc_store_label', sanitize_text_field( $label ) ); return rest_ensure_response( array( 'saved' => true ) ); },
    ) );
} );
