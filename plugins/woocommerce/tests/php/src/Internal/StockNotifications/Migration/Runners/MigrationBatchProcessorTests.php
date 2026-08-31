<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationRun;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\MigrationBatchProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
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
		$this->processor->init( $requirements, wc_get_container()->get( Writer::class ) );

		$this->state      = new MigrationState();
		$this->product_id = $this->create_product();

		// The processor only works while a run's lock is held, so every test that drives it
		// directly stands in for a run that ToolsRegistrar::start() already began.
		$this->state->acquire_lock( 'background migration' );
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
	 * @testdox a multi-batch run should migrate every row and end once the cursor reaches the last id.
	 */
	public function test_multi_batch_run_terminates_when_the_cursor_drains(): void {
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
	 * @testdox a re-run over a settled store should not process a single batch.
	 */
	public function test_a_second_run_processes_no_batches(): void {
		$this->seed_notifications( 4 );

		$this->assertGreaterThan( 0, $this->run_to_completion( 2 ) );
		$this->assertSame( 0, $this->run_to_completion( 2 ), 'The cursor is kept, so a settled store has nothing left to visit.' );
	}

	/**
	 * @testdox deleting migrated rows should re-admit exactly those rows once the cursor is reset.
	 */
	public function test_deleted_rows_return_when_the_cursor_is_reset(): void {
		$legacy_ids = $this->seed_notifications( 4 );

		$this->run_to_completion( 50 );

		$deleted = array( $legacy_ids[0], $legacy_ids[2] );
		$this->delete_core_rows_for( $deleted );

		// The cursor is past them, and it is kept between runs: re-walking the whole legacy
		// table on the off-chance a Core row was deleted is the cost this migration cannot
		// pay. `--force` and `--retry-failed` are what put those rows back into play.
		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );

		$this->state->reset_all_cursors();

		$this->assertSame( $legacy_ids, $this->raw_batch( 50 ), 'A reset scan serves every row again.' );

		$this->run_to_completion( 50 );

		$this->assertCount( 4, LegacyStore::get_core_rows(), 'Only the deleted rows are re-migrated.' );
		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ) );

		foreach ( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) as $values ) {
			$this->assertCount( 1, $values, 'A row the markers already cover must not be migrated twice.' );
		}
	}

	/**
	 * @testdox a row deleted behind an in-flight cursor should be left for a cursor reset.
	 */
	public function test_row_deleted_behind_the_cursor_waits_for_a_reset(): void {
		$legacy_ids = $this->seed_notifications( 4 );

		// Migrate the first two rows, leaving the cursor part-way through the section.
		$batch = $this->processor->get_next_batch_to_process( 2 );
		$this->processor->process_batch( $batch );
		$this->assertSame( $legacy_ids[1], $this->state->get_cursor( 'notifications' ) );

		$this->delete_core_rows_for( array( $legacy_ids[0] ) );

		$this->run_to_completion( 2 );

		$this->assertCount( 3, LegacyStore::get_core_rows(), 'The cursor only moves forward, so the deleted row is left behind.' );

		$this->state->reset_all_cursors();
		$this->run_to_completion( 2 );

		$this->assertCount( 4, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox the run should hold the lock while it works and hand it back when it drains.
	 */
	public function test_the_run_holds_the_lock_until_it_drains(): void {
		$this->seed_notifications( 3 );

		$batch = $this->processor->get_next_batch_to_process( 2 );
		$this->processor->process_batch( $batch );

		$this->assertTrue( $this->state->is_lock_held(), 'A run with work left must keep its lock.' );

		$this->run_to_completion( 2 );

		$this->assertFalse( $this->state->is_lock_held(), 'A drained run must hand its lock back.' );
		$this->assertCount( 3, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a batch that loses the batch lock should write nothing and leave its rows.
	 */
	public function test_a_batch_that_loses_the_batch_lock_writes_nothing(): void {
		$this->seed_notifications( 3 );

		// `BatchProcessingController` can have two batch actions in flight for one processor,
		// and both read the same cursor. The second one must not walk the same rows.
		$concurrent_batch = new MigrationState();
		$this->assertTrue( $concurrent_batch->acquire_batch_lock() );

		$batch = $this->processor->get_next_batch_to_process( 50 );
		$this->assertNotEmpty( $batch );
		$this->processor->process_batch( $batch );

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'The losing batch must migrate nothing.' );
		$this->assertSame( 0, $this->state->get_cursor( 'notifications' ), 'The losing batch must not move the cursor.' );

		$concurrent_batch->release_batch_lock();

		// The rows are still candidates, so the next scheduled batch picks them up.
		$this->run_to_completion( 50 );

		$this->assertCount( 3, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a finished batch should hand the batch lock back.
	 */
	public function test_the_batch_lock_is_released_after_a_batch(): void {
		$this->seed_notifications( 2 );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 50 ) );

		$next_batch = new MigrationState();
		$this->assertTrue( $next_batch->acquire_batch_lock(), 'A finished batch must release the lock.' );
		$next_batch->release_batch_lock();
	}

	/**
	 * @testdox a stopped run should process nothing, even with a batch already in hand.
	 */
	public function test_a_run_without_the_lock_processes_nothing(): void {
		$this->seed_notifications( 2 );

		$batch = $this->processor->get_next_batch_to_process( 50 );
		$this->assertNotEmpty( $batch );

		// What ToolsRegistrar::stop() does, between the batch being handed out and processed.
		$this->state->release_lock();

		$this->processor->process_batch( $batch );

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'A stopped run must not write.' );
		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ), 'A stopped run must serve no batches.' );
	}

	/**
	 * A background run is a fresh PHP request, and so a fresh processor and Reporter, per
	 * batch. Reading the run's losses off one Reporter therefore only ever sees what that
	 * one request skipped; the counts have to accumulate in the run's own state instead.
	 *
	 * @testdox known losses should accumulate across the separate instances a background run uses.
	 */
	public function test_known_losses_accumulate_across_processor_instances(): void {
		// Two rows whose product is gone: each is a skip, and each is seen by a different
		// processor instance, exactly as consecutive Action Scheduler ticks would.
		LegacyStore::add_notification( array( 'product_id' => 999999 ) );
		LegacyStore::add_notification( array( 'product_id' => 999998 ) );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 1 ) );

		$this->processor = $this->new_processor();
		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 1 ) );

		$losses = $this->state->get_losses();

		$this->assertNotNull( $losses, 'A run that skipped rows must have cached what it skipped.' );
		$this->assertSame(
			2,
			(int) $losses['values']['product_missing'],
			'Both skips must be counted, not just the last instance\'s.'
		);
	}

	/**
	 * @testdox a batch that lands should not double-count a skip an earlier batch already reported.
	 */
	public function test_known_losses_are_not_counted_twice_by_one_instance(): void {
		LegacyStore::add_notification( array( 'product_id' => 999999 ) );
		$this->seed_notifications( 2 );

		// One instance, several batches: the Reporter's counters are cumulative across them,
		// so only the difference since the last batch may be added.
		$this->run_to_completion( 1 );

		$losses = $this->state->get_losses();

		$this->assertSame( 1, (int) $losses['values']['product_missing'] );
	}

	/**
	 * @testdox a batch that lands should clear a failure an earlier batch recorded.
	 */
	public function test_a_landed_batch_clears_a_recorded_failure(): void {
		$this->seed_notifications( 1 );
		$this->state->set_failure( 'notifications', 'an earlier explosion' );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 50 ) );

		$this->assertNull( $this->state->get_failure(), 'A run that got going again has not failed.' );
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

		// Give the middle row a Core row to adopt, so it takes the one write that happens
		// inside the per-row try/catch. The bulk insert the other rows take is a whole-batch
		// write, so failing that would fail the batch rather than the row.
		LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => "shopper{$failing_id}@example.com",
			)
		);

		$thrower = function ( $query ) {
			if ( false !== strpos( (string) $query, '_wc_bis_legacy_adopted' ) ) {
				throw new \RuntimeException( 'forced row failure' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );
		$this->run_to_completion( 50 );
		remove_filter( 'query', $thrower );

		$this->assertCount( 3, LegacyStore::get_core_rows(), 'The other two rows migrate alongside the adoption target.' );

		$marker = LegacyStore::get_legacy_meta( $failing_id, '_wc_bis_migration_failed' );
		$this->assertCount( 1, $marker );
		$this->assertSame( 'exception', $marker[0]['reason'] );

		$this->assertSame( array(), $this->processor->get_next_batch_to_process( 50 ), 'A failed row does not hold the run up.' );
	}

	/**
	 * @testdox a drained section should cache its zero count once and not count again.
	 */
	public function test_a_drained_section_is_counted_once(): void {
		$this->seed_notifications( 4 );

		$this->run_to_completion( 2 );

		$cached = $this->state->get_count( 'notifications' );

		$this->assertNotNull( $cached );
		$this->assertSame( 0, (int) $cached['count'] );

		$counted  = array();
		$recorder = function ( $query ) use ( &$counted ) {
			if ( false !== stripos( $query, 'COUNT(' ) && false !== stripos( $query, 'woocommerce_bis_notifications ' ) ) {
				$counted[] = $query;
			}

			return $query;
		};

		$this->state->acquire_lock( 'background migration' );

		add_filter( 'query', $recorder );
		$this->processor->get_next_batch_to_process( 50 );
		remove_filter( 'query', $recorder );

		$this->assertSame( array(), $counted, 'A section already counted on drain must not be counted again.' );
	}

	/**
	 * @testdox a section that settles nothing should still terminate, because the cursor advances.
	 */
	public function test_a_section_that_cannot_settle_its_rows_still_terminates(): void {
		// A section whose rows never leave its candidate set. Under a monotonic cursor that
		// cannot loop: the rows are served once, the cursor moves past them, and the section
		// drains whether or not anything settled.
		$stuck = new class() implements MigratorInterface {
			/**
			 * Section slug.
			 *
			 * @return string
			 */
			public function get_slug(): string {
				return 'notifications';
			}

			/**
			 * Remaining candidates.
			 *
			 * @param int $cursor Keyset cursor. Ignored.
			 * @return int
			 */
			public function count_remaining( int $cursor = 0 ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface.
				return 2;
			}

			/**
			 * The same two rows at the start of every pass.
			 *
			 * @param int $cursor Keyset cursor.
			 * @param int $size   Batch size.
			 * @return array
			 */
			public function get_batch( int $cursor, int $size ): array {
				return 0 === $cursor ? array( 1, 2 ) : array();
			}

			/**
			 * Settles nothing.
			 *
			 * @param array  $ids    Row ids.
			 * @param Writer $writer Writer.
			 * @return array
			 */
			public function migrate_batch( array $ids, Writer $writer ): array {
				return array();
			}
		};

		$this->processor->configure_run( array( 'notifications' => $stuck ), wc_get_container()->get( Writer::class ), 50 );

		$batches = $this->run_to_completion( 50 );

		$this->assertSame( 1, $batches, 'The rows are served once and the cursor moves past them.' );
	}

	/**
	 * The product-meta section keeps no cursor: a row leaves its candidate set only by being
	 * settled. A row that can be neither migrated nor marked is therefore served on every
	 * pass forever, and it would starve the notifications section, which shares this
	 * processor. The section is parked instead.
	 *
	 * @testdox a cursorless section that settles nothing should be parked rather than served again.
	 */
	public function test_a_cursorless_section_that_settles_nothing_is_parked(): void {
		$stuck = new class() implements MigratorInterface {
			/**
			 * Section slug.
			 *
			 * @return string
			 */
			public function get_slug(): string {
				return 'product-meta';
			}

			/**
			 * Remaining candidates. Never falls, since nothing ever settles.
			 *
			 * @param int $cursor Keyset cursor. Ignored; this section keeps none.
			 * @return int
			 */
			public function count_remaining( int $cursor = 0 ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface.
				return 2;
			}

			/**
			 * The same two rows on every pass, cursor or not.
			 *
			 * @param int $cursor Keyset cursor. Ignored; this section keeps none.
			 * @param int $size   Batch size.
			 * @return array
			 */
			public function get_batch( int $cursor, int $size ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface.
				return array( 1, 2 );
			}

			/**
			 * Reports every row as unsettled: failed, and not even marked as failed.
			 *
			 * @param array  $ids    Row ids.
			 * @param Writer $writer Writer.
			 * @return array
			 */
			public function migrate_batch( array $ids, Writer $writer ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface.
				return array( Reporter::OUTCOME_UNSETTLED => count( $ids ) );
			}
		};

		$this->processor->configure_run( array( 'product-meta' => $stuck ), wc_get_container()->get( Writer::class ), 50 );

		$batches = $this->run_to_completion( 50 );

		$this->assertSame( 1, $batches, 'The section must be served once, then parked.' );
		$this->assertTrue( $this->state->is_section_parked( 'product-meta' ) );
		$this->assertSame(
			array(),
			$this->processor->get_next_batch_to_process( 50 ),
			'A parked section must not be served again.'
		);
	}

	/**
	 * @testdox a section that settles part of its batch should keep running.
	 */
	public function test_a_section_that_makes_partial_progress_is_not_parked(): void {
		$this->seed_notifications( 3 );
		$reporter = new Reporter();

		$this->processor->configure_run(
			array( 'notifications' => new NotificationsMigrator( $reporter ) ),
			wc_get_container()->get( Writer::class ),
			2,
			$reporter
		);

		$this->run_to_completion( 2 );

		$this->assertFalse( $this->state->is_section_parked( 'notifications' ) );
	}

	/**
	 * @testdox a dry run should terminate without leaving a cursor a later live run would start above.
	 */
	public function test_a_dry_run_leaves_the_stored_state_untouched(): void {
		$legacy_ids = $this->seed_notifications( 5 );
		$reporter   = new Reporter();

		$this->processor->configure_run(
			array( 'notifications' => new NotificationsMigrator( $reporter ) ),
			new Writer( true ),
			2,
			$reporter
		);

		$this->assertGreaterThan( 1, $this->run_to_completion( 2 ), 'A dry run advances its own cursor, so it ends.' );

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'A dry run must write no rows.' );
		$this->assertSame( 0, $this->state->get_cursor( 'notifications' ), 'A dry run must leave the stored cursor at the start.' );
		$this->assertNull( $this->state->get_count( 'notifications' ), 'A dry run must cache no count.' );

		// The bug this pins: a rehearsal that stored its cursor left the next live run
		// starting above every row it had walked, migrating none of them.
		$this->processor->configure_run(
			array( 'notifications' => new NotificationsMigrator( new Reporter() ) ),
			wc_get_container()->get( Writer::class ),
			2
		);

		$this->run_to_completion( 2 );

		$markers             = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );
		$migrated_legacy_ids = array_map( 'intval', array_merge( ...array_values( $markers ) ) );
		sort( $migrated_legacy_ids );

		$this->assertSame( $legacy_ids, $migrated_legacy_ids, 'The live run after a dry run migrates every row.' );
	}

	/**
	 * The product-meta section normally drains because each write drops the row out of its
	 * own candidate query. A dry run makes no writes, so without a cursor to page by, the
	 * processor is handed the same batch forever and `wp wc bis-migrate run --dry-run` never
	 * returns on any store with a legacy-disabled product. Built through MigrationRun rather
	 * than by hand, so the wiring the CLI relies on is what is under test.
	 *
	 * @testdox a dry run of the product meta section should terminate and write nothing.
	 */
	public function test_a_dry_run_of_the_product_meta_section_terminates(): void {
		$product_ids = array( $this->create_product(), $this->create_product() );

		foreach ( $product_ids as $product_id ) {
			update_post_meta( $product_id, '_wc_bis_disabled', 'yes' );
		}

		$run       = new MigrationRun();
		$migrators = array( 'product-meta' => $run->build_migrators( true )['product-meta'] );

		$this->processor->configure_run( $migrators, new Writer( true ), 1, $run->get_reporter() );

		$this->assertGreaterThan( 1, $this->run_to_completion( 1 ), 'A dry run pages through the section rather than stopping at one batch.' );

		foreach ( $product_ids as $product_id ) {
			$this->assertSame( '', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ), 'A dry run must write no product meta.' );
		}
	}

	/**
	 * @testdox a whole-batch failure should be reported, hold the cursor, and still propagate.
	 */
	public function test_a_whole_batch_failure_is_reported_and_rethrown(): void {
		$this->seed_notifications( 2 );

		$reporter = new Reporter();
		$failing  = new class() implements MigratorInterface {

			/**
			 * Section slug.
			 *
			 * @return string
			 */
			public function get_slug(): string {
				return 'notifications';
			}

			/**
			 * Remaining candidates.
			 *
			 * @param int $cursor Keyset cursor. Ignored.
			 * @return int
			 */
			public function count_remaining( int $cursor = 0 ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface.
				return 1;
			}

			/**
			 * One row at the start of a pass.
			 *
			 * @param int $cursor Keyset cursor.
			 * @param int $size   Batch size.
			 * @return array
			 */
			public function get_batch( int $cursor, int $size ): array {
				return 0 === $cursor ? array( 1 ) : array();
			}

			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- the method exists to throw.
			/**
			 * Fails the whole batch, the way a lost connection would.
			 *
			 * @param array  $ids    Row ids.
			 * @param Writer $writer Writer.
			 * @throws \RuntimeException Always.
			 * @return array Never returns.
			 */
			public function migrate_batch( array $ids, Writer $writer ): array {
				throw new \RuntimeException( 'lost connection' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};

		$this->processor->configure_run(
			array( 'notifications' => $failing ),
			wc_get_container()->get( Writer::class ),
			50,
			$reporter
		);

		$batch = $this->processor->get_next_batch_to_process( 50 );
		$this->assertNotEmpty( $batch );

		$thrown = null;

		try {
			$this->processor->process_batch( $batch );
		} catch ( \RuntimeException $e ) {
			$thrown = $e;
		}

		$this->assertNotNull( $thrown, 'The batch must still propagate so the controller retries it.' );
		$this->assertTrue( $reporter->has_errors(), 'A failed batch must not leave the run reporting success.' );
		$this->assertSame( 0, $this->state->get_cursor( 'notifications' ), 'A failed batch must not advance the cursor.' );

		// The controller retries this batch and, once it keeps throwing, drops the processor
		// without telling it — so the note left here is the only thing that later tells a
		// killed run apart from a merchant who pressed Stop.
		$failure = $this->state->get_failure();

		$this->assertNotNull( $failure, 'A throwing batch must record why it stopped.' );
		$this->assertSame( 'notifications', $failure['section'] );
		$this->assertSame( 'lost connection', $failure['message'] );

		$next_batch = new MigrationState();
		$this->assertTrue( $next_batch->acquire_batch_lock(), 'A thrown batch must still hand the batch lock back.' );
		$next_batch->release_batch_lock();
	}

	/**
	 * @testdox a run should migrate the settings even when no section has a row to move.
	 */
	public function test_settings_migrate_on_a_store_with_no_rows_left(): void {
		update_option( 'wc_bis_allow_signups', 'no' );

		// No legacy notifications and no product meta: every section is drained from the
		// first call, so the settings are the only thing a batch can still be served for.
		$this->assertSame( array(), LegacyStore::get_core_rows() );

		$this->run_to_completion( 50 );

		$this->assertSame(
			'no',
			get_option( 'woocommerce_customer_stock_notifications_allow_signups' ),
			'Settings must migrate on a store whose sections have nothing left to move.'
		);
	}

	/**
	 * @testdox settings should migrate alongside a section's rows.
	 */
	public function test_settings_migrate_alongside_a_sections_rows(): void {
		update_option( 'wc_bis_allow_signups', 'no' );
		$this->seed_notifications( 2 );

		$this->run_to_completion( 50 );

		$this->assertCount( 2, LegacyStore::get_core_rows() );
		$this->assertSame( 'no', get_option( 'woocommerce_customer_stock_notifications_allow_signups' ) );
	}

	/**
	 * Run the processor until it reports an empty batch.
	 *
	 * @param int $size Batch size to request.
	 * @return int Number of batches processed.
	 */
	private function run_to_completion( int $size ): int {
		$batches = 0;

		// A run takes the lock on the way in and hands it back when it drains, so a second
		// run through this helper has to start its own.
		$this->state->acquire_lock( 'background migration' );

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
		$this->state->acquire_lock( 'background migration' );

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
					"SELECT notification_id FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'_wc_bis_legacy_id_' . $legacy_id
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
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
		delete_option( 'wc_bis_allow_signups' );
		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );
	}

	/**
	 * Build a processor the way the container builds one per background request.
	 *
	 * Each Action Scheduler tick is its own PHP request, so it gets its own processor over
	 * its own Reporter. Tests that care about what survives between ticks need that, rather
	 * than pumping one long-lived instance.
	 *
	 * @return MigrationBatchProcessor
	 */
	private function new_processor(): MigrationBatchProcessor {
		$requirements = new Requirements();
		$requirements->init( wc_get_container()->get( StockNotificationsDataStore::class ) );

		$processor = new MigrationBatchProcessor();
		$processor->init( $requirements, wc_get_container()->get( Writer::class ) );

		return $processor;
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
