<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once dirname( __FILE__ ) . '/class-wc-gateway-paypal-request.php';

class WC_Gateway_Paypal_Webhook_Handler {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wc-paypal-gateway/v1', '/webhook', [
            'methods'             => 'POST',
            'callback'            => array( $this, 'process_webhook' ),
            'permission_callback' => array( $this, 'get_permission' ),
        ] );

        // TODO: Test only. Remove before merging feature.
        register_rest_route( 'wc-paypal-gateway/v1', '/test-webhook', [
            'methods'             => 'GET',
            'callback'            => array( $this, 'test_webhook' ),
            'permission_callback' => '__return_true',
        ] );
    }

    private function get_permission() {
        // TODO: Should we check if the webhook is coming from wpcom?
        return true;
    }

    public function test_webhook( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        error_log( 'PayPal test webhook received: ' . print_r( $data, true ) );
        return new WP_REST_Response( 'Test webhook processed', 200 );
    }

    public function process_webhook( WP_REST_Request $request ) {
        // TODO: Validate the webhook signature

        $data = $request->get_json_params();
        WC_Gateway_Paypal::log( 'Webhook received: ' . wc_print_r( $data, true ) );

        switch ( $data['event_type'] ) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->process_checkout_order_approved( $data );
                break;
            default:
                WC_Gateway_Paypal::log( 'Unhandled PayPal webhook event: ' . wc_print_r( $data, true ) );
                break;
        }
    }

    private function process_checkout_order_approved( $data ) {
        $custom_id = $data['resource']['purchase_units'][0]['custom_id'];
        $order = $this->get_wc_order( $custom_id );
        if ( ! $order ) {
            WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
            return;
        }

        $status = $data['resource']['status'] ?? null;
        if ( $status === 'APPROVED' ) {
            WC_Gateway_Paypal::log( 'PayPal order approved. Order ID: ' . $order->get_id() );

            // TODO: Capture the payment.
        } else {
            WC_Gateway_Paypal::log( 'PayPal order not approved. Order ID: ' . $order->get_id() . ' Status: ' . $status );
        }
    }

    /**
     * Get the WC order from the custom ID.
     *
     * @param string $custom_id The custom ID string from the PayPal order.
     * @return WC_Order|null
     */
    private function get_wc_order( $custom_id ) {
        $data = json_decode( $custom_data );
        $order_id = $data->order_id ?? null;
        if ( ! $order_id ) {
            return null;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return null;
        }

        // Validate the order key.
        $order_key = $data->order_key ?? null;
        if ( $order_key !== $order->get_order_key() ) {
            return null;
        }

        return $order;
    }
}
