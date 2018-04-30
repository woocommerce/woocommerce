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
class WC_Privacy extends WC_Abstract_Privacy {

	/**
	 * Background process to clean up orders.
	 *
	 * @var WC_Privacy_Background_Process
	 */
	protected static $background_process;

	/**
	 * Init - hook into events.
	 */
	public function __construct() {
		parent::__construct( 'WooCommerce' );

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Privacy_Background_Process();
		}

		// Include supporting classes.
		include_once 'class-wc-privacy-erasers.php';
		include_once 'class-wc-privacy-exporters.php';

		// This hook registers WooCommerce data exporters.
		$this->add_exporter( __( 'Customer Data', 'woocommerce' ), array( 'WC_Privacy_Exporters', 'customer_data_exporter' ) );
		$this->add_exporter( __( 'Customer Orders', 'woocommerce' ), array( 'WC_Privacy_Exporters', 'order_data_exporter' ) );
		$this->add_exporter( __( 'Customer Downloads', 'woocommerce' ), array( 'WC_Privacy_Exporters', 'download_data_exporter' ) );

		// This hook registers WooCommerce data erasers.
		$this->add_eraser( __( 'Customer Data', 'woocommerce' ), array( 'WC_Privacy_Erasers', 'customer_data_eraser' ) );
		$this->add_eraser( __( 'Customer Orders', 'woocommerce' ), array( 'WC_Privacy_Erasers', 'order_data_eraser' ) );
		$this->add_eraser( __( 'Customer Downloads', 'woocommerce' ), array( 'WC_Privacy_Erasers', 'download_data_eraser' ) );

		// Cleanup orders daily - this is a callback on a daily cron event.
		add_action( 'woocommerce_cleanup_personal_data', array( $this, 'queue_cleanup_personal_data' ) );

		// Handles custom anonomization types not included in core.
		add_filter( 'wp_privacy_anonymize_data', array( $this, 'anonymize_custom_data_types' ), 10, 3 );

		// When this is fired, data is removed in a given order. Called from bulk actions.
		add_action( 'woocommerce_remove_order_personal_data', array( 'WC_Privacy_Erasers', 'remove_order_personal_data' ) );
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 *
	 * @since 3.4.0
	 */
	public function get_privacy_message() {
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

		return $content;
	}

	/**
	 * Spawn events for order cleanup.
	 */
	public function queue_cleanup_personal_data() {
		self::$background_process->push_to_queue( array( 'task' => 'trash_pending_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_failed_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'trash_cancelled_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'anonymize_completed_orders' ) );
		self::$background_process->push_to_queue( array( 'task' => 'delete_inactive_accounts' ) );
		self::$background_process->save()->dispatch();
	}

	/**
	 * Handle some custom types of data and anonymize them.
	 *
	 * @param string $anonymous Anonymized string.
	 * @param string $type Type of data.
	 * @param string $data The data being anonymized.
	 * @return string Anonymized string.
	 */
	public function anonymize_custom_data_types( $anonymous, $type, $data ) {
		switch ( $type ) {
			case 'address_state':
			case 'address_country':
				$anonymous = ''; // Empty string - we don't want to store anything after removal.
				break;
			case 'phone':
				$anonymous = preg_replace( '/\d/u', '0', $data );
				break;
			case 'numeric_id':
				$anonymous = 0;
				break;
		}
		return $anonymous;
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
	 * Anonymize old completed orders.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function anonymize_completed_orders( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_anonymize_completed_orders' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::anonymize_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option['number'] . ' ' . $option['unit'] ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-completed',
			'anonymized'   => false,
		) );
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
				WC_Privacy_Erasers::remove_order_personal_data( $order );
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Delete inactive accounts.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit users to process per batch.
	 * @return int Number of users processed.
	 */
	public static function delete_inactive_accounts( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_delete_inactive_accounts' ) );

		if ( empty( $option['number'] ) ) {
			return 0;
		}

		return self::delete_inactive_accounts_query( strtotime( '-' . $option['number'] . ' ' . $option['unit'] ), $limit );
	}

	/**
	 * Delete inactive accounts.
	 *
	 * @since 3.4.0
	 * @param int $timestamp Timestamp to delete customers before.
	 * @param int $limit     Limit number of users to delete per run.
	 * @return int Count of customers that were deleted.
	 */
	protected static function delete_inactive_accounts_query( $timestamp, $limit = 20 ) {
		$count      = 0;
		$user_query = new WP_User_Query( array(
			'fields'     => 'ID',
			'number'     => $limit,
			'role__in'   => apply_filters( 'woocommerce_delete_inactive_account_roles', array(
				'Customer',
				'Subscriber',
			) ),
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'wc_last_active',
					'value'   => (string) $timestamp,
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'wc_last_active',
					'value'   => '0',
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		) );

		$user_ids = $user_query->get_results();

		if ( $user_ids ) {
			foreach ( $user_ids as $user_id ) {
				wp_delete_user( $user_id );
				$count ++;
			}
		}

		return $count;
	}
}

new WC_Privacy();
