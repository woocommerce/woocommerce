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
	}

	/**
	 * Get fees.
	 * @return array
	 */
	public function get_coupons() {
		return array_filter( (array) $this->coupons );
	}

	/**
	 * Remove a single coupon by code.
	 * @param  string $coupon_code Code of the coupon to remove
	 */
	public function remove_coupon( $coupon_code ) {
		$coupon_code = apply_filters( 'woocommerce_coupon_code', $coupon_code );
		$position    = array_search( $coupon_code, $this->coupons );

		if ( $position !== false ) {
			unset( $this->coupons[ $position ] );
		}

		do_action( 'woocommerce_removed_coupon', $coupon_code );
	}
	/**
	 * Remove coupons from the cart of a defined type.
	 */
	public function remove_coupons() {
		$this->coupons = array();
	}

	/**
	 * Returns whether or not a discount has been applied.
	 * @param string $coupon_code
	 * @return bool
	 */
	public function has_discount( $coupon_code = '' ) {
		return $coupon_code ? in_array( apply_filters( 'woocommerce_coupon_code', $coupon_code ), $this->coupons ) : sizeof( $this->coupons ) > 0;
	}
	/**
	 * Get the discount amount for a used coupon.
	 * @param  string $code coupon code
	 * @param  bool $ex_tax inc or ex tax
	 * @return float discount amount
	 */
	public function get_coupon_discount_amount( $code, $ex_tax = true ) {
		$discount_amount = isset( $this->coupon_discount_amounts[ $code ] ) ? $this->coupon_discount_amounts[ $code ] : 0;

		if ( ! $ex_tax ) {
			$discount_amount += $this->get_coupon_discount_tax_amount( $code );
		}

		return wc_cart_round_discount( $discount_amount, $this->dp );
	}

	/**
	 * Get the discount tax amount for a used coupon (for tax inclusive prices).
	 * @param  string $code coupon code
	 * @param  bool inc or ex tax
	 * @return float discount amount
	 */
	public function get_coupon_discount_tax_amount( $code ) {
		return wc_cart_round_discount( isset( $this->coupon_discount_tax_amounts[ $code ] ) ? $this->coupon_discount_tax_amounts[ $code ] : 0, $this->dp );
	}

	public function add_discount( $coupon_code ) {
		if ( ! wc_coupons_enabled() ) {
			return false;
		}

		$the_coupon = new WC_Coupon( $coupon_code );

		// Check it can be used with cart
		if ( ! $the_coupon->is_valid() ) {
			wc_add_notice( $the_coupon->get_error_message(), 'error' );
			return false;
		}

		// Check if applied
		if ( $this->has_discount( $coupon_code ) ) {
			$the_coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_ALREADY_APPLIED );
			return false;
		}

		// If its individual use then remove other coupons
		if ( $the_coupon->get_individual_use() ) {
			$this->coupons = apply_filters( 'woocommerce_apply_individual_use_coupon', array(), $the_coupon, $this->coupons );
		}

		foreach ( $this->get_coupons() as $code ) {
			$coupon = new WC_Coupon( $code );

			if ( $coupon->get_individual_use() && false === apply_filters( 'woocommerce_apply_with_individual_use_coupon', false, $the_coupon, $coupon, $this->coupons ) ) {
				$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY );
				return false;
			}
		}

		$this->coupons[] = $coupon_code;

		// Choose free shipping
		if ( $the_coupon->get_free_shipping() ) {
			$packages = WC()->shipping->get_packages();
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

			foreach ( $packages as $i => $package ) {
				$chosen_shipping_methods[ $i ] = 'free_shipping';
			}

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		}

		$the_coupon->add_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );

		do_action( 'woocommerce_applied_coupon', $coupon_code );

		return true;
	}

	/**
	 * Check cart coupons for errors.
	 */
	public function check_cart_coupons() {
		foreach ( $this->coupons as $code ) {
			$coupon = new WC_Coupon( $code );

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
			foreach ( $this->coupons as $code ) {
				$coupon = new WC_Coupon( $code );

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

}
