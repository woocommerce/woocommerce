<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\Cli;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockWPCLI;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for `wp wc bis-migrate`: the gates every subcommand passes through, the concurrency
 * refusals in both directions, and the CLI-only run knobs.
 */
class CliTests extends WC_Unit_Test_Case {

	/**
	 * A 32-byte legacy hash key, as the legacy extension stored per notification.
	 *
	 * @var string
	 */
	private const HASH_KEY = '0123456789abcdef0123456789abcdef';

	/**
	 * A 16-byte legacy hash IV.
	 *
	 * @var string
	 */
	private const HASH_IV = 'fedcba9876543210';

	/**
	 * Run state, read back to assert what a command did to the lock and the cursors.
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
	 * Set up the legacy tables, the feature toggle and a WP_CLI that records instead of exiting.
	 */
	public function setUp(): void {
		parent::setUp();

		// Referencing the mock is what aliases it to the global WP_CLI the command calls.
		MockWPCLI::reset();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );
		update_option( 'wc_bis_db_version', '1.2.0' );

		LegacyStore::create_tables();
		LegacyStore::truncate_all();

		$this->clear_migration_options();

		$this->state      = new MigrationState();
		$this->product_id = $this->create_product();

		$this->set_processor_enqueued( false );
	}

	/**
	 * Drop the legacy tables, the container replacements and everything the migration persists.
	 */
	public function tearDown(): void {
		$this->reset_container_replacements();
		$this->reset_container_resolutions();

		LegacyStore::drop_tables();
		$this->clear_migration_options();
		delete_option( 'wc_bis_db_version' );
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );

		parent::tearDown();
	}

	/**
	 * @testdox --force should be refused without --yes.
	 */
	public function test_force_requires_yes(): void {
		$this->seed_notifications( 1 );

		$this->cli()->run( array(), array( 'force' => true ) );

		$this->assertSame( '--force requires --yes.', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox the feature toggle being off should stop the run without writing or erroring.
	 */
	public function test_feature_off_stops_the_run(): void {
		$this->seed_notifications( 1 );
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'Features', MockWPCLI::$last_success_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox an unknown --section value should be rejected and name the valid sections.
	 */
	public function test_unknown_section_is_rejected(): void {
		$this->cli()->run(
			array(),
			array(
				'section' => 'notifications,widgets',
				'yes'     => true,
			)
		);

		$this->assertStringContainsString( 'widgets', MockWPCLI::$last_error_message );
		$this->assertStringContainsString( 'product-meta', MockWPCLI::$last_error_message );
	}

	/**
	 * @testdox run should refuse while a background run is enqueued, and --force must not override it.
	 */
	public function test_run_refuses_while_the_processor_is_enqueued(): void {
		$this->seed_notifications( 1 );
		$this->set_processor_enqueued( true );

		$this->cli()->run(
			array(),
			array(
				'force' => true,
				'yes'   => true,
			)
		);

		$this->assertStringContainsString( 'already in progress in the background', MockWPCLI::$last_error_message );
		$this->assertStringContainsString( '--force does not override this', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox --yes should skip the confirmation prompt on a run that writes.
	 */
	public function test_yes_skips_the_confirmation_prompt(): void {
		$this->seed_notifications( 1 );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertSame( array(), MockWPCLI::$prompted_confirmations );
	}

	/**
	 * @testdox a run should still confirm when --yes is absent.
	 */
	public function test_a_run_confirms_without_yes(): void {
		$this->seed_notifications( 1 );

		$this->cli()->run( array(), array() );

		$this->assertCount( 1, MockWPCLI::$prompted_confirmations );
	}

	/**
	 * @testdox a held CLI lock should stop a run.
	 */
	public function test_a_held_lock_stops_a_run(): void {
		$this->seed_notifications( 1 );

		$this->state->acquire_lock( 'another run (pid 1)' );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a background run holding the lock should stop a CLI run from starting.
	 */
	public function test_a_background_run_stops_a_cli_run(): void {
		$this->seed_notifications( 1 );

		$this->state->acquire_lock( 'background migration' );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows(), 'A CLI run must not write while the background run holds the lock.' );
	}

	/**
	 * @testdox --dry-run should write nothing and still release the lock.
	 */
	public function test_dry_run_writes_nothing(): void {
		$this->seed_notifications( 2 );

		$this->cli()->run( array(), array( 'dry-run' => true ) );

		$this->assertSame( array(), LegacyStore::get_core_rows() );
		$this->assertSame( 'Dry run complete.', MockWPCLI::$last_success_message );
		$this->assertNull( $this->state->get_lock() );
	}

	/**
	 * @testdox --section should keep the run inside the sections it names.
	 */
	public function test_section_restriction_leaves_other_sections_alone(): void {
		$this->seed_notifications( 2 );

		$this->cli()->run(
			array(),
			array(
				'section' => 'product-meta',
				'yes'     => true,
			)
		);

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'The notifications section was not asked for.' );
		$this->assertNull( $this->state->get_count( 'notifications' ), 'A section outside --section is never counted.' );
	}

	/**
	 * @testdox --retry-failed should also clear the product-meta section's failure marker.
	 */
	public function test_retry_failed_clears_the_product_meta_failure_marker(): void {
		update_post_meta( $this->product_id, '_wc_bis_migration_signups_failed', (string) time() );

		$this->cli()->run(
			array(),
			array(
				'retry-failed' => true,
				'yes'          => true,
			)
		);

		$this->assertSame( '', get_post_meta( $this->product_id, '_wc_bis_migration_signups_failed', true ) );
	}

	/**
	 * @testdox --retry-failed should be ignored under --dry-run.
	 */
	public function test_retry_failed_is_ignored_under_dry_run(): void {
		$legacy_ids = $this->seed_notifications( 1 );

		LegacyStore::add_meta( $legacy_ids[0], '_wc_bis_migration_failed', array( 'reason' => 'exception' ) );

		$this->cli()->run(
			array(),
			array(
				'retry-failed' => true,
				'dry-run'      => true,
			)
		);

		$this->assertCount( 1, LegacyStore::get_legacy_meta( $legacy_ids[0], '_wc_bis_migration_failed' ) );
	}

	/**
	 * @testdox a second run should write nothing and report nothing outstanding.
	 */
	public function test_a_second_run_writes_nothing(): void {
		update_post_meta( $this->product_id, '_wc_bis_disabled', 'yes' );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$first = get_post_meta( $this->product_id, 'customer_stock_notifications_enable_signups', true );

		// Asserted before the comparison below, which two unmigrated runs would also satisfy.
		$this->assertSame( 'no', $first, 'The first run must have migrated the product.' );

		MockWPCLI::reset();
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertSame( $first, get_post_meta( $this->product_id, 'customer_stock_notifications_enable_signups', true ) );
		$this->assertSame( 'Run complete.', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox status should report the cached outstanding counts rather than counting the legacy rows itself.
	 */
	public function test_status_runs_no_counts_of_its_own(): void {
		$this->seed_notifications( 2 );

		$counted = array();

		// Only the notifications table matters here. `status` does count the permanent-failure
		// markers in the meta table, which is a different, deliberately live figure.
		$recorder = function ( $query ) use ( &$counted ) {
			if ( false !== stripos( $query, 'COUNT(' ) && false !== stripos( $query, 'woocommerce_bis_notifications ' ) ) {
				$counted[] = $query;
			}

			return $query;
		};

		add_filter( 'query', $recorder );
		$this->cli()->status( array(), array() );
		remove_filter( 'query', $recorder );

		$this->assertSame( array(), $counted, 'status must read the cached counts, never compute them.' );
	}

	/**
	 * Build a command instance against the current container replacements.
	 *
	 * @return Cli
	 */
	private function cli(): Cli {
		return new Cli();
	}

	/**
	 * Replace the batch processing controller with one reporting the given enqueued state.
	 *
	 * @param bool $enqueued What `is_enqueued()` should report.
	 * @return void
	 */
	private function set_processor_enqueued( bool $enqueued ): void {
		$controller = $this->createMock( BatchProcessingController::class );
		$controller->method( 'is_enqueued' )->willReturn( $enqueued );

		wc_get_container()->replace( BatchProcessingController::class, $controller );
	}

	/**
	 * Seed eligible legacy notification rows, one per unique email.
	 *
	 * @param int  $count       How many rows to seed.
	 * @param bool $with_secrets Whether to add the `_hash_key`/`_hash_iv` pair a legacy
	 *                           unsubscribe token is derived from.
	 * @return int[] The seeded legacy ids, ascending.
	 */
	private function seed_notifications( int $count, bool $with_secrets = false ): array {
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

			if ( $with_secrets ) {
				LegacyStore::add_meta( $legacy_id, '_hash_key', self::HASH_KEY );
				LegacyStore::add_meta( $legacy_id, '_hash_iv', self::HASH_IV );
			}
		}

		return $ids;
	}

	/**
	 * Clear every option the migration persists, including the autoloaded flags that survive
	 * the per-test transaction rollback through the object cache.
	 *
	 * @return void
	 */
	private function clear_migration_options(): void {
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
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
