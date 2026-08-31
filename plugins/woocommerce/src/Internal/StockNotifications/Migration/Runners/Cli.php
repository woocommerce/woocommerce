<?php
/**
 * Cli class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationRun;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
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
 * section order and cursors have one implementation. The CLI-only knobs
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
	private const SECTION_ORDER = Constants::SECTION_ORDER;

	/**
	 * Option recording that Back In Stock Notifications was ever installed. Absent means
	 * there is nothing to migrate, and the command is not registered at all.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = Constants::DB_VERSION_OPTION;

	/**
	 * Product meta key marking a product the product-meta section can never settle.
	 * Cleared by `--retry-failed`.
	 *
	 * @var string
	 */
	private const PRODUCT_META_FAILED_KEY = Constants::PRODUCT_FAILED_META_KEY;

	/**
	 * Default batch size for a CLI run. Raised by `--batch-size`; scheduled runs use their
	 * own, smaller default from `get_default_batch_size()`.
	 *
	 * @var int
	 */
	private const DEFAULT_BATCH_SIZE = 500;

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
	 * the facts that replace it: per-section cached counts with their timestamp, whether the
	 * settings have landed, whether a background run is enqueued, whether the CLI lock is
	 * held, and how many rows carry the permanent-failure marker.
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

		$run      = new MigrationRun();
		$reporter = $run->get_reporter();

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

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log(
			sprintf(
				'%-14s %s',
				'settings',
				$run->get_options_migrator()->is_done() ? 'imported' : 'not imported yet'
			)
		);

		$this->print_known_losses( $reporter );
		$this->print_parked_sections();

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

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::log( sprintf( 'Rows marked permanently failed: %d (product-meta)', $this->count_failed_products() ) );

		// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
		WP_CLI::success( 'Status reported.' );
	}

	/**
	 * Run the migration.
	 *
	 * Drives the requested sections, in their fixed order (never reordered by `--section`),
	 * each from the cursor the previous run left behind. A
	 * section is drained once its cursor reaches the end of what it has to visit.
	 *
	 * Settings migrate on every run, whatever `--section` asked for: they are a fixed set of
	 * values with nothing to scan, so restricting them to a section of their own would only
	 * let a run finish with Core reading settings that never moved.
	 *
	 * Cursors persist across runs, so a second run over a settled store costs almost nothing.
	 * `--force` and `--retry-failed` reset them, since both put rows back into play below
	 * wherever the cursor stands.
	 *
	 * `--max-batches` bounds this CLI loop only, and is a debugging aid rather than a
	 * throughput mode; repeated small invocations now resume where the last one stopped.
	 *
	 * ## OPTIONS
	 *
	 * [--section=<sections>]
	 * : Comma-separated sections to run. Defaults to every section.
	 *
	 * [--batch-size=<size>]
	 * : Maximum rows fetched per batch. Default 500.
	 *
	 * [--dry-run]
	 * : Compute and report everything without writing anything.
	 *
	 * [--force]
	 * : CLI only, requires --yes. Resets every cursor and unparks every section, so a run
	 * revisits rows it has already been past. Does not skip Requirements::check() and does
	 * not change any status mapping.
	 *
	 * [--retry-failed]
	 * : Clear the permanent-failure marker on failed rows so they are retried. Ignored under
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
	 *     wp wc bis-migrate run --section=product-meta --dry-run
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

		$run          = new MigrationRun();
		$sections     = $this->resolve_sections( $run, $assoc_args );
		$dry_run      = isset( $assoc_args['dry-run'] );
		$retry_failed = isset( $assoc_args['retry-failed'] );
		$batch_size   = isset( $assoc_args['batch-size'] ) ? max( 1, (int) $assoc_args['batch-size'] ) : self::DEFAULT_BATCH_SIZE;
		$max_batches  = isset( $assoc_args['max-batches'] ) ? max( 1, (int) $assoc_args['max-batches'] ) : null;

		if ( ! $dry_run ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::confirm( 'This will write to the database. Continue?', $assoc_args );
		}

		if ( ! $this->acquire_lock( 'run' ) ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::error( 'Could not acquire the migration CLI lock. Another CLI run may already be in progress.' );
			return;
		}

		// A dry run keeps its cursors and counts to itself, so a rehearsal never moves the
		// point a later live run starts from. The processor is handed this same instance
		// below, or a `--dry-run --force` would reset a cursor the batch loop never reads.
		// The lock is still the real one, taken above.
		$run_state = $run->get_state( $dry_run );

		// Set instead of halting inside the try: see the has_errors() branch below.
		$halt_code = null;

		try {
			if ( $retry_failed && ! $dry_run ) {
				$cleared = 0;

				if ( in_array( 'product-meta', $sections, true ) ) {
					$cleared += $this->clear_product_meta_failure_markers();
				}

				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::log( sprintf( 'Cleared the failed marker on %d row(s); they will be retried.', $cleared ) );
			}

			// Cursors are kept between runs: a scan that re-walks the whole legacy table on a
			// settled store is the migration's most expensive thing to repeat, and the
			// per-batch already-migrated lookup means a stale cursor costs a re-scan rather
			// than correctness. `--force` and `--retry-failed` are the two flags that put rows
			// back into play below the cursor, so they reset it; `MigrationBatchProcessor`
			// itself only ever advances one.
			if ( $force || $retry_failed ) {
				$run_state->reset_all_cursors();
				// Both flags put rows back in play, which is the only thing a parked section
				// was waiting for: whatever stopped its rows settling may since have been fixed.
				$run_state->unpark_all();
			}

			$reporter  = $run->get_reporter();
			$migrators = array_intersect_key( $run->build_migrators( $dry_run ), array_flip( $sections ) );
			$writer    = $run->build_writer( $dry_run );

			// Settings are not a section: the processor writes them on every batch, whatever
			// `--section` asked for, since there is nothing about them to scan or restrict.
			$options = $run->get_options_migrator();

			// The loop itself - section order, cursors, the per-batch
			// requirement check and lock refresh - belongs to MigrationBatchProcessor. The CLI
			// hands it the knobs the BatchProcessorInterface contract has no room for and then
			// pumps it, so both entry points run the same state machine.
			$processor = new MigrationBatchProcessor();
			$processor->init( $this->requirements(), $run->build_writer( false ) );
			$processor->configure_run( $migrators, $writer, $batch_size, $reporter, $options, $run_state );

			// Counts are cached, display-only, and refreshed at run start and on section
			// drain — never computed live outside a run.
			$total_estimate = 0;
			foreach ( $sections as $slug ) {
				$remaining = $migrators[ $slug ]->count_remaining( $run_state->get_cursor( $slug ) );
				$run_state->set_count( $slug, $remaining );
				$total_estimate += $remaining;
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
					$run_state->set_count( $slug, $migrators[ $slug ]->count_remaining( $run_state->get_cursor( $slug ) ) );
				}
			}

			// Known losses are added to the run's running total by the processor as each batch
			// lands, so there is nothing to snapshot here: a total taken at the end of one
			// command would drop what earlier runs of the same migration already found.

			$this->print_report( $reporter );
			$this->print_known_losses( $reporter );

			if ( $reporter->has_errors() ) {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::warning( 'Run finished with error-severity outcomes. See the table above.' );

				// Halting here would exit(), and PHP skips `finally` on exit, so the lock
				// would stay held until it went stale. A single permanently-failed row sets
				// has_errors(), so this is an ordinary outcome, not a catastrophe. Record the
				// code and halt below, once the lock is back.
				$halt_code = 1;
			} else {
				// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
				WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Run complete.' );
			}
		} finally {
			$this->release_lock();
		}

		if ( null !== $halt_code ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::halt( $halt_code );
		}
	}

	/**
	 * Resolve and validate the `--section` option, refusing a slug that names no section.
	 *
	 * @param MigrationRun $run        The run whose sections these are.
	 * @param array        $assoc_args Associative arguments.
	 * @return string[] Section slugs, in canonical order.
	 */
	private function resolve_sections( MigrationRun $run, array $assoc_args ): array {
		$requested = empty( $assoc_args['section'] )
			? array()
			: array_map( 'trim', explode( ',', (string) $assoc_args['section'] ) );

		$invalid = $run->unknown_sections( $requested );

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

		return $run->resolve_sections( $requested );
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
	 * Print the sections the run has stopped serving, if any.
	 *
	 * A parked section is why a run can look idle with work still outstanding, so status has
	 * to name it rather than leave the count unexplained.
	 *
	 * @return void
	 */
	private function print_parked_sections(): void {
		foreach ( $this->state()->get_parked_sections() as $slug => $parked ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::warning(
				sprintf(
					'%s is parked since %s: %s Run with --retry-failed to put it back in play.',
					$slug,
					$this->format_site_time( (int) ( $parked['at'] ?? 0 ) ),
					(string) ( $parked['reason'] ?? '' )
				)
			);
		}
	}

	/**
	 * Count products currently carrying the product-meta section's permanent-failure marker.
	 *
	 * Counted separately from the legacy rows: `--retry-failed` clears both marker sets, so
	 * both belong on the status surface that says how much a retry would put back in play.
	 *
	 * @return int
	 */
	private function count_failed_products(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", self::PRODUCT_META_FAILED_KEY );

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- $sql was built with $wpdb->prepare() above.
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
	 * Print the known-losses summary from the counts the last run accumulated.
	 *
	 * Reads the cache rather than counting, so `status` stays cheap; a run that has not
	 * happened yet says so instead of reporting zeroes as if they were measured. The counts
	 * describe the rows a run has walked, so they are complete only once one has finished.
	 *
	 * @param Reporter $reporter Used to turn the cached counts into merchant-facing lines.
	 * @return void
	 */
	private function print_known_losses( Reporter $reporter ): void {
		$cached = $this->state()->get_losses();

		if ( null === $cached ) {
			// @phpstan-ignore-next-line class.notFound -- WP_CLI is not resolvable to PHPStan outside a wp-cli runtime; see other CLI command classes in this codebase.
			WP_CLI::log( 'Skipped and lost populations: nothing recorded yet; a run records them as it walks its rows.' );
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
