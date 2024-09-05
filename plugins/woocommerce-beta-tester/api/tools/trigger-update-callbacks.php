<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-update-versions/v1',
	'tools_get_wc_admin_versions',
	array(
		'methods' => 'GET',
	)
);
register_woocommerce_admin_test_helper_rest_route(
	'/tools/trigger-selected-update-callbacks/v1',
	'trigger_selected_update_callbacks',
	array(
		'methods' => 'POST',
		'args'    => array(
			'version' => array(
				'description'       => 'Name of the update version',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

/**
 * A tool to get the list of WooCommerce Admin update versions.
 */
function tools_get_wc_admin_versions() {
	$db_updates = \WC_Install::get_db_update_callbacks();

	return new WP_REST_Response( array_keys( $db_updates ), 200 );
}

/**
 * Triggers the selected version update callback.
 *
 * @param WP_REST_Request $request The full request data.
 */
function trigger_selected_update_callbacks( $request ) {
	$version = $request->get_param( 'version' );
	if ( ! isset( $version ) ) {
		return;
	}

	$db_updates       = \WC_Install::get_db_update_callbacks();
	$update_callbacks = $db_updates[ $version ];

	foreach ( $update_callbacks as $update_callback ) {
		\WC_Install::run_update_callback( $update_callback );
	}

	return false;
}
