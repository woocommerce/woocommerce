<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\Utilities\Users;

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
	 * @return boolean|WP_Error
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		$order_id      = absint( $request['id'] );
		$order_key     = sanitize_text_field( wp_unslash( $request->get_param( 'key' ) ) );
		$billing_email = sanitize_text_field( wp_unslash( $request->get_param( 'billing_email' ) ) );

		try {
			// In this context, pay_for_order capability checks that the current user ID matches the customer ID stored
			// within the order, or if the order was placed by a guest.
			// See https://github.com/woocommerce/woocommerce/blob/abcedbefe02f9e89122771100c42ff588da3e8e0/plugins/woocommerce/includes/wc-user-functions.php#L458.
			if ( ! current_user_can( 'pay_for_order', $order_id ) ) {
				throw new RouteException( 'woocommerce_rest_invalid_user', __( 'This order belongs to a different customer.', 'woocommerce' ), 403 );
			}
			if ( get_current_user_id() === 0 ) {
				$this->order_controller->validate_order_key( $order_id, $order_key );
				$this->validate_billing_email_matches_order( $order_id, $billing_email );
			}
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

		if ( ! $order || Users::should_user_verify_order_email( $order_id, $billing_email ) ) {
			throw new RouteException( 'woocommerce_rest_invalid_billing_email', __( 'Invalid billing email provided.', 'woocommerce' ), 401 );
		}
	}

}
