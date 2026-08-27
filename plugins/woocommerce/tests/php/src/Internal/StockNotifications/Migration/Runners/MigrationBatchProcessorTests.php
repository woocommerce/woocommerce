<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\MigrationBatchProcessor;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the batch processor that drives the whole migration: section batching,
 * termination, resumption after an interrupted run, and idempotency across runs.
 */
class MigrationBatchProcessorTests extends WC_Unit_Test_Case {

	/**
	 * Processor under test.
	 *
	 * @var MigrationBatchProcessor
	 */
	private MigrationBatchProcessor $processor;

	/**
	 * Run state, shared with the processor through the option it persists to.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * A published simple product every seeded row points at.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up the legacy tables, the feature toggle and a product to subscribe to.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		LegacyStore::create_tables();
		LegacyStore::truncate_all();

		$this->clear_migration_options();

		$requirements = new Requirements();
		$requirements->init( wc_get_container()->get( StockNotificationsDataStore::class ) );

		$this->processor = new MigrationBatchProcessor();
		$this->processor->init( $requirements );

		$this->state      = new MigrationState();
		$this->product_id = $this->create_product();
	}

	/**
	 * Drop the legacy tables and clear everything the migration persists.
	 */
	public function tearDown(): void {
		LegacyStore::drop_tables();
		$this->clear_migration_options();
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );

