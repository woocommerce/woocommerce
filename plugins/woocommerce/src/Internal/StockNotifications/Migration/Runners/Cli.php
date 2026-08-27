<?php
/**
 * Cli class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\SettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\NullWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * `wp wc bis-migrate` — the CLI entry point for the Back In Stock Notifications migration.
 *
 * Registers unconditionally under `WP_CLI`, never gated on `wc_bis_db_version`: gating it on
 * that option would make `--force-discover` unreachable in exactly the situation it exists for.
 * On a store with nothing to migrate every subcommand still runs, reports "nothing to migrate",
 * and exits `0`.
 *
 * `run` drives the four migrators directly, in the fixed section order, rather than going
 * through `MigrationBatchProcessor`: that class is the Action Scheduler entry point and its
 * `BatchProcessorInterface::process_batch()` contract has no room for the CLI-only knobs
 * (`--dry-run`, `--force`, `--retry-failed`, `--section`). Both entry points share the same
 * state — `MigrationState` cursors/counts and the markers the migrators write — so a run
 * started from either one is resumed correctly by the other.
 *
 * This class only reads whether a background run is enqueued, via
 * `BatchProcessingController::is_enqueued()`; it never enqueues or dequeues the processor
 * itself, since only the Tools screen and this CLI are meant to trigger a run.
 */
class Cli {

	/**
	 * Section slugs in the fixed, load-bearing migration order. Matches every migrator's
	 * `get_slug()` and the CLI `--section` values.
	 *
	 * @var string[]
	 */
	private const SECTION_ORDER = array( 'notifications', 'product-meta', 'emails', 'settings' );

	/**
	 * Option recording that Back In Stock Notifications was ever installed. Absent means
	 * there is nothing to migrate; `--force-discover` is the only way past this gate.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'wc_bis_db_version';

	/**
	 * Autoloaded flag gating the legacy unsubscribe shim's registration. Narrower than
	 * HAS_MIGRATED_ROWS_OPTION below: only set when a migrated row carries a legacy
	 * unsubscribe token.
	 *
	 * @var string
	 */
	private const HAS_LEGACY_LINKS_OPTION = 'wc_bis_migration_has_legacy_links';

	/**
	 * Autoloaded flag set the first time any row is migrated, inserted or adopted. Backs
	 * the double-send admin notice in MigrationController.
	 *
	 * @var string
	 */
	private const HAS_MIGRATED_ROWS_OPTION = 'wc_bis_migration_has_migrated_rows';

	/**
	 * Legacy meta key recording a permanent per-row failure. Cleared by `--retry-failed`
	 * and by `rollback`.
	 *
	 * @var string
	 */
	private const LEGACY_FAILED_META_KEY = '_wc_bis_migration_failed';

	/**
	 * Migration marker recording a successful migration onto a Core notification.
	 *
	 * @var string
	 */
	private const LEGACY_ID_META_KEY = '_wc_bis_legacy_id';

	/**
	 * Meta key holding the precomputed legacy unsubscribe token digest.
	 *
	 * @var string
	 */
	private const LEGACY_UNSUB_HASH_META_KEY = '_wc_bis_legacy_unsub_hash';

	/**
	 * Marker meta written only when a legacy row adopted a pre-existing Core notification
	 * instead of being inserted. Distinguishes the two so `rollback` never deletes an
	 * adopted row - only inserted rows are deleted.
	 *
	 * @var string
	 */
	private const ADOPTED_MARKER_META_KEY = '_wc_bis_legacy_adopted';

	/**
	 * Legacy notifications table, unprefixed.
	 *
	 * @var string
	 */
	private const LEGACY_NOTIFICATIONS_TABLE = 'woocommerce_bis_notifications';

	/**
	 * Legacy notifications meta table, unprefixed.
	 *
	 * @var string
	 */
	private const LEGACY_META_TABLE = 'woocommerce_bis_notificationsmeta';

	/**
	 * Core notifications table, unprefixed.
	 *
	 * @var string
	 */
	private const CORE_NOTIFICATIONS_TABLE = 'wc_stock_notifications';

	/**
	 * Core notifications meta table, unprefixed.
	 *
	 * @var string
	 */
	private const CORE_META_TABLE = 'wc_stock_notificationmeta';

	/**
	 * Default batch size for a CLI run. Raised by `--batch-size`; scheduled runs use their
	 * own, smaller default from `get_default_batch_size()`.
	 *
	 * @var int
	 */
	private const DEFAULT_BATCH_SIZE = 500;

