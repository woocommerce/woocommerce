<?php // @codingStandardsIgnoreLine I don't know why it thinks the doc comment is missing.
/**
 * REST API endpoints for live branches installation.
 *
 * @package WC_Beta_Tester
 */

require_once __DIR__ . '/../../includes/class-wc-beta-tester-live-branches-installer.php';

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/install/v1',
	'install_version',
	array(
		'methods' => 'POST',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/deactivate/v1',
	'deactivate_woocommerce',
	array(
		'methods' => 'GET',
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
	$result    = $installer->activate( $version );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 400, "Could not activate version: $version with error {$result->get_error_message()}", '' );
	} else {
		return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ), 200 );
	}
}

/**
 * Respond to GET request to deactivate WooCommerce.
 */
function deactivate_woocommerce() {
	$installer = new WC_Beta_Tester_Live_Branches_Installer();
	$installer->deactivate_woocommerce();

	return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ), 200 );
}
