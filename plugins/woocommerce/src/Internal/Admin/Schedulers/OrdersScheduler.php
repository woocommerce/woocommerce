<?php
/**
 * Order syncing related functions and actions.
 */

namespace Automattic\WooCommerce\Internal\Admin\Schedulers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore as CouponsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrderDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore as TaxesDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * OrdersScheduler Class.
 */
class OrdersScheduler extends ImportScheduler {
	/**
	 * Slug to identify the scheduler.
	 *
	 * @var string
	 */
	public static $name = 'orders';

	/**
	 * Option name for storing the last processed order modified date.
	 *
	 * This is used as a cursor to track progress through the orders table.
	 * We need both date and ID because multiple orders can have the same
	 * date_updated timestamp (e.g., bulk operations, imports). Without tracking
	 * the ID, we would endlessly reprocess orders at the same timestamp when
	 * the batch size is smaller than the number of orders at that timestamp.
	 *
	 * @var string
	 */
	const LAST_PROCESSED_ORDER_DATE_OPTION = 'woocommerce_admin_scheduler_last_processed_order_modified_date';

	/**
	 * Option name for storing the last processed order ID.
	 *
	 * Used in conjunction with LAST_PROCESSED_ORDER_DATE_OPTION to handle
	 * cases where multiple orders have the same date_updated timestamp.
	 * Query pattern: WHERE (date > last_date) OR (date = last_date AND id > last_id)
	 *
	 * @var string
	 */
	const LAST_PROCESSED_ORDER_ID_OPTION = 'woocommerce_admin_scheduler_last_processed_order_id';

	/**
	 * Option name for storing whether to enable immediate order import.
	 *
	 * @var string
	 */
	const IMMEDIATE_IMPORT_OPTION = 'woocommerce_analytics_immediate_import';

	/**
	 * Attach order lookup update hooks.
	 *
	 * @internal
	 */
	public static function init() {
		// Activate WC_Order extension.
		\Automattic\WooCommerce\Admin\Overrides\Order::add_filters();
		\Automattic\WooCommerce\Admin\Overrides\OrderRefund::add_filters();

		// Legacy behavior: Schedule import immediately on order create/update/delete.
		add_action( 'woocommerce_update_order', array( __CLASS__, 'possibly_schedule_import' ) );
		add_filter( 'woocommerce_create_order', array( __CLASS__, 'possibly_schedule_import' ) );
		add_action( 'woocommerce_refund_created', array( __CLASS__, 'possibly_schedule_import' ) );
		add_action( 'woocommerce_schedule_import', array( __CLASS__, 'possibly_schedule_import' ) );

		// Schedule recurring batch processor.
		add_action( 'init', array( __CLASS__, 'schedule_recurring_batch_processor' ) );

		OrdersStatsDataStore::init();
		CouponsDataStore::init();
		ProductsDataStore::init();
		TaxesDataStore::init();
		OrderDataStore::init();

		parent::init();
	}

	/**
	 * Add customer dependencies.
	 *
	 * @internal
	 * @return array
	 */
	public static function get_dependencies() {
		return array(
			'import_batch_init' => \Automattic\WooCommerce\Internal\Admin\Schedulers\CustomersScheduler::get_action( 'import_batch_init' ),
		);
	}

	/**
	 * Get all available scheduling actions.
	 * Extends parent to add the new batch processor action.
	 *
	 * @internal
	 * @return array
	 */
	public static function get_scheduler_actions() {
		return array_merge(
			parent::get_scheduler_actions(),
			array(
				'process_pending_batch' => 'wc-admin_process_pending_orders_batch',
			)
		);
	}

	/**
	 * Get batch sizes for OrdersScheduler actions.
	 *
	 * @internal
	 * @return array
	 */
	public static function get_batch_sizes() {
		return array_merge(
			parent::get_batch_sizes(),
			array(
				'process_pending_batch' => 100,
			)
		);
	}

