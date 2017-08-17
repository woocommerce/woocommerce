<?php
/**
 * Legacy cart
 *
 * Legacy and deprecated functions are here to keep the WC_Cart class clean.
 * This class will be removed in future versions.
 *
 * @version  3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy cart class.
 */
abstract class WC_Legacy_Cart {

	/**
	 * Contains an array of coupon usage counts after they have been applied.
	 *
	 * @deprecated 3.2.0
	 * @var array
	 */
	public $coupon_applied_count = array();

	/**
	 * Init.
	 *
	 * @deprecated 3.2.0 Session is loaded via hooks rather than directly.
	 */
	public function init() {
		wc_deprecated_function( 'WC_Cart::init', '3.2', '' );
		$this->get_cart_from_session();
	}

	/**
	 * Function to apply discounts to a product and get the discounted price (before tax is applied).
	 *
	 * @deprecated Calculation and coupon logic is handled in WC_Cart_Totals.
	 * @param mixed $values Cart item.
	 * @param mixed $price Price of item.
	 * @param bool  $add_totals Legacy.
	 * @return float price
	 */
	public function get_discounted_price( $values, $price, $add_totals = false ) {
		wc_deprecated_function( 'WC_Cart::get_discounted_price', '3.2', '' );

		$cart_item_key = $values['key'];
		$cart_item     = $this->cart_contents[ $cart_item_key ];

		return $cart_item->get_line_total();
	}

	/**
	 * Gets the url to the cart page.
	 *
	 * @deprecated 2.5.0 in favor to wc_get_cart_url()
	 * @return string url to page
	 */
	public function get_cart_url() {
		wc_deprecated_function( 'WC_Cart::get_cart_url', '2.5', 'wc_get_cart_url' );
		return wc_get_cart_url();
	}

	/**
	 * Gets the url to the checkout page.
	 *
	 * @deprecated 2.5.0 in favor to wc_get_checkout_url()
	 * @return string url to page
	 */
	public function get_checkout_url() {
		wc_deprecated_function( 'WC_Cart::get_checkout_url', '2.5', 'wc_get_checkout_url' );
		return wc_get_checkout_url();
	}

	/**
	 * Sees if we need a shipping address.
	 *
	 * @deprecated 2.5.0 in favor to wc_ship_to_billing_address_only()
	 * @return bool
	 */
	public function ship_to_billing_address_only() {
		wc_deprecated_function( 'WC_Cart::ship_to_billing_address_only', '2.5', 'wc_ship_to_billing_address_only' );
		return wc_ship_to_billing_address_only();
	}

	/**
	 * Coupons enabled function. Filterable.
	 *
	 * @deprecated 2.5.0 in favor to wc_coupons_enabled()
	 * @return bool
	 */
	public function coupons_enabled() {
		return wc_coupons_enabled();
	}

	/**
	 * Gets the total (product) discount amount - these are applied before tax.
	 *
	 * @deprecated Order discounts (after tax) removed in 2.3 so multiple methods for discounts are no longer required.
	 * @return mixed formatted price or false if there are none.
	 */
	public function get_discounts_before_tax() {
		wc_deprecated_function( 'get_discounts_before_tax', '2.3', 'get_total_discount' );
		if ( $this->get_cart_discount_total() ) {
			$discounts_before_tax = wc_price( $this->get_cart_discount_total() );
		} else {
			$discounts_before_tax = false;
		}
		return apply_filters( 'woocommerce_cart_discounts_before_tax', $discounts_before_tax, $this );
	}

	/**
	 * Get the total of all order discounts (after tax discounts).
	 *
	 * @deprecated Order discounts (after tax) removed in 2.3.
	 * @return int
	 */
	public function get_order_discount_total() {
		wc_deprecated_function( 'get_order_discount_total', '2.3' );
		return 0;
	}

	/**
	 * Function to apply cart discounts after tax.
	 *
	 * @deprecated Coupons can not be applied after tax.
	 * @param $values
	 * @param $price
	 */
	public function apply_cart_discounts_after_tax( $values, $price ) {
		wc_deprecated_function( 'apply_cart_discounts_after_tax', '2.3' );
	}

	/**
	 * Function to apply product discounts after tax.
	 *
	 * @deprecated Coupons can not be applied after tax.
	 *
	 * @param $values
	 * @param $price
	 */
	public function apply_product_discounts_after_tax( $values, $price ) {
		wc_deprecated_function( 'apply_product_discounts_after_tax', '2.3' );
	}

	/**
	 * Gets the order discount amount - these are applied after tax.
	 *
	 * @deprecated Coupons can not be applied after tax.
	 */
	public function get_discounts_after_tax() {
		wc_deprecated_function( 'get_discounts_after_tax', '2.3' );
	}
}
