<?php
/**
 * PayPal Ajax Handler Class
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wc-gateway-paypal-request.php';

/**
 * Class WC_Gateway_Paypal_Ajax_Handler.
 */
class WC_Gateway_Paypal_Ajax_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wc_ajax_create_order', [ $this, 'ajax_create_order' ] );
	}

	/**
	 * Create order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_create_order() {
        check_ajax_referer( 'create_order', 'security' );

        $gateway = new WC_Gateway_Paypal();
    
        if ( 'yes' !== $gateway->enabled ) {
            wp_send_json( [ 'error' => 'Gateway disabled.' ] );
        }
    
        // Ensure we have a checkout instance
        if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
            wp_send_json( [ 'error' => 'Checkout not available.' ] );
        }
    
        $checkout = WC()->checkout();
        $data = $checkout->get_posted_data();
        $data['payment_method'] = $gateway->id;

        // Create the order (includes fees, shipping, taxes, coupons)
        $order_id = $checkout->create_order( $data );
        if ( is_wp_error( $order_id ) ) {
            wp_send_json( [ 'error' => 'Could not create order.' ] );
        }
    
        $order = wc_get_order( $order_id );
    
        // Mark as pending and save the same totals Woo just calculated
        $order->set_payment_method( $gateway );
        $order->set_status( 'checkout-draft' );
        $order->save();

        $paypal_request = new WC_Gateway_Paypal_Request( $gateway );

		$paypal_order = $paypal_request->create_paypal_order( $order );
		if ( ! $paypal_order || empty( $paypal_order['id'] ) || empty( $paypal_order['redirect_url'] ) ) {
			wp_send_json( [ 'error' => 'Failed to create PayPalorder.' ] );
		}

        $order->update_meta_data( 'paypal_order_id', $paypal_order['id'] );
        $order->save();

		wp_send_json( [ 'id' => $paypal_order['id'] ?? null, 'order_id' => $order_id ] );
    }
}
