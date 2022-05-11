<?php

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use WP_CLI;

/**
 * Credits https://github.com/liquidweb/woocommerce-custom-orders-table/blob/develop/includes/class-woocommerce-custom-orders-table-cli.php.
 *
 * CLI tool for migrating order data to/from custom table.
 *
 * Class CLIRunner
 */
class CLIRunner {

	/**
	 * CustomOrdersTableController instance.
	 *
	 * @var CustomOrdersTableController
	 */
	private $controller;

	/**
	 * DataSynchronizer instance.
	 *
	 * @var DataSynchronizer;
	 */
	private $synchronizer;

	/**
	 * PostsToOrdersMigrationController instance.
	 *
	 * @var PostsToOrdersMigrationController
	 */
	private $post_to_cot_migrator;

	/**
	 * Init method, invoked by DI container.
	 *
	 * @param CustomOrdersTableController      $controller Instance.
	 * @param DataSynchronizer                 $synchronizer Instance.
	 * @param PostsToOrdersMigrationController $posts_to_orders_migration_controller Instance.
	 *
	 * @internal
	 */
	final public function init( CustomOrdersTableController $controller, DataSynchronizer $synchronizer, PostsToOrdersMigrationController $posts_to_orders_migration_controller ) {
		$this->controller           = $controller;
		$this->synchronizer         = $synchronizer;
		$this->post_to_cot_migrator = $posts_to_orders_migration_controller;
	}

	/**
	 * Check if the COT feature is enabled.
	 *
	 * @param bool $log Optionally log a error message.
	 *
	 * @return bool Whether the COT feature is enabled.
	 */
	private function is_enabled( $log = true ) {
		if ( ! $this->controller->is_feature_visible() ) {
			if ( $log ) {
				WP_CLI::log( __( 'Custom order table usage is not enabled.', 'woocommerce' ) );
			}
		}

		return $this->controller->is_feature_visible();
	}

	/**
	 * Helper method to log warning that feature is not yet production ready.
	 */
	private function log_product_warning() {
		WP_CLI::log( __( 'This feature is not production ready yet. Make sure you are not running these commands in your production environment.', 'woocommerce' ) );
	}

