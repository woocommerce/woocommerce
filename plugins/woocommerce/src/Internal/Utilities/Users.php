<?php

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use WP_Error, WP_User;

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
	 * Get a user from a valid user ID, but only if the active user is able to see them.
	 *
	 * In a multisite context, that may mean that they both must be members of the current blog, or else the active
	 * user must either have special permissions (manage_network_users) or else a special legacy mode
	 * (woocommerce_network_wide_customers) is enabled.
	 *
	 * @param int      $user_id            The ID of the desired user.
	 * @param int|null $requesting_user_id The ID of the user making the request. Optional, defaults to the current user.
	 *
	 * @return WP_User|WP_Error
	 */
	public static function get_user_in_current_site( $user_id, ?int $requesting_user_id = null ) {
		// User ID is expected to be an integer. Cast it if we can (avoiding additional runtime warnings), else treat it as 0.
		$user_id = is_numeric( $user_id ) ? (int) $user_id : 0;

		$legacy_proxy       = wc_get_container()->get( LegacyProxy::class );
		$requesting_user_id = $requesting_user_id > 0 ? $requesting_user_id : wp_get_current_user()->ID;
		$error              = new WP_Error( 'wc_user_invalid_id', __( 'Invalid user ID.', 'woocommerce' ) );

		if ( $user_id <= 0 ) {
			return $error;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return $error;
		}

		if (
			$legacy_proxy->call_function( 'is_multisite' )
			&& ! $legacy_proxy->call_function( 'is_user_member_of_blog', $user->ID )
			&& ! $legacy_proxy->call_function( 'user_can', $requesting_user_id, 'manage_network_users' )
			&& get_site_option( 'woocommerce_network_wide_customers', 'no' ) !== 'yes'
		) {
			return $error;
		}

		return $user;
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

	/**
	 * Site-specific method of retrieving the requested user meta.
	 *
	 * This is a multisite-aware wrapper around WordPress's own `get_user_meta()` function, and works by prefixing the
	 * supplied meta key with a blog-specific meta key.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
	 * @param bool   $single  Optional. Whether to return a single value. This parameter has no effect if `$key` is not
	 *                        specified. Default false.
	 *
	 * @return mixed An array of values if `$single` is false. The value of meta data field if `$single` is true.
	 *               False for an invalid `$user_id` (non-numeric, zero, or negative value). An empty string if a valid
	 *               but non-existing user ID is passed.
	 */
	public static function get_site_user_meta( int $user_id, string $key = '', bool $single = false ) {
		global $wpdb;
		$site_specific_key = $key . '_' . rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );
		return get_user_meta( $user_id, $site_specific_key, true );
	}

	/**
	 * Site-specific means of updating user meta.
	 *
	 * This is a multisite-aware wrapper around WordPress's own `update_user_meta()` function, and works by prefixing
	 * the supplied meta key with a blog-specific meta key.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating. If specified, only update existing
	 *                           metadata entries with this value. Otherwise, update all entries. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure or if the value
	 *                  passed to the function is the same as the one that is already in the database.
	 */
	public static function update_site_user_meta( int $user_id, string $meta_key, $meta_value, $prev_value = '' ) {
		global $wpdb;
		$site_specific_key = $meta_key . '_' . rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );
		return update_user_meta( $user_id, $site_specific_key, $meta_value, $prev_value );
	}

	/**
	 * Site-specific means of deleting user meta.
	 *
	 * This is a multisite-aware wrapper around WordPress's own `delete_user_meta()` function, and works by prefixing
	 * the supplied meta key with a blog-specific meta key.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. If provided, rows will only be removed that match the value.
	 *                           Must be serializable if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 * /
	 */
	public static function delete_site_user_meta(  $user_id, $meta_key, $meta_value = '' ) {
		global $wpdb;
		$site_specific_key = $meta_key . '_' . rtrim( $wpdb->get_blog_prefix(), '_' );
		return delete_user_meta( $user_id, $site_specific_key, $meta_value );
	}
}
