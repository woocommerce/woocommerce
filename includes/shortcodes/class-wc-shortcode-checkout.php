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
		return $woocommerce->get_helper( 'shortcode' )->shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce, $wp;

		// Backwards compat with old pay and thanks link arguments
		if ( isset( $_GET['order'] ) && isset( $_GET['key'] ) ) {
			_deprecated_argument( __CLASS__ . '->' . __FUNCTION__, '2.1', '"order" is no longer used to pass an order ID. Use the order-pay or order-received endpoint instead.' );

			// Get the order to work out what we are showing
			$order_id             = absint( $_GET['order'] );
			$order                = new WC_Order( $order_id );

			if ( $order->status == 'pending' )
				$wp->query_vars['order-pay'] = absint( $_GET['order'] );
			else
				$wp->query_vars['order-received'] = absint( $_GET['order'] );
		}

		// Handle checkout actions
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {

			self::order_pay( $wp->query_vars['order-pay'] );

		} elseif ( isset( $wp->query_vars['order-received'] ) ) {

			self::order_received( $wp->query_vars['order-received'] );

		} else {

			self::checkout();

		}
	}

	/**
	 * Show the pay page
	 */
	private function order_pay( $order_id ) {
		global $woocommerce;

		do_action( 'before_woocommerce_pay' );

		wc_print_messages();

		$order_id = absint( $order_id );

		// Handle payment
		if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && $order_id ) {

			// Pay for existing order
			$order_key            = urldecode( $_GET[ 'key' ] );
			$order_id             = absint( $order_id );
			$order                = new WC_Order( $order_id );
			$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order );

			if ( $order->id == $order_id && $order->order_key == $order_key && in_array( $order->status, $valid_order_statuses ) ) {

				// Set customer location to order location
				if ( $order->billing_country )
					$woocommerce->customer->set_country( $order->billing_country );
				if ( $order->billing_state )
					$woocommerce->customer->set_state( $order->billing_state );
				if ( $order->billing_postcode )
					$woocommerce->customer->set_postcode( $order->billing_postcode );

				// Show form
				woocommerce_get_template( 'checkout/form-pay.php', array( 'order' => $order ) );

			} elseif ( ! in_array( $order->status, $valid_order_statuses ) ) {

				wc_add_error( __( 'Your order has already been paid for. Please contact us if you need assistance.', 'woocommerce' ) );
				wc_print_messages();

			} else {

				wc_add_error( __( 'Invalid order.', 'woocommerce' ) );
				wc_print_messages();

			}

		} elseif ( $order_id ) {

			// Pay for order after checkout step
			$order_key            = isset( $_GET['key'] ) ? woocommerce_clean( $_GET['key'] ) : '';
			$order                = new WC_Order( $order_id );
			$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order );

			if ( $order->order_key == $order_key && in_array( $order->status, $valid_order_statuses ) ) {

				?>
				<ul class="order_details">
					<li class="order">
						<?php _e( 'Order:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_order_number(); ?></strong>
					</li>
					<li class="date">
						<?php _e( 'Date:', 'woocommerce' ); ?>
						<strong><?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?></strong>
					</li>
					<li class="total">
						<?php _e( 'Total:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_formatted_order_total(); ?></strong>
					</li>
					<?php if ($order->payment_method_title) : ?>
					<li class="method">
						<?php _e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php
							echo $order->payment_method_title;
						?></strong>
					</li>
					<?php endif; ?>
				</ul>

				<?php do_action( 'woocommerce_receipt_' . $order->payment_method, $order_id ); ?>

				<div class="clear"></div>
				<?php

			} elseif ( ! in_array( $order->status, $valid_order_statuses ) ) {

				wc_add_error( __( 'Your order has already been paid for. Please contact us if you need assistance.', 'woocommerce' ) );
				wc_print_messages();

			}

		} else {

			wc_add_error( __( 'Invalid order.', 'woocommerce' ) );
			wc_print_messages();

		}

		do_action( 'after_woocommerce_pay' );
	}

	/**
	 * Show the thanks page
	 */
	private function order_received( $order_id = 0 ) {
		global $woocommerce;

		wc_print_messages();

		$order = false;

		// Get the order
		$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $order_id ) );
		$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : woocommerce_clean( $_GET['key'] ) );

		if ( $order_id > 0 ) {
			$order = new WC_Order( $order_id );
			if ( $order->order_key != $order_key )
				unset( $order );
		}

		// Empty awaiting payment session
		unset( $woocommerce->session->order_awaiting_payment );

		woocommerce_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
	}

	/**
	 * Show the checkout
	 */
	private function checkout() {
		global $woocommerce;

		// Show non-cart errors
		wc_print_messages();

		// Check cart has contents
		if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
			return;

		// Calc totals
		$woocommerce->cart->calculate_totals();

		// Check cart contents for errors
		do_action('woocommerce_check_cart_items');

		// Get checkout object
		$checkout = $woocommerce->checkout();

		if ( empty( $_POST ) && wc_error_count() > 0 ) {

			woocommerce_get_template( 'checkout/cart-errors.php', array( 'checkout' => $checkout ) );

		} else {

			$non_js_checkout = ! empty( $_POST['woocommerce_checkout_update_totals'] ) ? true : false;

			if ( wc_error_count() == 0 && $non_js_checkout )
				wc_add_message( __( 'The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'woocommerce' ) );

			woocommerce_get_template( 'checkout/form-checkout.php', array( 'checkout' => $checkout ) );

		}
	}
}