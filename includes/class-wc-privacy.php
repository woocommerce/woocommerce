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
		// Add hooks here.
	}

	/**
	 * Anonymize/remove personal data for a given email address.
	 *
	 * @param string $email Email address.
	 */
	public static function remove_personal_data( $email ) {
		/**
		 * Order Data:
		 *
		 * Transaction ID
		 * Customer's IP Address and User Agent
		 * Billing Address First Name, Last Name, Company, Address Line 1, Address Line 2, City, Postcode/ZIP, Country, State/County, Phone, Email Address
		 * "Billing Fields" (_billing_address_index)
		 * Same as above for shipping
		 *
		 * Customer Data (meta):
		 *
		 * Billing and shipping addresses
		 *
		 * Misc:
		 *
		 * Downloadable Product User Email
		 * Download Log Entry User IP Address
		 * Carts for user ID/Sessions
		 * File based logs containing their email e.g. from webhooks
		 * Payment tokens?
		 */
	}

	/**
	 * Get personal data for a given email address. This can be used for exports.
	 *
	 * @param string $email Email address.
	 * @return array Array of personal data.
	 */
	public static function get_personal_data( $email ) {
		if ( ! is_email( $email ) ) {
			return array();
		}

		$personal_data = array();

		// Check if user has an ID in the DB to load stored personal data.
		$user = get_user_by( 'email', $email );
		if ( $user instanceof WP_User ) {
			$customer = new WC_Customer( $user->ID );
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
				$personal_data['Shipping First Name']      = $customer->get_shipping_first_name();
				$personal_data['Shipping Last Name']       = $customer->get_shipping_last_name();
				$personal_data['Shipping Company']         = $customer->get_shipping_company();
				$personal_data['Shipping Address 1']       = $customer->get_shipping_address_1();
				$personal_data['Shipping Address 2']       = $customer->get_shipping_address_2();
				$personal_data['Shipping City']            = $customer->get_shipping_city();
				$personal_data['Shipping Postal/Zip Code'] = $customer->get_shipping_postcode();
				$personal_data['Shipping State']           = $customer->get_shipping_state();
				$personal_data['Shipping Country']         = $customer->get_shipping_country();
				$personal_data['Shipping Phone']           = $customer->get_shipping_phone();
			}
		}

		// Retrieve all orders with billing email set to user's email.
		$orders = wc_get_orders( array(
			'billing_email' => $email,
			'limit'         => -1,
		) );

		if ( 0 < count( $orders ) ) {
			$personal_data['orders'] = array();
			foreach ( $orders as $order ) {
				$order_data = array(
					'Transaction ID'           => $order->get_order_number(),
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
					'Shipping First Name'      => $order->get_shipping_first_name(),
					'Shipping Last Name'       => $order->get_shipping_last_name(),
					'Shipping Company'         => $order->get_shipping_company(),
					'Shipping Address 1'       => $order->get_shipping_address_1(),
					'Shipping Address 2'       => $order->get_shipping_address_2(),
					'Shipping City'            => $order->get_shipping_city(),
					'Shipping Postal/Zip Code' => $order->get_shipping_postcode(),
					'Shipping State'           => $order->get_shipping_state(),
					'Shipping Country'         => $order->get_shipping_country(),
					'Shipping Phone'           => $order->get_shipping_phone(),
				);
				$personal_data['orders'][] = $order_data;
			}
		}
		return $personal_data;
	}

}

WC_Privacy::init();
