<?php
/**
 * MigrationBatchProcessor class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\SettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Drives the whole legacy Back In Stock Notifications migration as a single
 * `BatchProcessorInterface`, across four sections: notifications, product-meta, emails,
 * settings, in that fixed order.
 *
 * The order is load-bearing (settings must land last, see the plan's Idempotency
 * section), so this is one processor rather than four independently-enqueued ones.
 * `get_next_batch_to_process()` serves the first section with pending work and only
 * moves on once that section is drained; a returned batch never spans two sections.
 * Batch items are `{section}::{id}` strings, so a batch is self-describing in logs.
 *
 * The CLI drives the same instance through `configure_run()`, which swaps in its own
 * migrators (built with `--force`, restricted to `--section`), its writer (`NullWriter`
 * under `--dry-run`) and its batch size, so the section order, cursor handling and
 * pass-reset probe live here only.
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
	 * The rows the last pass-reset probe served per section, recorded once they have
	 * actually been processed, so a probe that serves exactly the same rows again - rows
	 * the section cannot settle - ends the section instead of looping on them.
	 *
	 * @var array<string, array>
	 */
	private array $last_probe = array();

	/**
	 * The probe result a section is currently holding out, promoted into `$last_probe`
	 * once `process_batch()` has been through it.
	 *
	 * @var array<string, array>
	 */
	private array $pending_probe = array();

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
	 * Run state: the CLI lock, per-section cursors, and cached pending counts.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Writer every migrator routes its persistence through. Always the live `DbWriter`
	 * here; `NullWriter` is a CLI `--dry-run` concern only.
	 *
	 * @var WriterInterface
	 */
	private WriterInterface $writer;

	/**
	 * The four section migrators, keyed by `get_slug()` and, since PHP preserves
	 * insertion order on associative arrays, ordered notifications, product-meta,
	 * emails, settings. That order is the section order the whole class runs on.
	 *
	 * @var array<string, MigratorInterface>
	 */
	private array $migrators = array();

	/**
	 * Init the service.
	 *
	 * Only `Requirements` is injected. Everything else is built here rather than resolved
	 * from the container: `WriterInterface` is an interface, and the migrators take
	 * constructor arguments, neither of which `RuntimeContainer` can reflect over.
	 *
	 * @internal
	 *
	 * @param Requirements $requirements Requirement checks, re-run on every batch.
	 */
	final public function init( Requirements $requirements ): void {
		$this->requirements = $requirements;
		$this->state        = new MigrationState();
		$this->writer       = new DbWriter();

		$reporter = new Reporter();

		foreach (
			array(
				new NotificationsMigrator( $reporter ),
				new ProductMetaMigrator( $reporter, $this->state ),
				new EmailSettingsMigrator( $this->state, $reporter ),
				new SettingsMigrator( $this->state, $reporter ),
			) as $migrator
		) {
			$this->migrators[ $migrator->get_slug() ] = $migrator;
		}
	}

	/**
	 * Point this processor at a CLI run's own migrators, writer and batch size.
	 *
	 * The CLI needs migrators that share one `Reporter` and carry `--force`, a
	 * `NullWriter` under `--dry-run`, and only the sections `--section` asked for. It
	 * still runs the loop through this class, so the section order, the cursor and the
	 * pass-reset probe have a single implementation.
	 *
	 * @param array<string, MigratorInterface> $migrators  Migrators keyed by slug, in section order.
	 * @param WriterInterface                  $writer     Writer every migrator routes persistence through.
	 * @param int                              $batch_size Batch size the caller will request.
	 * @return void
	 */
	public function configure_run( array $migrators, WriterInterface $writer, int $batch_size ): void {
		$this->migrators  = $migrators;
		$this->writer     = $writer;
		$this->batch_size = max( 1, $batch_size );
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
	 * and after each migrated batch, cached in `wc_bis_migration_state`, and only read
	 * here - never a live query, so a Tools page load never triggers one. A section
	 * whose count has never been cached contributes zero.
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
	 * Serves the first section, in fixed order, that still has work; only moves past a
	 * section once a query run from the start of a new pass also comes back empty. Never
	 * writes a cursor, so calling it twice in a row returns the same batch; the one write
	 * it does make is the drained section's cached count, once per section per instance.
	 *
	 * Runs only while the run's lock is held, and releases it once there is nothing left
	 * to do - an empty batch is what tells the controller the run is over, so it is also
	 * the point the run hands the lock back.
	 *
	 * @param int $size Maximum size of the batch to be returned.
	 * @return array Batch of `{section}::{id}` items, containing $size or fewer items.
	 */
	public function get_next_batch_to_process( int $size ): array {
		if ( ! $this->state->is_lock_held() ) {
			return array();
		}

		if ( ! $this->requirements_met() ) {
			$this->state->release_lock();

			return array();
		}

		foreach ( $this->migrators as $slug => $migrator ) {
			$batch = $this->get_section_batch( $slug, $migrator, $size );

			if ( ! empty( $batch ) ) {
				return $this->prefix_ids( $slug, $batch );
			}

			$this->refresh_count_on_drain( $slug, $migrator );
		}

		$this->state->release_lock();

		return array();
	}

	/**
	 * Process data for the supplied batch.
	 *
	 * The batch is expected to hold identifiers for a single section, as produced by
	 * `get_next_batch_to_process()`. Each section's `migrate_batch()` already catches
	 * and marks per-row failures; only a whole-batch transient failure (a DB error, a
	 * lost connection) is allowed to propagate here, so the controller can retry it -
	 * safely, since rows that already succeeded wrote their own markers and left the
	 * candidate set. A section's cursor and cached count are written only after its
	 * migrate_batch() call returns without throwing - this is the only method in the
	 * class that persists a cursor.
	 *
	 * Processes only while the run's lock is held, and refreshes it as each section's
	 * batch lands so a long run is never mistaken for an abandoned one.
	 *
	 * @param array $batch Batch to process, as returned by 'get_next_batch_to_process'.
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		if ( ! $this->state->is_lock_held() || ! $this->requirements_met() ) {
			return;
		}

		foreach ( $this->group_by_section( $batch ) as $slug => $raw_ids ) {
			$migrator = $this->migrators[ $slug ];

			$migrator->migrate_batch( $raw_ids, $this->writer );
			$this->state->refresh_lock();
			$this->store_cursor( $slug, $raw_ids );

			if ( isset( $this->pending_probe[ $slug ] ) && $this->pending_probe[ $slug ] === $raw_ids ) {
				$this->last_probe[ $slug ] = $raw_ids;
			}
		}
	}

	/**
	 * Default (preferred) batch size to pass to 'get_next_batch_to_process'.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		return $this->batch_size;
	}

	/**
	 * Fetch one section's next batch, taking its cursor-pass reset into account.
	 *
	 * An empty query at the stored cursor does not by itself prove the section is
	 * drained: a row deleted mid-run can re-enter the candidate set at an id the cursor
	 * already passed. Query once more from the start of a pass; the section is drained
	 * only when that query is also empty. The reset is only computed here, never
	 * persisted - `process_batch()` writes the cursor once the batch has migrated, so
	 * this method performs no writes and repeated calls return the same batch.
	 *
	 * A dry run never gets the probe: nothing is written, so no row leaves the candidate
	 * set and a pass from the start would serve the same rows forever. A real run has the
	 * same failure mode whenever a section cannot settle a row it admits, so a probe that
	 * returns exactly what the previous probe returned ends the section instead.
	 *
	 * @param string            $slug     Section slug.
	 * @param MigratorInterface $migrator Section migrator.
	 * @param int               $size     Maximum number of ids to return.
	 * @return array List of raw identifiers, ascending, or empty when drained.
	 */
	private function get_section_batch( string $slug, MigratorInterface $migrator, int $size ): array {
		$cursor = $this->state->get_cursor( $slug );
		$batch  = $migrator->get_batch( $cursor, $size );

		if ( ! empty( $batch ) || 0 === $cursor || $this->writer->is_dry_run() ) {
			return $batch;
		}

		$probe = $migrator->get_batch( 0, $size );

		if ( ! empty( $probe ) && isset( $this->last_probe[ $slug ] ) && $this->last_probe[ $slug ] === $probe ) {
			// Shares Reporter::LOG_SOURCE's value; this is a run-level condition, not a
			// per-row outcome.
			wc_get_logger()->warning(
				sprintf( 'section %s served the same rows twice without settling them; moving on', $slug ),
				array( 'source' => 'bis-migration' )
			);

			return array();
		}

		$this->pending_probe[ $slug ] = $probe;

		return $probe;
	}

	/**
	 * Store a section's cursor at the highest identifier in a successfully migrated
	 * batch, when that section uses a keyset cursor at all.
	 *
	 * The new value can be lower than the stored one: that is a batch the probe fetched
	 * from the start of a new pass, and writing it here is what persists that pass
	 * reset.
	 *
	 * The emails and settings sections identify their outstanding items by option key,
	 * not by a sequential id, and never read the cursor; their raw ids are never
	 * numeric, so this is a no-op for them.
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
	 * `count_remaining()` is the migration's most expensive query, so the cached count is
	 * written at run start and here, on drain - never per batch. Once refreshed the
	 * section is not counted again by this instance, so the sections a run has already
	 * passed cost nothing on every later batch.
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

		$this->state->set_count( $slug, $migrator->count_remaining() );
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
