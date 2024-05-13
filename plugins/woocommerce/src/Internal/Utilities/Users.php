<?php

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Helper functions for working with users.
 */
class Users {
	/**
	 * Indicates if the user qualifies as site administrator.
	 *
	 * In the context of multisite networks, this means that they must have the `manage_sites`
	 * capability. In all other cases, they must have the `manage_options` capability.
	 *
	 * @param int $user_id Optional, used to specify a specific user (otherwise we look at the current user).
	 *
	 * @return bool
	 */
	public static function is_site_administrator( int $user_id = 0 ): bool {
		$user = 0 === $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id );

		if ( false === $user ) {
			return false;
		}

		return is_multisite() ? $user->has_cap( 'manage_sites' ) : $user->has_cap( 'manage_options' );
	}

	/**
	 * Check if the email is valid.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $supplied_email Supplied email.
	 * @param string $context Context in which we are checking the email.
	 * @return bool
	 */
	public static function should_user_verify_order_email( $order_id, $supplied_email = null, $context = 'view' ) {
		$order         = wc_get_order( $order_id );
		$billing_email = $order->get_billing_email();
		$customer_id   = $order->get_customer_id();

		// If we do not have a billing email for the order (could happen in the order is created manually, or if the
		// requirement for this has been removed from the checkout flow), email verification does not make sense.
		if ( empty( $billing_email ) ) {
			return false;
		}

		// No verification step is needed if the user is logged in and is already associated with the order.
		if ( $customer_id && get_current_user_id() === $customer_id ) {
			return false;
		}

		/**
		 * Controls the grace period within which we do not require any sort of email verification step before rendering
		 * the 'order received' or 'order pay' pages.
		 *
		 * To eliminate the grace period, set to zero (or to a negative value). Note that this filter is not invoked
		 * at all if email verification is deemed to be unnecessary (in other words, it cannot be used to force
		 * verification in *all* cases).
		 *
		 * @since 8.0.0
		 *
		 * @param int      $grace_period Time in seconds after an order is placed before email verification may be required.
		 * @param WC_Order $this         The order for which this grace period is being assessed.
		 * @param string   $context      Indicates the context in which we might verify the email address. Typically 'order-pay' or 'order-received'.
		 */
		$verification_grace_period = (int) apply_filters( 'woocommerce_order_email_verification_grace_period', 10 * MINUTE_IN_SECONDS, $order, $context );
		$date_created              = $order->get_date_created();

		// We do not need to verify the email address if we are within the grace period immediately following order creation.
		if (
			is_a( $date_created, \WC_DateTime::class, true )
			&& time() - $date_created->getTimestamp() <= $verification_grace_period
		) {
			return false;
		}

		$session       = wc()->session;
		$session_email = '';

		if ( is_a( $session, \WC_Session::class ) ) {
			$customer      = $session->get( 'customer' );
			$session_email = is_array( $customer ) && isset( $customer['email'] ) ? $customer['email'] : '';
		}

		// Email verification is required if the user cannot be identified, or if they supplied an email address but the nonce check failed.
		$can_view_orders      = current_user_can( 'read_private_shop_orders' );
		$session_email_match  = $session_email === $billing_email;
		$supplied_email_match = $supplied_email === $billing_email;

		$email_verification_required = ! $session_email_match && ! $supplied_email_match && ! $can_view_orders;

		/**
		 * Provides an opportunity to override the (potential) requirement for shoppers to verify their email address
		 * before we show information such as the order summary, or order payment page.
		 *
		 * Note that this hook is not always triggered, therefore it is (for example) unsuitable as a way of forcing
		 * email verification across all order confirmation/order payment scenarios. Instead, the filter primarily
		 * exists as a way to *remove* the email verification step.
		 *
		 * @since 7.9.0
		 *
		 * @param bool     $email_verification_required If email verification is required.
		 * @param WC_Order $order                       The relevant order.
		 * @param string   $context                     The context under which we are performing this check.
		 */
		return (bool) apply_filters( 'woocommerce_order_email_verification_required', $email_verification_required, $order, $context );
	}
}
