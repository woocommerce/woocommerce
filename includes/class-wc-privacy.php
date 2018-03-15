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
		$page          = (int) $page;
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
				foreach ( $orders as $order ) {
					$personal_data = array_merge( $personal_data, self::get_order_personal_data( $order ) );
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
		$personal_data   = array();
		$customer        = new WC_Customer( $user->ID );
		$props_to_export = array(
			'billing_first_name'  => 'Billing First Name',
			'billing_last_name'   => 'Billing Last Name',
			'billing_company'     => 'Billing Company',
			'billing_address_1'   => 'Billing Address 1',
			'billing_address_2'   => 'Billing Address 2',
			'billing_city'        => 'Billing City',
			'billing_postcode'    => 'Billing Postal/Zip Code',
			'billing_state'       => 'Billing State',
			'billing_country'     => 'Billing Country',
			'billing_phone'       => 'Billing Phone',
			'billing_email'       => 'Billing Email',
			'shipping_first_name' => 'Shipping First Name',
			'shipping_last_name'  => 'Shipping Last Name',
			'shipping_company'    => 'Shipping Company',
			'shipping_address_1'  => 'Shipping Address 1',
			'shipping_address_2'  => 'Shipping Address 2',
			'shipping_city'       => 'Shipping City',
			'shipping_postcode'   => 'Shipping Postal/Zip Code',
			'shipping_state'      => 'Shipping State',
			'shipping_country'    => 'Shipping Country',
		);

		foreach ( $props_to_export as $prop => $description ) {
			$value = $customer->{"get_$prop"}( 'edit' );

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $description,
					'value' => $value,
				);
			}
		}

		/**
		 * Allow extensions to register their own personal data for this customer for the export.
		 *
		 * @since 3.4.0
		 * @param array    $personal_data Array of name value pairs.
		 * @param WC_Order $order A customer object.
		 */
		$personal_data = apply_filters( 'woocommerce_personal_data_export_customer', $personal_data, $customer );

		return $personal_data;
	}

	/**
	 * Get personal data (key/value pairs) for an order object.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected static function get_order_personal_data( $order ) {
		$personal_data   = array();
		$props_to_export = array(
			'customer_ip_address'        => 'IP Address',
			'customer_user_agent'        => 'User Agent',
			'formatted_billing_address'  => 'Billing Address',
			'formatted_shipping_address' => 'Shipping Address',
			'billing_phone'              => 'Billing Phone',
			'billing_email'              => 'Billing Email',
		);

		foreach ( $props_to_export as $prop => $description ) {
			/* translators: %1$d: ID of an order, %2$s: Description of an order field */
			$name  = sprintf( __( 'Order %1$d: %2$s', 'woocommerce' ), $order->get_id(), $description );
			$value = $order->{"get_$prop"}( 'edit' );

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		/**
		 * Allow extensions to register their own personal data for this order for the export.
		 *
		 * @since 3.4.0
		 * @param array    $personal_data Array of name value pairs.
		 * @param WC_Order $order An order object.
		 */
		$personal_data = apply_filters( 'woocommerce_personal_data_export_order', $personal_data, $order );

		return $personal_data;
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
