<?php
/**
 * Order Tracking Shortcode
 *
 * Lets a user see the status of an order by entering their order details.
 *
 * @package WooCommerce\Shortcodes\Order_Tracking
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode order tracking class.
 */
class WC_Shortcode_Order_Tracking {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		// Check cart class is loaded or abort.
		if ( is_null( WC()->cart ) ) {
			return;
		}

		$atts        = shortcode_atts( array(), $atts, 'woocommerce_order_tracking' );
		$nonce_value = wc_get_var( $_REQUEST['woocommerce-order-tracking-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( isset( $_REQUEST['orderid'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-order_tracking' ) ) {

			$order_id    = empty( $_REQUEST['orderid'] ) ? 0 : ltrim( wc_clean( wp_unslash( $_REQUEST['orderid'] ) ), '#' );
			$order_email = empty( $_REQUEST['order_email'] ) ? '' : sanitize_email( wp_unslash( $_REQUEST['order_email'] ) );

			if ( ! $order_id ) {
				$notice = wc_print_notice( __( 'Please enter a valid order ID', 'woocommerce' ), 'error', array(), true );
			} elseif ( ! $order_email ) {
				$notice = wc_print_notice( __( 'Please enter a valid email address', 'woocommerce' ), 'error', array(), true );
			} else {
				$order = wc_get_order( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $order_id ) );

				if ( $order && $order->get_id() && is_a( $order, 'WC_Order' ) && strtolower( $order->get_billing_email() ) === strtolower( $order_email ) ) {
					$customer_id = $order->get_customer_id();

					// The order ID and billing email prove nothing for account orders, so match the order-received page and ask the customer to log in.
					if ( $customer_id && get_current_user_id() !== $customer_id ) {
						echo '<div class="woocommerce-notices-wrapper">' . wc_print_notice( esc_html__( 'Please log in to your account to view this order.', 'woocommerce' ), 'notice', array(), true ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_print_notice() returns kses-filtered markup.
						woocommerce_login_form( array( 'redirect' => $order->get_view_order_url() ) );
						return;
					}

					do_action( 'woocommerce_track_order', $order->get_id() );

					// For guest orders the visitor may not be the shopper, so leave out links carrying the order key, paid content and the shopper's checkout note.
					if ( ! $customer_id ) {
						add_filter( 'woocommerce_order_downloads_table_show_downloads', '__return_false', PHP_INT_MAX );
						add_filter( 'woocommerce_my_account_my_orders_actions', '__return_empty_array', PHP_INT_MAX );
						add_filter( 'woocommerce_purchase_note_order_statuses', '__return_empty_array', PHP_INT_MAX );
						add_filter( 'woocommerce_order_get_customer_note', '__return_empty_string', PHP_INT_MAX );
					}

					wc_get_template(
						'order/tracking.php',
						array(
							'order' => $order,
						)
					);

					remove_filter( 'woocommerce_order_downloads_table_show_downloads', '__return_false', PHP_INT_MAX );
					remove_filter( 'woocommerce_my_account_my_orders_actions', '__return_empty_array', PHP_INT_MAX );
					remove_filter( 'woocommerce_purchase_note_order_statuses', '__return_empty_array', PHP_INT_MAX );
					remove_filter( 'woocommerce_order_get_customer_note', '__return_empty_string', PHP_INT_MAX );
					return;
				} else {
					$notice = wc_print_notice( __( 'Sorry, the order could not be found. Please contact us if you are having difficulty finding your order details.', 'woocommerce' ), 'error', array(), true );
				}
			}
		}

		if ( ! empty( $notice ) ) {
			echo '<div class="woocommerce-notices-wrapper">' . $notice . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_print_notice() returns kses-filtered markup.
		}

		wc_get_template( 'order/form-tracking.php' );
	}
}
