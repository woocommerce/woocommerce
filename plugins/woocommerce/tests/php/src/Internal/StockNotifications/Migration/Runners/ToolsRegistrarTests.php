<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\OptionsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\MigrationBatchProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\ToolsRegistrar;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the Tools screen entry: who may see and run it, how it refuses to race a CLI
 * run, and what it is allowed to query while rendering.
 */
class ToolsRegistrarTests extends WC_Unit_Test_Case {

	/**
	 * Registrar under test.
	 *
	 * @var ToolsRegistrar
	 */
	private ToolsRegistrar $registrar;

	/**
	 * Run state.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Set up the legacy tables, an admin user and a clean state.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		LegacyStore::create_tables();
		LegacyStore::truncate_all();
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );

		$this->registrar = wc_get_container()->get( ToolsRegistrar::class );
		$this->state     = new MigrationState();

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Dequeue anything a test enqueued and clear the state.
	 */
	public function tearDown(): void {
		wc_get_container()->get( BatchProcessingController::class )->remove_processor( MigrationBatchProcessor::class );

		LegacyStore::drop_tables();
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox the tool entry and both callbacks should refuse a user without manage_woocommerce.
	 */
	public function test_capability_is_required_for_the_entry_and_both_callbacks(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( array(), $this->registrar->handle_woocommerce_debug_tools( array() ) );
		$this->assertSame( 'You do not have permission to do this.', $this->registrar->start() );
		$this->assertSame( 'You do not have permission to do this.', $this->registrar->stop() );

		$this->assertFalse(
			wc_get_container()->get( BatchProcessingController::class )->is_enqueued( MigrationBatchProcessor::class ),
			'A refused start must not enqueue anything.'
		);
	}

	/**
	 * @testdox starting should enqueue the processor and swap the entry for a stop button.
	 */
	public function test_start_enqueues_and_swaps_the_entry(): void {
		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );
		$this->assertArrayHasKey( 'start_bis_migration', $tools );

		$this->registrar->start();

		$this->assertTrue(
			wc_get_container()->get( BatchProcessingController::class )->is_enqueued( MigrationBatchProcessor::class )
		);

		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );
		$this->assertArrayHasKey( 'stop_bis_migration', $tools );
		$this->assertArrayNotHasKey( 'start_bis_migration', $tools );

