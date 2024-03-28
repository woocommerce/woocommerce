<?php
/**
 * DataSynchronizer class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCacheController;
use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\Admin\Orders\EditLock;
use Automattic\WooCommerce\Internal\BatchProcessing\{ BatchProcessingController, BatchProcessorInterface };
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Proxies\LegacyProxy;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the database structure creation and the data synchronization for the custom orders tables. Its responsibilites are:
 *
 * - Providing entry points for creating and deleting the required database tables.
 * - Synchronizing changes between the custom orders tables and the posts table whenever changes in orders happen.
 */
class DataSynchronizer implements BatchProcessorInterface {

	use AccessiblePrivateMethods;

	public const ORDERS_DATA_SYNC_ENABLED_OPTION           = 'woocommerce_custom_orders_table_data_sync_enabled';
	public const PLACEHOLDER_ORDER_POST_TYPE               = 'shop_order_placehold';

	public const DELETED_RECORD_META_KEY        = '_deleted_from';
	public const DELETED_FROM_POSTS_META_VALUE  = 'posts_table';
	public const DELETED_FROM_ORDERS_META_VALUE = 'orders_table';

	public const ORDERS_TABLE_CREATED = 'woocommerce_custom_orders_table_created';

	private const ORDERS_SYNC_BATCH_SIZE = 250;

	// Allowed values for $type in get_ids_of_orders_pending_sync method.
	public const ID_TYPE_MISSING_IN_ORDERS_TABLE   = 0;
	public const ID_TYPE_MISSING_IN_POSTS_TABLE    = 1;
	public const ID_TYPE_DIFFERENT_UPDATE_DATE     = 2;
	public const ID_TYPE_DELETED_FROM_ORDERS_TABLE = 3;
	public const ID_TYPE_DELETED_FROM_POSTS_TABLE  = 4;

	public const BACKGROUND_SYNC_MODE_OPTION     = 'woocommerce_custom_orders_table_background_sync_mode';
	public const BACKGROUND_SYNC_INTERVAL_OPTION = 'woocommerce_custom_orders_table_background_sync_interval';
	public const BACKGROUND_SYNC_MODE_INTERVAL   = 'interval';
	public const BACKGROUND_SYNC_MODE_CONTINUOUS = 'continuous';
	public const BACKGROUND_SYNC_MODE_OFF        = 'off';
	public const BACKGROUND_SYNC_EVENT_HOOK      = 'woocommerce_custom_orders_table_background_sync';

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	/**
	 * The database util object to use.
	 *
	 * @var DatabaseUtil
	 */
	private $database_util;

	/**
	 * The posts to COT migrator to use.
	 *
	 * @var PostsToOrdersMigrationController
	 */
	private $posts_to_cot_migrator;

	/**
	 * Logger object to be used to log events.
	 *
	 * @var \WC_Logger
	 */
	private $error_logger;

	/**
	 * The instance of the LegacyProxy object to use.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * The order cache controller.
	 *
	 * @var OrderCacheController
	 */
	private $order_cache_controller;

	/**
	 * The batch processing controller.
	 *
	 * @var BatchProcessingController
	 */
	private $batch_processing_controller;

	/**
	 * Whether datastores are in sync or not (cached value).
	 *
	 * @var null|bool
	 */
	private $is_in_sync_cached = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		self::add_filter( 'pre_delete_post', array( $this, 'maybe_prevent_deletion_of_post' ), 10, 2 );
		self::add_action( 'deleted_post', array( $this, 'handle_deleted_post' ), 10, 2 );
		self::add_action( 'woocommerce_new_order', array( $this, 'handle_updated_order' ), 100 );
		self::add_action( 'woocommerce_refund_created', array( $this, 'handle_updated_order' ), 100 );
		self::add_action( 'woocommerce_update_order', array( $this, 'handle_updated_order' ), 100 );
		self::add_action( 'wp_scheduled_auto_draft_delete', array( $this, 'delete_auto_draft_orders' ), 9 );
		self::add_action( 'wp_scheduled_delete', array( $this, 'delete_trashed_orders' ), 9 );
		self::add_filter( 'updated_option', array( $this, 'process_updated_option' ), 999, 3 );
		self::add_filter( 'added_option', array( $this, 'process_added_option' ), 999, 2 );
		self::add_filter( 'deleted_option', array( $this, 'process_deleted_option' ), 999 );
		self::add_action( self::BACKGROUND_SYNC_EVENT_HOOK, array( $this, 'handle_interval_background_sync' ) );
		if ( self::BACKGROUND_SYNC_MODE_CONTINUOUS === $this->get_background_sync_mode() ) {
			self::add_action( 'shutdown', array( $this, 'handle_continuous_background_sync' ) );
		}
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param OrdersTableDataStore             $data_store The data store to use.
	 * @param DatabaseUtil                     $database_util The database util class to use.
	 * @param PostsToOrdersMigrationController $posts_to_cot_migrator The posts to COT migration class to use.
	 * @param LegacyProxy                      $legacy_proxy The legacy proxy instance to use.
	 * @param OrderCacheController             $order_cache_controller The order cache controller instance to use.
	 * @param BatchProcessingController        $batch_processing_controller The batch processing controller to use.
	 * @internal
	 */
	final public function init(
		OrdersTableDataStore $data_store,
		DatabaseUtil $database_util,
		PostsToOrdersMigrationController $posts_to_cot_migrator,
		LegacyProxy $legacy_proxy,
		OrderCacheController $order_cache_controller,
		BatchProcessingController $batch_processing_controller
	) {
		$this->data_store                  = $data_store;
		$this->database_util               = $database_util;
		$this->posts_to_cot_migrator       = $posts_to_cot_migrator;
		$this->legacy_proxy                = $legacy_proxy;
		$this->error_logger                = $legacy_proxy->call_function( 'wc_get_logger' );
		$this->order_cache_controller      = $order_cache_controller;
		$this->batch_processing_controller = $batch_processing_controller;
	}