	/**
	 * Count how many orders have yet to be migrated into the custom orders table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc cot count
	 *
	 * @param bool $log Whether to also log the order remaining count.
	 *
	 * @return int The number of orders to be migrated.*
	 */
	public function count( $log = true ) {
		if ( ! $this->is_enabled() ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$order_count = $this->synchronizer->get_current_orders_pending_sync_count();

		if ( $log ) {
			WP_CLI::log(
				sprintf(
				/* Translators: %1$d is the number of orders to be migrated. */
					_n( 'There is %1$d order to be migrated.', 'There are %1$d orders to be migrated.', $order_count, 'woocommerce' ),
					$order_count
				)
			);
		}

		return (int) $order_count;
	}

	/**
	 * Migrate order data to the custom orders table.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The number of orders to process in each batch.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc cot migrate --batch-size=100
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function migrate( $args = array(), $assoc_args = array() ) {
		$this->log_product_warning();
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $this->synchronizer->custom_orders_table_is_authoritative() ) {
			return WP_CLI::error( __( 'Migration is not yet supported when custom tables are authoritative. Switch to post tables as authoritative source if you are testing.', 'woocommerce' ) );
		}

		$order_count = $this->count();

		// Abort if there are no orders to migrate.
		if ( ! $order_count ) {
			return WP_CLI::warning( __( 'There are no orders to migrate, aborting.', 'woocommerce' ) );
		}

		$assoc_args         = wp_parse_args(
			$assoc_args,
			array(
				'batch-size' => 500,
			)
		);
		$batch_size         = ( (int) $assoc_args['batch-size'] ) === 0 ? 100 : (int) $assoc_args['batch-size'];
		$progress           = WP_CLI\Utils\make_progress_bar( 'Order Data Migration', $order_count / $batch_size );
		$processed          = 0;
		$batch_count        = 1;
		$avg_time_per_batch = 0.0;
		$total_time         = 0;

		while ( $order_count > 0 ) {

			WP_CLI::debug(
				sprintf(
				/* Translators: %1$d is the batch number, %2$d is the batch size. */
					__( 'Beginning batch #%1$d (%2$d orders/batch).', 'woocommerce' ),
					$batch_count,
					$batch_size
				)
			);
			$batch_start_time = microtime( true );
			$order_ids        = $this->synchronizer->get_ids_of_orders_pending_sync( $this->synchronizer::ID_TYPE_MISSING_IN_ORDERS_TABLE, $batch_size );
			$this->post_to_cot_migrator->migrate_orders( $order_ids );
			$processed       += count( $order_ids );
			$batch_total_time = microtime( true ) - $batch_start_time;

			WP_CLI::debug(
				sprintf(
				// Translators: %1$d is the batch number, %2$f is time taken to process batch.
					__( 'Batch %1$d (%2$d orders) completed in %3$f seconds', 'woocommerce' ),
					$batch_count,
					count( $order_ids ),
					$batch_total_time
				)
			);

			$avg_time_per_batch = ( ( $avg_time_per_batch * $batch_count ) + $batch_total_time ) / ( $batch_count + 1 );
			$batch_count ++;
			$total_time += $batch_total_time;

			$progress->tick();

			$remaining_count = $this->count( false );
			if ( $remaining_count === $order_count ) {
				return WP_CLI::error( __( 'Infinite loop detected, aborting.', 'woocommerce' ) );
			}
			$order_count = $remaining_count;
		}

		$progress->finish();

		// Issue a warning if no orders were migrated.
		if ( ! $processed ) {
			return WP_CLI::warning( __( 'No orders were migrated.', 'woocommerce' ) );
		}

		WP_CLI::log( __( 'Migration completed.', 'woocommerce' ) );

		return WP_CLI::success(
			sprintf(
			/* Translators: %1$d is the number of migrated orders. */
				_n( '%1$d order was migrated, in %2$f seconds', '%1$d orders were migrated in %2$f seconds', $processed, 'woocommerce' ),
				$processed,
				$total_time
			)
		);
	}

	/**
	 * Copy order data into the postmeta table.
	 *
	 * Note that this could dramatically increase the size of your postmeta table, but is recommended
	 * if you wish to stop using the custom orders table plugin.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The number of orders to process in each batch. Passing a value of 0 will disable batching.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Copy all order data into the post meta table, 100 posts at a time.
	 *     wp wc cot backfill --batch-size=100
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function backfill( $args = array(), $assoc_args = array() ) {
		return WP_CLI::error( __( 'Error: Backfill is not implemented yet.', 'woocommerce' ) );
	}

	/**
	 * Verify migrated order data with original posts data.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The number of orders to verify in each batch.
	 * ---
	 * default: 500
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Verify migrated order data, 100 orders at a time.
	 *     wp wc cot verify_cot_data --batch-size=100
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function verify_cot_data( $args = array(), $assoc_args = array() ) {
		global $wpdb;
		$this->log_product_warning();
		if ( ! $this->is_enabled() ) {
			return;
		}

		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'batch-size' => 500,
				'start-from' => 0,
			)
		);

		$batch_count    = 1;
		$total_time     = 0;
		$failed_ids     = array();
		$processed      = 0;
		$order_id_start = (int) $assoc_args['start-from'];
		$order_count    = $this->get_verify_order_count( $order_id_start );
		$batch_size     = ( (int) $assoc_args['batch-size'] ) === 0 ? 500 : (int) $assoc_args['batch-size'];

		$progress = WP_CLI\Utils\make_progress_bar( 'Order Data Verification', $order_count / $batch_size );

		if ( ! $order_count ) {
			return WP_CLI::warning( __( 'There are no orders to verify, aborting.', 'woocommerce' ) );
		}

		while ( $order_count > 0 ) {
			WP_CLI::debug(
				sprintf(
				/* Translators: %1$d is the batch number, %2$d is the batch size. */
					__( 'Beginning verification for batch #%1$d (%2$d orders/batch).', 'woocommerce' ),
					$batch_count,
					$batch_size
				)
			);

			$order_ids        = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND ID > %d ORDER BY ID ASC LIMIT %d",
					$order_id_start,
					$batch_size
				)
			);
			$batch_start_time = microtime( true );
			$failed_ids       = $failed_ids + $this->post_to_cot_migrator->verify_migrated_orders( $order_ids );
			$processed       += count( $order_ids );
			$batch_total_time = microtime( true ) - $batch_start_time;
			$batch_count ++;
			$total_time += $batch_total_time;

			$progress->tick();

			WP_CLI::debug(
				sprintf(
				// Translators: %1$d is the batch number, %2$f is time taken to process batch.
					__( 'Batch %1$d (%2$d orders) completed in %3$f seconds', 'woocommerce' ),
					$batch_count,
					count( $order_ids ),
					$batch_total_time
				)
			);

			$order_id_start  = max( $order_ids );
			$remaining_count = $this->get_verify_order_count( $order_id_start, false );
			if ( $remaining_count === $order_count ) {
				return WP_CLI::error( __( 'Infinite loop detected, aborting. No errors found.', 'woocommerce' ) );
			}
			$order_count = $remaining_count;
		}

		$progress->finish();
		WP_CLI::log( __( 'Verification completed.', 'woocommerce' ) );

		if ( 0 === count( $failed_ids ) ) {
			return WP_CLI::success(
				sprintf(
				/* Translators: %1$d is the number of migrated orders and %2$f is time taken */
					_n( '%1$d order was verified, in %2$f seconds', '%1$d orders were verified in %2$f seconds', $processed, 'woocommerce' ),
					$processed,
					$total_time
				)
			);
		} else {
			$errors = print_r( $failed_ids, true );

			return WP_CLI::error(
				sprintf(
				/* Translators: %1$d is the number of migrated orders, %2$f is time taken, $3%s is formatted array of order ids. */
					_n( '%1$d order was verified, in %2$f seconds. Errors were found: %3$s', '%1$d orders were verified in %2$f seconds. Errors were found %3$s', $processed, 'woocommerce' ),
					$processed,
					$total_time,
					$errors
				)
			);
		}
	}

	/**
	 * Helper method to get remaining order count
	 *
	 * @param int  $order_id_start Order ID to start from.
	 * @param bool $log            Whether to also log an error message.
	 *
	 * @return int Order count.
	 */
	private function get_verify_order_count( int $order_id_start, $log = true ) {
		global $wpdb;

		$order_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'shop_order' AND ID > %d",
				$order_id_start
			)
		);

		if ( $log ) {
			WP_CLI::log(
				sprintf(
				/* Translators: %1$d is the number of orders to be verified. */
					_n( 'There is %1$d order to be verified.', 'There are %1$d orders to be verified.', $order_count, 'woocommerce' ),
					$order_count
				)
			);
		}
		return $order_count;
	}
}
