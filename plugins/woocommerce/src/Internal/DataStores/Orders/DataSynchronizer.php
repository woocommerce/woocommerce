<?php
/**
 * DataSynchronizer class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the database structure creation and the data synchronization for the custom orders tables. Its responsibilites are:
 *
 * - Providing entry points for creating and deleting the required database tables.
 * - Synchronizing changes between the custom orders tables and the posts table whenever changes in orders happen.
 */
class DataSynchronizer implements BatchProcessorInterface {

	public const ORDERS_DATA_SYNC_ENABLED_OPTION           = 'woocommerce_custom_orders_table_data_sync_enabled';
	private const INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION = 'woocommerce_initial_orders_pending_sync_count';
	public const PENDING_SYNCHRONIZATION_FINISHED_ACTION   = 'woocommerce_orders_sync_finished';
	public const PLACEHOLDER_ORDER_POST_TYPE               = 'shop_order_placehold';

	private const ORDERS_SYNC_BATCH_SIZE = 250;
	// Allowed values for $type in get_ids_of_orders_pending_sync method.
	public const ID_TYPE_MISSING_IN_ORDERS_TABLE = 0;
	public const ID_TYPE_MISSING_IN_POSTS_TABLE  = 1;
	public const ID_TYPE_DIFFERENT_UPDATE_DATE   = 2;

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
	 * Class constructor.
	 */
	public function __construct() {
		// When posts is authoritative and sync is enabled, deleting a post also deletes COT data.
		add_action(
			'deleted_post',
			function( $postid, $post ) {
				if ( 'shop_order' === $post->post_type && ! $this->custom_orders_table_is_authoritative() && $this->data_sync_is_enabled() ) {
					$this->data_store->delete_order_data_from_custom_order_tables( $postid );
				}
			},
			10,
			2
		);
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param OrdersTableDataStore             $data_store The data store to use.
	 * @param DatabaseUtil                     $database_util The database util class to use.
	 * @param PostsToOrdersMigrationController $posts_to_cot_migrator The posts to COT migration class to use.
	 *@internal
	 */
	final public function init( OrdersTableDataStore $data_store, DatabaseUtil $database_util, PostsToOrdersMigrationController $posts_to_cot_migrator ) {
		$this->data_store            = $data_store;
		$this->database_util         = $database_util;
		$this->posts_to_cot_migrator = $posts_to_cot_migrator;
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
	 * Get the current sync process status.
	 * The information is meaningful only if pending_data_sync_is_in_progress return true.
	 *
	 * @return array
	 */
	public function get_sync_status() {
		return array(
			'initial_pending_count' => (int) get_option( self::INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION, 0 ),
			'current_pending_count' => $this->get_total_pending_count(),
		);
	}

	/**
	 * Calculate how many orders need to be synchronized currently.
	 * A database query is performed to get how many orders match one of the following:
	 *
	 * - Existing in the authoritative table but not in the backup table.
	 * - Existing in both tables, but they have a different update date.
	 */
	public function get_current_orders_pending_sync_count(): int {
		global $wpdb;

		$orders_table                = $this->data_store::get_orders_table_name();
		$order_post_types            = wc_get_order_types( 'cot-migration' );
		$order_post_type_placeholder = implode( ', ', array_fill( 0, count( $order_post_types ), '%s' ) );

		if ( $this->custom_orders_table_is_authoritative() ) {
			$missing_orders_count_sql = "
SELECT COUNT(1) FROM $wpdb->posts posts
INNER JOIN $orders_table orders ON posts.id=orders.id
WHERE posts.post_type = '" . self::PLACEHOLDER_ORDER_POST_TYPE . "'
 AND orders.status not in ( 'auto-draft' )
";
			$operator                 = '>';
		} else {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $order_post_type_placeholder is prepared.
			$missing_orders_count_sql = $wpdb->prepare(
				"
SELECT COUNT(1) FROM $wpdb->posts posts
LEFT JOIN $orders_table orders ON posts.id=orders.id
WHERE
  posts.post_type in ($order_post_type_placeholder)
  AND posts.post_status != 'auto-draft'
  AND orders.id IS NULL",
				$order_post_types
			);
			// phpcs:enable
			$operator = '<';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $missing_orders_count_sql is prepared.
		$sql = $wpdb->prepare(
			"
SELECT(
	($missing_orders_count_sql)
	+
	(SELECT COUNT(1) FROM (
		SELECT orders.id FROM $orders_table orders
		JOIN $wpdb->posts posts on posts.ID = orders.id
		WHERE
		  posts.post_type IN ($order_post_type_placeholder)
		  AND orders.date_updated_gmt $operator posts.post_modified_gmt
	) x)
) count",
			$order_post_types
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
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

		switch ( $type ) {
			case self::ID_TYPE_MISSING_IN_ORDERS_TABLE:
				$sql = $wpdb->prepare(
					"
SELECT posts.ID FROM $wpdb->posts posts
LEFT JOIN $orders_table orders ON posts.ID = orders.id
WHERE
  posts.post_type IN ($order_post_type_placeholders)
  AND posts.post_status != 'auto-draft'
  AND orders.id IS NULL",
					$order_post_types
				);
				break;
			case self::ID_TYPE_MISSING_IN_POSTS_TABLE:
				$sql = "
SELECT posts.ID FROM $wpdb->posts posts
INNER JOIN $orders_table orders ON posts.id=orders.id
WHERE posts.post_type = '" . self::PLACEHOLDER_ORDER_POST_TYPE . "'
AND orders.status not in ( 'auto-draft' )
";
				break;
			case self::ID_TYPE_DIFFERENT_UPDATE_DATE:
				$operator = $this->custom_orders_table_is_authoritative() ? '>' : '<';
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $order_post_type_placeholders is prepared.
				$sql = $wpdb->prepare(
					"
SELECT orders.id FROM $orders_table orders
JOIN $wpdb->posts posts on posts.ID = orders.id
WHERE
  posts.post_type IN ($order_post_type_placeholders)
  AND orders.date_updated_gmt $operator posts.post_modified_gmt
",
					$order_post_types
				);
				// phpcs:enable
				break;
			default:
				throw new \Exception( 'Invalid $type, must be one of the ID_TYPE_... constants.' );
		}

		// phpcs:ignore WordPress.DB
		return array_map( 'intval', $wpdb->get_col( $sql . " LIMIT $limit" ) );
	}

	/**
	 * Cleanup all the synchronization status information,
	 * because the process has been disabled by the user via settings,
	 * or because there's nothing left to synchronize.
	 */
	public function cleanup_synchronization_state() {
		delete_option( self::INITIAL_ORDERS_PENDING_SYNC_COUNT_OPTION );
	}

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	public function process_batch( array $batch ) : void {
		if ( $this->custom_orders_table_is_authoritative() ) {
			foreach ( $batch as $id ) {
				$order      = wc_get_order( $id );
				$data_store = $order->get_data_store();
				$data_store->backfill_post_record( $order );
			}
		} else {
			$this->posts_to_cot_migrator->migrate_orders( $batch );
		}
		if ( 0 === $this->get_total_pending_count() ) {
			$this->cleanup_synchronization_state();
		}
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
		if ( $this->custom_orders_table_is_authoritative() ) {
			$order_ids = $this->get_ids_of_orders_pending_sync( self::ID_TYPE_MISSING_IN_POSTS_TABLE, $size );
		} else {
			$order_ids = $this->get_ids_of_orders_pending_sync( self::ID_TYPE_MISSING_IN_ORDERS_TABLE, $size );
		}
		if ( count( $order_ids ) >= $size ) {
			return $order_ids;
		}

		$order_ids = $order_ids + $this->get_ids_of_orders_pending_sync( self::ID_TYPE_DIFFERENT_UPDATE_DATE, $size - count( $order_ids ) );
		return $order_ids;
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
}
