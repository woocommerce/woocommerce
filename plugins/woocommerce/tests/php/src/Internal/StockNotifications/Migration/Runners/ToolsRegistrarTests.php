<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
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
		$this->assertStringContainsString( 'notifications: 3', $tools['stop_bis_migration']['desc'] );
	}

	/**
	 * @testdox starting a run should reset every section cursor.
	 */
	public function test_start_resets_the_cursors(): void {
		$this->state->set_cursor( 'notifications', 42 );

		$this->registrar->start();

		$this->assertSame( 0, $this->state->get_cursor( 'notifications' ) );
	}
}