	/**
	 * Get the order/refund IDs and total count that need to be synced.
	 *
	 * @internal
	 * @param int      $limit Number of records to retrieve.
	 * @param int      $page  Page number.
	 * @param int|bool $days Number of days prior to current date to limit search results.
	 * @param bool     $skip_existing Skip already imported orders.
	 */
	public static function get_items( $limit = 10, $page = 1, $days = false, $skip_existing = false ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return self::get_items_from_orders_table( $limit, $page, $days, $skip_existing );
		} else {
			return self::get_items_from_posts_table( $limit, $page, $days, $skip_existing );
		}
	}

	/**
	 * Helper method to ger order/refund IDS and total count that needs to be synced.
	 *
	 * @internal
	 * @param int      $limit Number of records to retrieve.
	 * @param int      $page  Page number.
	 * @param int|bool $days Number of days prior to current date to limit search results.
	 * @param bool     $skip_existing Skip already imported orders.
	 *
	 * @return object Total counts.
	 */
	private static function get_items_from_posts_table( $limit, $page, $days, $skip_existing ) {
		global $wpdb;
		$where_clause = '';
		$offset       = $page > 1 ? ( $page - 1 ) * $limit : 0;

		if ( is_int( $days ) ) {
			$days_ago      = gmdate( 'Y-m-d 00:00:00', time() - ( DAY_IN_SECONDS * $days ) );
			$where_clause .= " AND post_date_gmt >= '{$days_ago}'";
		}

		if ( $skip_existing ) {
			$where_clause .= " AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->prefix}wc_order_stats
				WHERE {$wpdb->prefix}wc_order_stats.order_id = {$wpdb->posts}.ID
			)";
		}

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type IN ( 'shop_order', 'shop_order_refund' )
			AND post_status NOT IN ( 'wc-auto-draft', 'auto-draft', 'trash' )
			{$where_clause}"
		); // phpcs:ignore unprepared SQL ok.

		$order_ids = absint( $count ) > 0 ? $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type IN ( 'shop_order', 'shop_order_refund' )
				AND post_status NOT IN ( 'wc-auto-draft', 'auto-draft', 'trash' )
				{$where_clause}
				ORDER BY post_date_gmt ASC
				LIMIT %d
				OFFSET %d",
				$limit,
				$offset
			)
		) : array(); // phpcs:ignore unprepared SQL ok.

		return (object) array(
			'total' => absint( $count ),
			'ids'   => $order_ids,
		);
	}

	/**
	 * Helper method to ger order/refund IDS and total count that needs to be synced from HPOS.
	 *
	 * @internal
	 * @param int      $limit Number of records to retrieve.
	 * @param int      $page  Page number.
	 * @param int|bool $days Number of days prior to current date to limit search results.
	 * @param bool     $skip_existing Skip already imported orders.
	 *
	 * @return object Total counts.
	 */
	private static function get_items_from_orders_table( $limit, $page, $days, $skip_existing ) {
		global $wpdb;
		$where_clause = '';
		$offset       = $page > 1 ? ( $page - 1 ) * $limit : 0;
		$order_table  = OrdersTableDataStore::get_orders_table_name();

		if ( is_int( $days ) ) {
			$days_ago      = gmdate( 'Y-m-d 00:00:00', time() - ( DAY_IN_SECONDS * $days ) );
			$where_clause .= " AND orders.date_created_gmt >= '{$days_ago}'";
		}

		if ( $skip_existing ) {
			$where_clause .= "AND NOT EXiSTS (
					SELECT 1 FROM {$wpdb->prefix}wc_order_stats
					WHERE {$wpdb->prefix}wc_order_stats.order_id = orders.id
					)
				";
		}

		$count = $wpdb->get_var(
			"
SELECT COUNT(*) FROM {$order_table} AS orders
WHERE type in ( 'shop_order', 'shop_order_refund' )
AND status NOT IN ( 'wc-auto-draft', 'trash', 'auto-draft' )
{$where_clause}
"
		); // phpcs:ignore unprepared SQL ok.

		$order_ids = absint( $count ) > 0 ? $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$order_table} AS orders
				WHERE type IN ( 'shop_order', 'shop_order_refund' )
				AND status NOT IN ( 'wc-auto-draft', 'auto-draft', 'trash' )
				{$where_clause}
				ORDER BY date_created_gmt ASC
				LIMIT %d
				OFFSET %d",
				$limit,
				$offset
			)
		) : array(); // phpcs:ignore unprepared SQL ok.

		return (object) array(
			'total' => absint( $count ),
			'ids'   => $order_ids,
		);
	}

	/**
	 * Get total number of rows imported.
	 *
	 * @internal
	 */
	public static function get_total_imported() {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_stats" );
	}

	/**
	 * Schedule this import if the post is an order or refund.
	 * Note: This method is only called when immediate import is enabled
	 * via the 'woocommerce_analytics_enable_immediate_import' filter.
	 * Otherwise, orders are processed in batches periodically.
	 *
	 * @param int $order_id Post ID.
	 *
	 * @internal
	 * @returns int The order id
	 */
	public static function possibly_schedule_import( $order_id ) {
		if ( ! self::is_immediate_import_enabled() ) {
			return $order_id;
		}

		if ( ! OrderUtil::is_order( $order_id, array( 'shop_order' ) ) && 'woocommerce_refund_created' !== current_filter() && 'woocommerce_schedule_import' !== current_filter() ) {
			return $order_id;
		}

		self::schedule_action( 'import', array( $order_id ) );
		return $order_id;
	}

	/**
	 * Imports a single order or refund to update lookup tables for.
	 * If an error is encountered in one of the updates, a retry action is scheduled.
	 *
	 * @internal
	 * @param int $order_id Order or refund ID.
	 * @return void
	 */
	public static function import( $order_id ) {
		$order = wc_get_order( $order_id );

		// If the order isn't found for some reason, skip the sync.
		if ( ! $order ) {
			return;
		}

		$type = $order->get_type();

		// If the order isn't the right type, skip sync.
		if ( 'shop_order' !== $type && 'shop_order_refund' !== $type ) {
			return;
		}

		// If the order has no id or date created, skip sync.
		if ( ! $order->get_id() || ! $order->get_date_created() ) {
			return;
		}

		$results = array(
			OrdersStatsDataStore::sync_order( $order_id ),
			ProductsDataStore::sync_order_products( $order_id ),
			CouponsDataStore::sync_order_coupons( $order_id ),
			TaxesDataStore::sync_order_taxes( $order_id ),
			CustomersDataStore::sync_order_customer( $order_id ),
		);

		if ( 'shop_order' === $type ) {
			$order_refunds = $order->get_refunds();

			foreach ( $order_refunds as $refund ) {
				OrdersStatsDataStore::sync_order( $refund->get_id() );
			}
		}

		ReportsCache::invalidate();

		/**
		 * Fires after an order or refund has been imported into Analytics lookup tables
		 * and the reports cache has been invalidated.
		 *
		 * @since 10.3.0
		 * @param int $order_id Order or refund ID.
		 */
		do_action( 'woocommerce_order_scheduler_after_import_order', $order_id );
	}

	/**
	 * Schedule recurring batch processor for order imports.
	 *
	 * @internal
	 */
	public static function schedule_recurring_batch_processor() {
		if ( self::is_immediate_import_enabled() ) {
			// No need to schedule if immediate import is enabled.
			if ( self::has_existing_jobs( 'process_pending_batch', array() ) ) {
				$action_hook = self::get_action( 'process_pending_batch' );
				as_unschedule_all_actions( $action_hook, array(), static::$group );
			}
			return;
		}
		// Initialize last processed date if not set.
		self::initialize_last_processed_date();

		/**
		 * Filters the interval for the recurring batch processor.
		 *
		 * @since 9.5.0
		 * @param int $interval Interval in seconds. Default 12 hours.
		 */
		$interval = apply_filters( 'woocommerce_analytics_import_interval', 12 * HOUR_IN_SECONDS );

		$action_hook = self::get_action( 'process_pending_batch' );

		// Schedule recurring action if not already scheduled.
		if ( ! self::has_existing_jobs( 'process_pending_batch', array() ) ) {
			self::queue()->schedule_recurring( time(), $interval, $action_hook, array(), static::$group );
		}
	}

	/**
	 * Initialize the last processed date option if not set.
	 *
	 * @internal
	 */
	private static function initialize_last_processed_date() {
		if ( false !== get_option( self::LAST_PROCESSED_ORDER_DATE_OPTION, false ) ) {
			return; // Already initialized.
		}

		/**
		 * Add buffer to ensure orders created or updated during plugin activation, upgrade, or prior to import are accounted for.
		 * Buffer in seconds. 10 minutes.
		 */
		$buffer_seconds = 10 * MINUTE_IN_SECONDS;
		$start_date     = gmdate( 'Y-m-d H:i:s', time() - $buffer_seconds );

		update_option( self::LAST_PROCESSED_ORDER_DATE_OPTION, $start_date, false );
		update_option( self::LAST_PROCESSED_ORDER_ID_OPTION, 0, false );
	}

	/**
	 * Process pending orders in batch.
	 *
	 * This method queries for orders updated since the last cursor position
	 * (compound cursor: date + ID) and imports them into the analytics tables.
	 *
	 * @internal
	 * @param string|null $cursor_date Cursor date in 'Y-m-d H:i:s' format. Orders after this date will be processed.
	 * @param int|null    $cursor_id   Cursor order ID. Combined with $cursor_date to form compound cursor.
	 * @return void
	 */
	public static function process_pending_batch( $cursor_date = null, $cursor_id = null ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'wc-analytics-order-import' );

		if ( self::is_importing() ) {
			// No need to process if an import is already in progress.
			$logger->info( 'Import is already in progress, skipping batch import.', $context );
			return;
		}

		// Load cursor position from options if not provided.
		$cursor_date = $cursor_date ?? get_option( self::LAST_PROCESSED_ORDER_DATE_OPTION );
		$cursor_id   = $cursor_id ?? (int) get_option( self::LAST_PROCESSED_ORDER_ID_OPTION, 0 );

		// Validate cursor date.
		if ( ! $cursor_date || ! strtotime( $cursor_date ) ) {
			$logger->error( 'Invalid cursor date: ' . $cursor_date, $context );
			$cursor_date = gmdate( 'Y-m-d H:i:s', 0 ); // Fallback to the earliest possible date.
		}

		$batch_size = self::get_batch_size( 'process_pending_batch' );

		$logger->info(
			sprintf( 'Starting batch import. Cursor: %s (ID: %d), batch size: %d', $cursor_date, $cursor_id, $batch_size ),
			$context
		);

		$start_time = microtime( true );

		// Get orders updated since the cursor position.
		$orders = self::get_orders_since( $cursor_date, $cursor_id, $batch_size );

		if ( empty( $orders ) ) {
			$logger->info( 'No orders to process', $context );
			return;
		}

		$processed_count = 0;
		foreach ( $orders as $order ) {
			try {
				self::import( $order->id );
				++$processed_count;

				// Advance cursor after each successful import. Since orders are sorted by
				// date ASC, id ASC, we can simply overwrite with the current order's values.
				// If an error occurs, we break and save the last successful position.
				$cursor_date = $order->date_updated_gmt;
				$cursor_id   = $order->id;
			} catch ( \Exception $e ) {
				$logger->error(
					sprintf( 'Failed to import order %d: %s', $order->id, $e->getMessage() ),
					$context
				);
				break;
			}
		}

		// Save the updated cursor position.
		update_option( self::LAST_PROCESSED_ORDER_DATE_OPTION, $cursor_date, false );
		update_option( self::LAST_PROCESSED_ORDER_ID_OPTION, $cursor_id, false );

		$elapsed_time = microtime( true ) - $start_time;
		$logger->info(
			sprintf(
				'Batch import completed. Processed: %d orders in %.2f seconds. Cursor: %s (ID: %d)',
				$processed_count,
				$elapsed_time,
				$cursor_date,
				$cursor_id
			),
			$context
		);

		// If we got a full batch, there might be more orders to process.
		// Schedule immediate next batch.
		if ( $processed_count === $batch_size ) {
			$logger->info( 'Full batch processed, scheduling next batch', $context );
			self::schedule_action(
				'process_pending_batch',
				array( $cursor_date, $cursor_id )
			);
		}
	}

	/**
	 * Get orders updated since the specified cursor position.
	 *
	 * Uses a compound cursor (date + ID) to handle cases where multiple orders
	 * have the same timestamp. This ensures we can paginate through orders reliably
	 * even when batch_size < number of orders at the same timestamp.
	 *
	 * @internal
	 * @param string $cursor_date Cursor date in 'Y-m-d H:i:s' format.
	 * @param int    $cursor_id   Cursor order ID.
	 * @param int    $limit       Number of orders to retrieve.
	 * @return array Array of objects with 'id' and 'date_updated_gmt' properties.
	 */
	private static function get_orders_since( $cursor_date, $cursor_id, $limit ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return self::get_orders_since_from_orders_table( $cursor_date, $cursor_id, $limit );
		} else {
			return self::get_orders_since_from_posts_table( $cursor_date, $cursor_id, $limit );
		}
	}

	/**
	 * Get orders from HPOS orders table updated since the specified cursor position.
	 *
	 * Query logic uses a compound cursor (date, ID) to handle pagination when multiple
	 * orders share the same timestamp:
	 * - WHERE date > cursor_date: Get orders with newer timestamps
	 * - OR (date = cursor_date AND id > cursor_id): Continue processing same timestamp
	 *
	 * Example: With batch_size=100 and 1000 orders at '2024-01-01 10:00:00',
	 * this processes them across 10 batches without infinite loops or duplicates.
	 *
	 * @internal
	 * @param string $cursor_date Cursor date in 'Y-m-d H:i:s' format.
	 * @param int    $cursor_id   Cursor order ID.
	 * @param int    $limit       Number of orders to retrieve.
	 * @return array Array of objects with 'id' and 'date_updated_gmt' properties.
	 */
	private static function get_orders_since_from_orders_table( $cursor_date, $cursor_id, $limit ) {
		global $wpdb;
		$orders_table = OrdersTableDataStore::get_orders_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, date_updated_gmt
				FROM {$orders_table}
				WHERE type IN ('shop_order', 'shop_order_refund')
				AND status NOT IN ('wc-auto-draft', 'auto-draft', 'trash')
				AND (
					date_updated_gmt > %s
					OR (date_updated_gmt = %s AND id > %d)
				)
				ORDER BY date_updated_gmt ASC, id ASC
				LIMIT %d",
				$cursor_date,
				$cursor_date,
				$cursor_id,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get orders from posts table updated since the specified cursor position.
	 *
	 * Uses the same compound cursor logic as get_orders_since_from_orders_table()
	 * but queries the posts table instead of the HPOS orders table.
	 *
	 * @internal
	 * @param string $cursor_date Cursor date in 'Y-m-d H:i:s' format.
	 * @param int    $cursor_id   Cursor order ID.
	 * @param int    $limit       Number of orders to retrieve.
	 * @return array Array of objects with 'id' and 'date_updated_gmt' properties.
	 */
	private static function get_orders_since_from_posts_table( $cursor_date, $cursor_id, $limit ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID as id, post_modified_gmt as date_updated_gmt
				FROM {$wpdb->posts}
				WHERE post_type IN ('shop_order', 'shop_order_refund')
				AND post_status NOT IN ('wc-auto-draft', 'auto-draft', 'trash')
				AND (
					post_modified_gmt > %s
					OR (post_modified_gmt = %s AND ID > %d)
				)
				ORDER BY post_modified_gmt ASC, ID ASC
				LIMIT %d",
				$cursor_date,
				$cursor_date,
				$cursor_id,
				$limit
			)
		);
	}

	/**
	 * Delete a batch of orders.
	 *
	 * @internal
	 * @param int $batch_size Number of items to delete.
	 * @return void
	 */
	public static function delete( $batch_size ) {
		global $wpdb;

		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_order_stats ORDER BY order_id ASC LIMIT %d",
				$batch_size
			)
		);

		foreach ( $order_ids as $order_id ) {
			OrdersStatsDataStore::delete_order( $order_id );
		}
	}

	/**
	 * Check if immediate import is enabled.
	 *
	 * @internal
	 * @return bool
	 */
	private static function is_immediate_import_enabled(): bool {
		$enable_immediate_import = get_option( self::IMMEDIATE_IMPORT_OPTION, true );
		return filter_var( $enable_immediate_import, FILTER_VALIDATE_BOOLEAN );
	}
}
