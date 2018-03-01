<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store Interface
 *
 * Functions that must be defined by customer store classes.
 *
 * @version  3.0.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Customer_Data_Store_Interface {

	/**
	 * Gets the customers last order.
	 *
	 * @param WC_Customer &$customer
	 * @return WC_Order|false
	 */
	public function get_last_order( &$customer );

	/**
	 * Return the number of orders this customer has.
	 *
	 * @param WC_Customer &$customer
	 * @return integer
	 */
	public function get_order_count( &$customer );

	/**
	 * Return how much money this customer has spent.
	 *
	 * @param WC_Customer &$customer
	 * @return float
	 */
	public function get_total_spent( &$customer );
}