	/**
	 * Does the custom orders tables exist in the database?
	 *
	 * @return bool True if the custom orders tables exist in the database.
	 */
	public function check_orders_table_exists(): bool {
		$missing_tables = $this->database_util->get_missing_tables( $this->data_store->get_database_schema() );

		if ( count( $missing_tables ) === 0 ) {
			update_option( self::ORDERS_TABLE_CREATED, 'yes' );
			return true;
		} else {
			update_option( self::ORDERS_TABLE_CREATED, 'no' );
			return false;
		}
	}

	/**
	 * Returns the value of the orders table created option. If it's not set, then it checks the orders table and set it accordingly.
	 *
	 * @return bool Whether orders table exists.
	 */
	public function get_table_exists(): bool {
		$table_exists = get_option( self::ORDERS_TABLE_CREATED );
		switch ( $table_exists ) {
			case 'no':
			case 'yes':
				return 'yes' === $table_exists;
			default:
				return $this->check_orders_table_exists();
		}
	}

	/**
	 * Create the custom orders database tables and log an error if that's not possible.
	 *
	 * @return bool True if all the tables were successfully created, false otherwise.
	 */
	public function create_database_tables() {
		$this->database_util->dbdelta( $this->data_store->get_database_schema() );
		$success = $this->check_orders_table_exists();
		if ( ! $success ) {
			$missing_tables = $this->database_util->get_missing_tables( $this->data_store->get_database_schema() );
			$missing_tables = implode( ', ', $missing_tables );
			$this->error_logger->error( "HPOS tables are missing in the database and couldn't be created. The missing tables are: $missing_tables" );
		}
		return $success;
	}

	/**
	 * Delete the custom orders database tables.
	 */
	public function delete_database_tables() {
		$table_names = $this->data_store->get_all_table_names();

		foreach ( $table_names as $table_name ) {
			$this->database_util->drop_database_table( $table_name );
		}
		delete_option( self::ORDERS_TABLE_CREATED );
	}

	/**
	 * Is the real-time data sync between old and new tables currently enabled?
	 *
	 * @return bool
	 */
	public function data_sync_is_enabled(): bool {
		return 'yes' === get_option( self::ORDERS_DATA_SYNC_ENABLED_OPTION );
	}

	/**
	 * Get the current background data sync mode.
	 *
	 * @return string
	 */
	public function get_background_sync_mode(): string {
		$default = $this->data_sync_is_enabled() ? self::BACKGROUND_SYNC_MODE_INTERVAL : self::BACKGROUND_SYNC_MODE_OFF;

		return get_option( self::BACKGROUND_SYNC_MODE_OPTION, $default );
	}

	/**
	 * Is the background data sync between old and new tables currently enabled?
	 *
	 * @return bool
	 */
	public function background_sync_is_enabled(): bool {
		$enabled_modes = array( self::BACKGROUND_SYNC_MODE_INTERVAL, self::BACKGROUND_SYNC_MODE_CONTINUOUS );
		$mode          = $this->get_background_sync_mode();

		return in_array( $mode, $enabled_modes, true );
	}

