<?php
/**
 * WooCommerce Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * is_woocommerce - Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included)
 *
 * @access public
 * @return bool
 */
function is_woocommerce() {
	return ( is_shop() || is_product_taxonomy() || is_product() ) ? true : false;
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
		return is_page( wc_get_page_id( 'cart' ) );
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
		return is_page( wc_get_page_id( 'checkout' ) ) ? true : false;
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

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		if ( defined('DOING_AJAX') )
			return true;

		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) ? true : false;
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
	 * @param $name of the attribute
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
	 * @param $name of the attribute
	 * @param $product_id
	 * @return bool
	 */
	function meta_is_product_attribute( $name, $value, $product_id ) {
		$product    = get_product( $product_id );
		
		if ( $product->product_type != 'variation' ) {
			return false;
		}
		
		$attributes = $product->get_variation_attributes();

		return ( in_array( $name, array_keys( $attributes ) ) && in_array( $value, $attributes[ $name ] ) );
	}
}
