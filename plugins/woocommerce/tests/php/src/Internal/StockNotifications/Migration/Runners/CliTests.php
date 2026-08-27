<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\Cli;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockWPCLI;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for `wp wc bis-migrate`: the gates every subcommand passes through, the concurrency
 * refusals in both directions, the CLI-only run knobs, and the two commands that only exist
 * here - `rollback` and `disable-legacy-links`.
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
	 * @testdox a store that never installed the legacy extension should exit cleanly with nothing to migrate.
	 */
	public function test_clean_store_reports_nothing_to_migrate(): void {
		delete_option( 'wc_bis_db_version' );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'never installed', MockWPCLI::$last_success_message );
		$this->assertSame( '', MockWPCLI::$last_error_message, 'Nothing to migrate is a success, not an error.' );
	}

	/**
	 * @testdox --force-discover should get past the option gate and reach the requirements check.
	 */
	public function test_force_discover_skips_the_option_gate(): void {
		delete_option( 'wc_bis_db_version' );
		LegacyStore::drop_tables();

		$this->cli()->run(
			array(),
			array(
				'force-discover' => true,
				'yes'            => true,
			)
		);

		$this->assertStringContainsString( 'woocommerce_bis_notifications', MockWPCLI::$last_success_message );
		$this->assertStringNotContainsString( 'never installed', MockWPCLI::$last_success_message );
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
	 * @testdox rollback should refuse while a background run is enqueued.
	 */
	public function test_rollback_refuses_while_the_processor_is_enqueued(): void {
		$this->set_processor_enqueued( true );

		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'in progress in the background', MockWPCLI::$last_error_message );
	}

	/**
	 * @testdox disable-legacy-links should refuse while a background run is enqueued.
	 */
	public function test_disable_legacy_links_refuses_while_the_processor_is_enqueued(): void {
		update_option( 'wc_bis_migration_has_legacy_links', 'yes' );
		$this->set_processor_enqueued( true );

		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'in progress in the background', MockWPCLI::$last_error_message );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_legacy_links' ) );
	}

	/**
	 * @testdox a held CLI lock should stop run, rollback and disable-legacy-links alike.
	 */
	public function test_a_held_lock_stops_every_writing_command(): void {
		$this->seed_notifications( 1 );

		$this->state->acquire_lock( 'another run (pid 1)' );

		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );
		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );

		MockWPCLI::reset();
		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );
		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
	}

	/**
	 * @testdox a lock left behind by a dead run should be reclaimed so the next run proceeds.
	 */
	public function test_a_stale_lock_is_reclaimed(): void {
		$this->seed_notifications( 1 );

		$this->state->acquire_lock( 'dead run (pid 1)' );
		$this->age_the_lock( 2 * HOUR_IN_SECONDS );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertSame( '', MockWPCLI::$last_error_message );
		$this->assertCount( 1, LegacyStore::get_core_rows() );
		$this->assertNull( $this->state->get_lock(), 'The run should release the lock it reclaimed.' );
	}

	/**
	 * @testdox legacy rows still queued by the active extension should refuse a run until --force.
	 */
	public function test_queued_rows_refuse_a_run_until_forced(): void {
		$this->seed_notifications( 1 );

		$this->replace_requirements_with_queued_rows( 3 );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( '3 legacy row(s) are still queued', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );

		MockWPCLI::reset();
		$this->cli()->run(
			array(),
			array(
				'force' => true,
				'yes'   => true,
			)
		);

		$this->assertSame( '', MockWPCLI::$last_error_message );
		$this->assertCount( 1, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a plain run should migrate every eligible row, release the lock and report success.
	 */
	public function test_run_migrates_every_row(): void {
		$this->seed_notifications( 3 );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertCount( 3, LegacyStore::get_core_rows() );
		$this->assertSame( 'Run complete.', MockWPCLI::$last_success_message );
		$this->assertNull( $this->state->get_lock() );
		$this->assertNull( MockWPCLI::$last_halt_code );
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
	 * @testdox --max-batches should bound the CLI loop.
	 */
	public function test_max_batches_bounds_the_loop(): void {
		$this->seed_notifications( 6 );

		$this->cli()->run(
			array(),
			array(
				'section'     => 'notifications',
				'batch-size'  => 2,
				'max-batches' => 2,
				'yes'         => true,
			)
		);

		$this->assertCount( 4, LegacyStore::get_core_rows(), 'Two batches of two is where the run should stop.' );
	}

	/**
	 * @testdox --retry-failed should clear the permanent-failure marker and re-admit the row.
	 */
	public function test_retry_failed_re_admits_a_failed_row(): void {
		$legacy_ids = $this->seed_notifications( 2 );
		$failed_id  = $legacy_ids[0];

		LegacyStore::add_meta( $failed_id, '_wc_bis_migration_failed', array( 'reason' => 'exception' ) );

		$this->cli()->run(
			array(),
			array(
				'section' => 'notifications',
				'yes'     => true,
			)
		);
		$this->assertCount( 1, LegacyStore::get_core_rows(), 'A marked row stays out of the candidate set.' );

		MockWPCLI::reset();
		$this->cli()->run(
			array(),
			array(
				'section'      => 'notifications',
				'retry-failed' => true,
				'yes'          => true,
			)
		);

		$this->assertCount( 2, LegacyStore::get_core_rows() );
		$this->assertSame( array(), LegacyStore::get_legacy_meta( $failed_id, '_wc_bis_migration_failed' ) );
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
	 * @testdox a run with an error-severity outcome should halt non-zero.
	 */
	public function test_error_severity_halts_non_zero(): void {
		$legacy_ids = $this->seed_notifications( 2 );
		$failing_id = $legacy_ids[1];

		// The adoption lookup is the only per-row query, so failing it is the cleanest way to
		// simulate a row that cannot be mapped.
		$thrower = static function ( $query ) use ( $failing_id ) {
			if ( false !== strpos( $query, "shopper{$failing_id}@example.com" ) ) {
				throw new \RuntimeException( 'forced row failure' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );

		try {
			$this->cli()->run(
				array(),
				array(
					'section' => 'notifications',
					'yes'     => true,
				)
			);
		} finally {
			remove_filter( 'query', $thrower );
		}

		$this->assertSame( 1, MockWPCLI::$last_halt_code );
		$this->assertStringContainsString( 'error-severity outcomes', MockWPCLI::$last_warning_message );
	}

	/**
	 * @testdox verify should report a status a merchant changed after migrating as a mismatch.
	 */
	public function test_verify_reports_a_status_mismatch(): void {
		$this->seed_notifications( 1 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->set_core_status( LegacyStore::get_core_rows()[0]['id'], NotificationStatus::CANCELLED );

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'status mismatches', MockWPCLI::$last_error_message );
		$this->assertSame( '', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox verify should pass on an untouched migration and write nothing.
	 */
	public function test_verify_passes_on_an_untouched_migration(): void {
		$this->seed_notifications( 2 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$before = LegacyStore::get_core_rows();

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'Verified', MockWPCLI::$last_success_message );
		$this->assertSame( $before, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox rollback should delete the rows it inserted and clear the migration flags.
	 */
	public function test_rollback_deletes_inserted_rows(): void {
		$this->seed_notifications( 2 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertCount( 2, LegacyStore::get_core_rows() );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$this->assertSame( array(), LegacyStore::get_core_rows() );
		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
		$this->assertFalse( get_option( 'wc_bis_migration_has_legacy_links' ) );
		$this->assertFalse( get_option( 'wc_bis_migration_has_migrated_rows' ) );
		$this->assertSame( 'Rollback complete.', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox rollback --dry-run should report what it would do without deleting anything.
	 */
	public function test_rollback_dry_run_deletes_nothing(): void {
		$this->seed_notifications( 2 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'dry-run' => true ) );

		$this->assertCount( 2, LegacyStore::get_core_rows() );
		$this->assertStringContainsString( 'would be deleted', implode( ' ', MockWPCLI::$all_log_messages ) );
		$this->assertSame( 'Dry run complete.', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox rollback should leave a row whose status a merchant changed after migrating.
	 */
	public function test_rollback_refuses_a_merchant_changed_row(): void {
		$this->seed_notifications( 2 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$rows = LegacyStore::get_core_rows();
		$this->set_core_status( $rows[0]['id'], NotificationStatus::CANCELLED );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$remaining = LegacyStore::get_core_rows();
		$this->assertCount( 1, $remaining );
		$this->assertSame( (string) $rows[0]['id'], (string) $remaining[0]['id'] );
		$this->assertStringContainsString( '1 row(s) left untouched', implode( ' ', MockWPCLI::$all_log_messages ) );
	}

	/**
	 * @testdox rollback should keep an adopted row and only remove the markers it added to it.
	 */
	public function test_rollback_keeps_an_adopted_row(): void {
		$legacy_ids = $this->seed_notifications( 1 );

		$adopted_id = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => "shopper{$legacy_ids[0]}@example.com",
				'status'     => NotificationStatus::ACTIVE,
			)
		);

		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertCount( 1, LegacyStore::get_core_rows(), 'The legacy row should have adopted the existing Core row.' );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$rows = LegacyStore::get_core_rows();
		$this->assertCount( 1, $rows );
		$this->assertSame( (string) $adopted_id, (string) $rows[0]['id'] );
		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_adopted' ) );
	}

	/**
	 * @testdox rollback should refuse outright once the legacy tables are gone, rather than report zero.
	 */
	public function test_rollback_refuses_without_the_legacy_tables(): void {
		$this->seed_notifications( 1 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		LegacyStore::drop_tables();

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$this->assertStringContainsString( 'Refusing to roll back', MockWPCLI::$last_error_message );
		$this->assertCount( 1, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox disable-legacy-links should drop the token hashes and the flag but keep the legacy id marker.
	 */
	public function test_disable_legacy_links_keeps_the_idempotency_marker(): void {
		$this->seed_notifications( 1, true );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertNotEmpty( LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' ) );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_legacy_links' ) );

		MockWPCLI::reset();
		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );

		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' ) );
		$this->assertNotEmpty( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
		$this->assertFalse( get_option( 'wc_bis_migration_has_legacy_links' ) );
	}

	/**
	 * @testdox a run after disable-legacy-links should still write nothing, and rollback should still work.
	 */
	public function test_rollback_still_works_after_disable_legacy_links(): void {
		$this->seed_notifications( 2, true );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );

		MockWPCLI::reset();
		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertCount( 2, LegacyStore::get_core_rows(), 'The legacy id marker still makes the run idempotent.' );

		MockWPCLI::reset();
		$this->cli()->rollback( array(), array( 'yes' => true ) );

		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a second run should write nothing and report nothing outstanding.
	 */
	public function test_a_second_run_writes_nothing(): void {
		$this->seed_notifications( 3 );

		$this->cli()->run( array(), array( 'yes' => true ) );
		$first = LegacyStore::get_core_rows();

		MockWPCLI::reset();
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertSame( $first, LegacyStore::get_core_rows() );
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
	 * Replace the requirements check with one reporting outstanding legacy queued rows, as
	 * only an active legacy extension can.
	 *
	 * @param int $count Rows to report as queued.
	 * @return void
	 */
	private function replace_requirements_with_queued_rows( int $count ): void {
		$requirements = $this->createMock( Requirements::class );
		$requirements->method( 'check' )->willReturn( true );
		$requirements->method( 'count_legacy_queued_rows' )->willReturn( $count );

		wc_get_container()->replace( Requirements::class, $requirements );
	}

	/**
	 * Push the current lock's acquisition time into the past.
	 *
	 * @param int $seconds How far back to move it.
	 * @return void
	 */
	private function age_the_lock( int $seconds ): void {
		$state                        = get_option( 'wc_bis_migration_state' );
		$state['lock']['acquired_at'] = time() - $seconds;

		update_option( 'wc_bis_migration_state', $state );
	}

	/**
	 * Set a Core notification's status by direct SQL, as a merchant edit in admin would.
	 *
	 * @param int|string $notification_id Core notification id.
	 * @param string     $status          New status.
	 * @return void
	 */
	private function set_core_status( $notification_id, string $status ): void {
		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'wc_stock_notifications',
			array( 'status' => $status ),
			array( 'id' => (int) $notification_id )
		);
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
