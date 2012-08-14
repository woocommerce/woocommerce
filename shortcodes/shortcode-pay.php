<?php
/**
 * Pay Shortcode
 *
 * The pay page. Used for form based gateways to show payment forms and order info.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Pay
 * @version     1.6.4
 */

/**
 * Get the pay shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_pay( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_pay', $atts);
}


/**
 * Outputs the pay page - payment gateways can hook in here to show payment forms etc
 *
 * @access public
 * @return void
 */
function woocommerce_pay() {
	global $woocommerce;

	$woocommerce->nocache();

	do_action('before_woocommerce_pay');

	$woocommerce->show_messages();

	if ( isset($_GET['pay_for_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :

		// Pay for existing order
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		$order = new WC_Order( $order_id );

		if ($order->id == $order_id && $order->order_key == $order_key && in_array($order->status, array('pending', 'failed'))) :

			// Set customer location to order location
			if ($order->billing_country) $woocommerce->customer->set_country( $order->billing_country );
			if ($order->billing_state) $woocommerce->customer->set_state( $order->billing_state );
			if ($order->billing_postcode) $woocommerce->customer->set_postcode( $order->billing_postcode );

			// Show form
			woocommerce_get_template('checkout/form-pay.php', array('order' => $order));

		elseif (!in_array($order->status, array('pending', 'failed'))) :

			$woocommerce->add_error( __('Your order has already been paid for. Please contact us if you need assistance.', 'woocommerce') );
			$woocommerce->show_messages();

		else :

			$woocommerce->add_error( __('Invalid order.', 'woocommerce') );
			$woocommerce->show_messages();

		endif;

	else :

		// Pay for order after checkout step
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';

		if ($order_id > 0) :

			$order = new WC_Order( $order_id );

			if ($order->order_key == $order_key && in_array($order->status, array('pending', 'failed'))) :

				?>
				<ul class="order_details">
					<li class="order">
						<?php _e('Order:', 'woocommerce'); ?>
						<strong><?php echo $order->get_order_number(); ?></strong>
					</li>
					<li class="date">
						<?php _e('Date:', 'woocommerce'); ?>
						<strong><?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?></strong>
					</li>
					<li class="total">
						<?php _e('Total:', 'woocommerce'); ?>
						<strong><?php echo $order->get_formatted_order_total(); ?></strong>
					</li>
					<?php if ($order->payment_method_title) : ?>
					<li class="method">
						<?php _e('Payment method:', 'woocommerce'); ?>
						<strong><?php
							echo $order->payment_method_title;
						?></strong>
					</li>
					<?php endif; ?>
				</ul>

				<?php do_action( 'woocommerce_receipt_' . $order->payment_method, $order_id ); ?>

				<div class="clear"></div>
				<?php

			elseif (!in_array($order->status, array('pending', 'failed'))) :

				$woocommerce->add_error( __('Your order has already been paid for. Please contact us if you need assistance.', 'woocommerce') );
				$woocommerce->show_messages();

			endif;

		else :

			$woocommerce->add_error( __('Invalid order.', 'woocommerce') );
			$woocommerce->show_messages();

		endif;

	endif;

	do_action('after_woocommerce_pay');
}