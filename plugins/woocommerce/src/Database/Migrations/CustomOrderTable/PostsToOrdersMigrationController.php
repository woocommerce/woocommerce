<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * This is the main class used to perform the complete migration of orders
 * from the posts table to the custom orders table.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
class PostsToOrdersMigrationController {

	/**
	 * Error logger for migration errors.
	 *
	 * @var \WC_Logger
	 */
	private $error_logger;

	/**
	 * Array of objects used to perform the migration.
	 *
	 * @var \Automattic\WooCommerce\Database\Migrations\TableMigrator[]
	 */
	private $all_migrators;

	/**
	 * The source name to use for logs.
	 */
	public const LOGS_SOURCE_NAME = 'posts-to-orders-migration';

	/**
	 * Option name used as a self-expiring lock around a migration batch. Its value is the lock's release time.
	 *
	 * Without it, two processes migrating the same orders (for example the CLI sync and the background sync)
	 * both see the orders as not migrated yet and both insert their meta, which has no unique key to stop it.
	 *
	 * @since 11.3.0
	 */
	private const MIGRATION_LOCK_OPTION = 'wc_posts_to_orders_migration_lock';

	/**
	 * Seconds a held lock stays valid before another process may take it over, and how long a process polls for it.
	 * The holder pushes its release time forward between migration steps, so only a single step has to fit in it.
	 * While the holder's transaction is open its refresh also row-locks the option, so a contender's INSERT waits
	 * inside MySQL for the commit rather than polling.
	 */
	private const MIGRATION_LOCK_TTL = 15.0;

	/**
	 * Every release time this process has written to the lock row during the current batch, latest last.
	 *
	 * A refresh runs inside the batch's transaction, so a rollback puts an earlier value back. Matching on any of
	 * them keeps the lock refreshable and releasable after a failed step. Empty when this process holds no lock.
	 *
	 * @var string[]
	 */
	private $migration_lock_expiries = array();

	/**
	 * PostsToOrdersMigrationController constructor.
	 */
	public function __construct() {

		$this->all_migrators                           = array();
		$this->all_migrators['order']                  = new PostToOrderTableMigrator();
		$this->all_migrators['order_address_billing']  = new PostToOrderAddressTableMigrator( 'billing' );
		$this->all_migrators['order_address_shipping'] = new PostToOrderAddressTableMigrator( 'shipping' );
		$this->all_migrators['order_operational_data'] = new PostToOrderOpTableMigrator();
		$this->all_migrators['order_meta']             = new PostMetaToOrderMetaMigrator( $this->get_migrated_meta_keys() );
		$this->error_logger                            = wc_get_logger();
	}

	/**
	 * Helper method to get migrated keys for all the tables in this controller.
	 *
	 * @return string[] Array of meta keys.
	 */
	public function get_migrated_meta_keys() {
		$migrated_meta_keys = array();
		foreach ( $this->all_migrators as $name => $migrator ) {
			if ( method_exists( $migrator, 'get_meta_column_config' ) ) {
				$migrated_meta_keys = array_merge( $migrated_meta_keys, $migrator->get_meta_column_config() );
			}
		}
		return array_keys( $migrated_meta_keys );
	}

