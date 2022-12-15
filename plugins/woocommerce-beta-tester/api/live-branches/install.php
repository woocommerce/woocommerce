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


/**
 * Respond to POST request to install a plugin by download url.
 *
 * @param Object $request - The request parameter.
 */
function install_version( $request ) {
	$params       = json_decode( $request->get_body() );
	$download_url = $params->download_url;
	$pr_name      = $params->pr_name;

	// error_log( var_dump( $params ) );
	// $download_url = $params['download_url'];
	// $pr_name      = $params['pr_name'];

	$installer = new WC_Beta_Tester_Live_Branches_Installer();
	$result    = $installer->install( $download_url, $pr_name );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 400, "Could not install $pr_name with error {$result->get_error_message()}", '' );
	} else {
		return new WP_REST_Response( wp_json_encode( array( 'ok' => true ) ) );
	}
}
