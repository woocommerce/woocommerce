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
 * per-section `cursor`, cached display-only `counts`, the known-`losses` snapshot, and
 * the set of `options` already migrated.
 *
 * State here is an optimization, never authority. What has actually been migrated is
 * recorded by the markers the migrators write onto legacy and Core rows
 * (`_wc_bis_legacy_id`, `_wc_bis_migration_failed`), not by anything in this option.
 * Deleting `wc_bis_migration_state` mid-run and re-running the migration must produce
 * an identical result: cursors and counts are cheaply re-derivable from the markers,
 * so losing them costs a re-scan, never correctness.
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
	);

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

		foreach ( array( 'cursor', 'counts', 'options' ) as $key ) {
			if ( ! is_array( $state[ $key ] ) ) {
				$state[ $key ] = array();
			}
		}

		return $state;
	}

	/**
	 * Persist the full state, with autoload off.
	 *
	 * @param array $state The full state, as returned by get_state().
	 * @return void
	 */
	private function save_state( array $state ): void {
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
		$state = $this->get_state();

		if ( $this->is_lock_fresh( $state['lock'] ) ) {
			return false;
		}

		$state['lock'] = array(
			'owner'       => $owner,
			'acquired_at' => time(),
		);

		$this->save_state( $state );

		return true;
	}

	/**
	 * Release the CLI run lock, whoever holds it.
	 *
	 * @return void
	 */
	public function release_lock(): void {
		$state         = $this->get_state();
		$state['lock'] = null;
		$this->save_state( $state );
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
		$state = $this->get_state();

		if ( null === $state['lock'] ) {
			return;
		}

		$state['lock']['acquired_at'] = time();

		$this->save_state( $state );
	}

	/**
	 * Whether a CLI run lock is currently held and not stale.
	 *
	 * @return bool
	 */
	public function is_lock_held(): bool {
		return $this->is_lock_fresh( $this->get_state()['lock'] );
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
		return $this->get_state()['lock'];
	}

	/**
	 * Whether a lock entry is present and within the stale threshold.
	 *
	 * @param array|null $lock The lock entry from state.
	 * @return bool
	 */
	private function is_lock_fresh( ?array $lock ): bool {
		if ( null === $lock || ! isset( $lock['acquired_at'] ) ) {
			return false;
		}

		return ( time() - (int) $lock['acquired_at'] ) < self::STALE_LOCK_SECONDS;
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
	 * Reset a single section's cursor to the start of a pass.
	 *
	 * @param string $section Migrator section slug.
	 * @return void
	 */
	public function reset_cursor( string $section ): void {
		$this->set_cursor( $section, 0 );
	}

	/**
	 * Clear every section's cursor.
	 *
	 * Called at the start of a run: a run always starts from zero, so nothing a
	 * previous run left behind can strand rows below a stale cursor.
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
