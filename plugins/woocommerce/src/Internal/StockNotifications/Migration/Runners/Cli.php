<?php
/**
 * Cli class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\CancellationSourceMiner;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusTransitions;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\SettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Tables;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\NullWriter;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * `wp wc bis-migrate` — the CLI entry point for the Back In Stock Notifications migration.
 *
 * Registers only on a store that has the Customer stock notifications feature on and once had
 * the legacy extension installed. Anywhere else the command does not exist, so an unrelated
 * `wp` invocation costs two autoloaded option reads and nothing more.
 *
 * `run` pumps `MigrationBatchProcessor`, the same class Action Scheduler drives, so the
 * section order, cursors and pass-reset probe have one implementation. The CLI-only knobs
 * (`--dry-run`, `--force`, `--section`, `--batch-size`) reach it through
 * `configure_run()`, and `--retry-failed` and `--max-batches` stay here, on either side of
 * the loop. Both entry points share the same state — `MigrationState` cursors/counts and
 * the markers the migrators write — so a run started from either one is resumed correctly
 * by the other.
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
	 * there is nothing to migrate, and the command is not registered at all.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = Constants::DB_VERSION_OPTION;

	/**
	 * Autoloaded flag gating the legacy unsubscribe shim's registration. Only set when a
	 * migrated row carries a legacy unsubscribe token.
	 *
	 * @var string
	 */
	private const HAS_LEGACY_LINKS_OPTION = Constants::HAS_LEGACY_LINKS_OPTION;

	/**
	 * Legacy meta key recording a permanent per-row failure. Cleared by `--retry-failed`.
	 *
	 * @var string
	 */
	private const LEGACY_FAILED_META_KEY = Constants::LEGACY_FAILED_META_KEY;

	/**
	 * Product meta key marking a product the product-meta section can never settle.
	 * Cleared by `--retry-failed`, matching LEGACY_FAILED_META_KEY for notifications.
	 *
	 * @var string
	 */
	private const PRODUCT_META_FAILED_KEY = Constants::PRODUCT_FAILED_META_KEY;

	/**
	 * Migration marker recording a successful migration onto a Core notification.
	 *
	 * @var string
	 */
	private const LEGACY_ID_META_KEY = Constants::LEGACY_ID_META_KEY;

	/**
	 * Meta key holding the precomputed legacy unsubscribe token digest.
	 *
	 * @var string
	 */
	private const LEGACY_UNSUB_HASH_META_KEY = Constants::LEGACY_UNSUB_HASH_META_KEY;

	/**
	 * Meta key holding the precomputed legacy verification token digest and its expiry.
	 *
	 * @var string
	 */
	private const LEGACY_VERIFY_HASH_META_KEY = Constants::LEGACY_VERIFY_HASH_META_KEY;

	/**
	 * Default batch size for a CLI run. Raised by `--batch-size`; scheduled runs use their
	 * own, smaller default from `get_default_batch_size()`.
	 *
	 * @var int
	 */
	private const DEFAULT_BATCH_SIZE = 500;

	/**
	 * Row count used to page through Core notifications in `verify`.
	 *
	 * @var int
	 */
	private const ORACLE_BATCH_SIZE = 500;

	/**
	 * Verifies whether a run may start or continue. Resolved on first use; see `state()`.
	 *
	 * @var Requirements|null
	 */
	private ?Requirements $requirements = null;

	/**
	 * Used only to read whether the background processor is currently enqueued. Resolved on
	 * first use; see `state()`.
	 *
	 * @var BatchProcessingController|null
	 */
	private ?BatchProcessingController $batch_processing_controller = null;

	/**
	 * Live writer, built on first use; see `db_writer()`.
	 *
	 * @var DbWriter|null
	 */
	private ?DbWriter $db_writer = null;

	/**
	 * Migration run state: the CLI lock, per-section cursors and cached counts. Built on
	 * first use; see `state()`.
	 *
	 * @var MigrationState|null
	 */
	private ?MigrationState $state = null;

	/**
	 * Migration run state, built on first use.
	 *
	 * Every dependency here is resolved lazily rather than in a constructor. The command is
	 * registered on `after_wp_load`, which fires for every `wp` invocation that boots
	 * WordPress, so building these up front would cost three container resolutions on
	 * `wp plugin list` and every other unrelated command.
	 *
	 * @return MigrationState
	 */
	private function state(): MigrationState {
		return $this->state ??= new MigrationState();
	}

	/**
	 * Requirements checker, resolved on first use.
	 *
	 * @return Requirements
	 */
	private function requirements(): Requirements {
		return $this->requirements ??= wc_get_container()->get( Requirements::class );
	}

	/**
	 * Batch processing controller, resolved on first use.
	 *
	 * @return BatchProcessingController
	 */
	private function batch_processing_controller(): BatchProcessingController {
		return $this->batch_processing_controller ??= wc_get_container()->get( BatchProcessingController::class );
	}

	/**
	 * Live writer, resolved on first use.
	 *
	 * Comes from the container rather than `new`: `DbWriter` takes its bulk insert engine
	 * through `init()`.
	 *
	 * @return DbWriter
	 */
	private function db_writer(): DbWriter {
		return $this->db_writer ??= wc_get_container()->get( DbWriter::class );
	}

	/**
	 * Register the `wp wc bis-migrate` command and its subcommands.
	 *
	 * Runs on `after_wp_load`, which fires for every `wp` invocation that boots WordPress, so
	 * it stops after two autoloaded option reads on a store with nothing to migrate, and the
	 * command object is only built once those reads pass.
	 *
	 * Needs the Customer stock notifications feature on, since with it off there is nothing to
	 * migrate into, and needs the legacy extension to have been installed, since otherwise
	 * there is nothing to migrate from. The feature option is read directly rather than
	 * through `FeaturesUtil::feature_is_enabled()`, which builds translated feature
	 * definitions before `init` has loaded translations.
	 *
	 * Static on purpose: `WP_CLI::add_command()` turns every public non-static method of the
	 * command object into a subcommand, so as an instance method this would show up as
	 * `wp wc bis-migrate register` alongside the real ones.
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		if ( 'yes' !== get_option( StockNotifications::ENABLE_OPTION_NAME, 'no' ) ) {
			return;
		}

		if ( ! get_option( self::DB_VERSION_OPTION ) ) {
			return;
		}

		// @phpstan-ignore-next-line -- WP_CLI is only defined in a CLI context.
		WP_CLI::add_command( 'wc bis-migrate', wc_get_container()->get( self::class ) );
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
		$check = $this->requirements()->check();

		if ( is_wp_error( $check ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
		}

		$reporter = new Reporter();

		foreach ( self::SECTION_ORDER as $slug ) {
			$cached = $this->state()->get_count( $slug );

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

		$lock = $this->state()->get_lock();

		if ( $this->state()->is_lock_held() && null !== $lock ) {
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
	 * : CLI only, requires --yes. Overrides the is_queued='on' pre-flight refusal, so a run
	 * can start while the legacy extension still has rows queued for its own sender. Does not
	 * skip Requirements::check() and does not change any status mapping.
	 *
	 * [--retry-failed]
	 * : Clear the permanent-failure marker on legacy rows so they are retried. Ignored under
	 * --dry-run.
	 *
	 * [--max-batches=<n>]
	 * : Stop after this many batches, across all sections. Debugging aid only.
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

		$check = $this->requirements()->check();

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
			$queued = $this->requirements()->count_legacy_queued_rows();

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
			if ( $retry_failed && ! $dry_run ) {
				$cleared = 0;

				if ( in_array( 'notifications', $sections, true ) ) {
					$cleared += $this->clear_failed_markers();
				}

				if ( in_array( 'product-meta', $sections, true ) ) {
					$cleared += $this->clear_product_meta_failure_markers();
				}

				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::log( sprintf( 'Cleared the failed marker on %d row(s); they will be retried.', $cleared ) );
			}

			// A run always starts from zero: cursors left behind by a previous run cannot be
			// trusted (a row deleted in Core mid-run re-enters the candidate set below them).
			// `MigrationBatchProcessor` only ever advances cursors, never resets them, so this
			// invariant is enforced only at run-start entry points — this one and the Tools
			// start callback — not inside the processor itself.
			$this->state()->reset_all_cursors();

			$reporter               = new Reporter();
			$notifications_migrator = new NotificationsMigrator( $reporter );
			$migrators              = array_intersect_key( $this->build_migrators( $reporter, $notifications_migrator ), array_flip( $sections ) );
			$writer                 = $dry_run ? new NullWriter() : $this->db_writer();

			// The loop itself - section order, cursors, the pass-reset probe, the per-batch
			// requirement check and lock refresh - belongs to MigrationBatchProcessor. The CLI
			// hands it the knobs the BatchProcessorInterface contract has no room for and then
			// pumps it, so both entry points run the same state machine.
			$processor = new MigrationBatchProcessor();
			$processor->init( $this->requirements(), $this->db_writer() );
			$processor->configure_run( $migrators, $writer, $batch_size );

			// Counts are cached, display-only, and refreshed at run start and on section
			// drain — never computed live outside a run.
			$total_estimate = 0;
			foreach ( $sections as $slug ) {
				$remaining = $migrators[ $slug ]->count_remaining();
				$this->state()->set_count( $slug, $remaining );
				$total_estimate += $remaining;
			}

			if ( in_array( 'notifications', $sections, true ) ) {
				// One COUNT(*) per skipped population, run once here and cached: `status` and
				// the Tools description report these from the cache and never compute them.
				$this->state()->set_losses( $reporter->collect_known_losses( $notifications_migrator ) );
			}

			// @phpstan-ignore-next-line function.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			$progress    = WP_CLI\Utils\make_progress_bar( 'BIS migration', max( 1, $total_estimate ) );
			$batches_run = 0;

			while ( null === $max_batches || $batches_run < $max_batches ) {
				$batch = $processor->get_next_batch_to_process( $batch_size );

				if ( empty( $batch ) ) {
					break;
				}

				$processor->process_batch( $batch );
				$progress->tick( count( $batch ) );
				++$batches_run;
			}

			$progress->finish();

			$check = $this->requirements()->check();

			if ( is_wp_error( $check ) ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::warning( sprintf( 'Stopping: %s', $check->get_error_message() ) );
			} elseif ( null !== $max_batches && $batches_run >= $max_batches ) {
				// --max-batches stopped the run before any section drained, and draining is
				// where the processor refreshes a cached count, so refresh them here instead.
				foreach ( $sections as $slug ) {
					$this->state()->set_count( $slug, $migrators[ $slug ]->count_remaining() );
				}
			}

			if ( in_array( 'notifications', $sections, true ) ) {
				$cached = $this->state()->get_losses();
				$values = is_array( $cached['values'] ?? null ) ? $cached['values'] : array();
				$this->state()->set_losses( $reporter->with_run_losses( $values, $notifications_migrator ) );
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
		$check = $this->requirements()->check();

		if ( is_wp_error( $check ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success( sprintf( 'Nothing to migrate: %s', $check->get_error_message() ) );
			return;
		}

		$reporter  = new Reporter();
		$migrators = $this->build_migrators( $reporter );

		foreach ( self::SECTION_ORDER as $slug ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( sprintf( '%-14s %d row(s) still outstanding.', $slug, $migrators[ $slug ]->count_remaining() ) );
		}

		list( $verified, $mismatched, $drifted ) = $this->verify_notification_statuses();

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log(
			sprintf(
				'%-14s %d migrated row(s) checked against their legacy source; %d status mismatch(es).',
				'notifications',
				$verified,
				$mismatched
			)
		);

		if ( $drifted > 0 ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log(
				sprintf(
					'%-14s %d row(s) have moved on since the migration ran (verified, cancelled or notified). Not a mismatch.',
					'notifications',
					$drifted
				)
			);
		}

		if ( $mismatched > 0 ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Verification found status mismatches. See above.' );
			return;
		}

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::success( 'Verified: current state matches what the mappers derive from the legacy source.' );
	}

	/**
	 * Remove the legacy link shim's remaining footprint: both per-notification token
	 * digests — unsubscribe and verification — and the flag that guards the shim's
	 * registration. `_wc_bis_legacy_id` survives: it is the idempotency marker, not
	 * shim-specific.
	 *
	 * Both kinds go together on purpose. A partial teardown would leave the shim registered
	 * for one link kind and silently dead for the other.
	 *
	 * Writes by direct SQL, like `BulkNotificationWriter`: going through the CRUD layer
	 * would bump `date_modified_gmt` on every migrated row, rewriting merchant-visible
	 * history for what is only a marker cleanup.
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
		WP_CLI::confirm( 'This will permanently remove legacy unsubscribe and verification links. Continue?', $assoc_args );

		if ( ! $this->acquire_lock( 'disable-legacy-links' ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Could not acquire the migration CLI lock. Another CLI run may already be in progress.' );
			return;
		}

		try {
			global $wpdb;

			$core_meta_table = Tables::core_meta();

			// $core_meta_table is $wpdb->prefix-based, never user input.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare(
				"DELETE FROM {$core_meta_table} WHERE meta_key IN ( %s, %s )",
				self::LEGACY_UNSUB_HASH_META_KEY,
				self::LEGACY_VERIFY_HASH_META_KEY
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

			delete_option( self::HAS_LEGACY_LINKS_OPTION );

			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::success(
				sprintf(
					'Removed %d legacy link token(s) and cleared the legacy-links flag.',
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
	 * @param NotificationsMigrator|null $notifications The notifications migrator to use. Passed in by `run`, which
	 *                                                  also reads the known-losses totals off that same instance.
	 * @return array<string, MigratorInterface> Migrators keyed by section slug, in section order.
	 */
	private function build_migrators( Reporter $reporter, ?NotificationsMigrator $notifications = null ): array {
		return array(
			'notifications' => $notifications ?? new NotificationsMigrator( $reporter ),
			'product-meta'  => new ProductMetaMigrator( $reporter ),
			'emails'        => new EmailSettingsMigrator( $this->state(), $reporter ),
			'settings'      => new SettingsMigrator( $this->state(), $reporter ),
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
		return $this->batch_processing_controller()->is_enqueued( MigrationBatchProcessor::class );
	}

	/**
	 * Acquire the CLI run lock for the duration of a command.
	 *
	 * @param string $context Short label for the command taking the lock, used only for
	 *                        reporting who holds it.
	 * @return bool True when acquired.
	 */
	private function acquire_lock( string $context ): bool {
		return $this->state()->acquire_lock( sprintf( '%s (pid %d)', $context, getmypid() ) );
	}

	/**
	 * Release the CLI run lock, whoever holds it. Always called from a `finally` block so a
	 * fatal or uncaught exception mid-command cannot wedge it — the stale-lock reclaim in
	 * `MigrationState` is the backstop for the cases even that misses.
	 *
	 * @return void
	 */
	private function release_lock(): void {
		$this->state()->release_lock();
	}

	/**
	 * Count legacy rows currently carrying the permanent-failure marker.
	 *
	 * @return int
	 */
	private function count_failed_rows(): int {
		global $wpdb;

		$table = Tables::legacy_meta();

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE meta_key = %s", self::LEGACY_FAILED_META_KEY );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Clear the permanent-failure marker from every legacy row that carries it, re-admitting
	 * those rows to the candidate set. Used by `--retry-failed`.
	 *
	 * @return int Number of marker rows removed.
	 */
	private function clear_failed_markers(): int {
		global $wpdb;

		$table = Tables::legacy_meta();

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "DELETE FROM {$table} WHERE meta_key = %s", self::LEGACY_FAILED_META_KEY );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		return false === $result ? 0 : (int) $result;
	}

	/**
	 * Clear the product-meta section's permanent-failure marker from every product that
	 * carries it, re-admitting those products to its candidate set.
	 *
	 * @return int Number of marker rows removed.
	 */
	private function clear_product_meta_failure_markers(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$result = $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => self::PRODUCT_META_FAILED_KEY ), array( '%s' ) );

		return false === $result ? 0 : (int) $result;
	}

	/**
	 * Re-derive the expected status of every migrated Core notification from its legacy
	 * source and diff it against the status actually stored, paging through by notification
	 * id. Read-only.
	 *
	 * A row whose stored status is reachable from the derived one is counted as drift rather
	 * than a mismatch: the shopper verified, cancelled or was notified after the run, and the
	 * legacy source cannot know that. Only an unreachable difference is a real disagreement.
	 * Timestamps cannot tell the two apart — `date_modified_gmt` is the run time for every
	 * migrated row, so there is no per-row baseline to compare against.
	 *
	 * @return array{0: int, 1: int, 2: int} Tuple of [rows checked, mismatches found, rows that moved on].
	 */
	private function verify_notification_statuses(): array {
		$verified   = 0;
		$mismatched = 0;
		$drifted    = 0;
		$miner      = new CancellationSourceMiner();

		foreach ( $this->each_migrated_notification_page() as $page ) {
			$sources = $this->fetch_legacy_sources( array_column( $page, 'legacy_id' ) );

			// One query per page, matching what the write path mined per batch.
			$cancellations = $miner->mine( array_values( $sources ) );

			foreach ( $page as $row ) {
				$legacy_id = (int) $row['legacy_id'];
				$source    = $sources[ $legacy_id ] ?? null;

				if ( null === $source ) {
					// Legacy row is gone; nothing to re-derive against.
					continue;
				}

				++$verified;

				$derived = StatusMapper::map( $source, $cancellations[ $legacy_id ] ?? null );

				if ( $derived === $row['status'] ) {
					continue;
				}

				if ( StatusTransitions::is_forward( $derived, (string) $row['status'] ) ) {
					++$drifted;
					continue;
				}

				++$mismatched;
			}
		}

		return array( $verified, $mismatched, $drifted );
	}

	/**
	 * Yield every Core notification carrying a `_wc_bis_legacy_id` marker, one row per
	 * notification id with its lowest associated legacy id, a page at a time, paging by
	 * keyset on id. Pages rather than rows, so the caller can fetch a whole page's legacy
	 * sources in one round trip instead of two queries per row.
	 *
	 * The lowest legacy id is used as "the" legacy source for a row that was inserted by the
	 * migration; a row later adopted by additional legacy rows accumulates further markers
	 * that this deliberately ignores, since adoption never reconciled status onto the target
	 * in the first place.
	 *
	 * @return \Generator<array<int, array{id: string, status: string, legacy_id: string}>>
	 */
	private function each_migrated_notification_page(): \Generator {
		global $wpdb;

		$core_table      = Tables::core_notifications();
		$core_meta_table = Tables::core_meta();

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
			}

			if ( ! empty( $rows ) ) {
				yield $rows;
			}
		} while ( self::ORACLE_BATCH_SIZE === $rows_count );
	}

	/**
	 * Fetch a page of legacy rows in one query, regardless of how many ids are asked for.
	 *
	 * A legacy id with no row left in the legacy table is absent from the result, which is
	 * how the caller recognises a source that is gone. No legacy meta is read: `StatusMapper`
	 * derives status from the columns and the activity log alone.
	 *
	 * @param array $legacy_ids Legacy notification ids.
	 * @return array<int, array<string,mixed>> Legacy rows, keyed by legacy id.
	 */
	private function fetch_legacy_sources( array $legacy_ids ): array {
		global $wpdb;

		$legacy_ids = array_values( array_unique( array_map( 'intval', $legacy_ids ) ) );

		if ( empty( $legacy_ids ) ) {
			return array();
		}

		$table        = Tables::legacy_notifications();
		$placeholders = implode( ', ', array_fill( 0, count( $legacy_ids ), '%d' ) );

		// Table names are $wpdb->prefix-based, never user input; every id is bound below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id IN ( {$placeholders} )", $legacy_ids ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$sources = array();

		foreach ( $rows as $row ) {
			$sources[ (int) $row['id'] ] = $row;
		}

		return $sources;
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
		$cached = $this->state()->get_losses();

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
