<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Tracking Shortcode
 *
 * Lets a user see the status of an order by entering their order details.
 *
 * @author   WooThemes
 * @category Shortcodes
 * @package  WooCommerce/Shortcodes/Order_Tracking
 * @version  3.0.0
 */
class WC_Shortcode_Order_Tracking {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
		// Check cart class is loaded or abort
		if ( is_null( WC()->cart ) ) {
			return;
		}

		extract( shortcode_atts( array(), $atts, 'woocommerce_order_tracking' ) );

		if ( isset( $_REQUEST['orderid'], $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-order_tracking' ) ) {

			$order_id    = empty( $_REQUEST['orderid'] ) ? 0 : wc_clean( ltrim( $_REQUEST['orderid'], '#' ) );
			$order_email = empty( $_REQUEST['order_email'] ) ? '' : sanitize_email( $_REQUEST['order_email'] );

			if ( ! $order_id ) {
				wc_add_notice( __( 'Please enter a valid order ID', 'woocommerce' ), 'error' );
			} elseif ( ! $order_email ) {
				wc_add_notice( __( 'Please enter a valid email address', 'woocommerce' ), 'error' );
			} else {
				$order = wc_get_order( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $order_id ) );

				if ( $order && $order->get_id() && strtolower( $order->get_billing_email() ) === strtolower( $order_email ) ) {
					do_action( 'woocommerce_track_order', $order->get_id() );
					wc_get_template( 'order/tracking.php', array(
						'order' => $order,
					) );
					return;
				} else {
					wc_add_notice( __( 'Sorry, the order could not be found. Please contact us if you are having difficulty finding your order details.', 'woocommerce' ), 'error' );
				}
			}
		}

		wc_print_notices();

		wc_get_template( 'order/form-tracking.php' );
	}
}
