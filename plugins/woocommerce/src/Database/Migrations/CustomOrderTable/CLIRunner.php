<?php

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataHandler;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WP_CLI;

/**
 * CLI tool for migrating order data to/from custom table.
 *
 * Credits https://github.com/liquidweb/woocommerce-custom-orders-table/blob/develop/includes/class-woocommerce-custom-orders-table-cli.php.
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
	 * Registers commands for CLI.
	 */
	public function register_commands() {
		$legacy_commands = array( 'count_unmigrated', 'sync', 'verify_cot_data', 'enable', 'disable' );
		foreach ( $legacy_commands as $cmd ) {
			$new_cmd_name = 'verify_cot_data' === $cmd ? 'verify_data' : $cmd;

			WP_CLI::add_command( "wc hpos {$new_cmd_name}", array( $this, $cmd ) );
			WP_CLI::add_command(
				"wc cot {$cmd}",
				function ( array $args = array(), array $assoc_args = array() ) use ( $cmd, $new_cmd_name ) {
					WP_CLI::warning( "Command `wc cot {$cmd}` is deprecated since 8.9.0. Please use `wc hpos {$new_cmd_name}` instead." );
					return call_user_func( array( $this, $cmd ), $args, $assoc_args );
				}
			);
		}

		WP_CLI::add_command( 'wc hpos cleanup', array( $this, 'cleanup_post_data' ) );
		WP_CLI::add_command( 'wc hpos status', array( $this, 'status' ) );
		WP_CLI::add_command( 'wc hpos diff', array( $this, 'diff' ) );
		WP_CLI::add_command( 'wc hpos backfill', array( $this, 'backfill' ) );

		WP_CLI::add_command( 'wc cot migrate', array( $this, 'migrate' ) ); // Fully deprecated. No longer works.
	}

	/**
	 * Check if the COT feature is enabled.
	 *
	 * @param bool $log Optionally log a error message.
	 *
	 * @return bool Whether the COT feature is enabled.
	 */
	private function is_enabled( $log = true ): bool {
		if ( ! $this->controller->custom_orders_table_usage_is_enabled() ) {
			if ( $log ) {
				WP_CLI::log(
					sprintf(
						// translators: %s - link to testing instructions webpage.
						__( 'Custom order table usage is not enabled. If you are testing, you can enable it by following the testing instructions in %s', 'woocommerce' ),
						'https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book'
					)
				);
			}
		}

		return $this->controller->custom_orders_table_usage_is_enabled();
	}

	/**
	 * Count how many orders have yet to be migrated into the custom orders table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc hpos count_unmigrated
	 *
	 * @param array $args Positional arguments passed to the command.
	 *
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 *
	 * @return int The number of orders to be migrated.*
	 */
	public function count_unmigrated( $args = array(), $assoc_args = array() ): int {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$order_count = $this->synchronizer->get_current_orders_pending_sync_count();

		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'log' => true,
			)
		);
		if ( isset( $assoc_args['log'] ) && $assoc_args['log'] ) {
			WP_CLI::log(
				sprintf(
					/* Translators: %1$d is the number of orders to be synced. */
					_n(
						'There is %1$d order to be synced.',
						'There are %1$d orders to be synced.',
						$order_count,
						'woocommerce'
					),
					$order_count
				)
			);
		}

		return (int) $order_count;
	}

	/**
	 * Sync order data between the custom order tables and the core WordPress post tables.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The number of orders to process in each batch.
	 * ---
	 * default: 500
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc hpos sync --batch-size=500
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function sync( $args = array(), $assoc_args = array() ) {
		if ( ! $this->synchronizer->check_orders_table_exists() ) {
			WP_CLI::warning( __( 'Custom order tables does not exist, creating...', 'woocommerce' ) );
			$this->synchronizer->create_database_tables();
			if ( $this->synchronizer->check_orders_table_exists() ) {
				WP_CLI::success( __( 'Custom order tables were created successfully.', 'woocommerce' ) );
			} else {
				WP_CLI::error( __( 'Custom order tables could not be created.', 'woocommerce' ) );
			}
		}

		$order_count = $this->count_unmigrated();

		// Abort if there are no orders to migrate.
		if ( ! $order_count ) {
			return WP_CLI::warning( __( 'There are no orders to sync, aborting.', 'woocommerce' ) );
		}

		$assoc_args       = wp_parse_args(
			$assoc_args,
			array(
				'batch-size' => 500,
			)
		);
		$batch_size       = ( (int) $assoc_args['batch-size'] ) === 0 ? 500 : (int) $assoc_args['batch-size'];
		$progress         = WP_CLI\Utils\make_progress_bar( 'Order Data Sync', $order_count / $batch_size );
		$processed        = 0;
		$batch_count      = 1;
		$total_time       = 0;
		$orders_remaining = true;

		while ( $order_count > 0 || $orders_remaining ) {
			$remaining_count = $order_count;

			WP_CLI::debug(
				sprintf(
					/* Translators: %1$d is the batch number and %2$d is the batch size. */
					__( 'Beginning batch #%1$d (%2$d orders/batch).', 'woocommerce' ),
					$batch_count,
					$batch_size
				)
			);
			$batch_start_time = microtime( true );
			$order_ids        = $this->synchronizer->get_next_batch_to_process( $batch_size );
			if ( count( $order_ids ) ) {
				$this->synchronizer->process_batch( $order_ids );
			}
			$processed       += count( $order_ids );
			$batch_total_time = microtime( true ) - $batch_start_time;

			WP_CLI::debug(
				sprintf(
					// Translators: %1$d is the batch number, %2$d is the number of processed orders and %3$d is the execution time in seconds.
					__( 'Batch %1$d (%2$d orders) completed in %3$d seconds', 'woocommerce' ),
					$batch_count,
					count( $order_ids ),
					$batch_total_time
				)
			);

			++$batch_count;
			$total_time += $batch_total_time;

			$progress->tick();

			$orders_remaining = count( $this->synchronizer->get_next_batch_to_process( 1 ) ) > 0;
			$order_count      = $remaining_count - $batch_size;

			if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}

			$GLOBALS['wpdb']->flush();
		}

		$progress->finish();

		// Issue a warning if no orders were migrated.
		if ( ! $processed ) {
			return WP_CLI::warning( __( 'No orders were synced.', 'woocommerce' ) );
		}

		WP_CLI::log( __( 'Sync completed.', 'woocommerce' ) );

		return WP_CLI::success(
			sprintf(
				/* Translators: %1$d is the number of migrated orders and %2$d is the execution time in seconds. */
				_n(
					'%1$d order was synced in %2$d seconds.',
					'%1$d orders were synced in %2$d seconds.',
					$processed,
					'woocommerce'
				),
				$processed,
				$total_time
			)
		);
	}

	/**
	 * [Deprecated] Use `wp wc hpos sync` instead.
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
	 * default: 500
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Copy all order data into the post meta table, 500 posts at a time.
	 *     wp wc cot migrate --batch-size=500
	 */
	public function migrate() {
		WP_CLI::log( __( 'Migrate command is deprecated. Please use `sync` instead.', 'woocommerce' ) );
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
	 * [--start-from=<order_id>]
	 * : Order ID to start from.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--end-at=<order_id>]
	 * : Order ID to end at.
	 * ---
	 * default: -1
	 * ---
	 *
	 * [--verbose]
	 * : Whether to output errors as they happen in batch, or output them all together at the end.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--order-types]
	 * : Comma seperated list of order types that needs to be verified. For example, --order-types=shop_order,shop_order_refund
	 * ---
	 * default: Output of function `wc_get_order_types( 'cot-migration' )`
	 *
	 * [--re-migrate]
	 * : Attempt to re-migrate orders that failed verification. You should only use this option when you have never run the site with HPOS as authoritative source of order data yet, or you have manually checked the reported errors, otherwise, you risk stale data overwriting the more recent data.
	 * default: false
	 *
	 * ## EXAMPLES
	 *
	 *     # Verify migrated order data, 500 orders at a time.
	 *     wp wc hpos verify_cot_data --batch-size=500 --start-from=0 --end-at=10000
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function verify_cot_data( $args = array(), $assoc_args = array() ) {
		global $wpdb;

		if ( ! $this->synchronizer->check_orders_table_exists() ) {
			WP_CLI::error( __( 'Orders table does not exist.', 'woocommerce' ) );
			return;
		}

		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'batch-size'  => 500,
				'start-from'  => 0,
				'end-at'      => - 1,
				'verbose'     => false,
				'order-types' => '',
				're-migrate'  => false,
			)
		);

		$batch_count    = 1;
		$total_time     = 0;
		$failed_ids     = array();
		$processed      = 0;
		$order_id_start = (int) $assoc_args['start-from'];
		$order_id_end   = (int) $assoc_args['end-at'];
		$order_id_end   = -1 === $order_id_end ? PHP_INT_MAX : $order_id_end;
		$batch_size     = ( (int) $assoc_args['batch-size'] ) === 0 ? 500 : (int) $assoc_args['batch-size'];
		$verbose        = (bool) $assoc_args['verbose'];
		$order_types    = wc_get_order_types( 'cot-migration' );
		$remigrate      = (bool) $assoc_args['re-migrate'];
		if ( ! empty( $assoc_args['order-types'] ) ) {
			$passed_order_types = array_map( 'trim', explode( ',', $assoc_args['order-types'] ) );
			$order_types        = array_intersect( $order_types, $passed_order_types );
		}

		if ( 0 === count( $order_types ) ) {
			return WP_CLI::error(
				sprintf(
				/* Translators: %s is the comma seperated list of order types. */
					__( 'Passed order type does not match any registered order types. Following order types are registered: %s', 'woocommerce' ),
					implode( ',', wc_get_order_types( 'cot-migration' ) )
				)
			);
		}

		$order_types_pl = implode( ',', array_fill( 0, count( $order_types ), '%s' ) );

		$order_count = $this->get_verify_order_count( $order_id_start, $order_id_end, $order_types, false );

		$progress = WP_CLI\Utils\make_progress_bar( 'Order Data Verification', $order_count / $batch_size );

		$error_processing = false;

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

			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Inputs are prepared.
			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type in ( $order_types_pl ) AND ID >= %d AND ID <= %d ORDER BY ID ASC LIMIT %d",
					array_merge(
						$order_types,
						array(
							$order_id_start,
							$order_id_end,
							$batch_size,
						)
					)
				)
			);
			// phpcs:enable
			$batch_start_time            = microtime( true );
			$failed_ids_in_current_batch = $this->post_to_cot_migrator->verify_migrated_orders( $order_ids );
			$failed_ids_in_current_batch = $this->verify_meta_data( $order_ids, $failed_ids_in_current_batch );
			$failed_ids                  = $verbose ? array() : $failed_ids + $failed_ids_in_current_batch;
			$error_processing            = $error_processing || ! empty( $failed_ids_in_current_batch );
			$processed                  += count( $order_ids );
			$batch_total_time            = microtime( true ) - $batch_start_time;
			++$batch_count;
			$total_time += $batch_total_time;

			if ( count( $failed_ids_in_current_batch ) > 0 ) {
				if ( $verbose ) {
					$errors = wp_json_encode( $failed_ids_in_current_batch, JSON_PRETTY_PRINT );
					WP_CLI::warning(
						sprintf(
						/* Translators: %1$d is number of errors and %2$s is the formatted array of order IDs. */
							_n(
								'%1$d error found: %2$s. Please review the error above.',
								'%1$d errors found: %2$s. Please review the errors above.',
								count( $failed_ids_in_current_batch ),
								'woocommerce'
							),
							count( $failed_ids_in_current_batch ),
							$errors
						)
					);
				}

				if ( $remigrate ) {
					$failed_ids       = $failed_ids ? array_diff_key( $failed_ids, $failed_ids_in_current_batch ) : array();
					$error_processing = ( ! $verbose ) && $failed_ids;

					$verbose && WP_CLI::warning( sprintf( __( 'Attempting to remigrate...', 'woocommerce' ) ) );

					$failed_ids_in_current_batch_keys = array_keys( $failed_ids_in_current_batch );
					$this->synchronizer->process_batch( $failed_ids_in_current_batch_keys );
					$errors_in_remigrate_batch = $this->post_to_cot_migrator->verify_migrated_orders( $failed_ids_in_current_batch_keys );
					$errors_in_remigrate_batch = $this->verify_meta_data( $failed_ids_in_current_batch_keys, $errors_in_remigrate_batch );

					if ( count( $errors_in_remigrate_batch ) > 0 ) {
						$error_processing = true;
						$formatted_errors = wp_json_encode( $errors_in_remigrate_batch, JSON_PRETTY_PRINT );

						if ( $verbose ) {
							WP_CLI::warning(
								sprintf(
								/* Translators: %1$d is number of errors and %2$s is the formatted array of order IDs. */
									_n(
										'%1$d error found: %2$s when re-migrating order. Please review the error above.',
										'%1$d errors found: %2$s when re-migrating orders. Please review the errors above.',
										count( $errors_in_remigrate_batch ),
										'woocommerce'
									),
									count( $errors_in_remigrate_batch ),
									$formatted_errors
								)
							);
						} else {
							array_walk(
								$errors_in_remigrate_batch,
								function ( &$errors_for_order ) {
									$errors_for_order[] = array( 'remigrate_failed' => true );
								}
							);
							$failed_ids = $failed_ids + $errors_in_remigrate_batch;
						}
					} else {
						$verbose && WP_CLI::warning( 'Re-migration successful.', 'woocommerce' );
					}
				}
			}

			$progress->tick();

			WP_CLI::debug(
				sprintf(
					/* Translators: %1$d is the batch number, %2$d is time taken to process batch. */
					__( 'Batch %1$d (%2$d orders) completed in %3$d seconds.', 'woocommerce' ),
					$batch_count,
					count( $order_ids ),
					$batch_total_time
				)
			);

			$order_id_start  = max( $order_ids ) + 1;
			$remaining_count = $this->get_verify_order_count( $order_id_start, $order_id_end, $order_types, false );
			if ( $remaining_count === $order_count ) {
				return WP_CLI::error( __( 'Infinite loop detected, aborting. No errors found.', 'woocommerce' ) );
			}
			$order_count = $remaining_count;
		}

		$progress->finish();
		WP_CLI::log( __( 'Verification completed.', 'woocommerce' ) );

		if ( ! $error_processing ) {
			return WP_CLI::success(
				sprintf(
					/* Translators: %1$d is the number of migrated orders and %2$d is time taken. */
					_n(
						'%1$d order was verified in %2$d seconds.',
						'%1$d orders were verified in %2$d seconds.',
						$processed,
						'woocommerce'
					),
					$processed,
					$total_time
				)
			);
		} else {
			return WP_CLI::error(
				sprintf(
					'%1$s %2$s',
					sprintf(
						/* Translators: %1$d is the number of migrated orders and %2$d is the execution time in seconds. */
						_n(
							'%1$d order was verified in %2$d seconds.',
							'%1$d orders were verified in %2$d seconds.',
							$processed,
							'woocommerce'
						),
						$processed,
						$total_time
					),
					$failed_ids
						? sprintf(
							/* Translators: %1$d is number of errors and %2$s is the formatted array of order IDs. */
							_n(
								'%1$d error found: %2$s. Please review the error above.',
								'%1$d errors found: %2$s. Please review the errors above.',
								count( $failed_ids ),
								'woocommerce'
							),
							count( $failed_ids ),
							wp_json_encode( $failed_ids, JSON_PRETTY_PRINT )
						)
						: __( 'Please review the errors above.', 'woocommerce' )
				)
			);
		}
	}

	/**
	 * Helper method to get count for orders needing verification.
	 *
	 * @param int   $order_id_start Order ID to start from.
	 * @param int   $order_id_end Order ID to end at.
	 * @param array $order_types List of order types to verify.
	 * @param bool  $log Whether to also log an error message.
	 *
	 * @return int Order count.
	 */
	private function get_verify_order_count( int $order_id_start, int $order_id_end, array $order_types, bool $log = true ): int {
		global $wpdb;

		$order_types_placeholder = implode( ',', array_fill( 0, count( $order_types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Inputs are prepared.
		$order_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type in ($order_types_placeholder) AND ID >= %d AND ID <= %d",
				array_merge(
					$order_types,
					array(
						$order_id_start,
						$order_id_end,
					)
				)
			)
		);
		// phpcs:enable

		if ( $log ) {
			WP_CLI::log(
				sprintf(
					/* Translators: %1$d is the number of orders to be verified. */
					_n(
						'There is %1$d order to be verified.',
						'There are %1$d orders to be verified.',
						$order_count,
						'woocommerce'
					),
					$order_count
				)
			);
		}

		return $order_count;
	}

	/**
	 * Verify meta data as part of verifying the order object.
	 *
	 * @param array $order_ids Order IDs.
	 * @param array $failed_ids Array for storing failed IDs.
	 *
	 * @return array Failed IDs with meta details.
	 */
	private function verify_meta_data( array $order_ids, array $failed_ids ): array {
		$meta_keys_to_ignore = $this->synchronizer->get_ignored_order_props();

		global $wpdb;
		if ( ! count( $order_ids ) ) {
			return array();
		}
		$excluded_columns             = array_merge(
			$this->post_to_cot_migrator->get_migrated_meta_keys(),
			$meta_keys_to_ignore
		);
		$excluded_columns_placeholder = implode( ', ', array_fill( 0, count( $excluded_columns ), '%s' ) );
		$order_ids_placeholder        = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );
		$meta_table                   = OrdersTableDataStore::get_meta_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table names are hardcoded, orders_ids and excluded_columns are prepared.
		$query       = $wpdb->prepare(
			"
SELECT {$wpdb->postmeta}.post_id as entity_id, {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value
FROM $wpdb->postmeta
WHERE
      {$wpdb->postmeta}.post_id in ( $order_ids_placeholder ) AND
      {$wpdb->postmeta}.meta_key not in ( $excluded_columns_placeholder )
ORDER BY {$wpdb->postmeta}.post_id ASC, {$wpdb->postmeta}.meta_key ASC;
",
			array_merge(
				$order_ids,
				$excluded_columns
			)
		);
		$source_data = $wpdb->get_results( $query, ARRAY_A );
		// phpcs:enable

		$normalized_source_data = $this->normalize_raw_meta_data( $source_data );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table names are hardcoded, orders_ids and excluded_columns are prepared.
		$migrated_query = $wpdb->prepare(
			"
SELECT $meta_table.order_id as entity_id, $meta_table.meta_key, $meta_table.meta_value
FROM $meta_table
WHERE
	$meta_table.order_id in ( $order_ids_placeholder )
ORDER BY $meta_table.order_id ASC, $meta_table.meta_key ASC;
",
			$order_ids
		);
		$migrated_data  = $wpdb->get_results( $migrated_query, ARRAY_A );
		// phpcs:enable

		$normalized_migrated_meta_data = $this->normalize_raw_meta_data( $migrated_data );

		foreach ( $normalized_source_data as $order_id => $meta ) {
			foreach ( $meta as $meta_key => $values ) {
				$migrated_meta_values = isset( $normalized_migrated_meta_data[ $order_id ][ $meta_key ] ) ? $normalized_migrated_meta_data[ $order_id ][ $meta_key ] : array();
				$diff                 = array_diff( $values, $migrated_meta_values );

				if ( count( $diff ) ) {
					if ( ! isset( $failed_ids[ $order_id ] ) ) {
						$failed_ids[ $order_id ] = array();
					}
					$failed_ids[ $order_id ][] = array(
						'order_id'         => $order_id,
						'meta_key'         => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Not a meta query.
						'orig_meta_values' => $values,
						'new_meta_values'  => $migrated_meta_values,
					);
				}
			}
		}

		return $failed_ids;
	}

	/**
	 * Helper method to normalize response from meta queries into order_id > meta_key > meta_values.
	 *
	 * @param array $data Data fetched from meta queries.
	 *
	 * @return array Normalized data.
	 */
	private function normalize_raw_meta_data( array $data ): array {
		$clubbed_data = array();
		foreach ( $data as $row ) {
			if ( ! isset( $clubbed_data[ $row['entity_id'] ] ) ) {
				$clubbed_data[ $row['entity_id'] ] = array();
			}
			if ( ! isset( $clubbed_data[ $row['entity_id'] ][ $row['meta_key'] ] ) ) {
				$clubbed_data[ $row['entity_id'] ][ $row['meta_key'] ] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Not a meta query.
			}
			$clubbed_data[ $row['entity_id'] ][ $row['meta_key'] ][] = $row['meta_value'];
		}
		return $clubbed_data;
	}

	/**
	 * Set custom order tables (HPOS) to authoritative if: 1). HPOS and posts tables are in sync, or, 2). This is a new shop (in this case also create tables). Additionally, all installed WC plugins should be compatible.
	 *
	 * ## OPTIONS
	 *
	 * [--for-new-shop]
	 * : Enable only if this is a new shop, irrespective of whether tables are in sync.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--with-sync]
	 * : Also enables sync (if it's currently not enabled).
	 * ---
	 * default: false
	 * ---
	 *
	 * ### EXAMPLES
	 *
	 *      # Enable HPOS on new shops.
	 *      wp wc hpos enable --for-new-shop
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 *
	 * @return void
	 */
	public function enable( array $args = array(), array $assoc_args = array() ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'for-new-shop' => false,
				'with-sync'    => false,
			)
		);

		$enable_hpos = true;
		WP_CLI::log( __( 'Running pre-enable checks...', 'woocommerce' ) );

		$is_new_shop = \WC_Install::is_new_install();
		if ( $assoc_args['for-new-shop'] && ! $is_new_shop ) {
			WP_CLI::error( __( '[Failed] This is not a new shop, but --for-new-shop flag was passed.', 'woocommerce' ) );
		}

		/** Feature controller instance @var FeaturesController $feature_controller */
		$feature_controller = wc_get_container()->get( FeaturesController::class );
		$plugin_info        = $feature_controller->get_compatible_plugins_for_feature( 'custom_order_tables', true );
		if ( count( array_merge( $plugin_info['uncertain'], $plugin_info['incompatible'] ) ) > 0 ) {
			WP_CLI::warning( __( '[Failed] Some installed plugins are incompatible. Please review the plugins by going to WooCommerce > Settings > Advanced > Features and see the "Order data storage" section.', 'woocommerce' ) );
			$enable_hpos = false;
		}

		/** DataSynchronizer instance @var DataSynchronizer $data_synchronizer */
		$data_synchronizer = wc_get_container()->get( DataSynchronizer::class );
		$pending_orders    = $data_synchronizer->get_total_pending_count();
		$table_exists      = $data_synchronizer->check_orders_table_exists();

		if ( ! $table_exists ) {
			WP_CLI::warning( __( 'Orders table does not exist. Creating...', 'woocommerce' ) );
			if ( $is_new_shop || 0 === $pending_orders ) {
				$data_synchronizer->create_database_tables();
				if ( $data_synchronizer->check_orders_table_exists() ) {
					WP_CLI::log( __( 'Orders table created.', 'woocommerce' ) );
					$table_exists = true;
				} else {
					WP_CLI::warning( __( '[Failed] Orders table could not be created.', 'woocommerce' ) );
					$enable_hpos = false;
				}
			} else {
				WP_CLI::warning( __( '[Failed] The orders table does not exist and this is not a new shop. Please create the table by going to WooCommerce > Settings > Advanced > Features and enabling sync.', 'woocommerce' ) );
				$enable_hpos = false;
			}
		}

		if ( $pending_orders > 0 ) {
			WP_CLI::warning(
				sprintf(
					// translators: %s is the command to run (wp wc cot sync).
					__( '[Failed] There are orders pending sync. Please run `%s` to sync pending orders.', 'woocommerce' ),
					'wp wc hpos sync',
				)
			);
			$enable_hpos = false;
		}

		if ( $assoc_args['with-sync'] && $table_exists ) {
			if ( $data_synchronizer->data_sync_is_enabled() ) {
				WP_CLI::warning( __( 'Sync is already enabled.', 'woocommerce' ) );
			} else {
				$feature_controller->change_feature_enable( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, true );
				WP_CLI::success( __( 'Sync enabled.', 'woocommerce' ) );
			}
		}

		if ( ! $enable_hpos ) {
			WP_CLI::error( __( 'HPOS pre-checks failed, please see the errors above', 'woocommerce' ) );
			return;
		}

		/** CustomOrdersTableController instance @var CustomOrdersTableController $cot_status */
		$cot_status = wc_get_container()->get( CustomOrdersTableController::class );
		if ( $cot_status->custom_orders_table_usage_is_enabled() ) {
			WP_CLI::warning( __( 'HPOS is already enabled.', 'woocommerce' ) );
		} else {
			$feature_controller->change_feature_enable( 'custom_order_tables', true );
			if ( $cot_status->custom_orders_table_usage_is_enabled() ) {
				WP_CLI::success( __( 'HPOS enabled.', 'woocommerce' ) );
			} else {
				WP_CLI::error( __( 'HPOS could not be enabled.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Disables custom order tables (HPOS) and posts to authoritative if HPOS and post tables are in sync.
	 *
	 * ## OPTIONS
	 *
	 * [--with-sync]
	 * : Also disables sync (if it's currently enabled).
	 * ---
	 * default: false
	 * ---
	 *
	 * ### EXAMPLES
	 *
	 *  # Disable HPOS.
	 *  wp wc hpos disable
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function disable( $args, $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'with-sync' => false,
			)
		);

		WP_CLI::log( __( 'Running pre-disable checks...', 'woocommerce' ) );

		/** DataSynchronizer instance @var DataSynchronizer $data_synchronizer */
		$data_synchronizer = wc_get_container()->get( DataSynchronizer::class );
		$pending_orders    = $data_synchronizer->get_total_pending_count();
		if ( $pending_orders > 0 ) {
			return WP_CLI::error(
				sprintf(
					// translators: %s is the command to run (wp wc cot sync).
					__( '[Failed] There are orders pending sync. Please run `%s` to sync pending orders.', 'woocommerce' ),
					'wp wc hpos sync',
				)
			);
		}

		/** FeaturesController instance @var FeaturesController $feature_controller */
		$feature_controller = wc_get_container()->get( FeaturesController::class );

		/** CustomOrdersTableController instance @var CustomOrdersTableController $cot_status */
		$cot_status = wc_get_container()->get( CustomOrdersTableController::class );
		if ( ! $cot_status->custom_orders_table_usage_is_enabled() ) {
			WP_CLI::warning( __( 'HPOS is already disabled.', 'woocommerce' ) );
		} else {
			$feature_controller->change_feature_enable( 'custom_order_tables', false );
			if ( $cot_status->custom_orders_table_usage_is_enabled() ) {
				return WP_CLI::warning( __( 'HPOS could not be disabled.', 'woocommerce' ) );
			} else {
				WP_CLI::success( __( 'HPOS disabled.', 'woocommerce' ) );
			}
		}

		if ( $assoc_args['with-sync'] ) {
			if ( ! $data_synchronizer->data_sync_is_enabled() ) {
				return WP_CLI::warning( __( 'Sync is already disabled.', 'woocommerce' ) );
			}
			$feature_controller->change_feature_enable( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, false );
			if ( $data_synchronizer->data_sync_is_enabled() ) {
				return WP_CLI::warning( __( 'Sync could not be disabled.', 'woocommerce' ) );
			} else {
				WP_CLI::success( __( 'Sync disabled.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * When HPOS is enabled, this command lets you remove redundant data from the postmeta table for migrated orders.
	 *
	 * ## OPTIONS
	 *
	 * <all|id|range>...
	 * : ID or range of orders to clean up.
	 *
	 * [--batch-size=<batch-size>]
	 * : Number of orders to process per batch. Applies only to cleaning up of 'all' orders.
	 * ---
	 * default: 500
	 * ---
	 *
	 * [--force]
	 * : When true, post meta will be cleaned up even if the post appears to have been updated more recently than the order.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    # Cleanup post data for order 314.
	 *    $ wp wc hpos cleanup 314
	 *
	 *    # Cleanup postmeta for orders with IDs betweeen 10 and 100 and order 314.
	 *    $ wp wc hpos cleanup 10-100 314
	 *
	 *    # Cleanup postmeta for all orders.
	 *    wp wc hpos cleanup all
	 *
	 *    # Cleanup postmeta for all orders with a batch size of 200 (instead of the default 500).
	 *    wp wc hpos cleanup all --batch-size=200
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 * @return void
	 */
	public function cleanup_post_data( array $args = array(), array $assoc_args = array() ) {
		if ( ! $this->synchronizer->custom_orders_table_is_authoritative() || $this->synchronizer->data_sync_is_enabled() ) {
			WP_CLI::error( __( 'Cleanup can only be performed when HPOS is active and compatibility mode is disabled.', 'woocommerce' ) );
		}
		$handler = wc_get_container()->get( LegacyDataHandler::class );

		$all_orders  = 'all' === $args[0];
		$force       = (bool) ( $assoc_args['force'] ?? false );
		$q_order_ids = $all_orders ? array() : $args;
		$q_limit     = $all_orders ? absint( $assoc_args['batch-size'] ?? 500 ) : 0; // Limit per batch.

		$order_count = $handler->count_orders_for_cleanup( $q_order_ids );
		if ( ! $order_count ) {
			WP_CLI::warning( __( 'No orders to cleanup.', 'woocommerce' ) );
			return;
		}

		$progress   = WP_CLI\Utils\make_progress_bar( __( 'HPOS cleanup', 'woocommerce' ), $order_count );
		$count      = 0;
		$failed_ids = array();

		// translators: %d is the number of orders to clean up.
		WP_CLI::log( sprintf( _n( 'Starting cleanup for %d order...', 'Starting cleanup for %d orders...', $order_count, 'woocommerce' ), $order_count ) );

		do {
			$failed_ids_in_batch = array();
			$order_ids           = $handler->get_orders_for_cleanup( $q_order_ids, $q_limit );

			if ( $failed_ids && empty( array_diff( $order_ids, $failed_ids ) ) ) {
				break;
			}

			$order_ids = array_diff( $order_ids, $failed_ids ); // Do not reattempt IDs that have already failed.

			foreach ( $order_ids as $order_id ) {
				try {
					$handler->cleanup_post_data( $order_id, $force );
					++$count;

					// translators: %d is an order ID.
					WP_CLI::debug( sprintf( __( 'Cleanup completed for order %d.', 'woocommerce' ), $order_id ) );
				} catch ( \Exception $e ) {
					// translators: %1$d is an order ID, %2$s is an error message.
					WP_CLI::warning( sprintf( __( 'An error occurred while cleaning up order %1$d: %2$s', 'woocommerce' ), $order_id, $e->getMessage() ) );
					$failed_ids_in_batch[] = $order_id;
				}

				$progress->tick();
			}

			$failed_ids = array_merge( $failed_ids, $failed_ids_in_batch );

			if ( ! $all_orders ) {
				break;
			}

			if ( $failed_ids_in_batch && ! array_diff( $order_ids, $failed_ids_in_batch ) ) {
				WP_CLI::warning( __( 'Failed to clean up all orders in a batch. Aborting.', 'woocommerce' ) );
				break;
			}
		} while ( $order_ids );

		$progress->finish();

		if ( $failed_ids ) {
			return WP_CLI::error(
				sprintf(
					// translators: %d is the number of orders that were cleaned up.
					_n( 'Cleanup completed for %d order. Review errors above.', 'Cleanup completed for %d orders. Review errors above.', $count, 'woocommerce' ),
					$count
				)
			);
		}

		WP_CLI::success(
			sprintf(
				// translators: %d is the number of orders that were cleaned up.
				_n( 'Cleanup completed for %d order.', 'Cleanup completed for %d orders.', $count, 'woocommerce' ),
				$count
			)
		);
	}

	/**
	 * Displays a summary of HPOS situation on this site.
	 *
	 * @since 8.6.0
	 */
	public function status() {
		$legacy_handler = wc_get_container()->get( LegacyDataHandler::class );

		// translators: %s is either 'yes' or 'no'.
		WP_CLI::log( sprintf( __( 'HPOS enabled?: %s', 'woocommerce' ), wc_bool_to_string( $this->controller->custom_orders_table_usage_is_enabled() ) ) );

		// translators: %s is either 'yes' or 'no'.
		WP_CLI::log( sprintf( __( 'Compatibility mode enabled?: %s', 'woocommerce' ), wc_bool_to_string( $this->synchronizer->data_sync_is_enabled() ) ) );

		// translators: %d is an order count.
		WP_CLI::log( sprintf( __( 'Unsynced orders: %d', 'woocommerce' ), $this->synchronizer->get_current_orders_pending_sync_count() ) );

		WP_CLI::log(
			sprintf(
				/* translators: %d is an order count. */
				__( 'Orders subject to cleanup: %d', 'woocommerce' ),
				( $this->synchronizer->custom_orders_table_is_authoritative() && ! $this->synchronizer->data_sync_is_enabled() )
				? $legacy_handler->count_orders_for_cleanup()
				: 0
			)
		);
	}

	/**
	 * Displays differences for an order between the HPOS and post datastore.
	 *
	 * ## OPTIONS
	 *
	 * <order_id>
	 * :The ID of the order.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    # Find differences between datastores for order 123.
	 *    $ wp wc hpos diff 123
	 *
	 *    # Find differences for order 123 and display as CSV.
	 *    $ wp wc hpos diff 123 --format=csv
	 *
	 * @since 8.6.0
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function diff( array $args = array(), array $assoc_args = array() ) {
		$id = absint( $args[0] );

		try {
			$diff = wc_get_container()->get( LegacyDataHandler::class )->get_diff_for_order( $id );
		} catch ( \Exception $e ) {
			// translators: %1$d is an order ID, %2$s is an error message.
			WP_CLI::error( sprintf( __( 'An error occurred while computing a diff for order %1$d: %2$s', 'woocommerce' ), $id, $e->getMessage() ) );
		}

		if ( ! $diff ) {
			WP_CLI::success( __( 'No differences found.', 'woocommerce' ) );
			return;
		}

		// Format the diff array.
		$diff = array_map(
			function ( $key, $hpos_value, $cpt_value ) {
				// Format for dates.
				$hpos_value = is_a( $hpos_value, \WC_DateTime::class ) ? $hpos_value->format( DATE_ATOM ) : $hpos_value;
				$cpt_value  = is_a( $cpt_value, \WC_DateTime::class ) ? $cpt_value->format( DATE_ATOM ) : $cpt_value;

				// Format for NULL.
				$hpos_value = is_null( $hpos_value ) ? '' : $hpos_value;
				$cpt_value  = is_null( $cpt_value ) ? '' : $cpt_value;

				return array(
					'property' => $key,
					'hpos'     => $hpos_value,
					'post'     => $cpt_value,
				);
			},
			array_keys( $diff ),
			array_column( $diff, 0 ),
			array_column( $diff, 1 ),
		);

		WP_CLI::warning(
			// translators: %d is an order ID.
			sprintf( __( 'Differences found for order %d:', 'woocommerce' ), $id )
		);
		WP_CLI\Utils\format_items(
			$assoc_args['format'] ?? 'table',
			$diff,
			array( 'property', 'hpos', 'post' )
		);
	}

	/**
	 * Backfills an order from either the HPOS or the posts datastore.
	 *
	 * ## OPTIONS
	 *
	 * <order_id>
	 * : The ID of the order.
	 *
	 * --from=<datastore>
	 * : Source datastore. Either 'hpos' or 'posts'.
	 * ---
	 * options:
	 *   - hpos
	 *   - posts
	 * ---
	 *
	 * --to=<datastore>
	 * : Destination datastore. Either 'hpos' or 'posts'.
	 * ---
	 * options:
	 *   - hpos
	 *   - posts
	 * ---
	 *
	 * [--meta_keys=<meta_keys>]
	 * : Comma separated list of meta keys to backfill.
	 *
	 * [--props=<props>]
	 * : Comma separated list of order properties to backfill.
	 *
	 * @since 8.6.0
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function backfill( array $args = array(), array $assoc_args = array() ) {
		$legacy_handler = wc_get_container()->get( LegacyDataHandler::class );

		$from     = $assoc_args['from'] ?? '';
		$to       = $assoc_args['to'] ?? '';
		$order_id = absint( $args[0] );

		if ( ! $order_id ) {
			WP_CLI::error( __( 'Please provide a valid order ID.', 'woocommerce' ) );
		}

		foreach ( array( 'from', 'to' ) as $datastore ) {
			if ( ! in_array( ${"$datastore"}, array( 'posts', 'hpos' ), true ) ) {
				// translators: %s is a shell argument representing a datastore name.
				WP_CLI::error( sprintf( __( '\'%s\' is not a valid datastore.', 'woocommerce' ), ${"$datastore"} ) );
			}
		}

		if ( $from === $to ) {
			WP_CLI::error( __( 'Please use different source (--from) and destination (--to) datastores.', 'woocommerce' ) );
		}

		$fields = array_intersect_key( $assoc_args, array_flip( array( 'meta_keys', 'props' ) ) );
		foreach ( $fields as &$field_names ) {
			$field_names = is_string( $field_names ) ? array_map( 'trim', explode( ',', $field_names ) ) : $field_names;
			$field_names = array_unique( array_filter( array_filter( $field_names, 'is_string' ) ) );
		}

		try {
			$legacy_handler->backfill_order_to_datastore( $order_id, $from, $to, $fields );
		} catch ( \Exception $e ) {
			WP_CLI::error(
				sprintf(
					// translators: %1$d is an order ID, %2$s and %3$s are datastore names, %4$s is an error message.
					__( 'An error occurred while backfilling order %1$d from %2$s to %3$s: %4$s', 'woocommerce' ),
					$order_id,
					$from,
					$to,
					$e->getMessage()
				)
			);
		}

		WP_CLI::success(
			sprintf(
				// translators: %1$d is an order ID, %2$s and %3$s are datastore names ("hpos" or "posts" for example).
				__( 'Order %1$d backfilled from %2$s to %3$s.', 'woocommerce' ),
				$order_id,
				$from,
				$to
			)
		);
	}
}
