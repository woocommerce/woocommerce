<?php
/**
 * MigrationBatchProcessor class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationRun;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\OptionsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Drives the whole legacy Back In Stock Notifications migration as a single
 * `BatchProcessorInterface`, across its batched sections.
 *
 * `get_next_batch_to_process()` serves the first section with pending work and only
 * moves on once that section is drained; a returned batch never spans two sections.
 * Batch items are `{section}::{id}` strings, so a batch is self-describing in logs.
 *
 * Settings are not a section. They are a fixed set of values with nothing to scan, so
 * `OptionsMigrator::migrate()` runs at the top of every batch instead — inside the same
 * retry and requirement checks the sections get.
 *
 * The CLI drives the same instance through `configure_run()`, which swaps in its own
 * migrators (built with `--force`, restricted to `--section`), its writer (a dry-run one
 * under `--dry-run`) and its batch size, so the section order and cursor handling live
 * here only.
 *
 * There is no abort state. Every way a run can end - the feature toggled off, the
 * legacy tables gone, a CLI lock held, a killed worker - is expressed as an empty
 * batch. `BatchProcessingController` dequeues on an empty batch, and that is never a
 * terminal outcome: what has actually migrated is recorded by the markers the
 * migrators write, not by anything here, so the next run picks up exactly where the
 * markers say the previous one left off.
 */
class MigrationBatchProcessor implements BatchProcessorInterface {

	/**
	 * Delimiter joining a section slug and a raw identifier into one batch item.
	 */
	private const SECTION_DELIMITER = '::';

	/**
	 * The single batch item that stands for "the settings still need a pass".
	 *
	 * Settings are not a section, so they have no ids to hand out. Without an item of their
	 * own a store whose batched sections are already drained — or that never had a row in
	 * them — would never get a non-empty batch, `process_batch()` would never run, and its
	 * settings would never migrate at all.
	 */
	private const OPTIONS_ITEM = 'options' . self::SECTION_DELIMITER . 'pending';

	/**
	 * Default batch size for scheduled (Action Scheduler) runs. The CLI raises this for
	 * its own runs via `--batch-size`.
	 */
	private const DEFAULT_BATCH_SIZE = 50;

	/**
	 * Batch size this run works in. `DEFAULT_BATCH_SIZE` for scheduled runs; the CLI
	 * replaces it via `configure_run()`.
	 *
	 * @var int
	 */
	private int $batch_size = self::DEFAULT_BATCH_SIZE;

	/**
	 * Sections whose cached count this instance has already refreshed on drain, so a
	 * request that keeps passing over a drained section does not re-run the migration's
	 * most expensive query each time.
	 *
	 * @var array<string, true>
	 */
	private array $counted_on_drain = array();

	/**
	 * Requirement checks re-run on every batch, not just at run start.
	 *
	 * @var Requirements
	 */
	private Requirements $requirements;

	/**
	 * Outcome collector shared by this run's migrators.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Run state: the CLI lock, per-section cursors, and cached pending counts.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Writer every migrator routes its persistence through. Always a live writer here; a
	 * dry-run one is a CLI `--dry-run` concern only.
	 *
	 * @var Writer
	 */
	private Writer $writer;

	/**
	 * The section migrators, keyed by `get_slug()` and, since PHP preserves insertion order
	 * on associative arrays, held in the order the whole class runs them.
	 *
	 * @var array<string, MigratorInterface>
	 */
	private array $migrators = array();

	/**
	 * The settings migrator, run at the top of every batch rather than as a section.
	 *
	 * @var OptionsMigrator
	 */
	private OptionsMigrator $options;

	/**
	 * Init the service.
	 *
	 * Only `Requirements` and the writer are injected; `MigrationRun` assembles the rest, the
	 * same way the CLI and the Tools screen do.
	 *
	 * @internal
	 *
	 * @param Requirements $requirements Requirement checks, re-run on every batch.
	 * @param Writer       $writer       Live writer used by a background run.
	 */
	final public function init( Requirements $requirements, Writer $writer ): void {
		$this->requirements = $requirements;
		$this->state        = new MigrationState();
		$this->writer       = $writer;

		$run = new MigrationRun();

		$this->reporter  = $run->get_reporter();
		$this->options   = $run->get_options_migrator( false );
		$this->migrators = $run->build_migrators();
	}

