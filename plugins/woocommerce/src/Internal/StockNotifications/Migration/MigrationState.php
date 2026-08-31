<?php
/**
 * MigrationState class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes `wc_bis_migration_state`, the run state for the BIS migration.
 *
 * The option is written with autoload off: it changes on every batch, and nothing
 * outside a migration run needs it in memory. It holds the per-section `cursor`, cached
 * display-only `counts` and `totals`, and the known-`losses` snapshot. The run lock is not
 * among them: it lives in its own `wc_bis_migration_lock` row, claimed by an atomic INSERT,
 * because a lock read out of this option and written back could be handed to two runs at
 * once. This class owns both.
 *
 * State here is an optimization, never authority. What has actually been migrated is
 * recorded by the markers the migrators write onto legacy and Core rows
 * (`_wc_bis_legacy_id_*`, `_wc_bis_migration_failed`), not by anything in this option.
 * Deleting `wc_bis_migration_state` mid-run and re-running the migration must produce
 * an identical result: every row a lost cursor hands out again is recognised as already
 * migrated and skipped, so losing a cursor costs a re-scan, never correctness. The
 * re-scan is of the whole legacy notifications table, which is why cursors are kept
 * between runs rather than reset at the start of each one.
 *
 * A dry run builds this with `$persist = false`: the run then reads the stored state once
 * and keeps every change to itself, so a rehearsal can advance its own cursor batch after
 * batch — which is what ends the run — without moving the cursor a later live run starts
 * from, or caching counts for work it only pretended to do. The run lock is the exception:
 * it is cross-process mutual exclusion rather than run state, so it is always read from and
 * written to its own row, dry run or not.
 */
class MigrationState {

	/**
	 * The option name this class reads and writes.
	 */
	private const OPTION_NAME = Constants::STATE_OPTION;

	/**
	 * The option row holding the run lock.
	 */
	private const LOCK_OPTION_NAME = Constants::LOCK_OPTION;

	/**
	 * The option row holding the batch lock.
	 */
	private const BATCH_LOCK_OPTION_NAME = Constants::BATCH_LOCK_OPTION;

	/**
	 * Digits the lock's acquisition timestamp is zero-padded to in the stored value.
	 *
	 * Fixed width is what lets the database compare stored values as plain strings, without
	 * a numeric cast: for any timestamp this side of the year 2286 the padded form is ten
	 * digits, so lexical order matches chronological order.
	 */
	private const LOCK_TIME_DIGITS = 10;

	/**
	 * A CLI lock older than this is treated as abandoned and reclaimed.
	 *
	 * An hour, not minutes: too short a threshold reclaims the lock out from under a
	 * still-running worker and reintroduces the concurrent-run problem the lock exists
	 * to prevent.
	 */
	private const STALE_LOCK_SECONDS = HOUR_IN_SECONDS;

	/**
	 * A batch lock older than this is treated as abandoned and reclaimed.
	 *
	 * Minutes, not the hour the run lock gets: this one is held for a single batch, which
	 * `BatchProcessingController` keeps well inside an action runner's time limit, so a lock
	 * still held after this long belongs to a worker that was killed mid-batch. Until it is
	 * reclaimed the run makes no progress, which is why it is not longer.
	 */
	private const STALE_BATCH_LOCK_SECONDS = 5 * MINUTE_IN_SECONDS;

	/**
	 * The empty state shape, used when the option does not exist yet.
	 *
	 * @var array
	 */
	private const DEFAULT_STATE = array(
		'cursor'  => array(),
		'counts'  => array(),
		'losses'  => null,
		'totals'  => array(),
		'failure' => null,
		'parked'  => array(),
	);

	/**
	 * Whether this instance persists what it writes.
	 *
	 * @var bool
	 */
	private bool $persist;

	/**
	 * In-memory state for a non-persisting instance, seeded from the stored option on the
	 * first read. Null until then.
	 *
	 * @var array|null
	 */
	private ?array $scratch = null;

	/**
	 * The batch lock value this instance claimed, so it releases its own claim and not one a
	 * later worker took over. Null when this instance holds no batch lock.
	 *
	 * @var string|null
	 */
	private ?string $batch_lock_value = null;

