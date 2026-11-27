<?php
/**
 * Cart Shortcode
 *
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @package WooCommerce\Shortcodes\Cart
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode cart class.
 */
class WC_Shortcode_Cart {

	/**
	 * Calculate shipping for the cart.
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function calculate_shipping() {
		try {
			WC()->shipping()->reset_shipping();

			$address = array();

			$address['country']  = isset( $_POST['calc_shipping_country'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shipping/cart nonce is verified; address fields are sanitized and validated.
			$address['state']    = isset( $_POST['calc_shipping_state'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shipping/cart nonce is verified; address fields are sanitized and validated.
			$address['postcode'] = isset( $_POST['calc_shipping_postcode'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_postcode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shipping/cart nonce is verified; address fields are sanitized and validated.
			$address['city']     = isset( $_POST['calc_shipping_city'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_city'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shipping/cart nonce is verified; address fields are sanitized and validated.

			if ( $address['postcode'] ) {
				$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
			}

			$address = apply_filters( 'woocommerce_cart_calculate_shipping_address', $address );

			// The country field is not submitted when woocommerce_shipping_calculator_enable_country returns false. This runs after the
			// filter above so a country set there still wins.
			if ( ! $address['country'] && ! isset( $_POST['calc_shipping_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shipping/cart nonce is verified.
				$address = self::maybe_set_calculator_fallback_country( $address );
			}

			if ( $address['postcode'] && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
				throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
			}

			if ( $address['country'] ) {
				if ( ! WC()->customer->get_billing_first_name() ) {
					WC()->customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
				}
				WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
			} else {
				WC()->customer->set_billing_address_to_base();
				WC()->customer->set_shipping_address_to_base();
			}

			WC()->customer->set_calculated_shipping( true );
			WC()->customer->save();

			wc_add_notice( __( 'Shipping costs updated.', 'woocommerce' ), 'notice' );

			do_action( 'woocommerce_calculated_shipping' );

		} catch ( Exception $e ) {
			if ( ! empty( $e ) ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Set the country for a shipping calculator submission that did not include one.
	 *
	 * A store that ships to one country can only mean that country. For any other store the address is returned unchanged, so the
	 * location is reset to the store base as before, and a developer notice explains how to supply the country.
	 *
	 * @param array $address Calculator address after the woocommerce_cart_calculate_shipping_address filter.
	 * @return array
	 */
	private static function maybe_set_calculator_fallback_country( $address ) {
		$shipping_countries = WC()->countries->get_shipping_countries();

		if ( 1 !== count( $shipping_countries ) ) {
			wc_doing_it_wrong(
				__CLASS__ . '::calculate_shipping',
				__( 'The shipping calculator did not submit a country, so the shipping location was reset to the store base. Only return false from woocommerce_shipping_calculator_enable_country when the store ships to a single country, or set the country with the woocommerce_cart_calculate_shipping_address filter.', 'woocommerce' ),
				'11.3.0'
			);
			return $address;
		}

		$country = (string) array_key_first( $shipping_countries );
		$states  = WC()->countries->get_states( $country );

		$address['country'] = $country;

		// The posted state may belong to another country, because the template builds its list from the customer's saved country.
		if ( is_array( $states ) && ! isset( $states[ $address['state'] ] ) ) {
			$address['state'] = '';
		}

		if ( $address['postcode'] ) {
			$address['postcode'] = wc_format_postcode( $address['postcode'], $country );
		}

		return $address;
	}

	/**
	 * Output the cart shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		if ( ! apply_filters( 'woocommerce_output_cart_shortcode_content', true ) ) {
			return;
		}

		// Constants.
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		$atts        = shortcode_atts( array(), $atts, 'woocommerce_cart' );
		$nonce_value = wc_get_var( $_REQUEST['woocommerce-shipping-calculator-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		// Update Shipping. Nonce check uses new value and old value (woocommerce-cart). @todo remove in 4.0.
		if ( ! empty( $_POST['calc_shipping'] ) && ( wp_verify_nonce( $nonce_value, 'woocommerce-shipping-calculator' ) || wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) ) {
			self::calculate_shipping();

			// Also calc totals before we check items so subtotals etc are up to date.
			WC()->cart->calculate_totals();
		}

		// Check cart items are valid.
		do_action( 'woocommerce_check_cart_items' );

		// Calc totals.
		WC()->cart->calculate_totals();

		if ( WC()->cart->is_empty() ) {
			wc_get_template( 'cart/cart-empty.php' );
		} else {
			wc_get_template( 'cart/cart.php' );
		}
	}
}
