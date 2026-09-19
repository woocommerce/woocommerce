<?php
/**
 * Customer Data Store Timeframe Interface
 *
 * @version 11.1.0
 * @package WooCommerce\Interface
 */

/**
 * Optional customer data store capability for paid-date total-spent queries.
 */
interface WC_Customer_Data_Store_Timeframe_Interface {

	/**
	 * Return how much money this customer has spent in a paid-date timeframe.
	 *
	 * @param WC_Customer $customer Customer object.
	 * @param array       $args Paid-date filters.
	 * @return string
	 */
	public function get_total_spent_for_timeframe( &$customer, $args = array() );
}
