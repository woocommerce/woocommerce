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
 * here - `disable-legacy-links`.
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
	 * @testdox --yes should skip the confirmation prompt on every command that writes.
	 */
	public function test_yes_skips_the_confirmation_prompt(): void {
		$this->seed_notifications( 1 );
		update_option( 'wc_bis_migration_has_legacy_links', 'yes' );

		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertSame( array(), MockWPCLI::$prompted_confirmations );

		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );
		$this->assertSame( array(), MockWPCLI::$prompted_confirmations );
	}

	/**
	 * @testdox every writing command should still confirm when --yes is absent.
	 */
	public function test_every_writing_command_confirms_without_yes(): void {
		$this->seed_notifications( 1 );
		update_option( 'wc_bis_migration_has_legacy_links', 'yes' );

		$this->cli()->run( array(), array() );
		$this->cli()->disable_legacy_links( array(), array() );

		$this->assertCount( 2, MockWPCLI::$prompted_confirmations );
	}

	/**
	 * @testdox a held CLI lock should stop run and disable-legacy-links alike.
	 */
	public function test_a_held_lock_stops_every_writing_command(): void {
		$this->seed_notifications( 1 );

		$this->state->acquire_lock( 'another run (pid 1)' );

		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
		$this->assertSame( array(), LegacyStore::get_core_rows() );

		MockWPCLI::reset();
		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );
		$this->assertStringContainsString( 'Could not acquire the migration CLI lock', MockWPCLI::$last_error_message );
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
	 * @testdox --section should keep the run inside the sections it names.
	 */
	public function test_section_restriction_leaves_other_sections_alone(): void {
		$this->seed_notifications( 2 );

		$this->cli()->run(
			array(),
			array(
				'section' => 'settings',
				'yes'     => true,
			)
		);

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'The notifications section was not asked for.' );
		$this->assertNull( $this->state->get_count( 'notifications' ), 'A section outside --section is never counted.' );
	}

	/**
	 * @testdox a drained section should end the run with a zero cached count, whatever the batch size divides into.
	 */
	public function test_a_drained_section_caches_a_zero_count(): void {
		// Four rows in batches of two: every batch is full, so nothing but the drain itself
		// marks the section as finished.
		$this->seed_notifications( 4 );

		$this->cli()->run(
			array(),
			array(
				'section'    => 'notifications',
				'batch-size' => 2,
				'yes'        => true,
			)
		);

		$cached = $this->state->get_count( 'notifications' );

		$this->assertNotNull( $cached );
		$this->assertSame( 0, (int) $cached['count'] );
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
	 * @testdox a run with an error-severity outcome should halt non-zero.
	 */
	public function test_error_severity_halts_non_zero(): void {
		$legacy_ids = $this->seed_notifications( 2 );
		$failing_id = $legacy_ids[1];

		// Give the failing row a Core row to adopt: its marker write is the one write that
		// happens inside the per-row try/catch, so failing it fails that row alone.
		LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => "shopper{$failing_id}@example.com",
			)
		);

		$thrower = static function ( $query ) {
			if ( false !== strpos( $query, '_wc_bis_legacy_adopted' ) ) {
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
	 * @testdox verify should report a status no shopper action could have produced as a mismatch.
	 */
	public function test_verify_reports_a_status_mismatch(): void {
		$this->seed_notifications( 1 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		// Nothing in Core moves a live row back to pending, so this is a real disagreement
		// rather than the row moving on.
		$this->set_core_status( LegacyStore::get_core_rows()[0]['id'], NotificationStatus::PENDING );

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'status mismatches', MockWPCLI::$last_error_message );
		$this->assertSame( '', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox verify should report a row the shopper moved on as drift, not as a mismatch.
	 * @dataProvider provider_forward_transitions
	 *
	 * @param string $status Status the shopper's own action would leave behind.
	 */
	public function test_verify_reports_a_forward_transition_as_drift( string $status ): void {
		$this->seed_notifications( 1 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->set_core_status( LegacyStore::get_core_rows()[0]['id'], $status );

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'Verified', MockWPCLI::$last_success_message );
		$this->assertStringContainsString( 'have moved on since the migration ran', implode( "\n", MockWPCLI::$all_log_messages ) );
	}

	/**
	 * Statuses reachable from `active`, the status a seeded row migrates as.
	 *
	 * @return array
	 */
	public function provider_forward_transitions(): array {
		return array(
			'cancelled after the run' => array( NotificationStatus::CANCELLED ),
			'notified after the run'  => array( NotificationStatus::SENT ),
		);
	}

	/**
	 * @testdox verify should treat a pending row verified through a legacy link as drift.
	 */
	public function test_verify_treats_a_verified_pending_row_as_drift(): void {
		LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
			)
		);
		$this->cli()->run( array(), array( 'yes' => true ) );

		$row = LegacyStore::get_core_rows()[0];

		$this->assertSame( NotificationStatus::PENDING, $row['status'] );

		$this->set_core_status( $row['id'], NotificationStatus::ACTIVE );

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'Verified', MockWPCLI::$last_success_message );
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
	 * @testdox verify should read each page's legacy sources in one round trip, not per row.
	 */
	public function test_verify_reads_legacy_sources_per_page(): void {
		$this->seed_notifications( 6, true );
		$this->cli()->run( array(), array( 'yes' => true ) );

		MockWPCLI::reset();
		$queries = $this->record_legacy_reads(
			function () {
				$this->cli()->verify( array(), array() );
			}
		);

		// One page of markers, so one row query and one meta query for the whole page. The
		// per-row version issued two per row.
		$this->assertLessThanOrEqual( 2, count( $queries ), 'Legacy sources must be fetched a page at a time.' );
		$this->assertStringContainsString( 'Verified', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox a legacy flag no mapper reads should not make verify report anything.
	 */
	public function test_a_legacy_key_outside_the_write_path_is_ignored(): void {
		$legacy_ids = $this->seed_notifications( 1 );
		$this->cli()->run( array(), array( 'yes' => true ) );

		// `awaiting_verification` is unreliable — legacy deletes it on verify, on cancel and
		// on deactivate — so no mapper consults it, at write time or here.
		LegacyStore::add_meta( $legacy_ids[0], 'awaiting_verification', 'yes' );

		MockWPCLI::reset();
		$this->cli()->verify( array(), array() );

		$this->assertStringContainsString( 'Verified', MockWPCLI::$last_success_message );
	}

	/**
	 * @testdox disable-legacy-links should drop both token digests and the flag but keep the legacy id marker.
	 */
	public function test_disable_legacy_links_keeps_the_idempotency_marker(): void {
		$this->seed_notifications( 1, true );

		$pending = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
				'user_email'  => 'pending@example.com',
			)
		);
		LegacyStore::add_verification_data( $pending, 'a-verification-code', time() );

		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->assertNotEmpty( LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' ) );
		$this->assertNotEmpty( LegacyStore::get_core_meta( '_wc_bis_legacy_verify_hash' ) );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_legacy_links' ) );

		MockWPCLI::reset();
		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );

		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' ) );
		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_verify_hash' ) );
		$this->assertNotEmpty( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
		$this->assertFalse( get_option( 'wc_bis_migration_has_legacy_links' ) );
	}

	/**
	 * @testdox a run after disable-legacy-links should still write nothing.
	 */
	public function test_a_run_after_disable_legacy_links_writes_nothing(): void {
		$this->seed_notifications( 2, true );
		$this->cli()->run( array(), array( 'yes' => true ) );

		$this->cli()->disable_legacy_links( array(), array( 'yes' => true ) );

		MockWPCLI::reset();
		$this->cli()->run( array(), array( 'yes' => true ) );
		$this->assertCount( 2, LegacyStore::get_core_rows(), 'The legacy id marker still makes the run idempotent.' );
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
	 * Run a callback and collect the reads it makes against the legacy tables while
	 * re-deriving a row's expected state. Aggregates are left out: both commands report a
	 * section's outstanding count, which is one COUNT(*) regardless of row count.
	 *
	 * @param callable $callback Callback to run.
	 * @return string[] The recorded queries.
	 */
	private function record_legacy_reads( callable $callback ): array {
		global $wpdb;

		$queries = array();
		$tables  = array( $wpdb->prefix . 'woocommerce_bis_notifications ', $wpdb->prefix . 'woocommerce_bis_notificationsmeta ' );

		$recorder = function ( $query ) use ( &$queries, $tables ) {
			if ( 0 !== stripos( ltrim( (string) $query ), 'SELECT' ) || false !== stripos( (string) $query, 'COUNT(' ) ) {
				return $query;
			}

			foreach ( $tables as $table ) {
				if ( false !== stripos( (string) $query, ' ' . $table ) ) {
					$queries[] = (string) $query;
					break;
				}
			}

			return $query;
		};

		add_filter( 'query', $recorder );
		$callback();
		remove_filter( 'query', $recorder );

		return $queries;
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
