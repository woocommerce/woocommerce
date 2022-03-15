<?php
use Automattic\WooCommerce\Admin\Features\Features;

const OPTION_NAME_PREFIX = 'wc_admin_helper_feature_values';

register_woocommerce_admin_test_helper_rest_route(
	'/features/(?P<feature_name>[a-z0-9_\-]+)/toggle',
	'toggle_feature',
    array(
		'methods' => 'POST',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/features',
	'get_features',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/features/reset',
	'reset_features',
	array(
		'methods' => 'POST',
	)
);

function toggle_feature( $request ) {
    $features = get_features();
    $custom_feature_values = get_option( OPTION_NAME_PREFIX, array() );
    $feature_name = $request->get_param( 'feature_name' );

    if ( ! isset( $features[$feature_name ]) ) {
        return new WP_REST_Response( $features, 204 ); 
    }
	
    if ( isset( $custom_feature_values[$feature_name] ) ) {
        unset( $custom_feature_values[$feature_name] );
    } else {
        $custom_feature_values[$feature_name] = ! $features[ $feature_name ];
    }

	update_option(OPTION_NAME_PREFIX, $custom_feature_values );
	return new WP_REST_Response( get_features(), 200 );
}

function reset_features() {
    delete_option( OPTION_NAME_PREFIX );
    return new WP_REST_Response( get_features(), 200 );
}

function get_features() {
    if ( function_exists( 'wc_admin_get_feature_config' ) ) {
        return apply_filters( 'woocommerce_admin_get_feature_config', wc_admin_get_feature_config() );
    }
    return array();
}
