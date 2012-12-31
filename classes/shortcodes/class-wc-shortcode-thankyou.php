<?php
/**
 * Thankyou Shortcode
 *
 * The thankyou page displays after successful checkout and can be hooked into by payment gateways.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Thankyou
 * @version     2.0.0
 */

class WC_Shortcode_Thankyou {

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

		$woocommerce->nocache();

		$woocommerce->show_messages();

		$order = false;

		// Pay for order after checkout step
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';

		// Empty awaiting payment session
		unset( $woocommerce->session->order_awaiting_payment );

		if ($order_id > 0) :
			$order = new WC_Order( $order_id );
			if ($order->order_key != $order_key) :
				unset($order);
			endif;
		endif;

		woocommerce_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
	}
}