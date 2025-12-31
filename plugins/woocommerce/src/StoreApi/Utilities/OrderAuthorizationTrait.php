<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * OrderAuthorizationTrait
 *
 * Shared functionality for getting order authorization.
 */
trait OrderAuthorizationTrait {
	/**
	 * Check if authorized to get the order.
	 *
	 * @throws RouteException If the order is not found or the order key is invalid.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return boolean|\WP_Error
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		$order_id      = absint( $request['id'] );
		$order_key     = sanitize_text_field( wp_unslash( $request->get_param( 'key' ) ) );
		$billing_email = sanitize_text_field( wp_unslash( $request->get_param( 'billing_email' ) ) );

		try {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new RouteException( 'woocommerce_rest_invalid_order', esc_html__( 'Invalid order ID.', 'woocommerce' ), 404 );
			}

			$order_customer_id = $order->get_customer_id();

			// If the order belongs to a registered customer, check if the current user is the owner.
			if ( $order_customer_id ) {
				// If current user is the order owner, allow access, otherwise reject with an error.
				if ( get_current_user_id() === $order_customer_id ) {
					return true;
				} else {
					throw new RouteException( 'woocommerce_rest_invalid_user', esc_html__( 'This order belongs to a different customer.', 'woocommerce' ), 403 );
				}
			}

			// Guest order: require order key and billing email validation for all visitors (logged-in or not).
			$this->order_controller->validate_order_key( $order_id, $order_key );
			$this->validate_billing_email_matches_order( $order_id, $billing_email );
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		return true;
	}

	/**
	 * Validate a given billing email against an existing order.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param integer $order_id Order ID.
	 * @param string  $billing_email Billing email.
	 */
	public function validate_billing_email_matches_order( $order_id, $billing_email ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			throw new RouteException( 'woocommerce_rest_invalid_order', esc_html__( 'Invalid order ID.', 'woocommerce' ), 404 );
		}

		$order_billing_email = $order->get_billing_email();

		// If the order doesn't have an email, then allowing an empty billing_email param is acceptable. It will still be compared to order email below.
		if ( ! $billing_email && ! empty( $order_billing_email ) ) {
			throw new RouteException( 'woocommerce_rest_invalid_billing_email', esc_html__( 'No billing email provided.', 'woocommerce' ), 401 );
		}

		// For Store API authorization, the provided billing email must exactly match the order's billing email. We use
		// direct comparison rather than Users::should_user_verify_order_email() because that function has a grace
		// period for newly created orders which is inappropriate for use when querying orders on the API.
		if ( 0 !== strcasecmp( $order_billing_email, $billing_email ) ) {
			throw new RouteException( 'woocommerce_rest_invalid_billing_email', esc_html__( 'Invalid billing email provided.', 'woocommerce' ), 401 );
		}
	}
}
