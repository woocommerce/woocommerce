<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for parameter-based Order querying.
 *
 * @version  3.1.0
 * @since    3.1.0
 * @package  WooCommerce/Classes
 * @category Class
 */
class WC_Order_Query extends WC_Object_Query {

	/**
	 * Valid query vars for orders.
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'               => array_keys( wc_get_order_statuses() ),
				'type'                 => wc_get_order_types( 'view-orders' ),
				'currency'             => '',
				'version'              => '',
				'prices_include_tax'   => '',
				'date_created'         => '',
				'date_modified'        => '',
				'discount_total'       => '',
				'discount_tax'         => '',
				'shipping_total'       => '',
				'shipping_tax'         => '',
				'cart_tax'             => '',
				'total'                => '',
				'total_tax'            => '',
				'customer_user'        => '',
				'order_key'            => '',
				'billing_first_name'   => '',
				'billing_last_name'    => '',
				'billing_company'      => '',
				'billing_address_1'    => '',
				'billing_address_2'    => '',
				'billing_city'         => '',
				'billing_state'        => '',
				'billing_postcode'     => '',
				'billing_country'      => '',
				'billing_email'        => '',
				'billing_phone'        => '',
				'shipping_first_name'  => '',
				'shipping_last_name'   => '',
				'shipping_company'     => '',
				'shipping_address_1'   => '',
				'shipping_address_2'   => '',
				'shipping_city'        => '',
				'shipping_state'       => '',
				'shipping_postcode'    => '',
				'shipping_country'     => '',
				'payment_method'       => '',
				'payment_method_title' => '',
				'transaction_id'       => '',
				'customer_ip_address'  => '',
				'customer_user_agent'  => '',
				'created_via'          => '',
				'customer_note'        => '',
				'date_completed'       => '',
				'date_paid'            => '',
			)
		);
	}

	/**
	 * Get orders matching the current query vars.
	 * @return array of WC_Order objects
	 */
	public function get_orders() {
		return WC_Data_Store::load( 'order' )->query_orders( $this->query_vars );
	}
}
