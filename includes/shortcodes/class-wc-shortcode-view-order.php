<?php
/**
 * View_Order Shortcode
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/View_Order
 * @version     2.0.0
 */

class WC_Shortcode_View_Order {

	/**
	 * Get the shortcode content.
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
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

		if ( ! is_user_logged_in() ) return;

		extract( shortcode_atts( array(
	    	'order_count' => 10
		), $atts ) );

		$user_id      	= get_current_user_id();
		$order_id		= ( isset( $_GET['order'] ) ) ? $_GET['order'] : 0;
		$order 			= new WC_Order( $order_id );

		if ( $order_id == 0 ) {
			woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => 'all' == $order_count ? -1 : $order_count ) );
			return;
		}

		if ( !current_user_can( 'view_order', $order_id ) ) {
			echo '<div class="woocommerce-error">' . __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . get_permalink( woocommerce_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', 'woocommerce' ) . '</a>' . '</div>';
			return;
		}

		$status = get_term_by('slug', $order->status, 'shop_order_status');

		woocommerce_get_template( 'myaccount/view-order.php', array(
			'order_id'	=> $order_id,
			'order'		=> $order,
			'status'	=> $status
		) );
	}
}