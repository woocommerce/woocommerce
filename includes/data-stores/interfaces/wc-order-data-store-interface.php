<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Data Store Interface
 *
 * Functions that must be defined by order store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Order_Data_Store_Interface {
	/**
	 * Get amount already refunded.
	 *
	 * @param  WC_Order
	 * @return string
	 */
	public function get_total_refunded( $order );

	/**
	 * Get the total tax refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_tax_refunded( $order );

	/**
	 * Get the total shipping refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_shipping_refunded( $order );
}
