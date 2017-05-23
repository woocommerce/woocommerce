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

		global $post;

		if ( ! empty( $_REQUEST['orderid'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-order_tracking' ) ) {

			$order_id    = empty( $_REQUEST['orderid'] ) ? 0 : esc_attr( $_REQUEST['orderid'] );
			$order_email = empty( $_REQUEST['order_email'] ) ? '' : esc_attr( $_REQUEST['order_email'] );

			if ( ! $order_id ) {
				wc_add_notice( __( 'Please enter a valid order ID', 'woocommerce' ), 'error' );
			} elseif ( ! $order_email ) {
				wc_add_notice( __( 'Please enter a valid order email', 'woocommerce' ), 'error' );
			} else {
				$order = wc_get_order( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $order_id ) );

				if ( $order && $order->get_id() && $order_email ) {
					if ( strtolower( $order->get_billing_email() ) == strtolower( $order_email ) ) {
						do_action( 'woocommerce_track_order', $order->get_id() );
						wc_get_template( 'order/tracking.php', array(
							'order' => $order,
						) );

						return;
					}
				} else {
					wc_add_notice( __( 'Sorry, we could not find that order ID in our database.', 'woocommerce' ), 'error' );
				}
			}
		}

		wc_print_notices();

		wc_get_template( 'order/form-tracking.php' );
	}
}