	/**
	 * Migrates a set of orders from the posts table to the custom orders tables.
	 *
	 * @param array $order_post_ids List of post IDs of the orders to migrate.
	 */
	public function migrate_orders( array $order_post_ids ): void {
		$this->error_logger = WC()->call_function( 'wc_get_logger' );

		$data = array();
		try {
			foreach ( $this->all_migrators as $name => $migrator ) {
				$data[ $name ] = $migrator->fetch_sanitized_migration_data( $order_post_ids );
				if ( ! empty( $data[ $name ]['errors'] ) ) {
					$this->handle_migration_error( $order_post_ids, $data[ $name ]['errors'], null, null, $name );
					return;
				}
			}
		} catch ( \Exception $e ) {
			$this->handle_migration_error( $order_post_ids, $data, $e, null, 'Fetching data' );
			return;
		}

		$still_holds_lock = $this->refresh_migration_lock();
		if ( ! $still_holds_lock ) {
			return;
		}
		$using_transactions = $this->maybe_start_transaction();

		foreach ( $this->all_migrators as $name => $migrator ) {
			$results   = $migrator->process_migration_data( $data[ $name ] );
			$errors    = array_unique( $results['errors'] );
			$exception = $results['exception'];

			if ( null !== $exception || ! empty( $errors ) ) {
				$this->handle_migration_error( $order_post_ids, $errors, $exception, $using_transactions, $name );
				return;
			}

			// Another process took the lock over, so the batch is theirs now: leave it unwritten for them.
			if ( ! $this->refresh_migration_lock() ) {
				if ( $using_transactions ) {
					$this->rollback_transaction();
				}
				return;
			}
		}

		if ( $using_transactions ) {
			$this->commit_transaction();
		}
		$this->maybe_clear_order_datastore_cache_for_ids( $order_post_ids );
	}

	/**
	 * Migrates a batch of orders while holding a lock that keeps other batches waiting.
	 *
	 * Two batch processes (the CLI sync and the background sync) migrating the same orders at once each insert
	 * the orders' meta, so the batch paths take this lock. Single-order syncs on save do not, so a save never
	 * waits for a running batch. Nothing is written without the lock: if it cannot be taken, or is lost to
	 * another process mid-batch, the batch is left unwritten and its orders stay pending for the next run.
	 *
	 * @internal
	 * @since 11.3.0
	 *
	 * @param array $order_post_ids List of post IDs of the orders to migrate.
	 */
	public function migrate_orders_with_lock( array $order_post_ids ): void {
		if ( ! $this->acquire_migration_lock() ) {
			$this->log_lock_warning( sprintf( 'Could not take the orders migration lock within %d seconds, skipping this batch. The orders stay pending.', self::MIGRATION_LOCK_TTL ) );
			return;
		}
		try {
			$this->migrate_orders( $order_post_ids );
		} finally {
			$this->release_migration_lock();
		}
	}

	/**
	 * Take the migration lock, waiting for another process to release it or for it to expire.
	 *
	 * The lock is a row in the options table, as in BatchProcessingController: the unique option name makes the
	 * INSERT fail while another process holds it, and a lock whose release time has passed can be taken over.
	 *
	 * @return bool Whether the lock was taken.
	 */
	private function acquire_migration_lock(): bool {
		global $wpdb;

		$this->migration_lock_expiries = array();
		$deadline                      = microtime( true ) + self::MIGRATION_LOCK_TTL;
		$suppress                      = $wpdb->suppress_errors( true );
		try {
			do {
				$time   = microtime( true );
				$now    = number_format( $time, 6, '.', '' );
				$expiry = number_format( $time + self::MIGRATION_LOCK_TTL, 6, '.', '' );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$acquired = $wpdb->insert(
					$wpdb->options,
					array(
						'option_name'  => self::MIGRATION_LOCK_OPTION,
						'option_value' => $expiry,
						'autoload'     => 'no',
					),
					array( '%s', '%s', '%s' )
				);

				if ( $acquired ) {
					$this->migration_lock_expiries[] = $expiry;
					return true;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$taken_over = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value < %s",
						$expiry,
						self::MIGRATION_LOCK_OPTION,
						$now
					)
				);
				if ( $taken_over ) {
					$this->migration_lock_expiries[] = $expiry;
					$this->log_lock_warning( 'Took over an expired orders migration lock. The process holding it may have died mid-batch.' );
					return true;
				}

				usleep( 50000 );
			} while ( microtime( true ) < $deadline );

			return false;
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	/**
	 * Push the held lock's release time forward so a long batch does not lose it between steps.
	 *
	 * @return bool False if this process held the lock and has lost it to another process; true otherwise,
	 *              including when this process never held a lock (the single-order sync path).
	 */
	private function refresh_migration_lock(): bool {
		global $wpdb;

		if ( empty( $this->migration_lock_expiries ) ) {
			return true;
		}

		$expiry = number_format( microtime( true ) + self::MIGRATION_LOCK_TTL, 6, '.', '' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders -- The IN list is %s placeholders generated per expiry.
		$refreshed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value IN ( {$this->own_expiries_placeholders()} )",
				array_merge( array( $expiry, self::MIGRATION_LOCK_OPTION ), $this->migration_lock_expiries )
			)
		);
		// phpcs:enable

		if ( $refreshed ) {
			$this->migration_lock_expiries[] = $expiry;
			return true;
		}

		$this->migration_lock_expiries = array();
		$this->log_lock_warning( 'Lost the orders migration lock to another process mid-batch, leaving the batch for it.' );
		return false;
	}