		parent::tearDown();
	}

	/**
	 * @testdox a batch should be section-prefixed and never span two sections.
	 */
	public function test_batch_items_are_section_prefixed_and_single_section(): void {
		$this->seed_notifications( 3 );

		$batch = $this->processor->get_next_batch_to_process( 10 );

		$this->assertNotEmpty( $batch );

		$sections = array();
		foreach ( $batch as $item ) {
			$this->assertMatchesRegularExpression( '/^[a-z-]+::.+$/', (string) $item );
			$sections[] = explode( '::', (string) $item, 2 )[0];
		}

		$this->assertSame( array( 'notifications' ), array_values( array_unique( $sections ) ) );
	}

	/**
	 * @testdox get_next_batch_to_process should be side-effect free.
	 */
	public function test_get_next_batch_is_side_effect_free(): void {
		$this->seed_notifications( 3 );

		$first  = $this->processor->get_next_batch_to_process( 2 );
		$cursor = $this->state->get_cursor( 'notifications' );
		$second = $this->processor->get_next_batch_to_process( 2 );

		$this->assertSame( $first, $second );
		$this->assertSame( $cursor, $this->state->get_cursor( 'notifications' ) );
	}

	/**
	 * @testdox a multi-batch run should migrate every row and end with an empty probe.
	 */
	public function test_multi_batch_run_terminates_with_an_empty_probe(): void {
		$this->seed_notifications( 7 );

		$batches = $this->run_to_completion( 2 );

		$this->assertGreaterThan( 1, $batches, 'The run should have taken several batches.' );
		$this->assertCount( 7, LegacyStore::get_core_rows() );
		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );
	}

	/**
	 * @testdox a second run should write nothing.
	 */
	public function test_second_run_writes_nothing(): void {
		$this->seed_notifications( 4 );

		$this->run_to_completion( 50 );
		$after_first = LegacyStore::get_core_rows();

		$this->run_to_completion( 50 );

		$this->assertSame( $after_first, LegacyStore::get_core_rows(), 'A re-run must not write anything.' );
	}

	/**
	 * @testdox deleting migrated rows should re-admit exactly those rows on the next run.
	 */
	public function test_deleted_rows_return_on_the_next_run(): void {
		$legacy_ids = $this->seed_notifications( 4 );

		$this->run_to_completion( 50 );

		$deleted = array( $legacy_ids[0], $legacy_ids[2] );
		$this->delete_core_rows_for( $deleted );

		$this->assertSame( $deleted, $this->raw_batch( 50 ), 'Only the deleted rows should be outstanding.' );

		$this->run_to_completion( 50 );

		$this->assertCount( 4, LegacyStore::get_core_rows() );
		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );
	}

	/**
	 * @testdox a row deleted behind an in-flight cursor should be picked up by the pass reset.
	 */
	public function test_row_deleted_behind_the_cursor_is_picked_up(): void {
		$legacy_ids = $this->seed_notifications( 4 );

		// Migrate the first two rows, leaving the cursor part-way through the section.
		$batch = $this->processor->get_next_batch_to_process( 2 );
		$this->processor->process_batch( $batch );
		$this->assertSame( $legacy_ids[1], $this->state->get_cursor( 'notifications' ) );

		$this->delete_core_rows_for( array( $legacy_ids[0] ) );

		// The remaining rows are ahead of the cursor, so they drain first; only then does the
		// probe restart the pass and find the row that fell back in behind it.
		$this->run_to_completion( 2 );

		$this->assertCount( 4, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a held CLI lock should empty the batch and stop processing.
	 */
	public function test_a_held_lock_stops_the_processor(): void {
		$this->seed_notifications( 2 );

		$batch = $this->processor->get_next_batch_to_process( 50 );
		$this->assertNotEmpty( $batch );

		$this->state->acquire_lock( 'cli' );

		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );

		$this->processor->process_batch( $batch );
		$this->assertSame( array(), LegacyStore::get_core_rows(), 'A locked run must not write.' );

		$this->state->release_lock();
		$this->run_to_completion( 50 );
		$this->assertCount( 2, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox toggling the feature off mid-run should stop the run and leave it resumable.
	 */
	public function test_feature_toggled_off_mid_run_stops_and_resumes(): void {
		$this->seed_notifications( 4 );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 2 ) );
		$this->assertCount( 2, LegacyStore::get_core_rows() );

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );
		$this->assertCount( 2, LegacyStore::get_core_rows(), 'Nothing more may migrate while the feature is off.' );

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		$this->run_to_completion( 50 );
		$this->assertCount( 4, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox losing the state option mid-run should end at the same place as an uninterrupted run.
	 */
	public function test_losing_the_state_option_mid_run_ends_identically(): void {
		$legacy_ids = $this->seed_notifications( 5 );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 2 ) );

		delete_option( 'wc_bis_migration_state' );

		$this->run_to_completion( 2 );

		$rows = LegacyStore::get_core_rows();
		$this->assertCount( 5, $rows );

		$markers             = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );
		$migrated_legacy_ids = array_map( 'intval', array_merge( ...array_values( $markers ) ) );
		sort( $migrated_legacy_ids );

		$this->assertSame( $legacy_ids, $migrated_legacy_ids, 'Every legacy row migrates exactly once.' );
	}

	/**
	 * @testdox a run interrupted inside process_batch should migrate the rest and only the rest.
	 */
	public function test_run_interrupted_inside_process_batch_resumes(): void {
		$legacy_ids = $this->seed_notifications( 5 );

		// A killed worker leaves the markers written by the rows that made it through and no
		// cursor for the batch it died in; the next run re-queries from those markers.
		$this->processor->process_batch( array( 'notifications::' . $legacy_ids[0] ) );
		delete_option( 'wc_bis_migration_state' );

		$this->run_to_completion( 2 );

		$this->assertCount( 5, LegacyStore::get_core_rows() );

		foreach ( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) as $values ) {
			$this->assertCount( 1, $values, 'No legacy row may be migrated twice.' );
		}
	}

	/**
	 * @testdox a per-row failure should mark the row, leave the batch intact, and let the run finish.
	 */
	public function test_per_row_failure_marks_the_row_and_the_run_terminates(): void {
		$legacy_ids = $this->seed_notifications( 3 );
		$failing_id = $legacy_ids[1];

		$thrower = function ( $query ) use ( $failing_id ) {
			// The adoption lookup is the only per-row query, so failing it is the cleanest way
			// to simulate a row that cannot be mapped.
			if ( false !== strpos( $query, "shopper{$failing_id}@example.com" ) ) {
				throw new \RuntimeException( 'forced row failure' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );
		$this->run_to_completion( 50 );
		remove_filter( 'query', $thrower );

		$this->assertCount( 2, LegacyStore::get_core_rows() );

		$marker = LegacyStore::get_legacy_meta( $failing_id, '_wc_bis_migration_failed' );
		$this->assertCount( 1, $marker );
		$this->assertSame( 'exception', $marker[0]['reason'] );

		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ), 'A failed row leaves the candidate set.' );
	}

	/**
	 * Run the processor until it reports an empty batch.
	 *
	 * @param int $size Batch size to request.
	 * @return int Number of batches processed.
	 */
	private function run_to_completion( int $size ): int {
		$batches = 0;

		while ( true ) {
			$batch = $this->processor->get_next_batch_to_process( $size );

			if ( empty( $batch ) ) {
				break;
			}

			$this->processor->process_batch( $batch );
			++$batches;

			$this->assertLessThan( 60, $batches, 'The run failed to terminate.' );
		}

		return $batches;
	}

	/**
	 * The next batch's raw legacy ids, with the section prefix stripped.
	 *
	 * @param int $size Batch size to request.
	 * @return int[]
	 */
	private function raw_batch( int $size ): array {
		return array_map(
			static function ( $item ) {
				return (int) explode( '::', (string) $item, 2 )[1];
			},
			$this->processor->get_next_batch_to_process( $size )
		);
	}

	/**
	 * Seed a number of eligible legacy notification rows, one per unique email.
	 *
	 * @param int $count How many rows to seed.
	 * @return int[] The seeded legacy ids, ascending.
	 */
	private function seed_notifications( int $count ): array {
		global $wpdb;

		$ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$legacy_id = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );
			$ids[]     = $legacy_id;

			// The email carries the legacy id so a single row can be singled out in a query.
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prefix . 'woocommerce_bis_notifications',
				array( 'user_email' => "shopper{$legacy_id}@example.com" ),
				array( 'id' => $legacy_id )
			);
		}

		return $ids;
	}

	/**
	 * Delete the Core rows carrying the given legacy ids, markers and all.
	 *
	 * @param int[] $legacy_ids Legacy ids whose migrated rows should be removed.
	 * @return void
	 */
	private function delete_core_rows_for( array $legacy_ids ): void {
		global $wpdb;

		foreach ( $legacy_ids as $legacy_id ) {
			// Table names are $wpdb->prefix-based, never user input.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$notification_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT notification_id FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE meta_key = %s AND meta_value = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'_wc_bis_legacy_id',
					(string) $legacy_id
				)
			);

			$wpdb->delete( $wpdb->prefix . 'wc_stock_notifications', array( 'id' => $notification_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $wpdb->prefix . 'wc_stock_notificationmeta', array( 'notification_id' => $notification_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	/**
	 * Clear every option the migration persists, including the autoloaded flags that
	 * survive the per-test transaction rollback through the object cache.
	 *
	 * @return void
	 */
	private function clear_migration_options(): void {
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );
	}

	/**
	 * Create a published simple product.
	 *
	 * @return int
	 */
	private function create_product(): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Migration test product' );
		$product->save();

		return $product->get_id();
	}
}
