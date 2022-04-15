<?php
/**
 * DataSynchronizer class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the database structure creation and the data synchronization for the custom orders tables. Its responsibilites are:
 *
 * - Providing entry points for creating and deleting the required database tables.
 * - Synchronizing changes between the custom orders tables and the posts table whenever changes in orders happen.
 */
class DataSynchronizer {

	const ORDERS_DATA_SYNC_ENABLED_OPTION            = 'woocommerce_custom_orders_table_data_sync_enabled';
	const INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION   = 'woocommerce_initial_orders_pending_sync_count';
	const AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION = 'woocommerce_auto_flip_authoritative_table_roles';
	const PENDING_SYNC_IS_IN_PROGRESS_OPTION         = 'woocommerce_custom_orders_table_pending_sync_in_progress';
	const ORDERS_SYNC_SCHEDULED_ACTION_CALLBACK      = 'woocommerce_run_orders_sync_callback';
	const PENDING_SYNCHRONIZATION_FINISHED_ACTION    = 'woocommerce_orders_sync_finished';

	// Allowed values for $type in get_ids_of_orders_pending_sync method.
	const ID_TYPE_MISSING_IN_ORDERS_TABLE = 0;
	const ID_TYPE_MISSING_IN_POSTS_TABLE  = 1;
	const ID_TYPE_DIFFERENT_UPDATE_DATE   = 2;

	// TODO: Remove the usage of the fake pending orders count once development of the feature is complete.
	const FAKE_ORDERS_PENDING_SYNC_COUNT_OPTION = 'woocommerce_fake_orders_pending_sync_count';

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
	 * Class constructor.
	 */
	public function __construct() {
		add_action(
			self::ORDERS_SYNC_SCHEDULED_ACTION_CALLBACK,
			function() {
				$this->do_pending_orders_synchronization();
			}
		);
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore $data_store The data store to use.
	 * @param DatabaseUtil         $database_util The database util class to use.
	 */
	final public function init( OrdersTableDataStore $data_store, DatabaseUtil $database_util ) {
		$this->data_store    = $data_store;
		$this->database_util = $database_util;
	}

	/**
	 * Does the custom orders tables exist in the database?
	 *
	 * @return bool True if the custom orders tables exist in the database.
	 */
	public function check_orders_table_exists(): bool {
		$missing_tables = $this->database_util->get_missing_tables( $this->data_store->get_database_schema() );

		return count( $missing_tables ) === 0;
	}

	/**
	 * Create the custom orders database tables.
	 */
	public function create_database_tables() {
		$this->database_util->dbdelta( $this->data_store->get_database_schema() );
	}

	/**
	 * Delete the custom orders database tables.
	 */
	public function delete_database_tables() {
		$table_names = $this->data_store->get_all_table_names();

		foreach ( $table_names as $table_name ) {
			$this->database_util->drop_database_table( $table_name );
		}
	}

	/**
	 * Is the data sync between old and new tables currently enabled?
	 *
	 * @return bool
	 */
	public function data_sync_is_enabled(): bool {
		return 'yes' === get_option( self::ORDERS_DATA_SYNC_ENABLED_OPTION );
	}

	/**
	 * Is a sync process currently in progress?
	 *
	 * @return bool
	 */
	public function pending_data_sync_is_in_progress(): bool {
		return 'yes' === get_option( self::PENDING_SYNC_IS_IN_PROGRESS_OPTION );
	}

	/**
	 * Get the current sync process status.
	 * The information is meaningful only if pending_data_sync_is_in_progress return true.
	 *
	 * @return array
	 */
	public function get_sync_status() {
		return array(
			'initial_pending_count' => (int) get_option( self::INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION, 0 ),
			'current_pending_count' => $this->get_current_orders_pending_sync_count(),
			'auto_flip'             => 'yes' === get_option( self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION ),
			'sync_in_progress'      => $this->pending_data_sync_is_in_progress(),
		);
	}

	/**
	 * Calculate how many orders need to be synchronized currently.
	 *
	 * If an option whose name is given by self::FAKE_ORDERS_PENDING_SYNC_COUNT_OPTION exists,
	 * then the value of that option is returned. This is temporary, to ease testing the feature
	 * while it is in development.
	 *
	 * Otherwise a database query is performed to get how many orders match one of the following:
	 *
	 * - Existing in the authoritative table but not in the backup table.
	 * - Existing in both tables, but they have a different update date.
	 */
	public function get_current_orders_pending_sync_count(): int {
		global $wpdb;

		// TODO: Remove the usage of the fake pending orders count once development of the feature is complete.
		$count = get_option( self::FAKE_ORDERS_PENDING_SYNC_COUNT_OPTION );
		if ( false !== $count ) {
			return (int) $count;
		}

		$orders_table = $wpdb->prefix . 'wc_orders';

		if ( 'yes' === get_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) ) {
			$missing_orders_count_sql = "SELECT COUNT(1) FROM $orders_table WHERE post_id IS NULL";
		} else {
			$missing_orders_count_sql = "
SELECT COUNT( posts.ID ) FROM $wpdb->posts posts
LEFT JOIN $orders_table orders ON posts.ID = orders.post_id
WHERE posts.post_type='shop_order' AND orders.post_id IS NULL
AND posts.post_status != 'auto-draft'";
		}

		$sql = "
SELECT(
	($missing_orders_count_sql)
	+
	(SELECT COUNT(1) FROM (
		SELECT orders.post_id FROM $orders_table orders
		JOIN $wpdb->posts posts on posts.ID = orders.post_id
		WHERE orders.date_updated_gmt != posts.post_modified_gmt
	) x)
) count";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql ) );
	}

