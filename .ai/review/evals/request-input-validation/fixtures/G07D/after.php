<?php
/* REST search endpoint. */
function wc_export_search_rest( $request ) {
    $term = $request->get_param( 'search' );
    return urldecode( $term );
}
add_action( 'rest_api_init', function () {
    register_rest_route( 'wc-export/v1', '/search', array(
        'methods' => 'GET', 'callback' => 'wc_export_search_rest',
        'permission_callback' => function () { return current_user_can( 'manage_woocommerce' ); },
        'args' => array( 'search' => array( 'type' => 'string', 'required' => true ) ),
    ) );
} );
