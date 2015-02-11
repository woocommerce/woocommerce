<?php
/**
 * WooCommerce Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * is_woocommerce - Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included)
 *
 * @access public
 * @return bool
 */
function is_woocommerce() {
	return apply_filters( 'is_woocommerce', ( is_shop() || is_product_taxonomy() || is_product() ) ? true : false );
}

if ( ! function_exists( 'is_shop' ) ) {

	/**
	 * is_shop - Returns true when viewing the product type archive (shop).
	 *
	 * @access public
	 * @return bool
	 */
	function is_shop() {
		return ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_product_taxonomy' ) ) {

	/**
	 * is_product_taxonomy - Returns true when viewing a product taxonomy archive.
	 *
	 * @access public
	 * @return bool
	 */
	function is_product_taxonomy() {
		return is_tax( get_object_taxonomies( 'product' ) );
	}
}

if ( ! function_exists( 'is_product_category' ) ) {

	/**
	 * is_product_category - Returns true when viewing a product category.
	 *
	 * @access public
	 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_category( $term = '' ) {
		return is_tax( 'product_cat', $term );
	}
}

if ( ! function_exists( 'is_product_tag' ) ) {

	/**
	 * is_product_tag - Returns true when viewing a product tag.
	 *
	 * @access public
	 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_tag( $term = '' ) {
		return is_tax( 'product_tag', $term );
	}
}

if ( ! function_exists( 'is_product' ) ) {

	/**
	 * is_product - Returns true when viewing a single product.
	 *
	 * @access public
	 * @return bool
	 */
	function is_product() {
		return is_singular( array( 'product' ) );
	}
}

if ( ! function_exists( 'is_cart' ) ) {

	/**
	 * is_cart - Returns true when viewing the cart page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_cart() {
		return is_page( wc_get_page_id( 'cart' ) ) || defined( 'WOOCOMMERCE_CART' );
	}
}

if ( ! function_exists( 'is_checkout' ) ) {

	/**
	 * is_checkout - Returns true when viewing the checkout page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_checkout() {
		return is_page( wc_get_page_id( 'checkout' ) ) || apply_filters( 'woocommerce_is_checkout', false ) ? true : false;
	}
}

if ( ! function_exists( 'is_checkout_pay_page' ) ) {

	/**
	 * is_checkout_pay - Returns true when viewing the checkout's pay page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_checkout_pay_page() {
		global $wp;

		return is_checkout() && ! empty( $wp->query_vars['order-pay'] ) ? true : false;
	}
}

if ( ! function_exists( 'is_wc_endpoint_url' ) ) {

	/**
	 * is_wc_endpoint_url - Check if an endpoint is showing
	 *
	 * @access public
	 * @param  string $endpoint
	 * @return bool
	 */
	function is_wc_endpoint_url( $endpoint = false ) {
		global $wp;

		$wc_endpoints = WC()->query->get_query_vars();

		if ( $endpoint ) {
			if ( ! isset( $wc_endpoints[ $endpoint ] ) ) {
				return false;
			} else {
				$endpoint_var = $wc_endpoints[ $endpoint ];
			}

			return isset( $wp->query_vars[ $endpoint_var ] );
		} else {
			foreach ( $wc_endpoints as $key => $value ) {
				if ( isset( $wp->query_vars[ $key ] ) ) {
					return true;
				}
			}
			return false;
		}
	}
}

if ( ! function_exists( 'is_account_page' ) ) {

	/**
	 * is_account_page - Returns true when viewing an account page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_account_page() {
		return is_page( wc_get_page_id( 'myaccount' ) ) || apply_filters( 'woocommerce_is_account_page', false ) ? true : false;
	}
}

if ( ! function_exists( 'is_view_order_page' ) ) {

	/**
	* is_view_order_page - Returns true when on the view order page.
	*
	* @access public
	* @return bool
	*/
	function is_view_order_page() {
		global $wp;

		return ( is_page( wc_get_page_id( 'myaccount' ) ) && isset( $wp->query_vars['view-order'] ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_order_received_page' ) ) {

	/**
	* is_order_received_page - Returns true when viewing the order received page.
	*
	* @access public
	* @return bool
	*/
	function is_order_received_page() {
		global $wp;

		return ( is_page( wc_get_page_id( 'checkout' ) ) && isset( $wp->query_vars['order-received'] ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_add_payment_method_page' ) ) {

	/**
	* is_add_payment_method_page - Returns true when viewing the add payment method page.
	*
	* @access public
	* @return bool
	*/
	function is_add_payment_method_page() {
		global $wp;

		return ( is_page( wc_get_page_id( 'myaccount' ) ) && isset( $wp->query_vars['add-payment-method'] ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_lost_password_page' ) ) {

	/**
	* is_lost_password_page - Returns true when viewing the lost password page.
	*
	* @access public
	* @return bool
	*/
	function is_lost_password_page() {
		global $wp;

		return ( is_page( wc_get_page_id( 'myaccount' ) ) && isset( $wp->query_vars['lost-password'] ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}

if ( ! function_exists( 'is_store_notice_showing' ) ) {

	/**
	 * is_store_notice_showing - Returns true when store notice is active.
	 *
	 * @access public
	 * @return bool
	 */
	function is_store_notice_showing() {
		return get_option( 'woocommerce_demo_store' ) !== 'no' ? true : false;
	}
}

if ( ! function_exists( 'is_filtered' ) ) {

	/**
	 * is_filtered - Returns true when filtering products using layered nav or price sliders.
	 *
	 * @access public
	 * @return bool
	 */
	function is_filtered() {
		global $_chosen_attributes;

		return apply_filters( 'woocommerce_is_filtered', ( sizeof( $_chosen_attributes ) > 0 || ( isset( $_GET['max_price'] ) && isset( $_GET['min_price'] ) ) ) );
	}
}

if ( ! function_exists( 'taxonomy_is_product_attribute' ) ) {

	/**
	 * Returns true when the passed taxonomy name is a product attribute.
	 *
	 * @uses  $wc_product_attributes global which stores taxonomy names upon registration
	 * @param string $name of the attribute
	 * @return bool
	 */
	function taxonomy_is_product_attribute( $name ) {
		global $wc_product_attributes;

		return taxonomy_exists( $name ) && array_key_exists( $name, (array) $wc_product_attributes );
	}
}

if ( ! function_exists( 'meta_is_product_attribute' ) ) {

	/**
	 * Returns true when the passed meta name is a product attribute.
	 *
	 * @param string $name of the attribute
	 * @param mixed $value
	 * @param int $product_id
	 * @return bool
	 */
	function meta_is_product_attribute( $name, $value, $product_id ) {
		$product    = wc_get_product( $product_id );

		if ( $product->product_type != 'variation' ) {
			return false;
		}

		$attributes = $product->get_variation_attributes();

		return ( in_array( $name, array_keys( $attributes ) ) && in_array( $value, $attributes[ $name ] ) );
	}
}

if ( ! function_exists( 'wc_tax_enabled' ) ) {

	/**
	 * Are store-wide taxes enabled?
	 *
	 * @return bool
	 */
	function wc_tax_enabled() {
		return get_option( 'woocommerce_calc_taxes' ) === 'yes';
	}
}

if ( ! function_exists( 'wc_prices_include_tax' ) ) {

	/**
	 * Are prices inclusive of tax?
	 *
	 * @return bool
	 */
	function wc_prices_include_tax() {
		return wc_tax_enabled() && get_option( 'woocommerce_prices_include_tax' ) === 'yes';
	}
}

/**
 * Check if the given topic is a valid webhook topic, a topic is valid if:
 *
 * + starts with `action.woocommerce_` or `action.wc_`
 * + it has a valid resource & event
 *
 * @param string $topic webhook topic
 * @return bool true if valid, false otherwise
 */
function wc_is_webhook_valid_topic( $topic ) {

	// custom topics are prefixed with woocommerce_ or wc_ are valid
	if ( 0 === strpos( $topic, 'action.woocommerce_' ) || 0 === strpos( $topic, 'action.wc_' ) ) {
		return true;
	}

	@list( $resource, $event ) = explode( '.', $topic );

	if ( ! isset( $resource ) || ! isset( $event ) ) {
		return false;
	}

	$valid_resources = apply_filters( 'woocommerce_valid_webhook_resources', array( 'coupon', 'customer', 'order', 'product' ) );
	$valid_events    = apply_filters( 'woocommerce_valid_webhook_events', array( 'created', 'updated', 'deleted' ) );

	if ( in_array( $resource, $valid_resources ) && in_array( $event, $valid_events ) ) {
		return true;
	}

	return false;
}


/**
 * Simple check for validating a URL, it must start with http:// or https://
 * and pass FILTER_VALIDATE_URL validation
 *
 * @param string $url
 * @return bool
 */
function wc_is_valid_url( $url ) {

	// must start with http:// or https://
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// must pass validation
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}