	/**
	 * Release the migration lock, unless it expired and another process took it over.
	 */
	private function release_migration_lock(): void {
		global $wpdb;

		if ( empty( $this->migration_lock_expiries ) ) {
			return;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders -- The IN list is %s placeholders generated per expiry.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value IN ( {$this->own_expiries_placeholders()} )",
				array_merge( array( self::MIGRATION_LOCK_OPTION ), $this->migration_lock_expiries )
			)
		);
		// phpcs:enable
		$this->migration_lock_expiries = array();
	}

	/**
	 * Placeholder list for the release times this process has written, for use in an IN clause.
	 *
	 * @return string Comma-separated %s placeholders.
	 */
	private function own_expiries_placeholders(): string {
		return implode( ', ', array_fill( 0, count( $this->migration_lock_expiries ), '%s' ) );
	}

	/**
	 * Log a warning about the migration lock.
	 *
	 * @param string $message The message to log.
	 */
	private function log_lock_warning( string $message ): void {
		WC()->call_function( 'wc_get_logger' )->warning( $message, array( 'source' => self::LOGS_SOURCE_NAME ) );
	}

	/**
	 * Log migration errors if any.
	 *
	 * @param array           $order_post_ids List of post IDs of the orders to migrate.
	 * @param array           $errors List of errors to log.
	 * @param \Exception|null $exception Exception to log.
	 * @param bool|null       $using_transactions Whether transactions were used.
	 * @param string          $name Name of the migrator.
	 */
	private function handle_migration_error( array $order_post_ids, array $errors, ?\Exception $exception, ?bool $using_transactions, string $name ) {
		$batch = ArrayUtil::to_ranges_string( $order_post_ids );

		if ( null !== $exception ) {
			$exception_class = get_class( $exception );
			$this->error_logger->error(
				"$name: when processing ids $batch: ($exception_class) {$exception->getMessage()}, {$exception->getTraceAsString()}",
				array(
					'source'    => self::LOGS_SOURCE_NAME,
					'ids'       => $order_post_ids,
					'exception' => $exception,
				)
			);
		}

		foreach ( $errors as $error ) {
			$this->error_logger->error(
				"$name: when processing ids $batch: $error",
				array(
					'source' => self::LOGS_SOURCE_NAME,
					'ids'    => $order_post_ids,
					'error'  => $error,
				)
			);
		}

		if ( $using_transactions ) {
			$this->rollback_transaction();
		} else {
			$this->maybe_clear_order_datastore_cache_for_ids( $order_post_ids );
		}
	}

	/**
	 * Clear the cache of order data for modified orders during migration if cache is enabled.
	 *
	 * @param array $order_post_ids List of order IDs of the orders to clear from cache.
	 *
	 * @return void
	 */
	private function maybe_clear_order_datastore_cache_for_ids( array $order_post_ids ) {
		if ( OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			$orders_table_datastore = wc_get_container()->get( OrdersTableDataStore::class );
			if ( is_callable( array( $orders_table_datastore, 'clear_cached_data' ) ) ) {
				$orders_table_datastore->clear_cached_data( $order_post_ids );
			}
		}
	}

	/**
	 * Start a database transaction if the configuration mandates so.
	 *
	 * @return bool|null True if transaction started, false if transactions won't be used, null if transaction failed to start.
	 *
	 * @throws \Exception If the transaction isolation level is invalid.
	 */
	private function maybe_start_transaction(): ?bool {

		$use_transactions = get_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		if ( 'yes' !== $use_transactions ) {
			return null;
		}

		$transaction_isolation_level        = get_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, CustomOrdersTableController::DEFAULT_DB_TRANSACTIONS_ISOLATION_LEVEL );
		$valid_transaction_isolation_levels = array( 'READ UNCOMMITTED', 'READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE' );
		if ( ! in_array( $transaction_isolation_level, $valid_transaction_isolation_levels, true ) ) {
			throw new \Exception( "Invalid database transaction isolation level name $transaction_isolation_level" );
		}

		$set_transaction_isolation_level_command = "SET TRANSACTION ISOLATION LEVEL $transaction_isolation_level";

		// We suppress errors in transaction isolation level setting because it's not supported by all DB engines, additionally, this might be executing in context of another transaction with a different isolation level.
		if ( ! $this->db_query( $set_transaction_isolation_level_command, true ) ) {
			return null;
		}

		return $this->db_query( 'START TRANSACTION' ) ? true : null;
	}

	/**
	 * Commit the current database transaction.
	 *
	 * @return bool True on success, false on error.
	 */
	private function commit_transaction(): bool {
		return $this->db_query( 'COMMIT' );
	}

	/**
	 * Rollback the current database transaction.
	 *
	 * @return bool True on success, false on error.
	 */
	private function rollback_transaction(): bool {
		return $this->db_query( 'ROLLBACK' );
	}

	/**
	 * Execute a database query and log any errors.
	 *
	 * @param string $query          The SQL query to execute.
	 * @param bool   $suppress_errors Whether to suppress errors.
	 *
	 * @return bool True if the query succeeded, false if there were errors.
	 */
	private function db_query( string $query, bool $suppress_errors = false ): bool {
		$wpdb = WC()->get_global( 'wpdb' );

		try {
			if ( $suppress_errors ) {
				$suppress = $wpdb->suppress_errors( true );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query );
			if ( $suppress_errors ) {
				$wpdb->suppress_errors( $suppress );
			}
		} catch ( \Exception $exception ) {
			$exception_class = get_class( $exception );
			$this->error_logger->error(
				"PostsToOrdersMigrationController: when executing $query: ($exception_class) {$exception->getMessage()}, {$exception->getTraceAsString()}",
				array(
					'source'    => self::LOGS_SOURCE_NAME,
					'exception' => $exception,
				)
			);
			return false;
		}

		$error = $wpdb->last_error;
		if ( '' !== $error ) {
			$this->error_logger->error(
				"PostsToOrdersMigrationController: when executing $query: $error",
				array(
					'source' => self::LOGS_SOURCE_NAME,
					'error'  => $error,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Verify whether the given order IDs were migrated properly or not.
	 *
	 * @param array $order_post_ids Order IDs.
	 *
	 * @return array Array of failed IDs along with columns.
	 */
	public function verify_migrated_orders( array $order_post_ids ): array {
		$errors = array();
		foreach ( $this->all_migrators as $migrator ) {
			if ( method_exists( $migrator, 'verify_migrated_data' ) ) {
				$errors = $errors + $migrator->verify_migrated_data( $order_post_ids );
			}
		}
		return $errors;
	}

	/**
	 * Migrates an order from the posts table to the custom orders tables.
	 *
	 * @param int $order_post_id Post ID of the order to migrate.
	 */
	public function migrate_order( int $order_post_id ): void {
		$this->migrate_orders( array( $order_post_id ) );
	}
}
