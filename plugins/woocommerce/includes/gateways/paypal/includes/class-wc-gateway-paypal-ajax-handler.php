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
        add_action( 'wc_ajax_capture_order', [ $this, 'ajax_capture_order' ] );
	}

	/**
	 * Create order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_create_order() {
        check_ajax_referer( 'create_order', 'security' );

        $gateway = WC_Gateway_Paypal::get_instance();
    
        if ( 'yes' !== $gateway->enabled ) {
            wp_send_json( [ 'error' => 'Gateway disabled.' ] );
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

        if ( $order->get_meta( '_paypal_order_id' ) ) {
            wp_send_json( [ 'paypal_order_id' => $order->get_meta( '_paypal_order_id' ) ] );
        }

        // Set the order as awaiting payment in the current session.
        WC()->session->set( 'order_awaiting_payment', $order_id );
    
        // Mark as pending and save the same totals Woo just calculated
        $order->set_payment_method( $gateway );
        $order->set_cart_hash( WC()->cart->get_cart_hash() );
        $order->save();

        $paypal_request = new WC_Gateway_Paypal_Request( $gateway );
		$paypal_order   = $paypal_request->create_paypal_order( $order );

        if ( ! $paypal_order || empty( $paypal_order['id'] ) || empty( $paypal_order['redirect_url'] ) ) {
			wp_send_json( [ 'error' => 'Failed to create PayPalorder.' ] );
		}

        $order->update_meta_data( '_paypal_order_id', $paypal_order['id'] );
        $order->save();

		wp_send_json( [ 'paypal_order_id' => $paypal_order['id'] ?? null, 'order_id' => $order_id ] );
    }

    public function ajax_capture_order() {
        wc_get_logger()->debug( 'capture_order ajax-----' );
        check_ajax_referer( 'capture_order', 'security' );
        $paypal_order_id = $_POST['order_id'];
    
        $order = $this->find_woocommerce_order_by_paypal_order_id( $paypal_order_id );
       
        if ( ! $order ) {
            wp_send_json( [ 'error' => [ __( 'Order not found.', 'woocommerce' ) ] ] );
        }
    
        $paypal_request = new WC_Gateway_Paypal_Request( $this );
        $response = $paypal_request->capture_payment_for_order( $order, $paypal_order_id );
    
        $response['return_url'] = esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
    
        wp_send_json( $response );
    }
    
    /**
     * Find WooCommerce order by PayPal order ID.
     * 
     * @param string $paypal_order_id The PayPal order ID.
     * @return WC_Order|null The WooCommerce order object if found, otherwise null.
     */
    private function find_woocommerce_order_by_paypal_order_id( $paypal_order_id ) {
        $orders = wc_get_orders([
            'meta_key' => 'paypal_order_id',
            'meta_value' => $paypal_order_id,
            'limit' => 1,
            'return' => 'ids'
        ]);
        
        if ( ! empty( $orders ) ) {
            return wc_get_order( $orders[0] );
        }
        
        return null;
    }
}
