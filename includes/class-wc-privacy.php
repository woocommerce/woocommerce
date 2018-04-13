<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @since 3.4.0
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
		// We need to ensure we're using a version of WP with GDPR support.
		if ( ! function_exists( 'wp_privacy_anonymize_data' ) ) {
			return;
		}
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_data_exporters' ), 10 );
		add_action( 'woocommerce_remove_order_personal_data', array( __CLASS__, 'remove_order_personal_data' ) );
		add_filter( 'wp_privacy_anonymize_data', array( __CLASS__, 'privacy_anonymize_data_custom_types' ), 10, 3 );
	}

	/**
	 * Registers the personal data exporter for comments.
	 *
	 * @since 3.4.0
	 * @param array $exporters An array of personal data exporters.
	 * @return array An array of personal data exporters.
	 */
	public static function register_data_exporters( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'WooCommerce Customer Data', 'woocommerce' ),
			'callback'               => array( __CLASS__, 'customer_data_exporter' ),
		);
		$exporters[] = array(
			'exporter_friendly_name' => __( 'WooCommerce Order Data', 'woocommerce' ),
			'callback'               => array( __CLASS__, 'order_data_exporter' ),
		);
		$exporters[] = array(
			'exporter_friendly_name' => __( 'WooCommerce Download Logs', 'woocommerce' ),
			'callback'               => array( __CLASS__, 'download_log_data_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Finds and exports customer data by email address.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_data_exporter( $email_address, $page ) {
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export = array();

		if ( $user instanceof WP_User ) {
			$data_to_export[] = array(
				'group_id'    => 'woocommerce_customer',
				'group_label' => __( 'Customer Data', 'woocommerce' ),
				'item_id'     => 'user',
				'data'        => self::get_user_personal_data( $user ),
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Finds and exports data which could be used to identify a person from WooCommerce data assocated with an email address.
	 *
	 * Orders are exported in blocks of 10 to avoid timeouts.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function order_data_exporter( $email_address, $page ) {
		$done           = false;
		$page           = (int) $page;
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export = array();
		$order_query    = array(
			'limit' => 10,
			'page'  => $page,
		);

		if ( $user instanceof WP_User ) {
			$order_query['customer_id'] = (int) $user->ID;
		} else {
			$order_query['billing_email'] = $email_address;
		}

		$orders = wc_get_orders( $order_query );

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order ) {
				$data_to_export[] = array(
					'group_id'    => 'woocommerce_orders',
					'group_label' => __( 'Orders', 'woocommerce' ),
					'item_id'     => 'order-' . $order->get_id(),
					'data'        => self::get_order_personal_data( $order ),
				);
			}
			$done = 10 > count( $orders );
		} else {
			$done = true;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Finds and exports customer download logs by email address.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function download_log_data_exporter( $email_address, $page ) {
		// @todo
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	/**
	 * Get personal data (key/value pairs) for a user object.
	 *
	 * @since 3.4.0
	 * @param WP_User $user user object.
	 * @return array
	 */
	protected static function get_user_personal_data( $user ) {
		$personal_data   = array();
		$customer        = new WC_Customer( $user->ID );
		$props_to_export = array(
			'billing_first_name'  => __( 'Billing First Name', 'woocommerce' ),
			'billing_last_name'   => __( 'Billing Last Name', 'woocommerce' ),
			'billing_company'     => __( 'Billing Company', 'woocommerce' ),
			'billing_address_1'   => __( 'Billing Address 1', 'woocommerce' ),
			'billing_address_2'   => __( 'Billing Address 2', 'woocommerce' ),
			'billing_city'        => __( 'Billing City', 'woocommerce' ),
			'billing_postcode'    => __( 'Billing Postal/Zip Code', 'woocommerce' ),
			'billing_state'       => __( 'Billing State', 'woocommerce' ),
			'billing_country'     => __( 'Billing Country', 'woocommerce' ),
			'billing_phone'       => __( 'Phone Number', 'woocommerce' ),
			'billing_email'       => __( 'Email Address', 'woocommerce' ),
			'shipping_first_name' => __( 'Shipping First Name', 'woocommerce' ),
			'shipping_last_name'  => __( 'Shipping Last Name', 'woocommerce' ),
			'shipping_company'    => __( 'Shipping Company', 'woocommerce' ),
			'shipping_address_1'  => __( 'Shipping Address 1', 'woocommerce' ),
			'shipping_address_2'  => __( 'Shipping Address 2', 'woocommerce' ),
			'shipping_city'       => __( 'Shipping City', 'woocommerce' ),
			'shipping_postcode'   => __( 'Shipping Postal/Zip Code', 'woocommerce' ),
			'shipping_state'      => __( 'Shipping State', 'woocommerce' ),
			'shipping_country'    => __( 'Shipping Country', 'woocommerce' ),
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
		$personal_data = apply_filters( 'woocommerce_privacy_export_personal_data_customer', $personal_data, $customer );

		return $personal_data;
	}

	/**
	 * Get personal data (key/value pairs) for an order object.
	 *
	 * @since 3.4.0
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected static function get_order_personal_data( $order ) {
		$personal_data   = array();
		$props_to_export = array(
			'order_number'               => __( 'Order Number', 'woocommerce' ),
			'date_created'               => __( 'Order Date', 'woocommerce' ),
			'total'                      => __( 'Order Total', 'woocommerce' ),
			'items'                      => __( 'Items Purchased', 'woocommerce' ),
			'customer_ip_address'        => __( 'IP Address', 'woocommerce' ),
			'customer_user_agent'        => __( 'Browser User Agent', 'woocommerce' ),
			'formatted_billing_address'  => __( 'Billing Address', 'woocommerce' ),
			'formatted_shipping_address' => __( 'Shipping Address', 'woocommerce' ),
			'billing_phone'              => __( 'Phone Number', 'woocommerce' ),
			'billing_email'              => __( 'Email Address', 'woocommerce' ),
		);

		foreach ( $props_to_export as $prop => $name ) {
			switch ( $prop ) {
				case 'items':
					$item_names = array();
					foreach ( $order->get_items() as $item ) {
						$item_names[] = $item->get_name() . ' x ' . $item->get_quantity();
					}
					$value = implode( ', ', $item_names );
					break;
				case 'date_created':
					$value = wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) );
					break;
				case 'formatted_billing_address':
				case 'formatted_shipping_address':
					$value = preg_replace( '#<br\s*/?>#i', ', ', $order->{"get_$prop"}() );
					break;
				default:
					$value = $order->{"get_$prop"}();
					break;
			}

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
		 * @param array    $personal_data Array of name value pairs to expose in the export.
		 * @param WC_Order $order An order object.
		 */
		$personal_data = apply_filters( 'woocommerce_privacy_export_personal_data_order', $personal_data, $order );

		return $personal_data;
	}

	/**
	 * Anonymize/remove personal data for a given EMAIL ADDRESS. This user may not have an account.
	 *
	 * Note; this is separate to account deletion. WooCommerce handles account deletion/cleanup elsewhere.
	 * This logic is simply to clean up data for guest users.
	 *
	 * @param string $email_address Customer email address.
	 */
	public static function remove_personal_data( $email_address ) {
		$user        = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB.
		$has_account = $user instanceof WP_User;

		// Remove personal data from the user's orders. @todo add option for this.
		$order_query = array(
			'limit' => -1,
		);

		if ( $has_account ) {
			$order_query['customer_id'] = (int) $user->ID;
		} else {
			$order_query['billing_email'] = $email_address;
		}

		$orders = wc_get_orders( $order_query );

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order ) {
				self::remove_order_personal_data( $order );
			}
		}

		// Revoke things such as download permissions for this email if it's a guest account. This is handled elsewhere for user accounts on delete.
		if ( ! $has_account ) {
			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_user_email( $email_address );
		} else {
			self::remove_customer_personal_data( $user );
		}

		/**
		 * Allow extensions to remove their own personal data for this customer.
		 *
		 * @since 3.4.0
		 * @param string $email_address Customer email address.
		 */
		do_action( 'woocommerce_privacy_remove_personal_data', $email_address );
	}

	/**
	 * Remove personal data specific to WooCommerce from a user object.
	 *
	 * @param WP_User $user user object.
	 */
	protected static function remove_customer_personal_data( $user ) {
		$customer        = new WC_Customer( $user->ID );
		$anonymized_data = array();

		/**
		 * Expose props and data types we'll be anonymizing.
		 *
		 * @since 3.4.0
		 * @param array       $props Keys are the prop names, values are the data type we'll be passing to wp_privacy_anonymize_data().
		 * @param WC_Customer $customer A customer object.
		 */
		$props_to_remove = apply_filters( 'woocommerce_privacy_remove_customer_personal_data_props', array(
			'billing_first_name'  => 'address',
			'billing_last_name'   => 'address',
			'billing_company'     => 'address',
			'billing_address_1'   => 'address',
			'billing_address_2'   => 'address',
			'billing_city'        => 'address',
			'billing_postcode'    => 'address',
			'billing_state'       => 'address',
			'billing_country'     => 'address',
			'billing_phone'       => 'phone',
			'billing_email'       => 'email',
			'shipping_first_name' => 'address',
			'shipping_last_name'  => 'address',
			'shipping_company'    => 'address',
			'shipping_address_1'  => 'address',
			'shipping_address_2'  => 'address',
			'shipping_city'       => 'address',
			'shipping_postcode'   => 'address',
			'shipping_state'      => 'address',
			'shipping_country'    => 'address',
		), $customer );

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value in edit context.
				$value = $customer->{"get_$prop"}( 'edit' );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) ) {
					continue;
				}

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 *
				 * @since 3.4.0
				 * @param bool        $anonymized_data Value of this prop after anonymization.
				 * @param string      $prop Name of the prop being removed.
				 * @param string      $value Current value of the data.
				 * @param string      $data_type Type of data.
				 * @param WC_Customer $customer A customer object.
				 */
				$anonymized_data[ $prop ] = apply_filters( 'woocommerce_privacy_remove_personal_data_customer_prop_value', wp_privacy_anonymize_data( $data_type, $value ), $prop, $value, $data_type, $customer );
			}
		}

		// Set all new props and persist the new data to the database.
		$customer->set_props( $anonymized_data );
		$customer->save();

		/**
		 * Allow extensions to remove their own personal data for this customer.
		 *
		 * @since 3.4.0
		 * @param WC_Customer $customer A customer object.
		 * @param WP_User     $user User object.
		 */
		do_action( 'woocommerce_privacy_remove_customer_personal_data', $customer, $user );
	}

	/**
	 * Remove personal data specific to WooCommerce from an order object.
	 *
	 * Note; this will hinder order processing for obvious reasons!
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function remove_order_personal_data( $order ) {
		$anonymized_data = array();

		/**
		 * Expose props and data types we'll be anonymizing.
		 *
		 * @since 3.4.0
		 * @param array    $props Keys are the prop names, values are the data type we'll be passing to wp_privacy_anonymize_data().
		 * @param WC_Order $order A customer object.
		 */
		$props_to_remove = apply_filters( 'woocommerce_privacy_remove_order_personal_data_props', array(
			'customer_ip_address' => 'ip',
			'customer_user_agent' => 'text',
			'billing_first_name'  => 'text',
			'billing_last_name'   => 'text',
			'billing_company'     => 'text',
			'billing_address_1'   => 'text',
			'billing_address_2'   => 'text',
			'billing_city'        => 'text',
			'billing_postcode'    => 'text',
			'billing_state'       => 'address_state',
			'billing_country'     => 'address_country',
			'billing_phone'       => 'phone',
			'billing_email'       => 'email',
			'shipping_first_name' => 'text',
			'shipping_last_name'  => 'text',
			'shipping_company'    => 'text',
			'shipping_address_1'  => 'text',
			'shipping_address_2'  => 'text',
			'shipping_city'       => 'text',
			'shipping_postcode'   => 'text',
			'shipping_state'      => 'address_state',
			'shipping_country'    => 'address_country',
		), $order );

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value in edit context.
				$value = $order->{"get_$prop"}( 'edit' );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue;
				}

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 *
				 * @since 3.4.0
				 * @param bool     $anonymized_data Value of this prop after anonymization.
				 * @param string   $prop Name of the prop being removed.
				 * @param string   $value Current value of the data.
				 * @param string   $data_type Type of data.
				 * @param WC_Order $order An order object.
				 */
				$anonymized_data[ $prop ] = apply_filters( 'woocommerce_privacy_remove_order_personal_data_prop_value', wp_privacy_anonymize_data( $data_type, $value ), $prop, $value, $data_type, $order );
			}
		}

		// Set all new props and persist the new data to the database.
		$order->set_props( $anonymized_data );
		$order->save();

		/**
		 * Allow extensions to remove their own personal data for this order.
		 *
		 * @since 3.4.0
		 * @param WC_Order $order A customer object.
		 */
		do_action( 'woocommerce_privacy_remove_order_personal_data', $order );

		// Add note that this event occured.
		$order->add_order_note( __( 'Personal data removed.', 'woocommerce' ) );
	}

	/**
	 * Handle some custom types of data and anonymize them.
	 *
	 * @param string $anonymous Anonymized string.
	 * @param string $type Type of data.
	 * @param string $data The data being anonymized.
	 * @return string Anonymized string.
	 */
	public static function privacy_anonymize_data_custom_types( $anonymous, $type, $data ) {
		switch ( $type ) {
			case 'address_state':
			case 'address_country':
				$anonymous = ''; // Empty string - we don't want to store anything after removal.
				break;
			case 'phone':
				$anonymous = preg_replace( '/\d/u', '0', $data );
				break;
		}
		return $anonymous;
	}
}

WC_Privacy::init();
