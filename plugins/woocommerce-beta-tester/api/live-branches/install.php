<?php
/**
 * Register REST endpoint for installing live branches manifest.
 *
 * @package WC_Beta_Tester
 */

require __DIR__ . '/../../includes/class-wc-beta-tester-live-branches-installer.php';

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/install/v1',
	'install_version',
	array(
		'methods' => 'POST',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/deactivate_core/v1',
	'deactivate_core',
	array(
		'methods' => 'GET',
		// 'permission_callback' => function( $request ) {
		// Avoid using WC functions as core will be deactivated during this request.
		// $user = wp_get_current_user();
		// $allowed_roles = array( 'administrator' );
		// if ( array_intersect( $allowed_roles, $user->roles ) ) {
		// return true;
		// } else {
		// return new \WP_Error(
		// 'woocommerce_rest_cannot_edit',
		// __( 'Sorry, you cannot perform this action', 'woocommerce' )
		// );
		// }
		// },
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/activate/v1',
	'activate_version',
	array(
		'methods'             => 'POST',
		'permission_callback' => function( $request ) {
			// Avoid using WC functions as core will be deactivated during this request.
			$user = wp_get_current_user();
			$allowed_roles = array( 'administrator' );
			if ( array_intersect( $allowed_roles, $user->roles ) ) {
				return true;
			} else {
				return new \WP_Error(
					'woocommerce_rest_cannot_edit',
					__( 'Sorry, you cannot perform this action', 'woocommerce' )
				);
			}
		},
	)
);



/**
 * Respond to POST request to install a plugin by download url.
 *
 * @param Object $request - The request parameter.
 */
function install_version( $request ) {
	$params       = json_decode( $request->get_body() );
	$download_url = $params->download_url;
	$pr_name      = $params->pr_name;
	$version      = $params->version;

	$installer = new WC_Beta_Tester_Live_Branches_Installer();
	$result    = $installer->install( $download_url, $pr_name, $version );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 400, "Could not install $pr_name with error {$result->get_error_message()}", '' );
	} else {
		return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ), 200 );
	}
}

/**
 * Respond to POST request to activate a plugin by version.
 *
 * @param Object $request - The request parameter.
 */
function activate_version( $request ) {
	$params  = json_decode( $request->get_body() );
	$version = $params->version;

	$installer = new WC_Beta_Tester_Live_Branches_Installer();
	// $installer->deactivate();

	// add_action(
	// 'update_option_active_plugins',
	// function() use ( $installer, $version ) {
	// $installer->activate( $version );
	// },
	// 10,
	// 2
	// );

	$installer->activate( $version );

	// $result =

	// if ( is_wp_error( $result ) ) {
	// return new WP_Error( 400, "Could not activate version: $version with error {$result->get_error_message()}", '' );
	// } else {
		return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ), 200 );
	// }
}

function deactivate_core() {
	$installer = new WC_Beta_Tester_Live_Branches_Installer();
	$installer->deactivate();

	return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ), 200 );
}
