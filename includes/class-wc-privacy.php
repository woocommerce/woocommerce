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

		// Include supporting classes.
		include_once 'class-wc-privacy-erasers.php';
		include_once 'class-wc-privacy-exporters.php';

		// This hook registers WooCommerce data exporters.
		add_filter( 'wp_privacy_personal_data_exporters', array( 'WC_Privacy_Exporters', 'register' ), 10 );

		// This hook registers WooCommerce data erasers.
		add_filter( 'wp_privacy_personal_data_erasers', array( 'WC_Privacy_Erasers', 'register' ), 10 );

		// Privacy page content.
		add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_content' ) );

		// Cleanup orders daily - this is a callback on a daily cron event.
		add_action( 'woocommerce_cleanup_orders', array( __CLASS__, 'order_cleanup_process' ) );

		// When this is fired, data is removed in a given order. Called from bulk actions.
		add_action( 'woocommerce_remove_order_personal_data', array( __CLASS__, 'remove_order_personal_data' ) );

		// Handles custom anonomization types not included in core.
		add_filter( 'wp_privacy_anonymize_data', array( __CLASS__, 'anonymize_custom_data_types' ), 10, 3 );
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 *
	 * @since 3.4.0
	 */
	public static function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = wp_kses_post( apply_filters( 'wc_privacy_policy_content', wpautop( __( '
We collect information about you during the checkout process on our store. This information may include, but is not limited to, your name, billing address, shipping address, email address, phone number, credit card/payment details and any other details that might be requested from you for the purpose of processing your orders.

Handling this data also allows us to:

- Send you important account/order/service information.
- Respond to your queries, refund requests, or complaints.
- Process payments and to prevent fraudulent transactions. We do this on the basis of our legitimate business interests.
- Set up and administer your account, provide technical and customer support, and to verify your identity.

Additionally we may also collect the following information:

- Location and traffic data (including IP address and browser type) if you place an order, or if we need to estimate taxes and shipping costs based on your location.
- Product pages visited and content viewed whist your session is active.
- Your comments and product reviews if you choose to leave them on our website.
- Shipping address if you request shipping rates from us before checkout whist your session is active.
- Cookies which are essential to keep track of the contents of your cart whist your session is active.
- Account email/password to allow you to access your account, if you have one.
- If you choose to create an account with us, your name, address, email and phone number, which will be used to populate the checkout for future orders.
', 'woocommerce' ) ) ) );

		wp_add_privacy_policy_content( 'WooCommerce', $content );
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