	/**
	 * Row count used to page through Core notifications in `verify` and `rollback`.
	 *
	 * @var int
	 */
	private const ORACLE_BATCH_SIZE = 500;

	/**
	 * Verifies whether a run may start or continue.
	 *
	 * @var Requirements
	 */
	private Requirements $requirements;

	/**
	 * Used only to read whether the background processor is currently enqueued.
	 *
	 * @var BatchProcessingController
	 */
	private BatchProcessingController $batch_processing_controller;

	/**
	 * Migration run state: the CLI lock, per-section cursors and cached counts.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Constructor. Resolves its dependencies from the container directly rather than via
	 * autowired parameters, matching `ProductAttributesLookup\CLIRunner`: whatever registers
	 * this class with `WP_CLI::add_command()` may pass either a bare `new self()` or a
	 * container-resolved instance, and both must work.
	 */
	public function __construct() {
		$container = wc_get_container();

		$this->requirements                = $container->get( Requirements::class );
		$this->batch_processing_controller = $container->get( BatchProcessingController::class );
		$this->state                       = new MigrationState();
	}

	/**
	 * Register the `wp wc bis-migrate` command and its subcommands.
	 *
	 * Registration is unconditional under WP_CLI, so `--force-discover` stays reachable on a
	 * store whose `wc_bis_db_version` option was deleted by hand.
	 */
	public function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// @phpstan-ignore-next-line -- WP_CLI is only defined in a CLI context.
		WP_CLI::add_command( 'wc bis-migrate', $this );
	}

	/**
	 * Report migration status.
	 *
	 * There is no run-status field to report: a run is either holding the CLI lock or it is
	 * not, and what has migrated is recorded by markers rather than by a status. This reports
	 * the facts that replace it: per-section cached counts with their timestamp, whether a
	 * background run is enqueued, whether the CLI lock is held, and how many rows carry the
	 * permanent-failure marker.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc bis-migrate status
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function status( $args, $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- standard WP-CLI command signature.
		$check = $this->requirements->check();

		if ( is_wp_error( $check ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
		}

		$reporter = new Reporter();

		foreach ( self::SECTION_ORDER as $slug ) {
			$cached = $this->state->get_count( $slug );

			if ( null === $cached ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::log( sprintf( '%-14s not yet computed', $slug ) );
				continue;
			}

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log(
				sprintf( '%-14s %s', $slug, $reporter->format_cached_count( $cached['count'], $cached['at'] ) )
			);
		}

		$this->print_known_losses( $reporter );

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log( sprintf( 'Background run enqueued: %s', $this->is_processor_enqueued() ? 'yes' : 'no' ) );

		$lock = $this->state->get_lock();

		if ( $this->state->is_lock_held() && null !== $lock ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log(
				sprintf(
					'CLI lock held: yes (owner: %s, since %s)',
					$lock['owner'],
					$this->format_site_time( (int) $lock['acquired_at'] )
				)
			);
		} else {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( 'CLI lock held: no' );
		}

		$failed = is_wp_error( $check ) && 'legacy_tables_missing' === $check->get_error_code()
			? 0
			: $this->count_failed_rows();

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log( sprintf( 'Rows marked permanently failed: %d', $failed ) );

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::success( 'Status reported.' );
	}

	/**
	 * Run the migration.
	 *
	 * Drives the requested sections, in the fixed order (notifications, product-meta, emails,
	 * settings — load-bearing, never reordered by `--section`), each with its own cursor
	 * reset pass: when a section's query comes back empty, its cursor resets and the query
	 * runs again, and the section is only considered drained when a query immediately after a
	 * reset also comes back empty.
	 *
	 * `--max-batches` bounds this CLI loop only. It is a debugging aid, not a throughput
	 * mode: a run always re-walks the whole candidate range from the start of each section
	 * (cursors are reset at the top of every run), so repeated small invocations pay that
	 * re-scan every time.
	 *
	 * ## OPTIONS
	 *
	 * [--section=<sections>]
	 * : Comma-separated sections to run. Defaults to all four: notifications, product-meta,
	 * emails, settings.
	 *
	 * [--batch-size=<size>]
	 * : Maximum rows fetched per batch. Default 500.
	 *
	 * [--dry-run]
	 * : Compute and report everything without writing anything.
	 *
	 * [--force]
	 * : CLI only, requires --yes. Overwrites an option or product-meta value a merchant
	 * edited after a previous migration, and overrides the is_queued='on' pre-flight refusal.
	 * Does not skip Requirements::check() and does not change any status mapping.
	 *
	 * [--retry-failed]
	 * : Clear the permanent-failure marker on legacy rows so they are retried. Ignored under
	 * --dry-run.
	 *
	 * [--max-batches=<n>]
	 * : Stop after this many batches, across all sections. Debugging aid only.
	 *
	 * [--force-discover]
	 * : Skip the wc_bis_db_version option gate and go straight to Requirements::check(). The
	 * one path that reaches a store whose option was deleted by hand.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc bis-migrate run
	 *     wp wc bis-migrate run --section=notifications --dry-run
	 *     wp wc bis-migrate run --force --yes
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (options).
	 */
	public function run( $args, $assoc_args ): void {
		$force = isset( $assoc_args['force'] );

		if ( $force && ! isset( $assoc_args['yes'] ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( '--force requires --yes.' );
			return;
		}

		$force_discover = isset( $assoc_args['force-discover'] );

		if ( ! $force_discover && ! get_option( self::DB_VERSION_OPTION ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( 'Nothing to migrate: Back In Stock Notifications was never installed on this store.' );
			return;
		}

		$check = $this->requirements->check();

		if ( is_wp_error( $check ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
			return;
		}

		if ( $this->is_processor_enqueued() ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error(
				'A migration run is already in progress in the background (WooCommerce → Status → Tools). ' .
				'Stop it there before starting a CLI run — --force does not override this.'
			);
			return;
		}

		$sections     = $this->resolve_sections( $assoc_args );
		$dry_run      = isset( $assoc_args['dry-run'] );
		$retry_failed = isset( $assoc_args['retry-failed'] );
		$batch_size   = isset( $assoc_args['batch-size'] ) ? max( 1, (int) $assoc_args['batch-size'] ) : self::DEFAULT_BATCH_SIZE;
		$max_batches  = isset( $assoc_args['max-batches'] ) ? max( 1, (int) $assoc_args['max-batches'] ) : null;

		if ( in_array( 'notifications', $sections, true ) ) {
			$queued = $this->requirements->count_legacy_queued_rows();

			if ( $queued > 0 && ! $force ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::error(
					sprintf(
						'Refusing to start: %d legacy row(s) are still queued (is_queued=\'on\') by the active ' .
						'Back In Stock Notifications extension. Let the legacy queue drain, or pass --force --yes to override.',
						$queued
					)
				);
				return;
			}
		}

		if ( ! $dry_run ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::confirm( 'This will write to the database. Continue?', $assoc_args );
		}

		if ( ! $this->acquire_lock( 'run' ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Could not acquire the migration CLI lock. Another CLI run may already be in progress.' );
			return;
		}

		try {
			if ( $retry_failed && ! $dry_run && in_array( 'notifications', $sections, true ) ) {
				$cleared = $this->clear_failed_markers();
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::log( sprintf( 'Cleared the failed marker on %d row(s); they will be retried.', $cleared ) );
			}

			// A run always starts from zero: cursors left behind by a previous run cannot be
			// trusted (a row deleted in Core mid-run re-enters the candidate set below them).
			// `MigrationBatchProcessor` only ever advances cursors, never resets them, so this
			// invariant is enforced only at run-start entry points — this one and the Tools
			// start callback — not inside the processor itself.
			$this->state->reset_all_cursors();

			$reporter               = new Reporter();
			$notifications_migrator = new NotificationsMigrator( $reporter );
			$migrators              = $this->build_migrators( $reporter, $force, $notifications_migrator );
			$writer                 = $dry_run ? new NullWriter() : new DbWriter();

			// Counts are cached, display-only, and refreshed at run start and on section
			// drain — never computed live outside a run.
			$total_estimate = 0;
			foreach ( $sections as $slug ) {
				$remaining = $migrators[ $slug ]->count_remaining();
				$this->state->set_count( $slug, $remaining );
				$total_estimate += $remaining;
			}

			if ( in_array( 'notifications', $sections, true ) ) {
				// One COUNT(*) per skipped population, run once here and cached: `status` and
				// the Tools description report these from the cache and never compute them.
				$this->state->set_losses( $reporter->collect_known_losses( $notifications_migrator ) );
			}

			// @phpstan-ignore-next-line function.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			$progress    = WP_CLI\Utils\make_progress_bar( 'BIS migration', max( 1, $total_estimate ) );
			$batches_run = 0;

			foreach ( $sections as $slug ) {
				$migrator = $migrators[ $slug ];

				while ( null === $max_batches || $batches_run < $max_batches ) {
					$check = $this->requirements->check();

					if ( is_wp_error( $check ) ) {
						// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
						WP_CLI::warning( sprintf( 'Stopping: %s', $check->get_error_message() ) );
						break 2;
					}

					$cursor = $this->state->get_cursor( $slug );
					$ids    = $migrator->get_batch( $cursor, $batch_size );

					if ( empty( $ids ) ) {
						if ( 0 === $cursor || $writer->is_dry_run() ) {
							// Under a dry run nothing is written, so no row ever leaves the
							// candidate set; a reset-and-retry pass would just fetch the same
							// rows forever. Only a real run needs the reset pass, to pick up
							// rows that re-entered the candidate set below an advancing cursor.
							break;
						}

						$this->state->reset_cursor( $slug );
						continue;
					}

					$migrator->migrate_batch( $ids, $writer );
					// get_batch() returns ids as strings, as $wpdb hands them over.
					$this->state->set_cursor( $slug, (int) max( $ids ) );
					$progress->tick( count( $ids ) );
					++$batches_run;
				}

				$this->state->set_count( $slug, $migrator->count_remaining() );
			}

			$progress->finish();

			if ( in_array( 'notifications', $sections, true ) ) {
				$cached = $this->state->get_losses();
				$values = is_array( $cached['values'] ?? null ) ? $cached['values'] : array();
				$this->state->set_losses( $reporter->with_run_losses( $values, $notifications_migrator ) );
			}

			$this->print_report( $reporter );
			$this->print_known_losses( $reporter );

			if ( $reporter->has_errors() ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::warning( 'Run finished with error-severity outcomes. See the table above.' );
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::halt( 1 );
			}

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Run complete.' );
		} finally {
			$this->release_lock();
		}
	}

	/**
	 * Read-only verification: re-derive expected state via the mappers and diff it against
	 * what is actually stored. Never writes anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc bis-migrate verify
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function verify( $args, $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- standard WP-CLI command signature.
		$check = $this->requirements->check();

		if ( is_wp_error( $check ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
			return;
		}

		$reporter  = new Reporter();
		$migrators = $this->build_migrators( $reporter, false );

		foreach ( self::SECTION_ORDER as $slug ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( '%-14s %d row(s) still outstanding.', $slug, $migrators[ $slug ]->count_remaining() ) );
		}

		list( $verified, $mismatched ) = $this->verify_notification_statuses();

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log(
			sprintf(
				'%-14s %d migrated row(s) checked against their legacy source; %d status mismatch(es).',
				'notifications',
				$verified,
				$mismatched
			)
		);

		if ( $mismatched > 0 ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Verification found status mismatches. See above.' );
			return;
		}

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::success( 'Verified: current state matches what the mappers derive from the legacy source.' );
	}

	/**
	 * Undo the migration's writes for rows a merchant has not touched since.
	 *
	 * Splits by how a row entered Core. A row this migration *inserted* is deleted outright,
	 * gated on the "untouched" test below. A row it only *adopted* - a pre-existing Core row
	 * that already belonged to the merchant - is never deleted: only the markers this
	 * migration added for that legacy id (`_wc_bis_legacy_id`, `_wc_bis_legacy_adopted`, and
	 * the matching `_wc_bis_legacy_unsub_hash` row) are removed, leaving status, dates and
	 * every other meta untouched.
	 *
	 * "Untouched" (for an inserted row) is tested by status equality against what
	 * `StatusMapper` re-derives from the row's legacy source, never by `date_modified_gmt`:
	 * any meta write bumps that column, which would refuse every row. Requires the legacy
	 * tables to still exist, since they are the oracle that test reads from; refuses up front
	 * with that reason rather than silently reporting zero rows, which would look identical
	 * to success.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * [--dry-run]
	 * : Report what would be rolled back without deleting anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc bis-migrate rollback --dry-run
	 *     wp wc bis-migrate rollback --yes
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (options).
	 */
	public function rollback( $args, $assoc_args ): void {
		$check = $this->requirements->check();

		if ( is_wp_error( $check ) ) {
			if ( 'legacy_tables_missing' === $check->get_error_code() ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::error( sprintf( 'Refusing to roll back: %s', $check->get_error_message() ) );
				return;
			}

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
			return;
		}

		if ( $this->is_processor_enqueued() ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error(
				'A migration run is in progress in the background (WooCommerce → Status → Tools). Stop it before rolling back.'
			);
			return;
		}

		$dry_run = isset( $assoc_args['dry-run'] );

		if ( ! $dry_run ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::confirm( 'This will permanently delete migrated Stock Notifications rows. Continue?', $assoc_args );
		}

		if ( ! $this->acquire_lock( 'rollback' ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Could not acquire the migration CLI lock. Another CLI run may already be in progress.' );
			return;
		}

		try {
			list( $deleted, $markers_cleared, $skipped ) = $this->rollback_notifications( $dry_run );

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log(
				sprintf(
					'%d inserted row(s) %s, %d adopted row(s) %s, %d row(s) left untouched (status changed since migration).',
					$deleted,
					$dry_run ? 'would be deleted' : 'deleted',
					$markers_cleared,
					$dry_run ? 'would have their migration markers cleared' : 'had their migration markers cleared',
					$skipped
				)
			);

			if ( ! $dry_run ) {
				$cleared = $this->clear_failed_markers();
				delete_option( self::HAS_LEGACY_LINKS_OPTION );
				delete_option( self::HAS_MIGRATED_ROWS_OPTION );
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::log( sprintf( 'Cleared the failed marker on %d row(s) and the legacy-links/migrated-rows flags.', $cleared ) );
			}

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Rollback complete.' );
		} finally {
			$this->release_lock();
		}
	}

	/**
	 * Remove the legacy unsubscribe shim's remaining footprint: the per-notification token
	 * hash and the flag that gates the shim's registration. `_wc_bis_legacy_id` survives —
	 * it is the idempotency marker, not shim-specific.
	 *
	 * Writes by direct SQL, like `BulkNotificationWriter`: going through the CRUD layer
	 * would bump `date_modified_gmt` on every migrated row and break `rollback`'s status
	 * equality test.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc bis-migrate disable-legacy-links --yes
	 *
	 * @subcommand disable-legacy-links
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (options).
	 */
	public function disable_legacy_links( $args, $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- standard WP-CLI command signature.
		if ( $this->is_processor_enqueued() ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error(
				'A migration run is in progress in the background (WooCommerce → Status → Tools). Stop it before continuing.'
			);
			return;
		}

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::confirm( 'This will permanently remove legacy unsubscribe links. Continue?', $assoc_args );

		if ( ! $this->acquire_lock( 'disable-legacy-links' ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Could not acquire the migration CLI lock. Another CLI run may already be in progress.' );
			return;
		}

		try {
			global $wpdb;

			$core_meta_table = $wpdb->prefix . self::CORE_META_TABLE;

			// $core_meta_table is $wpdb->prefix-based, never user input.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare( "DELETE FROM {$core_meta_table} WHERE meta_key = %s", self::LEGACY_UNSUB_HASH_META_KEY );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

			delete_option( self::HAS_LEGACY_LINKS_OPTION );

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success(
				sprintf(
					'Removed %d legacy unsubscribe token(s) and cleared the legacy-links flag.',
					false === $deleted ? 0 : (int) $deleted
				)
			);
		} finally {
			$this->release_lock();
		}
	}

	/**
	 * Build the four migrators, sharing one Reporter and this instance's MigrationState.
	 *
	 * @param Reporter                   $reporter      Outcome collector shared across every section.
	 * @param bool                       $force         Whether `--force` was passed. Ignored by NotificationsMigrator,
	 *                                                  which has no merchant-editable fingerprint to overwrite.
	 * @param NotificationsMigrator|null $notifications The notifications migrator to use. Passed in by `run`, which
	 *                                                  also reads the known-losses totals off that same instance.
	 * @return array<string, MigratorInterface> Migrators keyed by section slug, in section order.
	 */
	private function build_migrators( Reporter $reporter, bool $force, ?NotificationsMigrator $notifications = null ): array {
		return array(
			'notifications' => $notifications ?? new NotificationsMigrator( $reporter ),
			'product-meta'  => new ProductMetaMigrator( $reporter, $this->state, $force ),
			'emails'        => new EmailSettingsMigrator( $this->state, $reporter, $force ),
			'settings'      => new SettingsMigrator( $this->state, $reporter, $force ),
		);
	}

	/**
	 * Resolve and validate the `--section` option, preserving the canonical section order
	 * regardless of the order requested.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return string[] Section slugs, in canonical order.
	 */
	private function resolve_sections( array $assoc_args ): array {
		if ( empty( $assoc_args['section'] ) ) {
			return self::SECTION_ORDER;
		}

		$requested = array_map( 'trim', explode( ',', (string) $assoc_args['section'] ) );
		$invalid   = array_diff( $requested, self::SECTION_ORDER );

		if ( ! empty( $invalid ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error(
				sprintf(
					'Unknown section(s): %1$s. Valid sections: %2$s.',
					implode( ', ', $invalid ),
					implode( ', ', self::SECTION_ORDER )
				)
			);
		}

		return array_values( array_intersect( self::SECTION_ORDER, $requested ) );
	}

	/**
	 * Whether the background batch processor is currently enqueued.
	 *
	 * @return bool
	 */
	private function is_processor_enqueued(): bool {
		return $this->batch_processing_controller->is_enqueued( MigrationBatchProcessor::class );
	}

	/**
	 * Acquire the CLI run lock for the duration of a command.
	 *
	 * @param string $context Short label for the command taking the lock, used only for
	 *                        reporting who holds it.
	 * @return bool True when acquired.
	 */
	private function acquire_lock( string $context ): bool {
		return $this->state->acquire_lock( sprintf( '%s (pid %d)', $context, getmypid() ) );
	}

	/**
	 * Release the CLI run lock, whoever holds it. Always called from a `finally` block so a
	 * fatal or uncaught exception mid-command cannot wedge it — the stale-lock reclaim in
	 * `MigrationState` is the backstop for the cases even that misses.
	 *
	 * @return void
	 */
	private function release_lock(): void {
		$this->state->release_lock();
	}

	/**
	 * Count legacy rows currently carrying the permanent-failure marker.
	 *
	 * @return int
	 */
	private function count_failed_rows(): int {
		global $wpdb;

		$table = $wpdb->prefix . self::LEGACY_META_TABLE;

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE meta_key = %s", self::LEGACY_FAILED_META_KEY );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Clear the permanent-failure marker from every legacy row that carries it, re-admitting
	 * those rows to the candidate set. Used by `--retry-failed` and by `rollback`.
	 *
	 * @return int Number of marker rows removed.
	 */
	private function clear_failed_markers(): int {
		global $wpdb;

		$table = $wpdb->prefix . self::LEGACY_META_TABLE;

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "DELETE FROM {$table} WHERE meta_key = %s", self::LEGACY_FAILED_META_KEY );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		return false === $result ? 0 : (int) $result;
	}

	/**
	 * Re-derive the expected status of every migrated Core notification from its legacy
	 * source and diff it against the status actually stored, paging through by notification
	 * id. Read-only.
	 *
	 * @return array{0: int, 1: int} Tuple of [rows checked, mismatches found].
	 */
	private function verify_notification_statuses(): array {
		$verified   = 0;
		$mismatched = 0;

		foreach ( $this->each_migrated_notification() as $row ) {
			list( $legacy_row, $legacy_meta ) = $this->fetch_legacy_source( (int) $row['legacy_id'] );

			if ( null === $legacy_row ) {
				continue;
				// Legacy row is gone; nothing to re-derive against.
			}

			++$verified;

			if ( StatusMapper::map( $legacy_row, $legacy_meta ) !== $row['status'] ) {
				++$mismatched;
			}
		}

		return array( $verified, $mismatched );
	}

	/**
	 * Roll back the migration's writes, one legacy-id marker at a time.
	 *
	 * An inserted row (no `_wc_bis_legacy_adopted` counterpart for that legacy id) is
	 * deleted outright when its status still equals what `StatusMapper` re-derives from its
	 * legacy source, paging through by marker id. An adopted row is never deleted: only that
	 * legacy id's markers are removed via `remove_adoption_markers()`. A row inserted by this
	 * migration and later also adopted by a duplicate legacy row carries both kinds of
	 * marker; once its insert marker triggers deletion, its other markers are skipped since
	 * the row itself is already gone.
	 *
	 * @param bool $dry_run When true, count what would happen without writing anything.
	 * @return array{0: int, 1: int, 2: int} Tuple of [rows deleted, rows with markers cleared, rows left untouched].
	 */
	private function rollback_notifications( bool $dry_run ): array {
		global $wpdb;

		$core_table      = $wpdb->prefix . self::CORE_NOTIFICATIONS_TABLE;
		$core_meta_table = $wpdb->prefix . self::CORE_META_TABLE;

		$deleted                  = 0;
		$markers_cleared          = 0;
		$skipped                  = 0;
		$deleted_notification_ids = array();

		foreach ( $this->each_legacy_id_marker() as $marker ) {
			$notification_id = (int) $marker['notification_id'];

			if ( isset( $deleted_notification_ids[ $notification_id ] ) ) {
				// Already deleted for its insert marker this run; its other markers went with it.
				continue;
			}

			list( $legacy_row, $legacy_meta ) = $this->fetch_legacy_source( (int) $marker['legacy_id'] );

			if ( null === $legacy_row ) {
				++$skipped;
				continue;
			}

			if ( ! empty( $marker['is_adopted'] ) ) {
				++$markers_cleared;

				if ( ! $dry_run ) {
					$this->remove_adoption_markers( $notification_id, (int) $marker['legacy_id'] );
				}

				continue;
			}

			if ( StatusMapper::map( $legacy_row, $legacy_meta ) !== $marker['status'] ) {
				++$skipped;
				continue;
			}

			++$deleted;
			$deleted_notification_ids[ $notification_id ] = true;

			if ( $dry_run ) {
				continue;
			}

			// Deletes meta first, matching Core's own delete() cascade order, so a failure
			// between the two statements never leaves an orphaned notification row without
			// its meta already gone.
			$wpdb->delete( $core_meta_table, array( 'notification_id' => $notification_id ), array( '%d' ) );
			$wpdb->delete( $core_table, array( 'id' => $notification_id ), array( '%d' ) );
		}

		return array( $deleted, $markers_cleared, $skipped );
	}

	/**
	 * Remove only the markers this migration added for one adopted legacy id, leaving the
	 * Core notification itself, and every marker belonging to any other legacy id, untouched.
	 *
	 * @param int $notification_id Core notification id the marker was written onto.
	 * @param int $legacy_id       Legacy notification id whose markers are being removed.
	 * @return void
	 */
	private function remove_adoption_markers( int $notification_id, int $legacy_id ): void {
		global $wpdb;

		$core_meta_table = $wpdb->prefix . self::CORE_META_TABLE;

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$wpdb->delete(
			$core_meta_table,
			array(
				'notification_id' => $notification_id,
				'meta_key'        => self::LEGACY_ID_META_KEY,
				'meta_value'      => (string) $legacy_id,
			),
			array( '%d', '%s', '%s' )
		);

		$wpdb->delete(
			$core_meta_table,
			array(
				'notification_id' => $notification_id,
				'meta_key'        => self::ADOPTED_MARKER_META_KEY,
				'meta_value'      => (string) $legacy_id,
			),
			array( '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		// $core_meta_table is $wpdb->prefix-based, never user input; the LIKE prefix is built
		// with $wpdb->esc_like() and bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"DELETE FROM {$core_meta_table} WHERE notification_id = %d AND meta_key = %s AND meta_value LIKE %s",
			$notification_id,
			self::LEGACY_UNSUB_HASH_META_KEY,
			$wpdb->esc_like( $legacy_id . ':' ) . '%'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Yield every Core notification carrying a `_wc_bis_legacy_id` marker, one row per
	 * notification id with its lowest associated legacy id, paging by keyset on id.
	 *
	 * The lowest legacy id is used as "the" legacy source for a row that was inserted by the
	 * migration; a row later adopted by additional legacy rows accumulates further markers
	 * that this deliberately ignores, since adoption never reconciled status onto the target
	 * in the first place.
	 *
	 * @return \Generator<array{id: string, status: string, legacy_id: string}>
	 */
	private function each_migrated_notification(): \Generator {
		global $wpdb;

		$core_table      = $wpdb->prefix . self::CORE_NOTIFICATIONS_TABLE;
		$core_meta_table = $wpdb->prefix . self::CORE_META_TABLE;

		$cursor = 0;

		do {
			// Table names are $wpdb->prefix-based, never user input; the meta key and the
			// cursor/limit bounds are passed through $wpdb->prepare() below.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare(
				"SELECT n.id, n.status, MIN( CAST( m.meta_value AS UNSIGNED ) ) AS legacy_id
				FROM {$core_table} n
				INNER JOIN {$core_meta_table} m ON m.notification_id = n.id AND m.meta_key = %s
				WHERE n.id > %d
				GROUP BY n.id, n.status
				ORDER BY n.id ASC
				LIMIT %d",
				self::LEGACY_ID_META_KEY,
				$cursor,
				self::ORACLE_BATCH_SIZE
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			$rows       = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
			$rows_count = count( $rows );

			foreach ( $rows as $row ) {
				$cursor = max( $cursor, (int) $row['id'] );
				yield $row;
			}
		} while ( self::ORACLE_BATCH_SIZE === $rows_count );
	}

	/**
	 * Yield every `_wc_bis_legacy_id` marker on a Core notification, one row per legacy id,
	 * paging by keyset on the marker's own meta id. Used only by `rollback`, which needs
	 * every legacy id a row carries - unlike `each_migrated_notification()`, which collapses
	 * a row to a single representative legacy id for `verify`.
	 *
	 * `is_adopted` is true when a `_wc_bis_legacy_adopted` marker with the same legacy id
	 * exists on the same notification: that legacy id adopted a pre-existing row rather than
	 * having been inserted by this migration.
	 *
	 * @return \Generator<array{notification_id: string, status: string, legacy_id: string, is_adopted: string|int}>
	 */
	private function each_legacy_id_marker(): \Generator {
		global $wpdb;

		$core_table      = $wpdb->prefix . self::CORE_NOTIFICATIONS_TABLE;
		$core_meta_table = $wpdb->prefix . self::CORE_META_TABLE;

		$cursor = 0;

		do {
			// Table names are $wpdb->prefix-based, never user input; the meta keys and the
			// cursor/limit bounds are passed through $wpdb->prepare() below.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare(
				"SELECT lm.id AS marker_id, n.id AS notification_id, n.status,
					CAST( lm.meta_value AS UNSIGNED ) AS legacy_id,
					( am.id IS NOT NULL ) AS is_adopted
				FROM {$core_meta_table} lm
				INNER JOIN {$core_table} n ON n.id = lm.notification_id
				LEFT JOIN {$core_meta_table} am
				       ON am.notification_id = lm.notification_id
				      AND am.meta_key = %s
				      AND am.meta_value = lm.meta_value
				WHERE lm.meta_key = %s AND lm.id > %d
				ORDER BY lm.id ASC
				LIMIT %d",
				self::ADOPTED_MARKER_META_KEY,
				self::LEGACY_ID_META_KEY,
				$cursor,
				self::ORACLE_BATCH_SIZE
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			$rows       = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
			$rows_count = count( $rows );

			foreach ( $rows as $row ) {
				$cursor = max( $cursor, (int) $row['marker_id'] );
				yield $row;
			}
		} while ( self::ORACLE_BATCH_SIZE === $rows_count );
	}

	/**
	 * Fetch one legacy row and its meta bag, in the shape `StatusMapper::map()` expects.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return array{0: array<string,mixed>|null, 1: array<string,mixed>} Tuple of
	 *                                                                    [legacy row or null, legacy meta bag].
	 */
	private function fetch_legacy_source( int $legacy_id ): array {
		global $wpdb;

		$table      = $wpdb->prefix . self::LEGACY_NOTIFICATIONS_TABLE;
		$meta_table = $wpdb->prefix . self::LEGACY_META_TABLE;

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$legacy_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $legacy_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $legacy_row ) {
			return array( null, array() );
		}

		// $meta_table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT meta_key, meta_value FROM {$meta_table} WHERE bis_notifications_id = %d", $legacy_id );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$meta_rows = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		$legacy_meta = array();
		foreach ( $meta_rows as $meta_row ) {
			$legacy_meta[ $meta_row['meta_key'] ] = maybe_unserialize( $meta_row['meta_value'] );
		}

		return array( $legacy_row, $legacy_meta );
	}

	/**
	 * Print the final section × outcome × count table.
	 *
	 * @param Reporter $reporter Outcome collector for the command that just ran.
	 * @return void
	 */
	private function print_report( Reporter $reporter ): void {
		$table = $reporter->get_table();

		if ( empty( $table ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( 'Nothing to migrate.' );
			return;
		}

		// @phpstan-ignore-next-line function.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI\Utils\format_items( 'table', $table, array( 'section', 'outcome', 'count' ) );
	}

	/**
	 * Print the known-losses summary from the counts cached at run start.
	 *
	 * Reads the cache rather than counting, so `status` stays cheap; a run that has not
	 * happened yet says so instead of reporting zeroes as if they were measured.
	 *
	 * @param Reporter $reporter Used to turn the cached counts into merchant-facing lines.
	 * @return void
	 */
	private function print_known_losses( Reporter $reporter ): void {
		$cached = $this->state->get_losses();

		if ( null === $cached ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( 'Skipped and lost populations: not yet counted; they are counted when a run starts.' );
			return;
		}

		$lines = $reporter->summarize_cached_losses( is_array( $cached['values'] ?? null ) ? $cached['values'] : array() );

		if ( empty( $lines ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( 'Skipped and lost populations: none (as of %s).', $this->format_site_time( (int) $cached['at'] ) ) );
			return;
		}

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log( sprintf( 'Skipped and lost populations (as of %s):', $this->format_site_time( (int) $cached['at'] ) ) );

		foreach ( $lines as $line ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( '  %s', $line ) );
		}
	}

	/**
	 * Format a UTC timestamp in site-local time, matching what merchants read in the legacy
	 * admin — a plain UTC value here would read as the migration having shifted their dates.
	 *
	 * @param int $timestamp Unix timestamp (UTC).
	 * @return string
	 */
	private function format_site_time( int $timestamp ): string {
		if ( 0 === $timestamp ) {
			return '';
		}

		$formatted = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );

		return false === $formatted ? '' : $formatted;
	}
}