	/**
	 * Point this processor at a CLI run's own migrators, writer and batch size.
	 *
	 * The CLI needs migrators that share one `Reporter` and carry `--force`, a dry-run
	 * writer under `--dry-run`, and only the sections `--section` asked for. It
	 * still runs the loop through this class, so the section order and the cursor have a
	 * single implementation.
	 *
	 * The run state is replaced here too. A caller with a state of its own passes it, so a
	 * dry run's in-memory cursors are the same ones the caller resets and reports on; without
	 * one, a state matching the writer's mode is built. Either way the cursor still advances
	 * batch by batch — nothing would ever end the run otherwise — but a dry run's advances
	 * only in memory, so a rehearsal cannot leave a later live run starting above rows it
	 * never migrated, and cannot cache counts for work it only pretended to do.
	 *
	 * @param array<string, MigratorInterface> $migrators  Migrators keyed by slug, in section order.
	 * @param Writer                           $writer     Writer every migrator routes persistence through.
	 * @param int                              $batch_size Batch size the caller will request.
	 * @param Reporter|null                    $reporter   The reporter those migrators share, so the known
	 *                                                     losses cached on drain are this run's own.
	 * @param OptionsMigrator|null             $options    The settings migrator this run shares, so its
	 *                                                     outcomes land on the same reporter.
	 * @param MigrationState|null              $state      The run state this run shares, or null to build
	 *                                                     one matching the writer's mode.
	 * @return void
	 */
	public function configure_run( array $migrators, Writer $writer, int $batch_size, ?Reporter $reporter = null, ?OptionsMigrator $options = null, ?MigrationState $state = null ): void {
		$this->migrators  = $migrators;
		$this->writer     = $writer;
		$this->batch_size = max( 1, $batch_size );

		$this->state = $state ?? new MigrationState( ! $writer->is_dry_run() );

		if ( null !== $reporter ) {
			$this->reporter = $reporter;
		}

		if ( null !== $options ) {
			$this->options = $options;
		}
	}

	/**
	 * Get a user-friendly name for this processor.
	 *
	 * @return string Name of the processor.
	 */
	public function get_name(): string {
		return __( 'Back In Stock Notifications migration', 'woocommerce' );
	}

	/**
	 * Get a user-friendly description for this processor.
	 *
	 * @return string Description of what this processor does.
	 */
	public function get_description(): string {
		return __( 'Migrates legacy Back In Stock Notifications data - signups, product settings, email settings and general settings - to Core customer stock notifications.', 'woocommerce' );
	}

	/**
	 * Sum of every section's cached remaining count.
	 *
	 * Display only; it never drives the batch loop. The counts are computed at run start
	 * and on section drain, cached in `wc_bis_migration_state`, and only read here - never
	 * a live query, so a Tools page load never triggers one. A section whose count has
	 * never been cached contributes zero. A section that scans by keyset caches rows left
	 * to visit, not rows left to migrate: its scan does not know which of them are candidates.
	 *
	 * @return int Number of items pending processing.
	 */
	public function get_total_pending_count(): int {
		$total = 0;

		foreach ( array_keys( $this->migrators ) as $slug ) {
			$cached = $this->state->get_count( $slug );
			$total += null !== $cached ? (int) $cached['count'] : 0;
		}

		return $total;
	}

	/**
	 * Returns the next batch of items that need to be processed.
	 *
	 * Serves the first section, in fixed order, that still has work, and moves past a
	 * section as soon as its query comes back empty. Once both are drained it serves one
	 * settings item, if this run has not been through the settings yet. Never writes a
	 * cursor, so calling it twice in a row returns the same batch; the one write it does
	 * make is the drained section's cached count, once per section per instance.
	 *
	 * Runs only while the run's lock is held, and releases it once there is nothing left
	 * to do - an empty batch is what tells the controller the run is over, so it is also
	 * the point the run hands the lock back.
	 *
	 * @param int $size Maximum size of the batch to be returned.
	 * @return array Batch of `{section}::{id}` items, containing $size or fewer items.
	 */
	public function get_next_batch_to_process( int $size ): array {
		// Serving a batch is the start of a batch cycle, and requirements are re-read once
		// per cycle: a merchant can turn the feature off or drop a table between batches, and
		// a run that pumps one instance through many of them has to see that. Within a cycle
		// the answer is memoized, so `process_batch()` does not pay for the same check again.
		$this->requirements->forget();

		if ( ! $this->state->is_lock_held() ) {
			return array();
		}

		if ( ! $this->requirements_met() ) {
			$this->state->release_lock();

			return array();
		}

		foreach ( $this->migrators as $slug => $migrator ) {
			// A parked section cannot settle its rows, so serving it again would hand back
			// the same batch forever and starve the other section, which shares this
			// processor. It stays skipped until `--retry-failed` or `--force` unparks it.
			if ( $this->state->is_section_parked( $slug ) ) {
				continue;
			}

			$batch = $this->get_section_batch( $slug, $migrator, $size );

			if ( ! empty( $batch ) ) {
				return $this->prefix_ids( $slug, $batch );
			}

			$this->refresh_count_on_drain( $slug, $migrator );
		}

		if ( $this->options->has_pending() ) {
			return array( self::OPTIONS_ITEM );
		}

		$this->state->release_lock();

		return array();
	}