	/**
	 * Process an option change for specific keys.
	 *
	 * @param string $option_key The option key.
	 * @param string $old_value  The previous value.
	 * @param string $new_value  The new value.
	 *
	 * @return void
	 */
	private function process_updated_option( $option_key, $old_value, $new_value ) {
		$sync_option_keys = array( self::ORDERS_DATA_SYNC_ENABLED_OPTION, self::BACKGROUND_SYNC_MODE_OPTION );
		if ( ! in_array( $option_key, $sync_option_keys, true ) || $new_value === $old_value ) {
			return;
		}

		if ( self::BACKGROUND_SYNC_MODE_OPTION === $option_key ) {
			$mode = $new_value;
		} else {
			$mode = $this->get_background_sync_mode();
		}
		switch ( $mode ) {
			case self::BACKGROUND_SYNC_MODE_INTERVAL:
				$this->schedule_background_sync();
				break;

			case self::BACKGROUND_SYNC_MODE_CONTINUOUS:
			case self::BACKGROUND_SYNC_MODE_OFF:
			default:
				$this->unschedule_background_sync();
				break;
		}

		if ( self::ORDERS_DATA_SYNC_ENABLED_OPTION === $option_key ) {
			if ( ! $this->check_orders_table_exists() ) {
				$this->create_database_tables();
			}

			if ( $this->data_sync_is_enabled() ) {
				wc_get_container()->get( LegacyDataCleanup::class )->toggle_flag( false );
				$this->batch_processing_controller->enqueue_processor( self::class );
			} else {
				$this->batch_processing_controller->remove_processor( self::class );
			}
		}
	}

	/**
	 * Process an option change when the key didn't exist before.
	 *
	 * @param string $option_key The option key.
	 * @param string $value      The new value.
	 *
	 * @return void
	 */
	private function process_added_option( $option_key, $value ) {
		$this->process_updated_option( $option_key, false, $value );
	}

	/**
	 * Process an option deletion for specific keys.
	 *
	 * @param string $option_key The option key.
	 *
	 * @return void
	 */
	private function process_deleted_option( $option_key ) {
		if ( self::BACKGROUND_SYNC_MODE_OPTION !== $option_key ) {
			return;
		}

		$this->unschedule_background_sync();
		$this->batch_processing_controller->remove_processor( self::class );
	}

