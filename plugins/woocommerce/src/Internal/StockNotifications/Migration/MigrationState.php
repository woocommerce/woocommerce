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
 * outside a migration run needs it in memory. It holds the CLI run `lock`, the
 * per-section `cursor`, cached display-only `counts` and `totals`, the known-`losses`
 * snapshot, and the set of `options` already migrated.
 *
 * State here is an optimization, never authority. What has actually been migrated is
 * recorded by the markers the migrators write onto legacy and Core rows
 * (`_wc_bis_legacy_id`, `_wc_bis_migration_failed`), not by anything in this option.
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
 * written to the stored option.
 */
class MigrationState {

	/**
	 * The option name this class reads and writes.
	 */
	private const OPTION_NAME = Constants::STATE_OPTION;

	/**
	 * A CLI lock older than this is treated as abandoned and reclaimed.
	 *
	 * An hour, not minutes: too short a threshold reclaims the lock out from under a
	 * still-running worker and reintroduces the concurrent-run problem the lock exists
	 * to prevent.
	 */
	private const STALE_LOCK_SECONDS = HOUR_IN_SECONDS;

	/**
	 * The empty state shape, used when the option does not exist yet.
	 *
	 * @var array
	 */
	private const DEFAULT_STATE = array(
		'lock'    => null,
		'cursor'  => array(),
		'counts'  => array(),
		'options' => array(),
		'losses'  => null,
		'totals'  => array(),
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
	 * Shape: `lock` (array|null), `cursor` (section slug => int), `counts` and `options`
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

		foreach ( array( 'lock', 'losses' ) as $key ) {
			if ( null !== $state[ $key ] && ! is_array( $state[ $key ] ) ) {
				$state[ $key ] = null;
			}
		}

		foreach ( array( 'cursor', 'counts', 'options', 'totals' ) as $key ) {
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
	 * Attempt to acquire the CLI run lock.
	 *
	 * @param string $owner Identifier for the process acquiring the lock, used only for
	 *                      reporting who holds it.
	 * @return bool True when the lock was acquired, false when another run already holds
	 *              a lock that is not yet stale.
	 */
	public function acquire_lock( string $owner ): bool {
		$state = $this->read_stored_state();

		if ( $this->is_lock_fresh( $state['lock'] ) ) {
			return false;
		}

		$state['lock'] = array(
			'owner'       => $owner,
			'acquired_at' => time(),
		);

		$this->write_stored_state( $state );

		return true;
	}

	/**
	 * Release the CLI run lock, whoever holds it.
	 *
	 * @return void
	 */
	public function release_lock(): void {
		$state         = $this->read_stored_state();
		$state['lock'] = null;
		$this->write_stored_state( $state );
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
		$state = $this->read_stored_state();

		if ( null === $state['lock'] ) {
			return;
		}

		$state['lock']['acquired_at'] = time();

		$this->write_stored_state( $state );
	}

	/**
	 * Whether a CLI run lock is currently held and not stale.
	 *
	 * @return bool
	 */
	public function is_lock_held(): bool {
		return $this->is_lock_fresh( $this->read_stored_state()['lock'] );
	}

	/**
	 * The current lock's details, for building a "run in progress" message.
	 *
	 * Returns the lock even when stale; callers that need "is it actually held" should
	 * use is_lock_held() instead.
	 *
	 * @return array|null `owner` and `acquired_at` when a lock entry is present.
	 */
	public function get_lock(): ?array {
		return $this->read_stored_state()['lock'];
	}

	/**
	 * Whether a lock entry is present and within the stale threshold.
	 *
	 * @param array|null $lock The lock entry from state.
	 * @return bool
	 */
	private function is_lock_fresh( ?array $lock ): bool {
		if ( null === $lock ) {
			return false;
		}

		$acquired_at = $lock['acquired_at'] ?? null;

		// A timestamp in the future makes the age below negative, which is always under the
		// stale threshold: the lock would then read as fresh forever and refuse every run,
		// with no way out but editing the option by hand. Clock skew on a restored or moved
		// site is enough to produce one, so treat it as stale and let the next run reclaim it.
		if ( ! is_int( $acquired_at ) || $acquired_at > time() ) {
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
	 * Whether this option key has already been migrated.
	 *
	 * This is the settle signal for the settings and email-settings sections. They have no
	 * per-row marker to stamp the way the notifications and product-meta migrators do, so
	 * without it every mapped key stays outstanding, the section never drains, and the run
	 * rewrites the same options on every batch.
	 *
	 * @param string $option_key The option name, or an option/sub-key pair for nested settings.
	 * @return bool
	 */
	public function is_option_migrated( string $option_key ): bool {
		$state = $this->get_state();

		return ! empty( $state['options'][ $option_key ] );
	}

	/**
	 * Record that an option was written by the migration, so later runs leave it alone.
	 *
	 * Callers must only record this once the write has landed: a key recorded without a
	 * successful write is never retried.
	 *
	 * @param string $option_key The option name, or an option/sub-key pair for nested settings.
	 * @return void
	 */
	public function mark_option_migrated( string $option_key ): void {
		$state                           = $this->get_state();
		$state['options'][ $option_key ] = true;
		$this->save_state( $state );
	}
}
