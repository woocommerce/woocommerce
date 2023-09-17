<?php

/**
 * Plugin Name: Enable or Disable Experimental Features
 * Description: Utility designed for E2E testing purposes. It activates or deactivates experimental features in WooCommerce via REST API.
 */
function register_feature_flag_routes() {
	register_rest_route(
		'e2e-feature-flags',
		'/update',
		array(
			'methods'             => 'POST',
			'callback'            => 'update_feature_flags',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-feature-flags',
		'/reset',
		array(
			'methods'             => 'GET',
			'callback'            => 'reset_feature_flags',
			'permission_callback' => 'is_allowed',
		)
	);
}

add_action( 'rest_api_init', 'register_feature_flag_routes' );

/**
 * Update feature flags
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function update_feature_flags( WP_REST_Request $request ) {
	$features     = get_option( 'e2e_feature_flags', array() );
	$new_features = json_decode( $request->get_body(), true );

	if ( is_array( $new_features ) ) {
		$features = array_merge( $features, $new_features );
		update_option( 'e2e_feature_flags', $features );
		return new WP_REST_Response( 'Feature flags updated', 200 );
	}

	return new WP_REST_Response( 'Invalid request body', 400 );
}

/**
 * Reset feature flags
 * @return WP_REST_Response
 */
function reset_feature_flags() {
	delete_option( 'e2e_feature_flags' );
	return new WP_REST_Response( 'Feature flags reset', 200 );
}

/**
 * Enable experimental features
 * @param array $features Array of features.
 * @return array
 */
function enable_experimental_features( $features ) {
	$stored_features = get_option( 'e2e_feature_flags', array() );

	// We always enable this for tests at the moment.
	$features['product-variation-management'] = true;

	return array_merge( $features, $stored_features );
}

add_filter( 'woocommerce_admin_get_feature_config', 'enable_experimental_features' );

/**
 * Check if user is admin
 * @return bool
 */
function is_allowed() {
	return current_user_can( 'manage_options' );
}
