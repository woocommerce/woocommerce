<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store Interface
 *
 * Functions that must be defined by customer store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Customer_Data_Store_Interface {

	/**
	 * Deletes a customer from the database.
	 *
	 * @param WC_Customer
	 * @param int|null $reassign Who to reassign posts to.
	 */
	public function delete( &$data, $reassign = null );

	/**
	 * Gets the customers last order.
	 *
	 * @param WC_Customer
	 * @return WC_Order|false
	 */
	public function get_last_order( &$customer );

	/**
	 * Return the number of orders this customer has.
	 *
	 * @param WC_Customer
	 * @return integer
	 */
	public function get_order_count( &$customer );

	/**
	 * Return how much money this customer has spent.
	 *
	 * @param WC_Customer
	 * @return float
	 */
	public function get_total_spent( &$customer );
}
