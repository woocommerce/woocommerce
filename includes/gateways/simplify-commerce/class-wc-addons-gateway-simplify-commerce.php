<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway for subscriptions
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
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 4 );
			add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 3 );
		}

		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
		}

		add_filter( 'woocommerce_simplify_commerce_hosted_args', array( $this, 'hosted_payment_args' ), 10, 2 );
		add_action( 'woocommerce_api_wc_addons_gateway_simplify_commerce', array( $this, 'return_handler' ) );
	}

	/**
	 * Hosted payment args
	 *
	 * @param  array $args
	 * @param  int   $order_id
	 *
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
	 *
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return class_exists( 'WC_Subscriptions_Order' ) && ( WC_Subscriptions_Order::order_contains_subscription( $order_id ) || WC_Subscriptions_Renewal_Order::is_renewal( $order_id ) );
	}

	/**
	 * Check if order contains pre-orders.
	 *
	 * @param  int $order_id
	 *
	 * @return bool
	 */
	protected function order_contains_pre_order( $order_id ) {
		return class_exists( 'WC_Pre_Orders_Order' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
	}

	/**
	 * Process the subscription
	 *
	 * @param WC_Order $order
	 * @param string   $cart_token
	 *
	 * @return array
	 */
	protected function process_subscription( $order, $cart_token = '' ) {
		try {
			if ( empty( $cart_token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
				}

				throw new Simplify_ApiException( $error_msg );
			}

			// Create customer
			$customer = Simplify_Customer::createCustomer( array(
				'token'     => $cart_token,
				'email'     => $order->billing_email,
				'name'      => trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
				'reference' => $order->id
			) );

			if ( is_object( $customer ) && '' != $customer->id ) {
				$customer_id = wc_clean( $customer->id );

				// Store the customer ID in the order
				update_post_meta( $order->id, '_simplify_customer_id', $customer_id );
			} else {
				$error_msg = __( 'Error creating user in Simplify Commerce.', 'woocommerce' );

				throw new Simplify_ApiException( $error_msg );
			}

			$initial_payment = WC_Subscriptions_Order::get_total_initial_payment( $order );

			if ( $initial_payment > 0 ) {
				$payment_response = $this->process_subscription_payment( $order, $initial_payment );
			}

			if ( isset( $payment_response ) && is_wp_error( $payment_response ) ) {

				throw new Exception( $payment_response->get_error_message() );

			} else {
				// Remove cart
				WC()->cart->empty_cart();

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
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
				'redirect' => ''
			);
		}
	}

	/**
	 * Process the pre-order
	 *
	 * @param WC_Order $order
	 * @param string   $cart_token
	 *
	 * @return array
	 */
	protected function process_pre_order( $order, $cart_token = '' ) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order->id ) ) {

			try {
				if ( $order->order_total * 100 < 50 ) {
					$error_msg = __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' );

					throw new Simplify_ApiException( $error_msg );
				}

				if ( empty( $cart_token ) ) {
					$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

					if ( 'yes' == $this->sandbox ) {
						$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
					}

					throw new Simplify_ApiException( $error_msg );
				}

				// Create customer
				$customer = Simplify_Customer::createCustomer( array(
					'token'     => $cart_token,
					'email'     => $order->billing_email,
					'name'      => trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
					'reference' => $order->id
				) );

				if ( is_object( $customer ) && '' != $customer->id ) {
					$customer_id = wc_clean( $customer->id );

					// Store the customer ID in the order
					update_post_meta( $order->id, '_simplify_customer_id', $customer_id );
				} else {
					$error_msg = __( 'Error creating user in Simplify Commerce.', 'woocommerce' );

					throw new Simplify_ApiException( $error_msg );
				}

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				WC()->cart->empty_cart();

				// Is pre ordered!
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
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
					'redirect' => ''
				);
			}

		} else {
			return parent::process_standard_payments( $order, $cart_token );
		}
	}

	/**
	 * Process the payment
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$cart_token = isset( $_POST['simplify_token'] ) ? wc_clean( $_POST['simplify_token'] ) : '';
		$order      = wc_get_order( $order_id );

		// Processing subscription
		if ( 'standard' == $this->mode && $this->order_contains_subscription( $order->id ) ) {
			return $this->process_subscription( $order, $cart_token );

		// Processing pre-order
		} elseif ( 'standard' == $this->mode && $this->order_contains_pre_order( $order->id ) ) {
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
	 * @param integer $amount (default: 0)
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {
		$order_items       = $order->get_items();
		$order_item        = array_shift( $order_items );
		$subscription_name = sprintf( __( '%s - Subscription for "%s"', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order_item['name'] ) . ' ' . sprintf( __( '(Order #%s)', 'woocommerce' ), $order->get_order_number() );

		if ( $amount * 100 < 50 ) {
			return new WP_Error( 'simplify_error', __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' ) );
		}

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
	 * scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $order The WC_Order object of the order which the subscription was purchased in.
	 * @param int $product_id The ID of the subscription product for which this payment relates.
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		} else {
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}

	/**
	 * Don't transfer customer meta when creating a parent renewal order.
	 *
	 * @param string $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= " AND `meta_key` NOT LIKE '_simplify_customer_id' ";
		}

		return $order_meta_query;
	}

	/**
	 * Update the customer_id for a subscription after using Simplify to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Order $original_order The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @param string $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @return void
	 */
	public function update_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {
		$new_customer_id = get_post_meta( $renewal_order->id, '_simplify_customer_id', true );

		update_post_meta( $original_order->id, '_simplify_customer_id', $new_customer_id );
	}

	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @param WC_Order $order
	 * @return wp_error|null
	 */
	public function process_pre_order_release_payment( $order ) {

		try {
			$order_items    = $order->get_items();
			$order_item     = array_shift( $order_items );
			$pre_order_name = sprintf( __( '%s - Pre-order for "%s"', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order_item['name'] ) . ' ' . sprintf( __( '(Order #%s)', 'woocommerce' ), $order->get_order_number() );

			$customer_id = get_post_meta( $order->id, '_simplify_customer_id', true );

			if ( ! $customer_id ) {
				return new WP_Error( 'simplify_error', __( 'Customer not found', 'woocommerce' ) );
			}

			// Charge the customer
			$payment = Simplify_Payment::createPayment( array(
				'amount'              => $order->order_total * 100, // In cents
				'customer'            => $customer_id,
				'description'         => trim( substr( $pre_order_name, 0, 1024 ) ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->id,
				'card.addressCity'    => $order->billing_city,
				'card.addressCountry' => $order->billing_country,
				'card.addressLine1'   => $order->billing_address_1,
				'card.addressLine2'   => $order->billing_address_2,
				'card.addressState'   => $order->billing_state,
				'card.addressZip'     => $order->billing_postcode
			) );

			if ( 'APPROVED' == $payment->paymentStatus ) {
				// Payment complete
				$order->payment_complete( $payment->id );

				// Add order note
				$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ), $payment->id, $payment->authCode ) );
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
	 * Return handler for Hosted Payments
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
			$order_total = absint( $order->order_total * 100 );

			if ( $amount === $order_total ) {
				if ( $this->order_contains_subscription( $order->id ) ) {
					$response = $this->process_subscription( $order, $cart_token );
				} elseif ( $this->order_contains_pre_order( $order->id ) ) {
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
