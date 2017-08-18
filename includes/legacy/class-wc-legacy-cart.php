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
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'prices_include_tax' :
				return wc_prices_include_tax();
			break;
			case 'round_at_subtotal' :
				return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
			break;
			case 'dp' :
				return wc_get_price_decimals();
			break;
			case 'display_totals_ex_tax' :
			case 'display_cart_ex_tax' :
				return 'excl' === $this->tax_display_cart;
			break;
			case 'cart_contents_weight' :
				return $this->get_cart_contents_weight();
			break;
			case 'cart_contents_count' :
				return $this->get_cart_contents_count();
			break;
			case 'tax' :
				wc_deprecated_argument( 'WC_Cart->tax', '2.3', 'Use WC_Tax:: directly' );
				$this->tax = new WC_Tax();
				return $this->tax;
			case 'discount_total':
				wc_deprecated_argument( 'WC_Cart->discount_total', '2.3', 'After tax coupons are no longer supported. For more information see: https://woocommerce.wordpress.com/2014/12/upcoming-coupon-changes-in-woocommerce-2-3/' );
				return 0;
			case 'coupons' :
				return $this->get_coupons();
		}
	}

	/**
	 * Methods moved to session class in 3.2.0.
	 */
	public function get_cart_from_session() { $this->session->get_cart_from_session(); }
	public function maybe_set_cart_cookies() { $this->session->maybe_set_cart_cookies(); }
	public function set_session() { $this->session->set_session(); }
	public function get_cart_for_session() { $this->session->get_cart_for_session(); }
	public function persistent_cart_update() { $this->session->persistent_cart_update(); }
	public function persistent_cart_destroy() { $this->session->persistent_cart_destroy(); }

	/**
	 * Renamed for consistency.
	 *
	 * @param string $coupon_code
	 * @return bool	True if the coupon is applied, false if it does not exist or cannot be applied.
	 */
	public function add_discount( $coupon_code ) {
		return $this->apply_coupon( $coupon_code );
	}
	/**
	 * Remove taxes.
	 *
	 * @deprecated 3.2.0 Taxes are never calculated if customer is tax except making this function unused.
	 */
	public function remove_taxes() {
		wc_deprecated_function( 'WC_Cart::remove_taxes', '3.2', '' );
	}
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
