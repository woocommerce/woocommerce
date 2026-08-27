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
 * outside a migration run needs it in memory. It holds four top-level keys: the CLI
 * run `lock`, the per-section `cursor`, cached display-only `counts`, and per-option
 * migration `fingerprints`.
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
	private const OPTION_NAME = 'wc_bis_migration_state';

	/**
	 * A CLI lock older than this is treated as abandoned and reclaimed.
	 *
	 * An hour, not minutes: too short a threshold reclaims the lock out from under a
	 * still-running worker and reintroduces the concurrent-run problem the lock exists
	 * to prevent.
	 */
	private const STALE_LOCK_SECONDS = HOUR_IN_SECONDS;

	/**
	 * Decision: no fingerprint is on record for this option, write it.
	 */
	public const OPTION_ACTION_WRITE = 'write';

	/**
	 * Decision: the option matches what we last wrote and its source has not changed
	 * since, nothing to do.
	 */
	public const OPTION_ACTION_SKIP_UNCHANGED = 'skip_unchanged';

	/**
	 * Decision: the option no longer matches what we last wrote, a merchant edited it.
	 * Leave it alone unless the caller forces the write.
	 */
	public const OPTION_ACTION_SKIP_USER_MODIFIED = 'skipped_user_modified';

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
	 * (keyed arrays). Typed loosely because the option is merchant-writable data.
	 *
	 * @return array
	 */
	public function get_state(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::DEFAULT_STATE, $stored );
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

		return $state['counts'][ $section ] ?? null;
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
	 * Get the recorded fingerprint for a migrated option key.
	 *
	 * @param string $option_key The option name.
	 * @return array|null `written`, `hash` and `at`, or null when this option has never been migrated.
	 */
	public function get_option_fingerprint( string $option_key ): ?array {
		$state = $this->get_state();

		return $state['options'][ $option_key ] ?? null;
	}

	/**
	 * Record that an option was written by the migration.
	 *
	 * @param string $option_key  The option name.
	 * @param string $source_hash Fingerprint of the legacy source data the value was
	 *                            derived from, used later to detect a changed source.
	 * @param string $target_hash Fingerprint of the value actually written, used later
	 *                            to detect a merchant edit.
	 * @param bool   $skipped     Whether this records a merchant-modified option that was
	 *                            reported and left alone, rather than one that was written.
	 *                            Recording it is what drains the section: an option that
	 *                            keeps deciding `skipped_user_modified` is otherwise
	 *                            outstanding forever and the run never terminates.
	 * @return void
	 */
	public function record_option_fingerprint( string $option_key, string $source_hash, string $target_hash, bool $skipped = false ): void {
		$state                           = $this->get_state();
		$state['options'][ $option_key ] = array(
			'written' => $source_hash,
			'hash'    => $target_hash,
			'skipped' => $skipped,
			'at'      => time(),
		);
		$this->save_state( $state );
	}

	/**
	 * Fingerprint a value for comparison, stable across arrays and scalars.
	 *
	 * The string cast is required: maybe_serialize() returns scalars untouched, so an
	 * integer value reaches hash() as an int and fatals on PHP 8. Casting also matches
	 * how the value reads back out of the database, where everything is a string, and
	 * keeps this in step with ProductMetaMigrator's SQL-side `SHA2()` comparison.
	 *
	 * @param mixed $value The value to fingerprint.
	 * @return string
	 */
	public function fingerprint_value( $value ): string {
		return hash( 'sha256', (string) maybe_serialize( $value ) );
	}

	/**
	 * Decide what to do with a migrated option, per the fingerprint table:
	 *
	 * | Target state              | Action                                          |
	 * |----------------------------|-------------------------------------------------|
	 * | Absent                     | Write                                            |
	 * | Present, hash matches      | We wrote it; rewrite only if the source changed  |
	 * | Present, hash differs      | Merchant edited it; skip, unless $force          |
	 *
	 * A recorded skip keeps its own entry, so a merchant-modified option reports once and
	 * then stops being outstanding. `$force` still overrides it: without that, an option
	 * skipped by an earlier run could never be forced again.
	 *
	 * @param string $option_key         The option name.
	 * @param string $source_hash        Fingerprint of the current legacy source data.
	 * @param string $current_target_hash Fingerprint of the option's current stored value.
	 * @param bool   $force              Whether to write anyway when the merchant edited it.
	 *                                   Overrides only the "hash differs" case; a changed
	 *                                   source already writes without it.
	 * @return string One of the OPTION_ACTION_* constants.
	 */
	public function decide_option_action( string $option_key, string $source_hash, string $current_target_hash, bool $force = false ): string {
		$fingerprint = $this->get_option_fingerprint( $option_key );

		if ( null === $fingerprint ) {
			return self::OPTION_ACTION_WRITE;
		}

		if ( $force && ! empty( $fingerprint['skipped'] ) ) {
			return self::OPTION_ACTION_WRITE;
		}

		if ( $fingerprint['hash'] !== $current_target_hash ) {
			return $force ? self::OPTION_ACTION_WRITE : self::OPTION_ACTION_SKIP_USER_MODIFIED;
		}

		if ( $fingerprint['written'] !== $source_hash ) {
			return self::OPTION_ACTION_WRITE;
		}

		return self::OPTION_ACTION_SKIP_UNCHANGED;
	}
}