	/**
	 * Process data for the supplied batch.
	 *
	 * The batch is expected to hold identifiers for a single section, as produced by
	 * `get_next_batch_to_process()`. Settings migrate first, then the section's own rows.
	 * Each section's `migrate_batch()` already catches
	 * and marks per-row failures; only a whole-batch transient failure (a DB error, a
	 * lost connection) is allowed to propagate here, so the controller can retry it -
	 * safely, since rows that already succeeded wrote their own markers and left the
	 * candidate set. A section's cursor and cached count are written only after its
	 * migrate_batch() call returns without throwing - this is the only method in the
	 * class that persists a cursor.
	 *
	 * Processes only while the run's lock is held, and refreshes it as each section's
	 * batch lands so a long run is never mistaken for an abandoned one. The batch lock taken
	 * here is a second, narrower one: the run lock lets a run in, this keeps two batches of
	 * that run - which `BatchProcessingController` can have in flight at once - from walking
	 * the same rows and inserting both times. A batch that loses the claim does nothing and
	 * leaves its rows for the next scheduled action.
	 *
	 * @param array $batch Batch to process, as returned by 'get_next_batch_to_process'.
	 * @throws \Throwable A whole-batch failure, after recording it. `BatchProcessingController`
	 *                    catches `\Exception` and reschedules with backoff; an `\Error` escapes
	 *                    it to the action runner, which is why this records before rethrowing
	 *                    rather than leaving the reporting to the controller.
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		if ( ! $this->state->is_lock_held() || ! $this->requirements_met() ) {
			return;
		}

		if ( ! $this->state->acquire_batch_lock() ) {
			return;
		}

		try {
			// Settings are cheap, idempotent and not worth a section of their own, so they go in
			// on every batch: a run stopped part-way still leaves them written.
			$this->options->migrate( $this->writer );

			foreach ( $this->group_by_section( $batch ) as $slug => $raw_ids ) {
				$migrator = $this->migrators[ $slug ];

				try {
					$outcomes = $migrator->migrate_batch( $raw_ids, $this->writer );
				} catch ( \Throwable $e ) {
					// Throwable, not Exception: an \Error would otherwise pass through unrecorded,
					// and it is the one kind of failure `BatchProcessingController` does not catch
					// either. Re-thrown so a retryable failure still reaches the controller.
					$this->reporter->report_exception( $slug, $e );

					// The controller retries this batch and, if it keeps throwing, drops the
					// processor without telling us. Leave a note behind first, so the Tools
					// screen can tell a killed run from a merchant who pressed Stop.
					$this->state->set_failure( $slug, $e->getMessage() );

					throw $e;
				}

				$this->state->refresh_lock();
				$this->park_if_stuck( $slug, $raw_ids, $outcomes );
				$this->store_cursor( $slug, $raw_ids );
			}

			// The batch landed, so whatever the last one failed on is no longer the run's state.
			$this->state->clear_failure();
		} finally {
			$this->state->release_batch_lock();
		}
	}

	/**
	 * Park a section whose whole batch came back unsettled.
	 *
	 * A row reported as `unsettled` failed and could not be marked as failed either, so it
	 * is still a candidate and will be served again. When that is true of every row in the
	 * batch the section has made no progress at all, and serving it again would repeat the
	 * same batch indefinitely. Partial progress is left alone: the section still drains, one
	 * batch at a time.
	 *
	 * @param string $slug     Section slug.
	 * @param array  $raw_ids  Ids the section was handed.
	 * @param array  $outcomes Outcome counts the section reported.
	 * @return void
	 */
	private function park_if_stuck( string $slug, array $raw_ids, array $outcomes ): void {
		$unsettled = (int) ( $outcomes[ Reporter::OUTCOME_UNSETTLED ] ?? 0 );

		if ( 0 === $unsettled || $unsettled < count( $raw_ids ) ) {
			return;
		}

		$reason = sprintf(
			'%d rows could neither be migrated nor marked as failed, so the section cannot progress.',
			$unsettled
		);

		$this->state->park_section( $slug, $reason );
		$this->reporter->report_section_parked( $slug, $reason );
	}

