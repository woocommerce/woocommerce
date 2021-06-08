<?php
/**
 * Logic for extending WC_REST_Payment_Gateways_Controller.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

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
		add_filter( 'admin_init', array( __CLASS__, 'possibly_do_connection_return_action' ) );
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

		if ( method_exists( $gateway, 'get_connection_url' ) ) {
			$return_url             = wc_admin_url( '&task=payments&connection-return=' . strtolower( $gateway->id ) );
			$data['connection_url'] = $gateway->get_connection_url( $return_url );
		}

		if ( method_exists( $gateway, 'get_setup_help_text' ) ) {
			$data['setup_help_text'] = $gateway->get_setup_help_text();
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

	/**
	 * Call an action after a gating has been successfully returned.
	 */
	public static function possibly_do_connection_return_action() {
		// phpcs:disable WordPress.Security.NonceVerification
		if (
			! isset( $_GET['page'] ) ||
			'wc-admin' !== $_GET['page'] ||
			! isset( $_GET['task'] ) ||
			'payments' !== $_GET['task'] ||
			! isset( $_GET['connection-return'] )
		) {
			return;
		}

		$gateway_id = sanitize_text_field( wp_unslash( $_GET['connection-return'] ) );

		// phpcs:enable WordPress.Security.NonceVerification

		do_action( 'woocommerce_admin_payment_gateway_connection_return', $gateway_id );
	}
}
