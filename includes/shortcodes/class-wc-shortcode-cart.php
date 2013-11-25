<?php
/**
 * Cart Shortcode
 *
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Cart
 * @version     2.0.0
 */
class WC_Shortcode_Cart {

	/**
	 * Output the cart shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce;

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) define( 'WOOCOMMERCE_CART', true );

		// Add Discount
		if ( ! empty( $_POST['apply_coupon'] ) ) {

			if ( ! empty( $_POST['coupon_code'] ) ) {
				$woocommerce->cart->add_discount( sanitize_text_field( $_POST['coupon_code'] ) );
			} else {
				wc_add_notice( WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
			}

		// Remove Coupon Codes
		} elseif ( isset( $_GET['remove_coupon'] ) ) {

			$woocommerce->cart->remove_coupon( woocommerce_clean( $_GET['remove_coupon'] ) );

		// Update Shipping
		} elseif ( ! empty( $_POST['calc_shipping'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-cart' ) ) {

			try {
				$woocommerce->shipping->reset_shipping();

				$country 	= woocommerce_clean( $_POST['calc_shipping_country'] );
				$state 		= woocommerce_clean( $_POST['calc_shipping_state'] );
				$postcode   = apply_filters( 'woocommerce_shipping_calculator_enable_postcode', true ) ? woocommerce_clean( $_POST['calc_shipping_postcode'] ) : '';
				$city       = apply_filters( 'woocommerce_shipping_calculator_enable_city', false ) ? woocommerce_clean( $_POST['calc_shipping_city'] ) : '';

				if ( $postcode && ! WC_Validation::is_postcode( $postcode, $country ) ) {
					throw new Exception( __( 'Please enter a valid postcode/ZIP.', 'woocommerce' ) );
				} elseif ( $postcode ) {
					$postcode = wc_format_postcode( $postcode, $country );
				}

				if ( $country ) {
					$woocommerce->customer->set_location( $country, $state, $postcode, $city );
					$woocommerce->customer->set_shipping_location( $country, $state, $postcode, $city );
				} else {
					$woocommerce->customer->set_to_base();
					$woocommerce->customer->set_shipping_to_base();
				}

				$woocommerce->customer->calculated_shipping( true );

				wc_add_notice(  __( 'Shipping costs updated.', 'woocommerce' ), 'notice' );

				do_action( 'woocommerce_calculated_shipping' );

			} catch ( Exception $e ) {

				if ( ! empty( $e ) )
					wc_add_notice( $e, 'error' );
			}
		}

		// Check cart items are valid
		do_action('woocommerce_check_cart_items');

		// Calc totals
		$woocommerce->cart->calculate_totals();

		if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
			wc_get_template( 'cart/cart-empty.php' );
		else
			wc_get_template( 'cart/cart.php' );

	}
}