	/**
	 * Get a list of ids of orders than are out of sync.
	 *
	 * Valid values for $type are:
	 *
	 * ID_TYPE_MISSING_IN_ORDERS_TABLE: orders that exist in posts table but not in orders table. Returns post ids.
	 * ID_TYPE_MISSING_IN_POSTS_TABLE: orders that exist in orders table but not in posts table. Returns ids from orders table.
	 * ID_TYPE_DIFFERENT_UPDATE_DATE: orders that exist in both tables but have different last update dates. Returns ids from orders table.
	 *
	 * @param int $type One of ID_TYPE_MISSING_IN_ORDERS_TABLE, ID_TYPE_MISSING_IN_POSTS_TABLE, ID_TYPE_DIFFERENT_UPDATE_DATE.
	 * @param int $limit Maximum number of ids to return.
	 * @return array An array of order or post ids.
	 * @throws \Exception Invalid parameter.
	 */
	private function get_ids_of_orders_pending_sync( int $type, int $limit ) {
		global $wpdb;

		if ( $limit < 1 ) {
			throw new \Exception( '$limit must be at least 1' );
		}

		$orders_table = $wpdb->prefix . 'wc_orders';

		switch ( $type ) {
			case self::ID_TYPE_MISSING_IN_ORDERS_TABLE:
				$sql = "
SELECT posts.ID FROM $wpdb->posts posts
LEFT JOIN $orders_table orders ON posts.ID = orders.post_id
WHERE posts.post_type='shop_order' AND orders.post_id IS NULL
AND posts.post_status != 'auto-draft'";
				break;
			case self::ID_TYPE_MISSING_IN_POSTS_TABLE:
				$sql = "SELECT id FROM $orders_table WHERE post_id IS NULL";
				break;
			case self::ID_TYPE_DIFFERENT_UPDATE_DATE:
				$sql = "
SELECT orders.id FROM $orders_table orders
JOIN $wpdb->posts posts on posts.ID = orders.post_id
WHERE orders.date_updated_gmt != posts.post_modified_gmt";
				break;
			default:
				throw new \Exception( 'Invalid $type, must be one of the ID_TYPE_... constants.' );
		}

		// phpcs:ignore WordPress.DB
		return array_map( 'intval', $wpdb->get_col( $sql . " LIMIT $limit" ) );
	}

	/**
	 * Start an orders synchronization process.
	 * This will setup the appropriate status information and schedule the first synchronization batch.
	 */
	public function start_synchronizing_pending_orders() {
		$initial_pending_count = $this->get_current_orders_pending_sync_count();
		if ( 0 === $initial_pending_count ) {
			return;
		}

		update_option( self::INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION, $initial_pending_count );

		$queue = WC()->get_instance_of( \WC_Queue::class );
		$queue->cancel_all( self::ORDERS_SYNC_SCHEDULED_ACTION_CALLBACK );

		update_option( self::PENDING_SYNC_IS_IN_PROGRESS_OPTION, 'yes' );
		$this->schedule_pending_orders_synchronization();
	}

	/**
	 * Schedule the next orders synchronization batch.
	 */
	private function schedule_pending_orders_synchronization() {
		$queue = WC()->get_instance_of( \WC_Queue::class );
		$queue->schedule_single(
			WC()->call_function( 'time' ) + 1,
			self::ORDERS_SYNC_SCHEDULED_ACTION_CALLBACK,
			array(),
			'woocommerce-db-updates'
		);
	}

	/**
	 * Run one orders synchronization batch.
	 */
	private function do_pending_orders_synchronization() {
		if ( ! $this->pending_data_sync_is_in_progress() ) {
			return;
		}

		// TODO: Remove the usage of the fake pending orders count once development of the feature is complete.
		$fake_count = get_option( self::FAKE_ORDERS_PENDING_SYNC_COUNT_OPTION );
		if ( false !== $fake_count ) {
			update_option( 'woocommerce_fake_orders_pending_sync_count', (int) $fake_count - 1 );
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
		} else {
			// TODO: Use get_ids_of_orders_pending_sync to get a batch of order ids and syncrhonize them.
		}

		if ( 0 === $this->get_current_orders_pending_sync_count() ) {
			$this->cleanup_synchronization_state();

			/**
			 * Hook to signal that the orders tables synchronization process has finished (nothing left to synchronize).
			 */
			do_action( self::PENDING_SYNCHRONIZATION_FINISHED_ACTION );
		} else {
			$this->schedule_pending_orders_synchronization();
		}
	}

	/**
	 * Cleanup all the synchronization status information,
	 * because the process has been disabled by the user via settings,
	 * or because there's nothing left to syncrhonize.
	 */
	public function cleanup_synchronization_state() {
		delete_option( self::INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION );
		delete_option( self::PENDING_SYNC_IS_IN_PROGRESS_OPTION );
		delete_option( self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION );
	}
}