	/**
	 * Constructor.
	 *
	 * @param bool $persist Whether to write changes to the option. False for a dry run.
	 */
	public function __construct( bool $persist = true ) {
		$this->persist = $persist;
	}

	/**
	 * Read the full state, filled out to the default shape.
	 *
	 * Shape: `lock` (array|null), `cursor` (section slug => int), `counts` and `totals`
	 * (keyed arrays). Every nested field is checked, not just the option itself: this is
	 * merchant-writable data, and a scalar where an array belongs would reach
	 * `is_lock_fresh( ?array )` and the `?array` accessors as a TypeError.
	 *
	 * @return array
	 */
	public function get_state(): array {
		if ( $this->persist ) {
			return $this->read_stored_state();
		}

		return $this->scratch ??= $this->read_stored_state();
	}

	/**
	 * Read the stored state, filled out to the default shape.
	 *
	 * @return array
	 */
	private function read_stored_state(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$state = array_merge( self::DEFAULT_STATE, $stored );

		foreach ( array( 'losses', 'failure' ) as $key ) {
			if ( null !== $state[ $key ] && ! is_array( $state[ $key ] ) ) {
				$state[ $key ] = null;
			}
		}

		foreach ( array( 'cursor', 'counts', 'totals', 'parked' ) as $key ) {
			if ( ! is_array( $state[ $key ] ) ) {
				$state[ $key ] = array();
			}
		}

		return $state;
	}

	/**
	 * Record the full state, persisting it unless this is a dry run's instance.
	 *
	 * @param array $state The full state, as returned by get_state().
	 * @return void
	 */
	private function save_state( array $state ): void {
		if ( ! $this->persist ) {
			$this->scratch = $state;

			return;
		}

		$this->write_stored_state( $state );
	}

	/**
	 * Persist the full state, with autoload off.
	 *
	 * @param array $state The full state, as returned by get_state().
	 * @return void
	 */
	private function write_stored_state( array $state ): void {
		update_option( self::OPTION_NAME, $state, false );
	}

	/**
	 * Attempt to acquire the run lock.
	 *
	 * The claim is an atomic INSERT, which the unique `option_name` key turns into a mutex.
	 * A read-then-write claim would hand the lock to both a CLI run and the Tools screen when
	 * they start at the same moment, and nothing downstream catches what follows: the two runs
	 * walk the same rows, both find no migration marker, and both insert - leaving the shopper
	 * subscribed twice and emailed twice on restock. A lock that has gone stale, or one stamped
	 * in the future by a skewed clock, is taken over by the conditional UPDATE instead. Modeled
	 * on `WC_Install::create_lock()`.
	 *
	 * @param string $owner Identifier for the process acquiring the lock, used only for
	 *                      reporting who holds it.
	 * @return bool True when the lock was acquired, false when another run already holds
	 *              a lock that is not yet stale.
	 */
	public function acquire_lock( string $owner ): bool {
		return null !== $this->claim( self::LOCK_OPTION_NAME, $owner, self::STALE_LOCK_SECONDS );
	}

	/**
	 * Release the run lock, whoever holds it.
	 *
	 * @return void
	 */
	public function release_lock(): void {
		$this->delete_lock_row( self::LOCK_OPTION_NAME );
	}

	/**
	 * Release the lock only when the named owner still holds it.
	 *
	 * The background run cannot always hand its own lock back: `BatchProcessingController`
	 * drops a consistently failing processor without telling it, so the lock outlives the
	 * run that took it. A caller that can prove the owner is gone — the Tools screen, which
	 * knows the processor is no longer enqueued — reclaims it here rather than making the
	 * merchant wait out STALE_LOCK_SECONDS.
	 *
	 * @param string $owner Owner string the lock must carry to be released.
	 * @return bool True when a lock owned by $owner was released.
	 */
	public function release_lock_owned_by( string $owner ): bool {
		global $wpdb;

		$stored = $this->read_stored_lock();

		if ( null === $stored || $stored['owner'] !== $owner ) {
			return false;
		}

		// Delete the exact value that was read, so a lock a third process has taken over in
		// the meantime is left where it is.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$wpdb->options,
			array(
				'option_name'  => self::LOCK_OPTION_NAME,
				'option_value' => $stored['value'],
			),
			array( '%s', '%s' )
		);

