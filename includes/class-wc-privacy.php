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
	}

	/**
	 * For a given query trash all matches.
	 *
	 * @since 3.4.0
	 * @param array $query Query array to pass to wc_get_orders().
	 * @return int Count of orders that were trashed.
	 */
	protected function trash_orders_query( $query ) {
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
	protected function anonymize_orders_query( $query ) {
		$orders = wc_get_orders( $query );
		$count  = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				// self::remove_order_personal_data( $order ); @todo Integrate with #19330 and set _anonymized meta after complete.
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
	}

	/**
	 * Find and trash old orders.
	 *
	 * @since 3.4.0
	 * @param  int $limit Limit orders to process per batch.
	 * @return int Number of orders processed.
	 */
	public static function trash_pending_orders( $limit = 20 ) {
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_pending_orders', array(
			'number' => 30,
			'unit'   => 'days',
		) ) );

		if ( empty( $option->number ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option->number . ' ' . $option->unit ),
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
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_failed_orders', array(
			'number' => 30,
			'unit'   => 'days',
		) ) );

		if ( empty( $option->number ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option->number . ' ' . $option->unit ),
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
		$option = wc_parse_relative_date_option( get_option( 'woocommerce_trash_cancelled_orders', array(
			'number' => 30,
			'unit'   => 'days',
		) ) );

		if ( empty( $option->number ) ) {
			return 0;
		}

		return self::trash_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option->number . ' ' . $option->unit ),
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

		if ( empty( $option->number ) ) {
			return 0;
		}

		return self::anonymize_orders_query( array(
			'date_created' => '<' . strtotime( '-' . $option->number . ' ' . $option->unit ),
			'limit'        => $limit, // Batches of 20.
			'status'       => 'wc-completed',
			'anonymized'   => false,
			'customer_id'  => 0,
		) );
	}
}

WC_Privacy::init();