		$this->assertStringContainsString( 'stopped', $this->registrar->stop() );
	}

	/**
	 * @testdox starting should refuse while a CLI run holds the lock, and name the run.
	 */
	public function test_start_refuses_while_a_cli_lock_is_held(): void {
		$this->state->acquire_lock( 'cli-run-1' );

		$message = $this->registrar->start();

		$this->assertStringContainsString( 'cli-run-1', $message );
		$this->assertFalse(
			wc_get_container()->get( BatchProcessingController::class )->is_enqueued( MigrationBatchProcessor::class ),
			'A refused start must not enqueue anything.'
		);

		$this->state->release_lock();
	}

	/**
	 * `BatchProcessingController` drops a consistently failing processor without telling the
	 * processor, so the lock the background run took outlives the run itself. Left alone it
	 * blocks the Tools screen for the full stale hour, behind a refusal naming a WP-CLI run
	 * that never existed.
	 *
	 * @testdox starting should reclaim a lock left behind by a background run that is gone.
	 */
	public function test_start_reclaims_an_orphaned_background_lock(): void {
		// What the watchdog leaves behind: the background owner's lock, still fresh, with
		// nothing enqueued to hand it back.
		$this->state->acquire_lock( 'background migration' );

		$this->assertFalse(
			wc_get_container()->get( BatchProcessingController::class )->is_enqueued( MigrationBatchProcessor::class ),
			'The fixture stands in for a processor the watchdog already removed.'
		);

		$message = $this->registrar->start();

		$this->assertStringContainsString( 'Migration started', $message, 'A dead run must not block a new one.' );
		$this->assertTrue(
			wc_get_container()->get( BatchProcessingController::class )->is_enqueued( MigrationBatchProcessor::class )
		);
	}

	/**
	 * @testdox a refusal should name the process actually holding the lock, not assume WP-CLI.
	 */
	public function test_a_refused_start_names_the_lock_owner(): void {
		$this->state->acquire_lock( 'cli-run-7' );

		$message = $this->registrar->start();

		$this->assertStringContainsString( 'cli-run-7', $message );
		$this->assertStringNotContainsString(
			'via WP-CLI',
			$message,
			'The message must read the owner rather than assert where the run came from.'
		);

		$this->state->release_lock();
	}

	/**
	 * @testdox the description should say a run stopped on an error rather than call it paused.
	 */
	public function test_the_description_reports_a_recorded_failure(): void {
		$this->state->set_count( 'notifications', 5 );
		$this->state->set_failure( 'notifications', 'lost connection' );

		$description = $this->registrar->handle_woocommerce_debug_tools( array() )['start_bis_migration']['desc'];

		$this->assertStringContainsString( 'stopped on an error', $description );
		$this->assertStringContainsString( 'lost connection', $description );
	}

	/**
	 * @testdox the description should not mention a failure once a run is going again.
	 */
	public function test_the_description_omits_a_cleared_failure(): void {
		$this->state->set_count( 'notifications', 5 );
		$this->state->set_failure( 'notifications', 'lost connection' );
		$this->state->clear_failure();

		$description = $this->registrar->handle_woocommerce_debug_tools( array() )['start_bis_migration']['desc'];

		$this->assertStringNotContainsString( 'stopped on an error', $description );
		$this->assertStringContainsString( 'Paused.', $description );
	}

	/**
	 * @testdox starting twice should not enqueue twice.
	 */
	public function test_start_is_idempotent(): void {
		$this->registrar->start();

		$this->assertSame( 'Migration already in progress, nothing done.', $this->registrar->start() );
	}

	/**
	 * @testdox stopping should report nothing to do when no run is enqueued.
	 */
	public function test_stop_without_a_run_reports_nothing_done(): void {
		$this->assertSame( 'Migration not in progress, nothing done.', $this->registrar->stop() );
	}

	/**
	 * @testdox rendering the Tools entry should not count the legacy tables.
	 */
	public function test_rendering_the_entry_runs_no_counts(): void {
		$product = new \WC_Product_Simple();
		$product->save();

		for ( $i = 0; $i < 3; $i++ ) {
			LegacyStore::add_notification(
				array(
					'product_id' => $product->get_id(),
					'user_email' => "shopper{$i}@example.com",
				)
			);
		}

		// Start the run once, so the counts are cached and the description has figures to render.
		$this->registrar->start();

		$counting_queries = 0;

		$counter = function ( $query ) use ( &$counting_queries ) {
			if ( false !== stripos( $query, 'COUNT(' ) && false !== stripos( $query, 'woocommerce_bis_' ) ) {
				++$counting_queries;
			}

			return $query;
		};

		add_filter( 'query', $counter );
		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );
		remove_filter( 'query', $counter );

		$this->assertSame( 0, $counting_queries, 'The Tools screen must render from the cached counts.' );
		$this->assertStringContainsString( '3 subscribers left to check', $tools['stop_bis_migration']['desc'] );
		$this->assertStringContainsString( '0 of 3 checked', $tools['stop_bis_migration']['desc'] );
		$this->assertStringNotContainsString( 'product-meta', $tools['stop_bis_migration']['desc'], 'Section slugs are internal names.' );
	}

	/**
	 * @testdox the description should say the migration has not started when nothing is cached.
	 */
	public function test_the_description_reads_as_not_started_without_cached_counts(): void {
		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );

		$this->assertStringContainsString( 'Not started yet', $tools['start_bis_migration']['desc'] );
	}

	/**
	 * @testdox the description should read as running while a background run is enqueued.
	 */
	public function test_the_description_reads_as_running_while_enqueued(): void {
		$product = new \WC_Product_Simple();
		$product->save();
		LegacyStore::add_notification( array( 'product_id' => $product->get_id() ) );

		$this->registrar->start();

		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );

		$this->assertStringContainsString( 'Running now.', $tools['stop_bis_migration']['desc'] );
	}

	/**
	 * @testdox the description should read as paused when work is left and no run is enqueued.
	 */
	public function test_the_description_reads_as_paused_between_runs(): void {
		$this->state->set_count( 'notifications', 5 );

		$tools = $this->registrar->handle_woocommerce_debug_tools( array() );

		$this->assertStringContainsString( 'Paused.', $tools['start_bis_migration']['desc'] );
		$this->assertStringContainsString( '5 subscribers left to check', $tools['start_bis_migration']['desc'] );
	}

	/**
	 * @testdox the description should read as finished once every section has drained.
	 */
	public function test_the_description_reads_as_finished_once_everything_is_checked(): void {
		foreach ( array( 'notifications', 'product-meta' ) as $section ) {
			$this->state->set_count( $section, 0 );
		}

		// Store settings are not a section with a cached count: the line reads them straight
		// off the options, so they have to actually be there.
		( new OptionsMigrator( new Reporter() ) )->migrate( wc_get_container()->get( Writer::class ) );

		$desc = $this->registrar->handle_woocommerce_debug_tools( array() )['start_bis_migration']['desc'];

		$this->assertStringContainsString( 'Every subscriber has been checked', $desc );
		$this->assertStringContainsString( 'Product settings and store settings have been imported.', $desc );
	}

	/**
	 * @testdox the description should show subscriber progress against the total a run started from.
	 */
	public function test_the_description_shows_progress_against_the_run_total(): void {
		$this->state->set_total( 'notifications', 10 );
		$this->state->set_count( 'notifications', 4 );

		$desc = $this->registrar->handle_woocommerce_debug_tools( array() )['start_bis_migration']['desc'];

		$this->assertStringContainsString( '6 of 10 checked (60%)', $desc );
	}

	/**
	 * @testdox subscriber progress should be left out until a run has recorded a total.
	 */
	public function test_the_description_omits_progress_without_a_total(): void {
		$this->state->set_count( 'notifications', 4 );

		$desc = $this->registrar->handle_woocommerce_debug_tools( array() )['start_bis_migration']['desc'];

		$this->assertStringContainsString( '4 subscribers left to check', $desc );
		$this->assertStringNotContainsString( 'checked', $desc, 'A percentage with no denominator would have to invent one.' );
	}

	/**
	 * @testdox starting a run should keep the cursor a previous run left behind.
	 */
	public function test_start_keeps_the_cursors(): void {
		$this->state->set_cursor( 'notifications', 42 );

		$this->registrar->start();

		$this->assertSame( 42, $this->state->get_cursor( 'notifications' ), 'Re-walking the whole legacy table is what a kept cursor avoids.' );
	}
}
