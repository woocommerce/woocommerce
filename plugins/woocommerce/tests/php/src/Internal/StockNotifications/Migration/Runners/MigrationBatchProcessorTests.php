<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationRun;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
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
	 * @testdox a batch that lands should clear a failure an earlier batch recorded.
	 */
	public function test_a_landed_batch_clears_a_recorded_failure(): void {
		$this->seed_notifications( 1 );
		$this->state->set_failure( 'notifications', 'an earlier explosion' );

		$this->processor->process_batch( $this->processor->get_next_batch_to_process( 50 ) );

		$this->assertNull( $this->state->get_failure(), 'A run that got going again has not failed.' );
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
