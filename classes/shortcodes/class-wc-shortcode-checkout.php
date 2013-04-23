<?php
/**
 * Checkout Shortcode
 *
 * Used on the checkout page, the checkout shortcode displays the checkout process.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Checkout
 * @version     2.0.0
 */

class WC_Shortcode_Checkout {

	/**
	 * Get the shortcode content.
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce;

		// Check checkout is configured correctly
		if ( current_user_can( 'manage_options' ) ) {
			$pay_page_id    = woocommerce_get_page_id( 'pay' );
			$thanks_page_id = woocommerce_get_page_id( 'thanks' );
			$pay_page       = get_permalink( $pay_page_id );
			$thanks_page    = get_permalink( $thanks_page_id );

			if ( ! $pay_page_id || ! $thanks_page_id || empty( $pay_page ) || empty( $thanks_page ) )
				$woocommerce->add_error( sprintf( __( 'WooCommerce Config Error: The checkout thanks/pay pages are missing - these pages are required for the checkout to function correctly. Please configure the pages <a href="%s">here</a>.', 'woocommerce' ), admin_url( 'admin.php?page=woocommerce_settings&tab=pages' ) ) );
		}

		// Show non-cart errors
		$woocommerce->show_messages();

		// Check cart has contents
		if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
			return;

		// Calc totals
		$woocommerce->cart->calculate_totals();

		// Check cart contents for errors
		do_action('woocommerce_check_cart_items');

		// Get checkout object
		$checkout = $woocommerce->checkout();

		if ( empty( $_POST ) && $woocommerce->error_count() > 0 ) {

			woocommerce_get_template( 'checkout/cart-errors.php', array( 'checkout' => $checkout ) );

		} else {

			$non_js_checkout = ! empty( $_POST['woocommerce_checkout_update_totals'] ) ? true : false;

			if ( $woocommerce->error_count() == 0 && $non_js_checkout )
				$woocommerce->add_message( __( 'The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'woocommerce' ) );

			woocommerce_get_template( 'checkout/form-checkout.php', array( 'checkout' => $checkout ) );

		}
	}
}