		$this->flush_lock_cache( self::LOCK_OPTION_NAME );

		return (bool) $deleted;
	}

	/**
	 * Push out the acquisition time of the lock currently held.
	 *
	 * A run longer than STALE_LOCK_SECONDS would otherwise have its lock treated as
	 * abandoned while it is still working. Called as each batch completes, so a lock only
	 * goes stale when the run holding it actually stopped.
	 *
	 * @return void
	 */
	public function refresh_lock(): void {
		global $wpdb;

		$stored = $this->read_stored_lock();

		if ( null === $stored ) {
			return;
		}

		// Match on the value that was read: a run whose lock has already been taken over must
		// not stamp its own time onto the new holder's row.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				$this->encode_lock( time(), $stored['owner'] ),
				self::LOCK_OPTION_NAME,
				$stored['value']
			)
		);

		$this->flush_lock_cache( self::LOCK_OPTION_NAME );
	}

	/**
	 * Attempt to acquire the batch lock, held for the length of one batch.
	 *
	 * The run lock is mutual exclusion between runs; this is mutual exclusion between two
	 * batches of the same run, which the run lock does not give. `BatchProcessingController`
	 * can have two batch actions in flight for one processor - its watchdog schedules a new
	 * one whenever none is *scheduled*, and an action already running does not count as
	 * scheduled - and two batches that overlap read the same cursor, walk the same rows, and
	 * both insert.
	 *
	 * Deliberately not taken while a batch is being *served*: an empty batch is what tells the
	 * controller a run is over, so a caller that returned empty because it lost this lock would
	 * have its migration dequeued part-way. The loser of a claim does no work and leaves the
	 * batch for the next scheduled action instead.
	 *
	 * @return bool True when the lock was acquired and the caller may process a batch.
	 */
	public function acquire_batch_lock(): bool {
		// The token, not just the pid: two claims a second apart from one worker would otherwise
		// store the same value, and release_batch_lock() compares on the value to tell its own
		// claim from a later one.
		$claimed = $this->claim(
			self::BATCH_LOCK_OPTION_NAME,
			sprintf( 'batch (pid %d, %s)', getmypid(), uniqid() ),
			self::STALE_BATCH_LOCK_SECONDS
		);

		if ( null === $claimed ) {
			return false;
		}

		$this->batch_lock_value = $claimed;

		return true;
	}

	/**
	 * Release the batch lock, if this instance still holds the claim it made.
	 *
	 * @return void
	 */
	public function release_batch_lock(): void {
		global $wpdb;

		if ( null === $this->batch_lock_value ) {
			return;
		}

		// Delete this instance's own claim: a batch that overran the stale threshold has had
		// its lock taken over, and the worker now holding it must keep it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->options,
			array(
				'option_name'  => self::BATCH_LOCK_OPTION_NAME,
				'option_value' => $this->batch_lock_value,
			),
			array( '%s', '%s' )
		);

		$this->flush_lock_cache( self::BATCH_LOCK_OPTION_NAME );

		$this->batch_lock_value = null;
	}

	/**
	 * Whether a run lock is currently held and not stale.
	 *
	 * @return bool
	 */
	public function is_lock_held(): bool {
		return $this->is_lock_fresh( $this->get_lock() );
	}

	/**
	 * The current lock's details, for building a "run in progress" message.
	 *
	 * Returns the lock even when stale; callers that need "is it actually held" should
	 * use is_lock_held() instead.
	 *
	 * @return array|null `owner` and `acquired_at` when a lock row is present and readable.
	 */
	public function get_lock(): ?array {
		$stored = $this->read_stored_lock();

		if ( null === $stored ) {
			return null;
		}

		return array(
			'owner'       => $stored['owner'],
			'acquired_at' => $stored['acquired_at'],
		);
	}

	/**
	 * Read the lock row, parsed into its parts.
	 *
	 * Read straight from the database rather than through `get_option()`: the claim and the
	 * takeover are direct writes, so a cached copy could report a lock that a concurrent
	 * request has already released or stolen. A value that does not parse - a row a merchant
	 * or an older build wrote by hand - reads as no lock at all, and `acquire_lock()` takes
	 * it over.
	 *
	 * @return array|null `value` (the stored string, for compare-and-set), `owner` and
	 *                    `acquired_at`, or null when there is no readable lock.
	 */
	private function read_stored_lock(): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::LOCK_OPTION_NAME
			)
		);

		if ( ! is_string( $value ) || ! preg_match( '/^(\d+)\|(.*)$/s', $value, $matches ) ) {
			return null;
		}

		return array(
			'value'       => $value,
			'owner'       => $matches[2],
			'acquired_at' => (int) $matches[1],
		);
	}

	/**
	 * Build the stored form of a lock: its acquisition time, then its owner.
	 *
	 * @param int    $acquired_at Acquisition time, as a Unix timestamp.
	 * @param string $owner       Identifier of the process holding the lock.
	 * @return string
	 */
	private function encode_lock( int $acquired_at, string $owner ): string {
		return sprintf( '%0' . self::LOCK_TIME_DIGITS . 'd|%s', max( 0, $acquired_at ), $owner );
	}

	/**
	 * Claim a lock row.
	 *
	 * The claim is an atomic INSERT, which the unique `option_name` key turns into a mutex.
	 * A read-then-write claim would hand the same lock to two callers that start at the same
	 * moment, and nothing downstream catches what follows: two runs walk the same rows, both
	 * find no migration marker, and both insert - leaving the shopper subscribed twice and
	 * emailed twice on restock. A lock that has gone stale, or one stamped in the future by
	 * a skewed clock, is taken over by the conditional UPDATE instead. Modeled on
	 * `WC_Install::create_lock()`.
	 *
	 * @param string $option      Option row to claim.
	 * @param string $owner       Identifier of the claiming process, for reporting.
	 * @param int    $stale_after Seconds after which a held lock is treated as abandoned.
	 * @return string|null The claimed value, for a later compare-and-delete, or null when the
	 *                     lock could not be claimed.
	 */
	private function claim( string $option, string $owner, int $stale_after ): ?string {
		global $wpdb;

		$now   = time();
		$claim = $this->encode_lock( $now, $owner );

		// A contended INSERT fails on the duplicate key by design, so keep that expected
		// failure from spamming the log in debug mode; restore the prior setting after.
		$suppress = $wpdb->suppress_errors( true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$acquired = $wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => $option,
				'option_value' => $claim,
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$wpdb->suppress_errors( $suppress );
		$this->flush_lock_cache( $option );

		if ( $acquired ) {
			return $claim;
		}

		// A value at or past `$now + 1` was stamped by a clock ahead of this one. Left alone it
		// would never age into staleness and would refuse every run forever, with no way out but
		// editing the option by hand, so it is taken over the same way an abandoned lock is.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stolen = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND ( option_value < %s OR option_value >= %s )",
				$claim,
				$option,
				$this->encode_lock( $now - $stale_after, '' ),
				$this->encode_lock( $now + 1, '' )
			)
		);

		$this->flush_lock_cache( $option );

		return $stolen ? $claim : null;
	}

	/**
	 * Delete a lock row, whoever holds it.
	 *
	 * @param string $option Option row to delete.
	 * @return void
	 */
	private function delete_lock_row( string $option ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->options, array( 'option_name' => $option ), array( '%s' ) );

		$this->flush_lock_cache( $option );
	}

	/**
	 * Drop the cached copy of a lock row after writing it directly.
	 *
	 * This class never reads a lock through `get_option()`, but the object cache does not
	 * know that: a direct write leaves whatever `get_option()` last cached in place, and a
	 * stale `notoptions` entry would keep hiding a row this process just created.
	 *
	 * @param string $option Option row that was written.
	 * @return void
	 */
	private function flush_lock_cache( string $option ): void {
		wp_cache_delete( $option, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Whether a lock entry is present and within the stale threshold.
	 *
	 * @param array|null $lock The lock entry, as returned by get_lock().
	 * @return bool
	 */
	private function is_lock_fresh( ?array $lock ): bool {
		if ( null === $lock ) {
			return false;
		}

		$acquired_at = $lock['acquired_at'];

		// A timestamp in the future makes the age below negative, which is always under the
		// stale threshold: the lock would then read as fresh forever and refuse every run,
		// with no way out but editing the option by hand. Clock skew on a restored or moved
		// site is enough to produce one, so treat it as stale and let the next run reclaim it.
		if ( $acquired_at > time() ) {
			return false;
		}

		return ( time() - $acquired_at ) < self::STALE_LOCK_SECONDS;
	}

	/**
	 * Get a section's keyset cursor.
	 *
	 * @param string $section Migrator section slug.
	 * @return int Last identifier handled in the current pass, or 0 to start a pass.
	 */
	public function get_cursor( string $section ): int {
		$state = $this->get_state();

		return (int) ( $state['cursor'][ $section ] ?? 0 );
	}

	/**
	 * Set a section's keyset cursor.
	 *
	 * @param string $section Migrator section slug.
	 * @param int    $cursor  Last identifier handled in the current pass.
	 * @return void
	 */
	public function set_cursor( string $section, int $cursor ): void {
		$state                       = $this->get_state();
		$state['cursor'][ $section ] = $cursor;
		$this->save_state( $state );
	}

	/**
	 * Clear every section's cursor.
	 *
	 * Called only by the CLI's `--force` and `--retry-failed`, the two flags that put rows
	 * back into play below wherever the cursor stands. An ordinary run resumes from the
	 * cursor it was left at.
	 *
	 * @return void
	 */
	public function reset_all_cursors(): void {
		$state           = $this->get_state();
		$state['cursor'] = array();
		// Rows below the cursor come back into play, so anything they were counted as
		// losing gets counted again on the way past. Start that tally over.
		$state['losses'] = null;
		$this->save_state( $state );
	}

	/**
	 * Get a section's cached remaining-count, for display only.
	 *
	 * @param string $section Migrator section slug.
	 * @return array|null `count` and `at`, or null when nothing has been cached yet.
	 */
	public function get_count( string $section ): ?array {
		$state = $this->get_state();
		$count = $state['counts'][ $section ] ?? null;

		return is_array( $count ) ? $count : null;
	}

	/**
	 * Cache a section's remaining-count, timestamped now.
	 *
	 * @param string $section Migrator section slug.
	 * @param int    $count   Remaining candidate rows for the section.
	 * @return void
	 */
	public function set_count( string $section, int $count ): void {
		$state                       = $this->get_state();
		$state['counts'][ $section ] = array(
			'count' => $count,
			'at'    => time(),
		);
		$this->save_state( $state );
	}

	/**
	 * Get a section's cached total: how many rows it had to visit when a run last started.
	 *
	 * The denominator the Tools screen shows progress against. Written at run start and left
	 * alone while the run works, so progress moves in one direction.
	 *
	 * @param string $section Migrator section slug.
	 * @return int|null Null when no run has recorded a total yet.
	 */
	public function get_total( string $section ): ?int {
		$total = $this->get_state()['totals'][ $section ] ?? null;

		return is_numeric( $total ) ? (int) $total : null;
	}

	/**
	 * Record a section's total at run start.
	 *
	 * @param string $section Migrator section slug.
	 * @param int    $total   Rows the section had to visit when the run started.
	 * @return void
	 */
	public function set_total( string $section, int $total ): void {
		$state                       = $this->get_state();
		$state['totals'][ $section ] = $total;
		$this->save_state( $state );
	}

	/**
	 * Get the cached known-losses counts, for display only.
	 *
	 * @return array|null `values` (the known-losses counts, keyed by name) and `at`, or
	 *                     null when nothing has been cached yet.
	 */
	public function get_losses(): ?array {
		return $this->get_state()['losses'];
	}

	/**
	 * Cache the known-losses counts, timestamped now.
	 *
	 * @param array<string,int> $values Known-losses counts, keyed by name.
	 * @return void
	 */
	public function set_losses( array $values ): void {
		$state           = $this->get_state();
		$state['losses'] = array(
			'values' => $values,
			'at'     => time(),
		);
		$this->save_state( $state );
	}

	/**
	 * Add to the cached known-losses counts, timestamped now.
	 *
	 * Losses are counted per batch and added here rather than snapshotted at the end of a
	 * run, because a background run is a fresh PHP request per batch: a Reporter never
	 * survives to see the whole run, so anything read off one at drain time would be the
	 * counts of a request that has processed nothing. Callers pass the delta since their
	 * own last call, so their own counters are not added twice.
	 *
	 * @param array<string,int> $deltas Amounts to add, keyed by loss name.
	 * @return void
	 */
	public function add_losses( array $deltas ): void {
		$state  = $this->get_state();
		$values = $state['losses']['values'] ?? array();

		if ( ! is_array( $values ) ) {
			$values = array();
		}

		foreach ( $deltas as $key => $delta ) {
			$values[ $key ] = (int) ( $values[ $key ] ?? 0 ) + (int) $delta;
		}

		$state['losses'] = array(
			'values' => $values,
			'at'     => time(),
		);

		$this->save_state( $state );
	}

	/**
	 * Drop the cached known-losses counts.
	 *
	 * Counts accumulate across the runs of one migration, so the only thing that may clear
	 * them is a reset that puts rows back into play — otherwise a re-walk would count the
	 * same skipped sign-up twice.
	 *
	 * @return void
	 */
	public function reset_losses(): void {
		$state           = $this->get_state();
		$state['losses'] = null;
		$this->save_state( $state );
	}

	/**
	 * The failure that stopped the last background run, if one did.
	 *
	 * @return array|null `section`, `message` and `at`, or null when the last run did not
	 *                    end in a failure.
	 */
	public function get_failure(): ?array {
		return $this->get_state()['failure'];
	}

	/**
	 * Record the failure a batch ended on, timestamped now.
	 *
	 * `BatchProcessingController` retries a throwing batch and eventually drops the
	 * processor, logging to `wc-logger` and deleting its own state as it goes. That leaves
	 * the Tools screen unable to tell a killed run from a deliberate stop, so the migration
	 * keeps its own note of why the last batch failed.
	 *
	 * @param string $section Section slug the failure came from.
	 * @param string $message Exception message.
	 * @return void
	 */
	public function set_failure( string $section, string $message ): void {
		$state            = $this->get_state();
		$state['failure'] = array(
			'section' => $section,
			'message' => $message,
			'at'      => time(),
		);
		$this->save_state( $state );
	}

	/**
	 * Park a section that cannot make progress, so the run stops serving it.
	 *
	 * A section whose rows can neither be migrated nor marked as failed would be handed out
	 * again on every pass, forever, and the notifications section shares the processor with
	 * it. Parking stops that: the section is skipped until `--retry-failed` or `--force`
	 * puts it back in play, and the reason travels with it so the Tools screen and the CLI
	 * can say why the run went quiet.
	 *
	 * @param string $section Section slug.
	 * @param string $reason  Why the section cannot progress.
	 * @return void
	 */
	public function park_section( string $section, string $reason ): void {
		$state = $this->get_state();

		if ( isset( $state['parked'][ $section ] ) ) {
			return;
		}

		$state['parked'][ $section ] = array(
			'reason' => $reason,
			'at'     => time(),
		);

		$this->save_state( $state );
	}

	/**
	 * Whether a section is currently parked.
	 *
	 * @param string $section Section slug.
	 * @return bool
	 */
	public function is_section_parked( string $section ): bool {
		return isset( $this->get_state()['parked'][ $section ] );
	}

	/**
	 * Every parked section, keyed by slug, with its `reason` and `at`.
	 *
	 * @return array
	 */
	public function get_parked_sections(): array {
		return $this->get_state()['parked'];
	}

	/**
	 * Put every parked section back in play.
	 *
	 * Called by the same two flags that clear failure markers and cursors: whatever blocked
	 * the section may since have been fixed, and a retry has to be able to find out.
	 *
	 * @return void
	 */
	public function unpark_all(): void {
		$state = $this->get_state();

		if ( array() === $state['parked'] ) {
			return;
		}

		$state['parked'] = array();

		$this->save_state( $state );
	}

	/**
	 * Clear the recorded failure, once a run gets going again.
	 *
	 * @return void
	 */
	public function clear_failure(): void {
		$state = $this->get_state();

		if ( null === $state['failure'] ) {
			return;
		}

		$state['failure'] = null;
		$this->save_state( $state );
	}
}
