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
	 * Background process to clean up orders.
	 *
	 * @var WC_Privacy_Background_Process
	 */
	protected static $background_process;

	/**
	 * Init - hook into events.
	 */
	public static function init() {
		self::$background_process = new WC_Privacy_Background_Process();

		// Cleanup orders daily - this is a callback on a daily cron event.
		add_action( 'woocommerce_cleanup_orders', array( __CLASS__, 'order_cleanup_process' ) );

		// This hook registers WooCommerce data exporters.
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_data_exporters' ), 10 );

		// When this is fired, data is removed in a given order. Called from bulk actions.
		add_action( 'woocommerce_remove_order_personal_data', array( __CLASS__, 'remove_order_personal_data' ) );

		// Handles custom anonomization types not included in core.
		add_filter( 'wp_privacy_anonymize_data', array( __CLASS__, 'anonymize_custom_data_types' ), 10, 3 );
	}

	/**
	 * For a given query trash all matches.
	 *
	 * @since 3.4.0
	 * @param array $query Query array to pass to wc_get_orders().
	 * @return int Count of orders that were trashed.
	 */
	protected static function trash_orders_query( $query ) {
		$orders = wc_get_orders( $query );
		$count  = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				$order->delete( false );
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * For a given query, anonymize all matches.
	 *
	 * @since 3.4.0
	 * @param array $query Query array to pass to wc_get_orders().
	 * @return int Count of orders that were anonymized.
	 */
	protected static function anonymize_orders_query( $query ) {
		$orders = wc_get_orders( $query );
		$count  = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				self::remove_order_personal_data( $order );
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Spawn events for order cleanup.
	 */
	public static function order_cleanup_process() {
		self::$background_process->push_to_queue( array( 'task' => 'trash_pending_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_failed_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_cancelled_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'anonymize_completed_orders' ) );
		self::$background_process->save()->dispatch();
	}

	/**
	 * Find and trash old orders.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_pending_orders( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_pending_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-pending',
		) );
	}

	/**
	 * Find and trash old orders.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_failed_orders( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_failed_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-failed',
		) );
	}

	/**
	 * Find and trash old orders.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_cancelled_orders( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_cancelled_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-cancelled',
		) );
	}

	/**
	 * Anonymize old completed orders from guests.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @param  int $page Page to process.
	 * @return int Number of orders processed.
	 */
	public static function anonymize_completed_orders( $limit = 20, $page = 1 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_anonymize_completed_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::anonymize_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-completed',
			'anonymized'   => false,
			'customer_id'  => 0,
		) );
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
			'exporter_friendly_name' => __( 'WooCommerce Downloads', 'woocommerce' ),
			'callback'               => array( __CLASS__, 'download_data_exporter' ),
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
	public static function download_data_exporter( $email_address, $page ) {
		$done            = false;
		$page            = (int) $page;
		$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export  = array();
		$downloads_query = array(
			'limit' => 10,
			'page'  => $page,
		);

		if ( $user instanceof WP_User ) {
			$downloads_query['user_id'] = (int) $user->ID;
		} else {
			$downloads_query['user_email'] = $email_address;
		}

		$customer_download_data_store     = WC_Data_Store::load( 'customer-download' );
		$customer_download_log_data_store = WC_Data_Store::load( 'customer-download-log' );
		$downloads                        = $customer_download_data_store->get_downloads( $downloads_query );

		if ( 0 < count( $downloads ) ) {
			foreach ( $downloads as $download ) {
				$data_to_export[] = array(
					'group_id'    => 'woocommerce_downloads',
					'group_label' => __( 'Order Downloads', 'woocommerce' ),
					'item_id'     => 'download-' . $download->get_id(),
					'data'        => array(
						array(
							'name'  => __( 'Download ID', 'woocommerce' ),
							'value' => $download->get_id(),
						),
						array(
							'name'  => __( 'Order ID', 'woocommerce' ),
							'value' => $download->get_order_id(),
						),
						array(
							'name'  => __( 'Product', 'woocommerce' ),
							'value' => get_the_title( $download->get_product_id() ),
						),
						array(
							'name'  => __( 'User email', 'woocommerce' ),
							'value' => $download->get_user_email(),
						),
						array(
							'name'  => __( 'Downloads remaining', 'woocommerce' ),
							'value' => $download->get_downloads_remaining(),
						),
						array(
							'name'  => __( 'Download count', 'woocommerce' ),
							'value' => $download->get_download_count(),
						),
						array(
							'name'  => __( 'Access granted', 'woocommerce' ),
							'value' => date( 'Y-m-d', $download->get_access_granted( 'edit' )->getTimestamp() ),
						),
						array(
							'name'  => __( 'Access expires', 'woocommerce' ),
							'value' => ! is_null( $download->get_access_expires( 'edit' ) ) ? date( 'Y-m-d', $download->get_access_expires( 'edit' )->getTimestamp() ) : null,
						),
					),
				);

				$download_logs = $customer_download_log_data_store->get_download_logs_for_permission( $download->get_id() );

				foreach ( $download_logs as $download_log ) {
					$data_to_export[] = array(
						'group_id'    => 'woocommerce_download_logs',
						'group_label' => __( 'Download Logs', 'woocommerce' ),
						'item_id'     => 'download-log-' . $download_log->get_id(),
						'data'        => array(
							array(
								'name'  => __( 'Download ID', 'woocommerce' ),
								'value' => $download_log->get_permission_id(),
							),
							array(
								'name'  => __( 'Timestamp', 'woocommerce' ),
								'value' => $download_log->get_timestamp(),
							),
							array(
								'name'  => __( 'IP Address', 'woocommerce' ),
								'value' => $download_log->get_user_ip_address(),
							),
						),
					);
				}
			}
			$done = 10 > count( $downloads );
		} else {
			$done = true;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
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
	 * @todo Add option to determine if order data should be left alone when removing personal data for a user.
	 * @todo Hook into core UI.
	 *
	 * @param string $email_address Customer email address.
	 */
	public static function remove_personal_data( $email_address ) {
		$user        = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB.
		$has_account = $user instanceof WP_User;

		/**
		 * Allow 3rd parties to modify this behavior. If true, orders belonging to this user will be anonyimized.
		 *
		 * @since 3.4.0
		 */
		if ( apply_filters( 'woocommerce_privacy_remove_personal_data_includes_orders', true, $email_address ) ) {
			$order_query = array(
				'limit' => -1,
			);

			if ( $user instanceof WP_User ) {
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
		}

		// Revoke download permissions.
		$data_store = WC_Data_Store::load( 'customer-download' );

		if ( $user instanceof WP_User ) {
			$data_store->delete_by_user_id( (int) $user->ID );
		} else {
			$data_store->delete_by_user_email( $email_address );
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

				if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
					$anon_value = wp_privacy_anonymize_data( $data_type, $value );
				} else {
					$anon_value = '';
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
				$anonymized_data[ $prop ] = apply_filters( 'woocommerce_privacy_remove_order_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $order );
			}
		}

		// Set all new props and persist the new data to the database.
		$order->set_props( $anonymized_data );
		$order->update_meta_data( '_anonymized', 'yes' );
		$order->save();

		// Add note that this event occured.
		$order->add_order_note( __( 'Personal data removed.', 'woocommerce' ) );

		/**
		 * Allow extensions to remove their own personal data for this order.
		 *
		 * @since 3.4.0
		 * @param WC_Order $order A customer object.
		 */
		do_action( 'woocommerce_privacy_remove_order_personal_data', $order );
	}

	/**
	 * Handle some custom types of data and anonymize them.
	 *
	 * @param string $anonymous Anonymized string.
	 * @param string $type Type of data.
	 * @param string $data The data being anonymized.
	 * @return string Anonymized string.
	 */
	public static function anonymize_custom_data_types( $anonymous, $type, $data ) {
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