	/**
	 * Default (preferred) batch size to pass to 'get_next_batch_to_process'.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		/**
		 * Filters the batch size the Back In Stock Notifications migration works in.
		 *
		 * The CLI sets its own through `--batch-size`, and passes it here, so a scheduled
		 * background run is the case this is for: a constrained host may need a smaller
		 * batch than the default, and has no command line to ask for one.
		 *
		 * @since 11.2.0
		 *
		 * @param int $batch_size Number of items per batch.
		 */
		return max( 1, (int) apply_filters( 'woocommerce_bis_migration_batch_size', $this->batch_size ) );
	}

	/**
	 * Fetch one section's next batch, from its stored cursor.
	 *
	 * A section is drained once its cursor has reached the last identifier it has to
	 * visit. Nothing re-walks from the start of a pass: every section's cursor only
	 * advances, and a row a batch cannot settle is left behind with a marker rather than
	 * served again, so the same rows can never be handed out twice.
	 *
	 * Performs no writes - `process_batch()` writes the cursor once the batch has
	 * migrated - so repeated calls return the same batch.
	 *
	 * @param string            $slug     Section slug.
	 * @param MigratorInterface $migrator Section migrator.
	 * @param int               $size     Maximum number of ids to return.
	 * @return array List of raw identifiers, ascending, or empty when drained.
	 */
	private function get_section_batch( string $slug, MigratorInterface $migrator, int $size ): array {
		return $migrator->get_batch( $this->state->get_cursor( $slug ), $size );
	}

	/**
	 * Store a section's cursor at the highest identifier in a successfully migrated batch.
	 *
	 * @param string $slug     Section slug.
	 * @param array  $raw_ids Raw identifiers migrate_batch() was just called with.
	 * @return void
	 */
	private function store_cursor( string $slug, array $raw_ids ): void {
		$numeric_ids = array_filter( $raw_ids, 'is_numeric' );

		if ( empty( $numeric_ids ) ) {
			return;
		}

		$this->state->set_cursor( $slug, (int) max( $numeric_ids ) );
	}

	/**
	 * Refresh a section's cached count the first time this instance finds it drained.
	 *
	 * The cached count is written at run start and here, on drain - never per batch. Once
	 * refreshed the section is not counted again by this instance, so the sections a run
	 * has already passed cost nothing on every later batch.
	 *
	 * @param string            $slug     Section slug.
	 * @param MigratorInterface $migrator Section migrator.
	 * @return void
	 */
	private function refresh_count_on_drain( string $slug, MigratorInterface $migrator ): void {
		if ( isset( $this->counted_on_drain[ $slug ] ) ) {
			return;
		}

		$this->counted_on_drain[ $slug ] = true;

		$this->state->set_count( $slug, $migrator->count_remaining( $this->state->get_cursor( $slug ) ) );
	}

	/**
	 * Re-run `Requirements::check()` and log why a run cannot continue.
	 *
	 * Called on every batch, not just at run start, since a merchant can toggle the
	 * feature off or drop the legacy tables while a background run is in progress.
	 *
	 * @return bool True when every requirement is met.
	 */
	private function requirements_met(): bool {
		$result = $this->requirements->check();

		if ( true === $result ) {
			return true;
		}

		// Shares Reporter::LOG_SOURCE's value; this check is not a per-row outcome, so
		// it does not go through Reporter's per-row logging methods.
		wc_get_logger()->warning(
			sprintf( 'run stopped: %s', $result->get_error_message() ),
			array( 'source' => 'bis-migration' )
		);

		return false;
	}

	/**
	 * Prefix a section's raw identifiers with its slug, into `{section}::{id}` items.
	 *
	 * @param string $slug     Section slug.
	 * @param array  $raw_ids Raw identifiers from the section migrator.
	 * @return string[] Section-prefixed batch items.
	 */
	private function prefix_ids( string $slug, array $raw_ids ): array {
		return array_map(
			static function ( $raw_id ) use ( $slug ) {
				return $slug . self::SECTION_DELIMITER . $raw_id;
			},
			$raw_ids
		);
	}

	/**
	 * Split a batch of `{section}::{id}` items back into raw identifiers, grouped by
	 * section. An item naming an unknown section is dropped rather than passed to a
	 * migrator that cannot handle it.
	 *
	 * @param array $batch Section-prefixed batch items.
	 * @return array<string, array> Raw identifiers, keyed by section slug.
	 */
	private function group_by_section( array $batch ): array {
		$grouped = array();

		foreach ( $batch as $item ) {
			$parts = explode( self::SECTION_DELIMITER, (string) $item, 2 );

			if ( 2 !== count( $parts ) || ! isset( $this->migrators[ $parts[0] ] ) ) {
				continue;
			}

			list( $slug, $raw_id ) = $parts;

			$grouped[ $slug ][] = is_numeric( $raw_id ) ? (int) $raw_id : $raw_id;
		}

		return $grouped;
	}
}
