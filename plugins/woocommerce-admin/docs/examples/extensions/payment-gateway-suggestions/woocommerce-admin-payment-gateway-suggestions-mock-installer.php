<?php
/**
 * This file includes functions that bypass the typical install process for the sake of the example.
 *
 * @package WooCommerce\Admin
 */

/**
 * This is a workaround to bypass intalling the gateway plugins from WP.org.
 * This is not necessary provided your suggestion is a valid WP.org plugin.
 *
 * @param array      $response Response data.
 * @param array      $handler Matched handler.
 * @param WP_Request $request Request data.
 * @return array
 */
function payment_gateway_suggestions_mock_install_activate_response( $response, $handler, $request ) {
	$plugins          = array( 'my-slot-filled-gateway-wporg-slug', 'my-simple-gateway-wporg-slug' );
	$params           = $request->get_params();
	$requested_plugin = isset( $params['plugins'] ) ? $params['plugins'] : null;

	if ( '/wc-admin/plugins/install' === $request->get_route() && in_array( $requested_plugin, $plugins, true ) ) {
		$response['data']    = array(
			'installed' => array( $requested_plugin ),
		);
		$response['errors']  = array(
			'errors' => array(),
		);
		$response['success'] = true;
		$response['message'] = __( 'Plugins were successfully installed.', 'woocommerce-admin' );
	}

	if ( '/wc-admin/plugins/activate' === $request->get_route() && in_array( $requested_plugin, $plugins, true ) ) {
		$response['data']['activated'] = array( $requested_plugin );
		$response['data']['active']    = array_merge( $response['data']['active'], array( $requested_plugin ) );
		$response['errors']            = array(
			'errors' => array(),
		);
		$response['success']           = true;
		$response['message']           = __( 'Plugins were successfully activated.', 'woocommerce-admin' );
	}
	return $response;
}
add_filter( 'rest_request_after_callbacks', 'payment_gateway_suggestions_mock_install_activate_response', 10, 3 );

/**
 * This is a workaround to fake the installation check for the plugins.
 * This is not necessary when a plugin with the matching slug has been installed from WP.org.
 *
 * @param array $active_plugins Active plugins.
 * @return array
 */
function payment_gateway_suggestions_mock_installed_gateway( $active_plugins ) {
	if ( 'yes' === get_option( 'slot_filled_gateway_installed' ) ) {
		$active_plugins[] = 'my-slot-filled-gateway-wporg-slug';
	}

	if ( 'yes' === get_option( 'my-simple-gateway-wporg-slug' ) ) {
		$active_plugins[] = 'my-simple-gateway-wporg-slug';
	}

	return $active_plugins;
}
add_filter( 'option_active_plugins', 'payment_gateway_suggestions_mock_installed_gateway' );
