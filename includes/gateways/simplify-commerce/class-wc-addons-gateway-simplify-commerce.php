<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway for subscriptions.
 *
 * @class 		WC_Addons_Gateway_Simplify_Commerce
 * @extends		WC_Gateway_Simplify_Commerce
 * @since       2.2.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Addons_Gateway_Simplify_Commerce extends WC_Gateway_Simplify_Commerce {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
			add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );

			add_action( 'wcs_resubscribe_order_created', array( $this, 'delete_resubscribe_meta' ), 10 );

			// Allow store managers to manually set Simplify as the payment method on a subscription
			add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'add_subscription_payment_meta' ), 10, 2 );
			add_filter( 'woocommerce_subscription_validate_payment_meta', array( $this, 'validate_subscription_payment_meta' ), 10, 2 );
		}

		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
		}

		add_filter( 'woocommerce_simplify_commerce_hosted_args', array( $this, 'hosted_payment_args' ), 10, 2 );
		add_action( 'woocommerce_api_wc_addons_gateway_simplify_commerce', array( $this, 'return_handler' ) );
	}

	/**
	 * Hosted payment args.
	 *
	 * @param  array $args
	 * @param  int   $order_id
	 * @return array
	 */
	public function hosted_payment_args( $args, $order_id ) {
		if ( ( $this->order_contains_subscription( $order_id ) ) || ( $this->order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) ) {
			$args['operation'] = 'create.token';
		}

		$args['redirect-url'] = WC()->api_request_url( 'WC_Addons_Gateway_Simplify_Commerce' );

		return $args;
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Check if order contains pre-orders.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_pre_order( $order_id ) {
		return class_exists( 'WC_Pre_Orders_Order' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order
	 * @param  string   $cart_token
	 * @uses   Simplify_ApiException
	 * @uses   Simplify_BadRequestException
	 * @return array
	 * @throws Exception
	 */
	protected function process_subscription( $order, $cart_token = '' ) {
		try {
			if ( empty( $cart_token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
				}

				throw new Simplify_ApiException( $error_msg );
			}

			// Create customer
			$customer = Simplify_Customer::createCustomer( array(
				'token'     => $cart_token,
				'email'     => $order->get_billing_email(),
				'name'      => trim( $order->get_formatted_billing_full_name() ),
				'reference' => $order->get_id(),
			) );

			if ( is_object( $customer ) && '' != $customer->id ) {
				$this->save_subscription_meta( $order->get_id(), $customer->id );
			} else {
				$error_msg = __( 'Error creating user in Simplify Commerce.', 'woocommerce' );

				throw new Simplify_ApiException( $error_msg );
			}

			$payment_response = $this->process_subscription_payment( $order, $order->get_total() );

			if ( is_wp_error( $payment_response ) ) {
				throw new Exception( $payment_response->get_error_message() );
			} else {
				// Remove cart
				WC()->cart->empty_cart();

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					wc_add_notice( $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')', 'error' );
				}
			} else {
				wc_add_notice( $e->getMessage(), 'error' );
			}

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Store the customer and card IDs on the order and subscriptions in the order.
	 *
	 * @param int $order_id
	 * @param string $customer_id
	 */
	protected function save_subscription_meta( $order_id, $customer_id ) {

		$customer_id = wc_clean( $customer_id );

		update_post_meta( $order_id, '_simplify_customer_id', $customer_id );

		// Also store it on the subscriptions being purchased in the order
		foreach ( wcs_get_subscriptions_for_order( $order_id ) as $subscription ) {
			update_post_meta( $subscription->id, '_simplify_customer_id', $customer_id );
		}
	}

	/**
	 * Process the pre-order.
	 *
	 * @param WC_Order $order
	 * @param string   $cart_token
	 * @uses  Simplify_ApiException
	 * @uses  Simplify_BadRequestException
	 * @return array
	 */
	protected function process_pre_order( $order, $cart_token = '' ) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order->get_id() ) ) {

			try {
				if ( $order->get_total() * 100 < 50 ) {
					$error_msg = __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' );

					throw new Simplify_ApiException( $error_msg );
				}

				if ( empty( $cart_token ) ) {
					$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

					if ( 'yes' == $this->sandbox ) {
						$error_msg .= ' ' . __( 'Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
					}

					throw new Simplify_ApiException( $error_msg );
				}

				// Create customer
				$customer = Simplify_Customer::createCustomer( array(
					'token'     => $cart_token,
					'email'     => $order->get_billing_email(),
					'name'      => trim( $order->get_formatted_billing_full_name() ),
					'reference' => $order->get_id(),
				) );

				if ( is_object( $customer ) && '' != $customer->id ) {
					$customer_id = wc_clean( $customer->id );

					// Store the customer ID in the order
					update_post_meta( $order->get_id(), '_simplify_customer_id', $customer_id );
				} else {
					$error_msg = __( 'Error creating user in Simplify Commerce.', 'woocommerce' );

					throw new Simplify_ApiException( $error_msg );
				}

				// Reduce stock levels
				wc_reduce_stock_levels( $order->get_id() );

				// Remove cart
				WC()->cart->empty_cart();

				// Is pre ordered!
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} catch ( Simplify_ApiException $e ) {
				if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
					foreach ( $e->getFieldErrors() as $error ) {
						wc_add_notice( $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')', 'error' );
					}
				} else {
					wc_add_notice( $e->getMessage(), 'error' );
				}

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}

		} else {
			return parent::process_standard_payments( $order, $cart_token );
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$cart_token = isset( $_POST['simplify_token'] ) ? wc_clean( $_POST['simplify_token'] ) : '';
		$order      = wc_get_order( $order_id );

		// Processing subscription
		if ( 'standard' == $this->mode && ( $this->order_contains_subscription( $order->get_id() ) || ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id ) ) ) ) {
			return $this->process_subscription( $order, $cart_token );

		// Processing pre-order
		} elseif ( 'standard' == $this->mode && $this->order_contains_pre_order( $order->get_id() ) ) {
			return $this->process_pre_order( $order, $cart_token );

		// Processing regular product
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param WC_order $order
	 * @param int $amount (default: 0)
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

		$customer_id = get_post_meta( $order->get_id(), '_simplify_customer_id', true );

		if ( ! $customer_id ) {
			return new WP_Error( 'simplify_error', __( 'Customer not found.', 'woocommerce' ) );
		}

		try {
			// Charge the customer
			$payment = Simplify_Payment::createPayment( array(
				'amount'              => $amount * 100, // In cents.
				'customer'            => $customer_id,
				'description'         => sprintf( __( '%1$s - Order #%2$s', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order->get_order_number() ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->get_id(),
			) );

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				$error_message = '';
				foreach ( $e->getFieldErrors() as $error ) {
					$error_message .= ' ' . $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')';
				}
			}

			$order->add_order_note( sprintf( __( 'Simplify payment error: %s.', 'woocommerce' ), $error_message ) );

			return new WP_Error( 'simplify_payment_declined', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		if ( 'APPROVED' == $payment->paymentStatus ) {
			// Payment complete
			$order->payment_complete( $payment->id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %1$s, Auth Code: %2$s)', 'woocommerce' ), $payment->id, $payment->authCode ) );

			return true;
		} else {
			$order->add_order_note( __( 'Simplify payment declined', 'woocommerce' ) );

			return new WP_Error( 'simplify_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce' ) );
		}
	}

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			$renewal_order->update_status( 'failed', sprintf( __( 'Simplify Transaction Failed (%s)', 'woocommerce' ), $result->get_error_message() ) );
		}
	}

	/**
	 * Update the customer_id for a subscription after using Simplify to complete a payment to make up for.
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Subscription $subscription The subscription for which the failing payment method relates.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {
		update_post_meta( $subscription->id, '_simplify_customer_id', get_post_meta( $renewal_order->get_id(), '_simplify_customer_id', true ) );
	}

	/**
	 * Include the payment meta data required to process automatic recurring payments so that store managers can.
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions v2.0+.
	 *
	 * @since 2.4
	 * @param array $payment_meta associative array of meta data required for automatic payments
	 * @param WC_Subscription $subscription An instance of a subscription object
	 * @return array
	 */
	public function add_subscription_payment_meta( $payment_meta, $subscription ) {

		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				'_simplify_customer_id' => array(
					'value' => get_post_meta( $subscription->id, '_simplify_customer_id', true ),
					'label' => 'Simplify Customer ID',
				),
			),
		);

		return $payment_meta;
	}

	/**
	 * Validate the payment meta data required to process automatic recurring payments so that store managers can.
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions 2.0+.
	 *
	 * @since  2.4
	 * @param  string $payment_method_id The ID of the payment method to validate
	 * @param  array $payment_meta associative array of meta data required for automatic payments
	 * @return array
	 * @throws Exception
	 */
	public function validate_subscription_payment_meta( $payment_method_id, $payment_meta ) {
		if ( $this->id === $payment_method_id ) {
			if ( ! isset( $payment_meta['post_meta']['_simplify_customer_id']['value'] ) || empty( $payment_meta['post_meta']['_simplify_customer_id']['value'] ) ) {
				throw new Exception( 'A "_simplify_customer_id" value is required.' );
			}
		}
	}

	/**
	 * Don't transfer customer meta to resubscribe orders.
	 *
	 * @access public
	 * @param int $resubscribe_order The order created for the customer to resubscribe to the old expired/cancelled subscription
	 * @return void
	 */
	public function delete_resubscribe_meta( $resubscribe_order ) {
		delete_post_meta( $resubscribe_order->get_id(), '_simplify_customer_id' );
	}

	/**
	 * Process a pre-order payment when the pre-order is released.
	 *
	 * @param WC_Order $order
	 * @return WP_Error|null
	 */
	public function process_pre_order_release_payment( $order ) {

		try {
			$order_items    = $order->get_items();
			$order_item     = array_shift( $order_items );
			/* translators: 1: site name 2: product name 3: order number */
			$pre_order_name = sprintf(
				__( '%1$s - Pre-order for "%2$s" (Order #%3$s)', 'woocommerce' ),
				esc_html( get_bloginfo( 'name', 'display' ) ),
				$order_item['name'],
				$order->get_order_number()
			);

			$customer_id = get_post_meta( $order->get_id(), '_simplify_customer_id', true );

			if ( ! $customer_id ) {
				return new WP_Error( 'simplify_error', __( 'Customer not found.', 'woocommerce' ) );
			}

			// Charge the customer
			$payment = Simplify_Payment::createPayment( array(
				'amount'              => $order->get_total() * 100, // In cents.
				'customer'            => $customer_id,
				'description'         => trim( substr( $pre_order_name, 0, 1024 ) ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->get_id(),
			) );

			if ( 'APPROVED' == $payment->paymentStatus ) {
				// Payment complete
				$order->payment_complete( $payment->id );

				// Add order note
				$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %1$s, Auth Code: %2$s)', 'woocommerce' ), $payment->id, $payment->authCode ) );
			} else {
				return new WP_Error( 'simplify_payment_declined', __( 'Payment was declined - the customer need to try another card.', 'woocommerce' ) );
			}
		} catch ( Exception $e ) {
			$order_note = sprintf( __( 'Simplify Transaction Failed (%s)', 'woocommerce' ), $e->getMessage() );

			// Mark order as failed if not already set,
			// otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
			if ( 'failed' != $order->get_status() ) {
				$order->update_status( 'failed', $order_note );
			} else {
				$order->add_order_note( $order_note );
			}
		}
	}

	/**
	 * Return handler for Hosted Payments.
	 */
	public function return_handler() {
		if ( ! isset( $_REQUEST['cardToken'] ) ) {
			parent::return_handler();
		}

		@ob_clean();
		header( 'HTTP/1.1 200 OK' );

		$redirect_url = wc_get_page_permalink( 'cart' );

		if ( isset( $_REQUEST['reference'] ) && isset( $_REQUEST['amount'] ) ) {
			$cart_token  = $_REQUEST['cardToken'];
			$amount      = absint( $_REQUEST['amount'] );
			$order_id    = absint( $_REQUEST['reference'] );
			$order       = wc_get_order( $order_id );
			$order_total = absint( $order->get_total() * 100 );

			if ( $amount === $order_total ) {
				if ( $this->order_contains_subscription( $order->get_id() ) ) {
					$response = $this->process_subscription( $order, $cart_token );
				} elseif ( $this->order_contains_pre_order( $order->get_id() ) ) {
					$response = $this->process_pre_order( $order, $cart_token );
				} else {
					$response = parent::process_standard_payments( $order, $cart_token );
				}

				if ( 'success' == $response['result'] ) {
					$redirect_url = $response['redirect'];
				} else {
					$order->update_status( 'failed', __( 'Payment was declined by Simplify Commerce.', 'woocommerce' ) );
				}

				wp_redirect( $redirect_url );
				exit();
			}
		}

		wp_redirect( $redirect_url );
		exit();
	}
}
