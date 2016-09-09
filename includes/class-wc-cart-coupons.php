<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cart Coupons.
 *
 * @class 		WC_Cart_Coupons
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Coupons {

	/**
	 * An array of coupon objects.
	 * @var object[]
	 */
	private $coupons = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_coupons' ), 1 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_customer_coupons' ), 1 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'applied_coupon' ), 10, 2 );
	}

	/**
	 * Get coupons.
	 * @return array
	 */
	public function get_coupons() {
		return $this->coupons;
	}

	/**
	 * Set coupons.
	 * @param array $set
	 */
	public function set_coupons( $set ) {
		$set           = array_filter( (array) $set );
		$this->coupons = array();

		foreach ( $set as $code => $coupon ) {
			if ( is_object( $coupon ) ) {
				$this->coupons[ $code ] = $coupon;
			} else {
				$this->coupons[ $coupon ] = new WC_Coupon( $coupon );
			}
		}
	}

	/**
	 * Remove a single coupon by code.
	 * @param  string $coupon_code Code of the coupon to remove
	 */
	public function remove_coupon( $coupon_code ) {
		$coupon_code = wc_format_coupon_code( $coupon_code );
		unset( $this->coupons[ $coupon_code ] );
		do_action( 'woocommerce_removed_coupon', $coupon_code );
	}

	/**
	 * Check cart coupons for errors.
	 */
	public function check_cart_coupons() {
		foreach ( $this->coupons as $code => $coupon ) {
			if ( ! $coupon->is_valid() ) {
				// Error message
				$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_INVALID_REMOVED );

				// Remove the coupon
				$this->remove_coupon( $code );

				// Flag totals for refresh
				WC()->session->set( 'refresh_totals', true );
			}
		}
	}

	/**
	 * Check for user coupons (now that we have billing email). If a coupon is invalid, add an error.
	 *
	 * Checks two types of coupons:
	 *  1. Where a list of customer emails are set (limits coupon usage to those defined).
	 *  2. Where a usage_limit_per_user is set (limits coupon usage to a number based on user ID and email).
	 *
	 * @param array $posted
	 */
	public function check_customer_coupons( $posted ) {
		if ( ! empty( $this->coupons ) ) {
			foreach ( $this->coupons as $code => $coupon ) {
				if ( $coupon->is_valid() ) {

					// Limit to defined email addresses
					if ( is_array( $coupon->get_email_restrictions() ) && sizeof( $coupon->get_email_restrictions() ) > 0 ) {
						$check_emails           = array();
						if ( is_user_logged_in() ) {
							$current_user   = wp_get_current_user();
							$check_emails[] = $current_user->user_email;
						}
						$check_emails[] = $posted['billing_email'];
						$check_emails   = array_map( 'sanitize_email', array_map( 'strtolower', $check_emails ) );

						if ( 0 == sizeof( array_intersect( $check_emails, $coupon->get_email_restrictions() ) ) ) {
							$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED );

							// Remove the coupon
							$this->remove_coupon( $code );

							// Flag totals for refresh
							WC()->session->set( 'refresh_totals', true );
						}
					}

					// Usage limits per user - check against billing and user email and user ID
					if ( $coupon->get_usage_limit_per_user() > 0 ) {
						$check_emails = array();
						$used_by      = $coupon->get_used_by();

						if ( is_user_logged_in() ) {
							$current_user   = wp_get_current_user();
							$check_emails[] = sanitize_email( $current_user->user_email );
							$usage_count    = sizeof( array_keys( $used_by, get_current_user_id() ) );
						} else {
							$check_emails[] = sanitize_email( $posted['billing_email'] );
							$user           = get_user_by( 'email', $posted['billing_email'] );
							if ( $user ) {
								$usage_count = sizeof( array_keys( $used_by, $user->ID ) );
							} else {
								$usage_count = 0;
							}
						}

						foreach ( $check_emails as $check_email ) {
							$usage_count = $usage_count + sizeof( array_keys( $used_by, $check_email ) );
						}

						if ( $usage_count >= $coupon->get_usage_limit_per_user() ) {
							$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED );

							// Remove the coupon
							$this->remove_coupon( $code );

							// Flag totals for refresh
							WC()->session->set( 'refresh_totals', true );
						}
					}
				}
			}
		}
	}

	/**
	 * Add a message for the applied coupon.
	 * @param  string $code
	 * @param  WC_Coupon $coupon
	 */
	public function applied_coupon( $code, $coupon ) {
		$coupon->add_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );
		if ( $coupon->get_free_shipping() ) {
			WC()->session->set( 'chosen_shipping_methods', null );
		}
	}
}
