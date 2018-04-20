<?php
/**
 * Personal data erasers.
 *
 * @since 3.4.0
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Privacy_Erasers Class.
 */
class WC_Privacy_Erasers {
	/**
	 * Registers the personal data eraser for WooCommerce data.
	 *
	 * @since 3.4.0
	 * @param array $erasers An array of personal data erasers.
	 * @return array An array of personal data erasers.
	 */
	public static function register( $erasers ) {
		$erasers[] = array(
			'eraser_friendly_name' => __( 'Customer Data', 'woocommerce' ),
			'callback'             => array( __CLASS__, 'customer_data_eraser' ),
		);
		$erasers[] = array(
			'eraser_friendly_name' => __( 'Customer Orders', 'woocommerce' ),
			'callback'             => array( __CLASS__, 'order_data_eraser' ),
		);
		$erasers[] = array(
			'eraser_friendly_name' => __( 'Customer Downloads', 'woocommerce' ),
			'callback'             => array( __CLASS__, 'download_data_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Finds and erases customer data by email address.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_data_eraser( $email_address, $page ) {
		$response = array(
			'messages'           => array(),
			'num_items_removed'  => 0,
			'num_items_retained' => 0,
			'done'               => true,
		);

		$user = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.

		if ( ! $user instanceof WP_User ) {
			return $response;
		}

		$customer = new WC_Customer( $user->ID );

		if ( ! $customer ) {
			return $response;
		}

		if ( 1 === $page ) {
			$props_to_erase = apply_filters( 'woocommerce_privacy_erase_customer_personal_data_props', array(
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
			), $customer );

			foreach ( $props_to_erase as $prop => $label ) {
				$erased = false;

				if ( is_callable( array( $customer, 'get_' . $prop ) ) && is_callable( array( $customer, 'set_' . $prop ) ) ) {
					$value = $customer->{"get_$prop"}( 'edit' );

					if ( $value ) {
						$customer->{"set_$prop"}( '' );
						$erased = true;
					}
				}

				$erased = apply_filters( 'woocommerce_privacy_erase_customer_personal_data_prop', $erased, $prop, $customer );

				if ( $erased ) {
					/* Translators: %s Prop name. */
					$response['messages'][] = sprintf( __( 'Removed customer "%s"', 'woocommerce' ), $label );
					$response['num_items_removed'] ++;
				}
			}

			$customer->save();
		}

		/**
		 * Allow extensions to remove data for this customer and adjust the response.
		 *
		 * @since 3.4.0
		 * @param array    $response Array resonse data. Must include messages, num_items_removed, num_items_retained, done.
		 * @param WC_Order $order A customer object.
		 */
		return apply_filters( 'woocommerce_privacy_erase_personal_data_customer', $response, $customer );
	}

	/**
	 * Finds and erases data which could be used to identify a person from WooCommerce data assocated with an email address.
	 *
	 * Orders are erased in blocks of 10 to avoid timeouts.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function order_data_eraser( $email_address, $page ) {
		$page            = (int) $page;
		$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$erasure_enabled = wc_string_to_bool( get_option( 'woocommerce_erasure_request_removes_order_data', 'no' ) );
		$response        = array(
			'messages'           => array(),
			'num_items_removed'  => 0,
			'num_items_retained' => 0,
			'done'               => false,
		);

		$order_query = array(
			'limit'    => 10,
			'page'     => $page,
			'customer' => array( $email_address ),
		);

		if ( $user instanceof WP_User ) {
			$order_query['customer'][] = (int) $user->ID;
		}

		$orders = wc_get_orders( $order_query );

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order ) {
				if ( apply_filters( 'woocommerce_privacy_erase_order_personal_data', $erasure_enabled, $order ) ) {
					WC_Privacy::remove_order_personal_data( $order );

					/* Translators: %s Order number. */
					$response['messages'][] = sprintf( __( 'Removed personal data from order %s.', 'woocommerce' ), $order->get_order_number() );
					$response['num_items_removed'] ++;
				} else {
					/* Translators: %s Order number. */
					$response['messages'][] = sprintf( __( 'Retained personal data in order %s due to settings.', 'woocommerce' ), $order->get_order_number() );
					$response['num_items_retained'] ++;
				}
			}
			$response['done'] = 10 > count( $orders );
		} else {
			$response['done'] = true;
		}

		return $response;
	}

	/**
	 * Finds and exports customer download logs by email address.
	 *
	 * @since 3.4.0
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function download_data_eraser( $email_address, $page ) {
		$page            = (int) $page;
		$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$erasure_enabled = wc_string_to_bool( get_option( 'woocommerce_erasure_request_removes_download_data', 'no' ) );
		$response        = array(
			'messages'           => array(),
			'num_items_removed'  => 0,
			'num_items_retained' => 0,
			'done'               => true,
		);

		$downloads_query = array(
			'limit'  => -1,
			'page'   => $page,
			'return' => 'ids',
		);

		if ( $user instanceof WP_User ) {
			$downloads_query['user_id'] = (int) $user->ID;
		} else {
			$downloads_query['user_email'] = $email_address;
		}

		$customer_download_data_store     = WC_Data_Store::load( 'customer-download' );
		$customer_download_log_data_store = WC_Data_Store::load( 'customer-download-log' );
		$downloads                        = $customer_download_data_store->get_downloads( $downloads_query );

		// Revoke download permissions.
		if ( apply_filters( 'woocommerce_privacy_erase_download_personal_data', $erasure_enabled, $email_address ) ) {
			if ( $user instanceof WP_User ) {
				$customer_download_data_store->delete_by_user_id( (int) $user->ID );
			} else {
				$customer_download_data_store->delete_by_user_email( $email_address );
			}
			$response['messages'][]        = __( 'Removed access to downloadable files.', 'woocommerce' );
			$response['num_items_removed'] = count( $downloads );
		} else {
			$response['messages'][]         = __( 'Retained access to downloadable files due to settings.', 'woocommerce' );
			$response['num_items_retained'] = count( $downloads );
		}

		return $response;
	}
}
