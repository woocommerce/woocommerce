<?php
/**
 * Fake WooCommercePayments account data for testing purposes.
 *
 * @package WC_Beta_Tester
 * */

defined( 'ABSPATH' ) || exit();


register_woocommerce_admin_test_helper_rest_route(
	'/tools/fake-wcpay-completion/v1',
	'tools_get_fake_wcpay_completion_status',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/fake-wcpay-completion/v1',
	'tools_set_fake_wcpay_completion',
	array(
		'methods' => 'POST',
		'args'    => array(
			'enabled' => array(
				'type'        => 'enum',
				'enum'        => array( 'yes', 'no' ),
				'required'    => true,
				'description' => 'Whether to enable or disable fake WooPayments completion',
			),
		),
	)
);

/**
 * Get the current status of fake WooPayments completion.
 */
function tools_get_fake_wcpay_completion_status() {
	return new WP_REST_Response( array( 'enabled' => get_option( 'wc_beta_tester_fake_wcpay_completion', 'no' ) ), 200 );
}

/**
 * A tool to enable/disable fake WooPayments completion.
 *
 * @param WP_REST_Request $request Request object.
 */
function tools_set_fake_wcpay_completion( $request ) {
	$enabled = $request->get_param( 'enabled' );
	update_option( 'wc_beta_tester_fake_wcpay_completion', $enabled );

	return new WP_REST_Response( array( 'enabled' => $enabled ), 200 );
}


if (
	'yes' === get_option( 'wc_beta_tester_fake_wcpay_completion', 'no' ) &&
	class_exists( 'WC_Payment_Gateway_WCPay' )
) {
	add_filter( 'woocommerce_payment_gateways', 'tools_fake_wcpay' );
	add_filter( 'woocommerce_available_payment_gateways', 'tools_fake_wcpay' );

	require_once dirname( __FILE__ ) . '/class-fake-wcpayments.php';

	/**
	 * Fake WooPayments completion.
	 *
	 * @param array $gateways List of available payment gateways.
	 */
	function tools_fake_wcpay( $gateways ) {
		$gateways['woocommerce_payments'] = new Fake_WCPayments();
		return $gateways;
	}
}
