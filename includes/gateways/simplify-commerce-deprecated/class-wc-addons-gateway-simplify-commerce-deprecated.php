<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway for Subscriptions < 2.0
 *
 * @class 		WC_Addons_Gateway_Simplify_Commerce_Deprecated
 * @extends		WC_Addons_Gateway_Simplify_Commerce
 * @since       2.4.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Addons_Gateway_Simplify_Commerce_Deprecated extends WC_Addons_Gateway_Simplify_Commerce {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 3 );
		}
	}

	/**
	 * Store the customer and card IDs on the order and subscriptions in the order
	 *
	 * @param int $order_id
	 * @param string $customer_id
	 * @return array
	 */
	protected function save_subscription_meta( $order_id, $customer_id ) {
		update_post_meta( $order_id, '_simplify_customer_id', wc_clean( $customer_id ) );
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param WC_order $order
	 * @param integer $amount (default: 0)
	 * @uses  Simplify_BadRequestException
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order, $amount = 0 ) {
		if ( 0 == $amount ) {
			// Payment complete
			$order->payment_complete();

			return true;
		}

		if ( $amount * 100 < 50 ) {
			return new WP_Error( 'simplify_error', __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' ) );
		}

		$order_items       = $order->get_items();
		$order_item        = array_shift( $order_items );
		$subscription_name = sprintf( __( '%s - Subscription for "%s"', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order_item['name'] ) . ' ' . sprintf( __( '(Order #%s)', 'woocommerce' ), $order->get_order_number() );

		$customer_id = get_post_meta( $order->id, '_simplify_customer_id', true );

		if ( ! $customer_id ) {
			return new WP_Error( 'simplify_error', __( 'Customer not found', 'woocommerce' ) );
		}

		try {
			// Charge the customer
			$payment = Simplify_Payment::createPayment( array(
				'amount'              => $amount * 100, // In cents
				'customer'            => $customer_id,
				'description'         => trim( substr( $subscription_name, 0, 1024 ) ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->id,
				'card.addressCity'    => $order->billing_city,
				'card.addressCountry' => $order->billing_country,
				'card.addressLine1'   => $order->billing_address_1,
				'card.addressLine2'   => $order->billing_address_2,
				'card.addressState'   => $order->billing_state,
				'card.addressZip'     => $order->billing_postcode
			) );

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				$error_message = '';
				foreach ( $e->getFieldErrors() as $error ) {
					$error_message .= ' ' . $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')';
				}
			}

			$order->add_order_note( sprintf( __( 'Simplify payment error: %s', 'woocommerce' ), $error_message ) );

			return new WP_Error( 'simplify_payment_declined', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		if ( 'APPROVED' == $payment->paymentStatus ) {
			// Payment complete
			$order->payment_complete( $payment->id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ), $payment->id, $payment->authCode ) );

			return true;
		} else {
			$order->add_order_note( __( 'Simplify payment declined', 'woocommerce' ) );

			return new WP_Error( 'simplify_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce' ) );
		}
	}

	/**
	 * process_scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $order The WC_Order object of the order which the subscription was purchased in.
	 * @param int $product_id The ID of the subscription product for which this payment relates.
	 */
	public function process_scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		} else {
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}
}