	/**
	 * Get the time interval, in seconds, between background syncs.
	 *
	 * @return int
	 */
	public function get_background_sync_interval(): int {
		$interval = filter_var(
			get_option( self::BACKGROUND_SYNC_INTERVAL_OPTION, HOUR_IN_SECONDS ),
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'default' => HOUR_IN_SECONDS,
				),
			)
		);

		return $interval;
	}

	/**
	 * Keys that can be ignored during synchronization or verification.
	 *
	 * @since 8.6.0
	 *
	 * @return string[]
	 */
	public function get_ignored_order_props() {
		/**
		 * Allows modifying the list of order properties that are ignored during HPOS synchronization or verification.
		 *
		 * @param string[] List of order properties or meta keys.
		 * @since 8.6.0
		 */
		$ignored_props = apply_filters( 'woocommerce_hpos_sync_ignored_order_props', array() );
		$ignored_props = array_filter( array_map( 'trim', array_filter( $ignored_props, 'is_string' ) ) );

		return array_merge(
			$ignored_props,
			array(
				'_paid_date', // This has been deprecated and replaced by '_date_paid' in the CPT datastore.
				'_completed_date', // This has been deprecated and replaced by '_date_completed' in the CPT datastore.
				EditLock::META_KEY_NAME,
			)
		);
	}

	/**
	 * Schedule an event to run background sync when the mode is set to interval.
	 *
	 * @return void
	 */
	private function schedule_background_sync() {
		$interval = $this->get_background_sync_interval();

		// Calling Action Scheduler directly because WC_Action_Queue doesn't support the unique parameter yet.
		as_schedule_recurring_action(
			time() + $interval,
			$interval,
			self::BACKGROUND_SYNC_EVENT_HOOK,
			array(),
			'',
			true
		);
	}

	/**
	 * Remove any pending background sync events.
	 *
	 * @return void
	 */
	private function unschedule_background_sync() {
		WC()->queue()->cancel_all( self::BACKGROUND_SYNC_EVENT_HOOK );
	}

	/**
	 * Callback to check for pending syncs and enqueue the background data sync processor when in interval mode.
	 *
	 * @return void
	 */
	private function handle_interval_background_sync() {
		if ( self::BACKGROUND_SYNC_MODE_INTERVAL !== $this->get_background_sync_mode() ) {
			$this->unschedule_background_sync();
			return;
		}

		$pending_count = $this->get_total_pending_count();
		if ( $pending_count > 0 ) {
			$this->batch_processing_controller->enqueue_processor( self::class );
		}
	}

	/**
	 * Callback to keep the background data sync processor enqueued when in continuous mode.
	 *
	 * @return void
	 */
	private function handle_continuous_background_sync() {
		if ( self::BACKGROUND_SYNC_MODE_CONTINUOUS !== $this->get_background_sync_mode() ) {
			$this->batch_processing_controller->remove_processor( self::class );
			return;
		}

		// This method already checks if a processor is enqueued before adding it to avoid duplication.
		$this->batch_processing_controller->enqueue_processor( self::class );
	}

	/**
	 * Get the current sync process status.
	 * The information is meaningful only if pending_data_sync_is_in_progress return true.
	 *
	 * @deprecated 8.9.0 In favor of direct calls to {@see get_total_pending_count()} or {@see is_in_sync()}.
	 *
	 * @return array
	 */
	public function get_sync_status() {
		return array(
			'initial_pending_count' => 0,
			'current_pending_count' => $this->get_total_pending_count(),
		);
	}

	/**
	 * Get the total number of orders pending synchronization.
	 *
	 * @return int
	 */
	public function get_current_orders_pending_sync_count_cached(): int {
		return $this->get_current_orders_pending_sync_count( true );
	}

	/**
	 * Calculate how many orders need to be synchronized currently.
	 * A database query is performed to get how many orders match one of the following:
	 *
	 * - Existing in the authoritative table but not in the backup table.
	 * - Existing in both tables, but they have a different update date.
	 *
	 * @param bool $use_cache Whether to use the cached value instead of fetching from database.
	 */
	public function get_current_orders_pending_sync_count( $use_cache = false ): int {
		global $wpdb;

		if ( $use_cache ) {
			$pending_count = wp_cache_get( 'woocommerce_hpos_pending_sync_count' );
			if ( false !== $pending_count ) {
				return (int) $pending_count;
			}
		}

		$order_post_types = wc_get_order_types( 'cot-migration' );
		if ( empty( $order_post_types ) ) {
			$this->error_logger->debug(
				sprintf(
					/* translators: 1: method name. */
					esc_html__( '%1$s was called but no order types were registered: it may have been called too early.', 'woocommerce' ),
					__METHOD__
				)
			);

			return 0;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- queries are already prepared.
		$pending_count  = 0;
		$pending_count += (int) $wpdb->get_var( $this->get_sql_for_count_query( 'out-of-sync' ) );
		$pending_count += (int) $wpdb->get_var( $this->get_sql_for_count_query( 'deleted' ) );
		// phpcs:enable

		wp_cache_set( 'woocommerce_hpos_pending_sync_count', $pending_count );
		return $pending_count;
	}

	/**
	 * Builds SQL queries to verify sync status between datastores.
	 *
	 * @since 8.9.0
	 *
	 * @param string $query_type Which query to construct. Use 'out-of-sync' (for missing or out of sync orders) and 'deleted' (for orders deleted from the current datastore).
	 * @param string $result     Either 'count' to obtain a query that counts orders or 'bool' for a query that only checks if out of sync orders exist (uses `SELECT 1`).
	 * @return string The prepared SQL query.
	 */
	private function get_sql_for_count_query( string $query_type = 'out-of-sync', string $result = 'count' ): string {
		global $wpdb;

		$order_post_types            = wc_get_order_types( 'cot-migration' );
		$order_post_type_placeholder = implode( ', ', array_fill( 0, count( $order_post_types ), '%s' ) );
		$orders_table                = $this->data_store::get_orders_table_name();

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

		switch ( $query_type ) {
			case 'out-of-sync':
				if ( ! $this->get_table_exists() ) {
					$sql = $wpdb->prepare(
						"
						SELECT COUNT(1) FROM {$wpdb->posts} p WHERE p.post_type IN ({$order_post_type_placeholder})
						",
						$order_post_types
					);
				} elseif ( $this->custom_orders_table_is_authoritative() ) {
					$sql = $wpdb->prepare(
						"
						SELECT COUNT(1) FROM {$orders_table} o LEFT JOIN {$wpdb->posts} p ON p.ID = o.id
						WHERE o.status != 'auto-draft'
						AND o.type IN ({$order_post_type_placeholder})
						AND (p.ID IS NULL OR p.post_type = '" . self::PLACEHOLDER_ORDER_POST_TYPE . "' OR o.date_updated_gmt > p.post_modified_gmt)
						",
						$order_post_types
					);
				} else {
					$sql = $wpdb->prepare(
						"
						SELECT COUNT(1) FROM {$wpdb->posts} o LEFT JOIN {$orders_table} p ON p.ID = o.id
						WHERE p.post_status != 'auto-draft'
						AND p.post_type IN ({$order_post_type_placeholder})
						AND (o.id IS NULL OR o.date_updated_gmt < p.post_modified_gmt)
						",
						$order_post_types
					);
				}

				break;

			case 'deleted':
				$sql = $wpdb->prepare(
					"SELECT COUNT(1) FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s AND meta_value = %s",
					self::DELETED_RECORD_META_KEY,
					$this->get_current_deletion_record_meta_value()
				);

				break;
			default:
				break;
		}

		// phpcs:enable

		if ( 'bool' === $result ) {
			$sql  = str_replace( array( 'COUNT(1)', 'COUNT(*)' ), '1', $sql );
			$sql .= ' LIMIT 1';
		}

		return $sql;
	}

	/**
	 * Checks whether datastores are in sync.
	 *
	 * @since 8.9.0
	 *
	 * @return bool TRUE if datastores are in sync, FALSE otherwise.
	 */
	public function is_in_sync(): bool {
		global $wpdb;

		if ( is_null( $this->is_in_sync_cached ) ) {
			foreach ( array( 'out-of-sync', 'deleted' ) as $query_type ) {
				if ( ! empty( $wpdb->get_var( $this->get_sql_for_count_query( $query_type, 'bool' ) ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is already prepared.
					$this->is_in_sync_cached = false;
					break;
				}
			}
		}

		return $this->is_in_sync_cached;
	}

	/**
	 * Get the meta value for order deletion records based on which table is currently authoritative.
	 *
	 * @return string self::DELETED_FROM_ORDERS_META_VALUE if the orders table is authoritative, self::DELETED_FROM_POSTS_META_VALUE otherwise.
	 */
	private function get_current_deletion_record_meta_value() {
		return $this->custom_orders_table_is_authoritative() ?
				self::DELETED_FROM_ORDERS_META_VALUE :
				self::DELETED_FROM_POSTS_META_VALUE;
	}

	/**
	 * Is the custom orders table the authoritative data source for orders currently?
	 *
	 * @return bool Whether the custom orders table the authoritative data source for orders currently.
	 */
	public function custom_orders_table_is_authoritative(): bool {
		return wc_string_to_bool( get_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) );
	}

	/**
	 * Get a list of ids of orders than are out of sync.
	 *
	 * Valid values for $type are:
	 *
	 * ID_TYPE_MISSING_IN_ORDERS_TABLE: orders that exist in posts table but not in orders table.
	 * ID_TYPE_MISSING_IN_POSTS_TABLE: orders that exist in orders table but not in posts table (the corresponding post entries are placeholders).
	 * ID_TYPE_DIFFERENT_UPDATE_DATE: orders that exist in both tables but have different last update dates.
	 * ID_TYPE_DELETED_FROM_ORDERS_TABLE: orders deleted from the orders table but not yet from the posts table.
	 * ID_TYPE_DELETED_FROM_POSTS_TABLE: orders deleted from the posts table but not yet from the orders table.
	 *
	 * @param int $type One of ID_TYPE_MISSING_IN_ORDERS_TABLE, ID_TYPE_MISSING_IN_POSTS_TABLE, ID_TYPE_DIFFERENT_UPDATE_DATE.
	 * @param int $limit Maximum number of ids to return.
	 * @return array An array of order ids.
	 * @throws \Exception Invalid parameter.
	 */
	public function get_ids_of_orders_pending_sync( int $type, int $limit ) {
		global $wpdb;

		if ( $limit < 1 ) {
			throw new \Exception( '$limit must be at least 1' );
		}

		$orders_table                 = $this->data_store::get_orders_table_name();
		$order_post_types             = wc_get_order_types( 'cot-migration' );
		$order_post_type_placeholders = implode( ', ', array_fill( 0, count( $order_post_types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
		switch ( $type ) {
			case self::ID_TYPE_MISSING_IN_ORDERS_TABLE:
				$sql = $wpdb->prepare(
					"
SELECT posts.ID FROM $wpdb->posts posts
LEFT JOIN $orders_table orders ON posts.ID = orders.id
WHERE
  posts.post_type IN ($order_post_type_placeholders)
  AND posts.post_status != 'auto-draft'
  AND orders.id IS NULL
ORDER BY posts.ID ASC",
					$order_post_types
				);
				break;
			case self::ID_TYPE_MISSING_IN_POSTS_TABLE:
				$sql = $wpdb->prepare(
					"
SELECT orders.id FROM $wpdb->posts posts
RIGHT JOIN $orders_table orders ON posts.ID=orders.id
WHERE (posts.post_type IS NULL OR posts.post_type = '" . self::PLACEHOLDER_ORDER_POST_TYPE . "')
AND orders.status NOT IN ( 'auto-draft' )
AND orders.type IN ($order_post_type_placeholders)
ORDER BY posts.ID ASC",
					$order_post_types
				);
				break;
			case self::ID_TYPE_DIFFERENT_UPDATE_DATE:
				$operator = $this->custom_orders_table_is_authoritative() ? '>' : '<';

				$sql = $wpdb->prepare(
					"
SELECT orders.id FROM $orders_table orders
JOIN $wpdb->posts posts on posts.ID = orders.id
WHERE
  posts.post_type IN ($order_post_type_placeholders)
  AND orders.date_updated_gmt $operator posts.post_modified_gmt
ORDER BY orders.id ASC
",
					$order_post_types
				);
				break;
			case self::ID_TYPE_DELETED_FROM_ORDERS_TABLE:
				return $this->get_deleted_order_ids( true, $limit );
			case self::ID_TYPE_DELETED_FROM_POSTS_TABLE:
				return $this->get_deleted_order_ids( false, $limit );
			default:
				throw new \Exception( 'Invalid $type, must be one of the ID_TYPE_... constants.' );
		}
		// phpcs:enable

		// phpcs:ignore WordPress.DB
		return array_map( 'intval', $wpdb->get_col( $sql . " LIMIT $limit" ) );
	}

	/**
	 * Get the ids of the orders that are marked as deleted in the orders meta table.
	 *
	 * @param bool $deleted_from_orders_table True to get the ids of the orders deleted from the orders table, false o get the ids of the orders deleted from the posts table.
	 * @param int  $limit The maximum count of orders to return.
	 * @return array An array of order ids.
	 */
	private function get_deleted_order_ids( bool $deleted_from_orders_table, int $limit ) {
		global $wpdb;

		$deleted_from_table = $this->get_current_deletion_record_meta_value();

		$order_ids = $wpdb->get_col(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT DISTINCT(order_id) FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key=%s AND meta_value=%s LIMIT {$limit}",
				self::DELETED_RECORD_META_KEY,
				$deleted_from_table
			)
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return array_map( 'absint', $order_ids );
	}

	/**
	 * Cleanup all the synchronization status information,
	 * because the process has been disabled by the user via settings,
	 * or because there's nothing left to synchronize.
	 */
	public function cleanup_synchronization_state() {
		delete_option( 'woocommerce_initial_orders_pending_sync_count' ); // In case it existed from other times.
		wp_cache_delete( 'woocommerce_hpos_orders_in_sync' );
		$this->is_in_sync_cached = null;
	}

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		$batch = array_map( 'absint', $batch );

		$this->order_cache_controller->temporarily_disable_orders_cache_usage();

		$custom_orders_table_is_authoritative = $this->custom_orders_table_is_authoritative();
		$deleted_order_ids                    = $this->process_deleted_orders( $batch, $custom_orders_table_is_authoritative );
		$batch                                = array_diff( $batch, $deleted_order_ids );

		if ( ! empty( $batch ) ) {
			if ( $custom_orders_table_is_authoritative ) {
				foreach ( $batch as $id ) {
					$order = wc_get_order( $id );
					if ( ! $order ) {
						$this->error_logger->error( "Order $id not found during batch process, skipping." );
						continue;
					}
					$data_store = $order->get_data_store();
					$data_store->backfill_post_record( $order );
				}
			} else {
				$this->posts_to_cot_migrator->migrate_orders( $batch );
			}
		}

		if ( $this->is_in_sync() ) {
			$this->cleanup_synchronization_state();
			$this->order_cache_controller->maybe_restore_orders_cache_usage();
		}
	}

	/**
	 * Take a batch of order ids pending synchronization and process those that were deleted, ignoring the others
	 * (which will be orders that were created or modified) and returning the ids of the orders actually processed.
	 *
	 * @param array $batch Array of ids of order pending synchronization.
	 * @param bool  $custom_orders_table_is_authoritative True if the custom orders table is currently authoritative.
	 * @return array Order ids that have been actually processed.
	 */
	private function process_deleted_orders( array $batch, bool $custom_orders_table_is_authoritative ): array {
		global $wpdb;

		$deleted_from_table_name = $this->get_current_deletion_record_meta_value();

		$data_store_for_deletion =
			$custom_orders_table_is_authoritative ?
			new \WC_Order_Data_Store_CPT() :
			wc_get_container()->get( OrdersTableDataStore::class );

		$order_ids_as_sql_list = '(' . implode( ',', $batch ) . ')';

		$deleted_order_ids  = array();
		$meta_ids_to_delete = array();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deletion_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key=%s AND meta_value=%s AND order_id IN $order_ids_as_sql_list ORDER BY order_id DESC",
				self::DELETED_RECORD_META_KEY,
				$deleted_from_table_name
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $deletion_data ) ) {
			return array();
		}

		foreach ( $deletion_data as $item ) {
			$meta_id  = $item['id'];
			$order_id = $item['order_id'];

			if ( isset( $deleted_order_ids[ $order_id ] ) ) {
				$meta_ids_to_delete[] = $meta_id;
				continue;
			}

			if ( ! $data_store_for_deletion->order_exists( $order_id ) ) {
				$this->error_logger->warning( "Order {$order_id} doesn't exist in the backup table, thus it can't be deleted" );
				$deleted_order_ids[]  = $order_id;
				$meta_ids_to_delete[] = $meta_id;
				continue;
			}

			try {
				$order = new \WC_Order();
				$order->set_id( $order_id );
				$data_store_for_deletion->read( $order );

				$data_store_for_deletion->delete(
					$order,
					array(
						'force_delete'     => true,
						'suppress_filters' => true,
					)
				);
			} catch ( \Exception $ex ) {
				$this->error_logger->error( "Couldn't delete order {$order_id} from the backup table: {$ex->getMessage()}" );
				continue;
			}

			$deleted_order_ids[]  = $order_id;
			$meta_ids_to_delete[] = $meta_id;
		}

		if ( ! empty( $meta_ids_to_delete ) ) {
			$order_id_rows_as_sql_list = '(' . implode( ',', $meta_ids_to_delete ) . ')';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE id IN {$order_id_rows_as_sql_list}" );
		}

		return $deleted_order_ids;
	}

	/**
	 * Get total number of pending records that require update.
	 *
	 * @return int Number of pending records.
	 */
	public function get_total_pending_count(): int {
		return $this->get_current_orders_pending_sync_count();
	}

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int $size Size of the batch.
	 *
	 * @return array Batch of records.
	 */
	public function get_next_batch_to_process( int $size ): array {
		$orders_table_is_authoritative = $this->custom_orders_table_is_authoritative();

		$order_ids = $this->get_ids_of_orders_pending_sync(
			$orders_table_is_authoritative ? self::ID_TYPE_MISSING_IN_POSTS_TABLE : self::ID_TYPE_MISSING_IN_ORDERS_TABLE,
			$size
		);
		if ( count( $order_ids ) >= $size ) {
			return $order_ids;
		}

		$updated_order_ids = $this->get_ids_of_orders_pending_sync( self::ID_TYPE_DIFFERENT_UPDATE_DATE, $size - count( $order_ids ) );
		$order_ids         = array_merge( $order_ids, $updated_order_ids );
		if ( count( $order_ids ) >= $size ) {
			return $order_ids;
		}

		$deleted_order_ids = $this->get_ids_of_orders_pending_sync(
			$orders_table_is_authoritative ? self::ID_TYPE_DELETED_FROM_ORDERS_TABLE : self::ID_TYPE_DELETED_FROM_POSTS_TABLE,
			$size - count( $order_ids )
		);
		$order_ids         = array_merge( $order_ids, $deleted_order_ids );

		return array_map( 'absint', $order_ids );
	}

	/**
	 * Default batch size to use.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		$batch_size = self::ORDERS_SYNC_BATCH_SIZE;

		if ( $this->custom_orders_table_is_authoritative() ) {
			// Back-filling is slower than migration.
			$batch_size = absint( self::ORDERS_SYNC_BATCH_SIZE / 10 ) + 1;
		}
		/**
		 * Filter to customize the count of orders that will be synchronized in each step of the custom orders table to/from posts table synchronization process.
		 *
		 * @since 6.6.0
		 *
		 * @param int Default value for the count.
		 */
		return apply_filters( 'woocommerce_orders_cot_and_posts_sync_step_size', $batch_size );
	}

	/**
	 * A user friendly name for this process.
	 *
	 * @return string Name of the process.
	 */
	public function get_name(): string {
		return 'Order synchronizer';
	}

	/**
	 * A user friendly description for this process.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return 'Synchronizes orders between posts and custom order tables.';
	}

	/**
	 * Prevents deletion of order backup posts (regardless of sync setting) when HPOS is authoritative and the order
	 * still exists in HPOS.
	 * This should help with edge cases where wp_delete_post() would delete the HPOS record too or backfill would sync
	 * incorrect data from an order with no metadata from the posts table.
	 *
	 * @since 8.8.0
	 *
	 * @param WP_Post|false|null $delete Whether to go forward with deletion.
	 * @param WP_Post            $post   Post object.
	 * @return WP_Post|false|null
	 */
	private function maybe_prevent_deletion_of_post( $delete, $post ) {
		if ( self::PLACEHOLDER_ORDER_POST_TYPE !== $post->post_type && $this->custom_orders_table_is_authoritative() && $this->data_store->order_exists( $post->ID ) ) {
			$delete = false;
		}

		return $delete;
	}

	/**
	 * Handle the 'deleted_post' action.
	 *
	 * When posts is authoritative and sync is enabled, deleting a post also deletes COT data.
	 *
	 * @param int     $postid The post id.
	 * @param WP_Post $post The deleted post.
	 */
	private function handle_deleted_post( $postid, $post ): void {
		global $wpdb;

		$order_post_types = wc_get_order_types( 'cot-migration' );
		if ( ! in_array( $post->post_type, $order_post_types, true ) ) {
			return;
		}

		if ( ! $this->get_table_exists() ) {
			return;
		}

		if ( $this->data_sync_is_enabled() ) {
			$this->data_store->delete_order_data_from_custom_order_tables( $postid );
		} elseif ( $this->custom_orders_table_is_authoritative() ) {
			return;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $wpdb->get_var(
			$wpdb->prepare(
				"SELECT EXISTS (SELECT id FROM {$this->data_store::get_orders_table_name()} WHERE ID=%d)
						AND NOT EXISTS (SELECT order_id FROM {$this->data_store::get_meta_table_name()} WHERE order_id=%d AND meta_key=%s AND meta_value=%s)",
				$postid,
				$postid,
				self::DELETED_RECORD_META_KEY,
				self::DELETED_FROM_POSTS_META_VALUE
			)
		)
		) {
			$wpdb->insert(
				$this->data_store::get_meta_table_name(),
				array(
					'order_id'   => $postid,
					'meta_key'   => self::DELETED_RECORD_META_KEY,
					'meta_value' => self::DELETED_FROM_POSTS_META_VALUE,
				)
			);
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Handle the 'woocommerce_update_order' action.
	 *
	 * When posts is authoritative and sync is enabled, updating a post triggers a corresponding change in the COT table.
	 *
	 * @param int $order_id The order id.
	 */
	private function handle_updated_order( $order_id ): void {
		if ( ! $this->custom_orders_table_is_authoritative() && $this->data_sync_is_enabled() ) {
			$this->posts_to_cot_migrator->migrate_orders( array( $order_id ) );
		}
	}

	/**
	 * Handles deletion of auto-draft orders in sync with WP's own auto-draft deletion.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	private function delete_auto_draft_orders() {
		if ( ! $this->custom_orders_table_is_authoritative() ) {
			return;
		}

		// Fetch auto-draft orders older than 1 week.
		$to_delete = wc_get_orders(
			array(
				'date_query' => array(
					array(
						'column' => 'date_created',
						'before' => '-1 week',
					),
				),
				'orderby'    => 'date',
				'order'      => 'ASC',
				'status'     => 'auto-draft',
			)
		);

		foreach ( $to_delete as $order ) {
			$order->delete( true );
		}

		/**
		 * Fires after schedueld deletion of auto-draft orders has been completed.
		 *
		 * @since 7.7.0
		 */
		do_action( 'woocommerce_scheduled_auto_draft_delete' );
	}

	/**
	 * Handles deletion of trashed orders after `EMPTY_TRASH_DAYS` as defined by WordPress.
	 *
	 * @since 8.5.0
	 *
	 * @return void
	 */
	private function delete_trashed_orders() {
		if ( ! $this->custom_orders_table_is_authoritative() ) {
			return;
		}

		$delete_timestamp = $this->legacy_proxy->call_function( 'time' ) - ( DAY_IN_SECONDS * EMPTY_TRASH_DAYS );
		$args             = array(
			'status'        => 'trash',
			'limit'         => self::ORDERS_SYNC_BATCH_SIZE,
			'date_modified' => '<' . $delete_timestamp,
		);

		$orders = wc_get_orders( $args );
		if ( ! $orders || ! is_array( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {
			if ( $order->get_status() !== 'trash' ) {
				continue;
			}
			if ( $order->get_date_modified()->getTimestamp() >= $delete_timestamp ) {
				continue;
			}
			$order->delete( true );
		}
	}
}
