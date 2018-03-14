<?php
/**
 * Privacy/GDPR related functionality.
 *
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Privacy Class.
 */
class WC_Privacy {

	/**
	 * Init - hook into events.
	 */
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_data_exporter' ), 10 );
	}

	/**
	 * Registers the personal data exporter for comments.
	 *
	 * @param array $exporters An array of personal data exporters.
	 * @return array An array of personal data exporters.
	 */
	public static function register_data_exporter( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'WooCommerce Data', 'woocommerce' ),
			'callback'               => array( __CLASS__, 'data_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Finds and exports data which could be used to identify a person from WooCommerce data assocated with an email address.
	 *
	 * To split the export into manangeable chunks we'll export customer profile data on page 0, and orders from then on until done.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page, zero based.
	 * @return array An array of personal data in name value pairs
	 */
	public static function data_exporter( $email_address, $page ) {
		$personal_data = array();
		$done          = false;
		$user          = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.

		// Export customer data first.
		if ( 0 === $page ) {
			if ( $user instanceof WP_User ) {
				$personal_data = self::get_user_personal_data( $user );
			}
		} else { // Export orders - 10 at a time.
			$order_query = array(
				'limit' => 10,
				'page'  => $page,
			);

			if ( $user instanceof WP_User ) {
				$order_query['customer_id'] = $user->ID;
			} else {
				$order_query['billing_email'] = $email_address;
			}

			$orders = wc_get_orders( $order_query );

			if ( 0 < count( $orders ) ) {
				$personal_data['orders'] = array();

				foreach ( $orders as $order ) {
					$personal_data['orders'][] = self::get_order_personal_data( $order );
				}

				$done = 10 > count( $orders );
			} else {
				$done = true;
			}
		}

		return array(
			'data' => $personal_data,
			'done' => $done,
		);
	}

	/**
	 * Get personal data (key/value pairs) for a user object.
	 *
	 * @param WP_User $user user object.
	 * @return array
	 */
	protected static function get_user_personal_data( $user ) {
		$personal_data = array();
		$customer      = new WC_Customer( $user->ID );

		if ( $customer ) {
			$personal_data['Billing First Name']       = $customer->get_billing_first_name();
			$personal_data['Billing Last Name']        = $customer->get_billing_last_name();
			$personal_data['Billing Company']          = $customer->get_billing_company();
			$personal_data['Billing Address 1']        = $customer->get_billing_address_1();
			$personal_data['Billing Address 2']        = $customer->get_billing_address_2();
			$personal_data['Billing City']             = $customer->get_billing_city();
			$personal_data['Billing Postal/Zip Code']  = $customer->get_billing_postcode();
			$personal_data['Billing State']            = $customer->get_billing_state();
			$personal_data['Billing Country']          = $customer->get_billing_country();
			$personal_data['Billing Phone']            = $customer->get_billing_phone();
			$personal_data['Billing Email']            = $customer->get_billing_email();
			$personal_data['Shipping First Name']      = $customer->get_shipping_first_name();
			$personal_data['Shipping Last Name']       = $customer->get_shipping_last_name();
			$personal_data['Shipping Company']         = $customer->get_shipping_company();
			$personal_data['Shipping Address 1']       = $customer->get_shipping_address_1();
			$personal_data['Shipping Address 2']       = $customer->get_shipping_address_2();
			$personal_data['Shipping City']            = $customer->get_shipping_city();
			$personal_data['Shipping Postal/Zip Code'] = $customer->get_shipping_postcode();
			$personal_data['Shipping State']           = $customer->get_shipping_state();
			$personal_data['Shipping Country']         = $customer->get_shipping_country();
		}

		return array_filter( $personal_data );
	}

	/**
	 * Get personal data (key/value pairs) for an order object.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected static function get_order_personal_data( $order ) {
		return array_filter( array(
			'Order ID'                 => $order->get_id(),
			'Order Number'             => $order->get_order_number(),
			'IP Address'               => $order->get_customer_ip_address(),
			'User Agent'               => $order->get_customer_user_agent(),
			'Billing First Name'       => $order->get_billing_first_name(),
			'Billing Last Name'        => $order->get_billing_last_name(),
			'Billing Company'          => $order->get_billing_company(),
			'Billing Address 1'        => $order->get_billing_address_1(),
			'Billing Address 2'        => $order->get_billing_address_2(),
			'Billing City'             => $order->get_billing_city(),
			'Billing Postal/Zip Code'  => $order->get_billing_postcode(),
			'Billing State'            => $order->get_billing_state(),
			'Billing Country'          => $order->get_billing_country(),
			'Billing Phone'            => $order->get_billing_phone(),
			'Billing Email'            => $order->get_billing_email(),
			'Shipping First Name'      => $order->get_shipping_first_name(),
			'Shipping Last Name'       => $order->get_shipping_last_name(),
			'Shipping Company'         => $order->get_shipping_company(),
			'Shipping Address 1'       => $order->get_shipping_address_1(),
			'Shipping Address 2'       => $order->get_shipping_address_2(),
			'Shipping City'            => $order->get_shipping_city(),
			'Shipping Postal/Zip Code' => $order->get_shipping_postcode(),
			'Shipping State'           => $order->get_shipping_state(),
			'Shipping Country'         => $order->get_shipping_country(),
		) );
	}

	/**
	 * Anonymize/remove personal data for a given email address.
	 *
	 * @param string $email Email address.
	 */
	public static function remove_personal_data( $email ) {
		/**
		 * Personal Data:
		 *
		 *   - Everything exported above for orders and customers
		 *   - _billing_address_index - just an index for searching which needs clearing?
		 *   - _shipping_address_index - just an index for searching which needs clearing?
		 *
		 * Misc:
		 *
		 *   - Downloadable Product User Email (does not export becasue it matches order/user data).
		 *   - Download logs by user ID and IP address.
		 *   - File based logs containing email? Do search and clear if found.
		 *   - Payment tokens? Check if these need exporting/clearing. Based on User ID.
		 */
	}
}

WC_Privacy::init();
