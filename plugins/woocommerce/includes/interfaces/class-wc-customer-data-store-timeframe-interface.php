<?php
/**
 * Customer Data Store Timeframe Interface
 *
 * @version 11.4.0
 * @package WooCommerce\Interface
 */

/**
 * WC Customer Data Store Timeframe Interface
 *
 * Optional capability for customer data stores that can calculate
 * total spent values restricted to a paid-date timeframe. Data stores
 * are not required to implement this interface; stores that don't are
 * served by the core data store fallback in wc_get_customer_total_spent().
 *
 * @version  11.4.0
 */
interface WC_Customer_Data_Store_Timeframe_Interface {

	/**
	 * Return how much money this customer has spent within a paid-date timeframe.
	 *
	 * @param WC_Customer $customer Customer object.
	 * @param array       $args     Optional timeframe arguments. Supports the exclusive
	 *                              `before` and `after` paid-date filters, each accepted
	 *                              as a date string, a Unix timestamp, or a DateTimeInterface
	 *                              object. With no filters, this must return the same value
	 *                              as get_total_spent().
	 * @return string
	 */
	public function get_total_spent_for_timeframe( &$customer, $args = array() );
}
