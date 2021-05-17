<?php
/**
 * Logic for extending WC_REST_Payment_Gateways_Controller.
 */

namespace Automattic\WooCommerce\Admin\Features\RemotePaymentMethods;

defined( 'ABSPATH' ) || exit;

/**
 * PaymentGateway class
 */
class PaymentGatewaysController {

	/**
	 * Initialize payment gateway changes.
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_prepare_payment_gateway', array( __CLASS__, 'extend_response' ), 10, 3 );
	}

	/**
	 * Add necessary fields to REST API response.
	 *
	 * @param  WP_REST_Response   $response   Response data.
	 * @param  WC_Payment_Gateway $gateway    Payment gateway object.
	 * @param  WP_REST_Request    $request    Request object.
	 * @return WP_REST_Response
	 */
	public static function extend_response( $response, $gateway, $request ) {
		$data = $response->get_data();

		$data['needs_setup']          = $gateway->needs_setup();
		$data['post_install_scripts'] = self::get_post_install_scripts( $gateway );
		$data['settings_url']         = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) );

		if ( method_exists( $gateway, 'get_oauth_connection_url' ) ) {
			$data['oauth_connection_url'] = $gateway->get_oauth_connection_url();
		}

		if ( method_exists( $gateway, 'get_required_settings_keys' ) ) {
			$data['required_settings_keys'] = $gateway->get_required_settings_keys();
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Get payment gateway scripts for post-install.
	 *
	 * @param  WC_Payment_Gateway $gateway Payment gateway object.
	 * @return array Install scripts.
	 */
	public static function get_post_install_scripts( $gateway ) {
		$scripts    = array();
		$wp_scripts = wp_scripts();

		$handles = method_exists( $gateway, 'get_post_install_script_handles' )
			? $gateway->get_post_install_script_handles()
			: array();

		foreach ( $handles as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$scripts[] = $wp_scripts->registered[ $handle ];
			}
		}

		return $scripts;
	}
}
