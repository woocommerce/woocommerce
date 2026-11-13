<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

/**
 * A site-wide mutex for a running product CSV import.
 *
 * The admin product importer and the `wp wc product import` CLI command both run cleanup of
 * placeholder products at the end of an import, and that cleanup is site-wide rather than scoped
 * to the run that created the placeholders. Both entry points hold this lock for the duration
 * of their run to keep their cleanup passes apart. This is best-effort: the lock has no owner
 * check, so a run that loses it to a stale takeover can still touch the new run's state.
 *
 * @since 11.4.0
 */
final class ProductImportLock {

	/**
	 * Lock holder for an import started from the admin screens.
	 */
	public const HOLDER_ADMIN = 'admin';

	/**
	 * Lock holder for an import started from WP-CLI.
	 */
	public const HOLDER_CLI = 'cli';

	/**
	 * Option name used as the mutex for a running product import. Its value is `<timestamp>:<holder>`.
	 */
	private const IMPORT_LOCK_OPTION = 'wc_product_importing';

	/**
	 * How long a lock is honored before a new import may take it over, in seconds.
	 */
	private const IMPORT_LOCK_STALE_AFTER = MINUTE_IN_SECONDS * 10;

	/**
	 * How long an admin import's lock is honored against another admin import, in seconds.
	 */
	private const ADMIN_LOCK_STALE_AFTER = MINUTE_IN_SECONDS * 2;

	/**
	 * Minimum time between lock refreshes during a batch, in seconds.
	 */
	private const REFRESH_INTERVAL = MINUTE_IN_SECONDS;

	/**
	 * Try to take the site-wide product import lock.
	 *
	 * Modeled on `WC_Install::create_lock()`: the option row acts as a mutex through an
	 * `INSERT` that fails if a lock already exists, with a stale takeover after
	 * `IMPORT_LOCK_STALE_AFTER` seconds so a crashed run cannot block imports forever.
	 * An admin import takes over another admin import's lock after only `ADMIN_LOCK_STALE_AFTER`
	 * seconds, because closing the browser tab leaves no way to release it. A running import
	 * refreshes the lock before each row, so a single row that outlasts the timeout can still lose it.
	 *
	 * @param string $holder Who is taking the lock, one of the `HOLDER_*` constants.
	 * @return bool Whether the lock was acquired.
	 */
	public function acquire( string $holder ): bool {
		global $wpdb;

		$value = time() . ':' . $holder;

		// A contended INSERT fails on the duplicate key by design, so keep wpdb from logging or printing it.
		$suppress = $wpdb->suppress_errors( true );
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$acquired = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
					self::IMPORT_LOCK_OPTION,
					$value
				)
			);
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		if ( ! $acquired ) {
			$stale_after = self::HOLDER_ADMIN === $holder && self::HOLDER_ADMIN === $this->get_holder()
				? self::ADMIN_LOCK_STALE_AFTER
				: self::IMPORT_LOCK_STALE_AFTER;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$acquired = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND CAST( SUBSTRING_INDEX( option_value, ':', 1 ) AS UNSIGNED ) < %d",
					$value,
					self::IMPORT_LOCK_OPTION,
					time() - $stale_after
				)
			);
		}

		return (bool) $acquired;
	}

	/**
	 * Refresh the product import lock's timestamp so a long-running import isn't
	 * mistaken for stale and taken over by another run.
	 *
	 * The caller must already hold the lock; this does not check ownership, since a
	 * batch of an admin import runs in a request of its own and has no other way to
	 * prove it.
	 */
	public function refresh(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = CONCAT( %d, ':', SUBSTRING_INDEX( option_value, ':', -1 ) ) WHERE option_name = %s",
				time(),
				self::IMPORT_LOCK_OPTION
			)
		);
	}

	/**
	 * Refresh the lock if its last refresh is old enough.
	 *
	 * Called before each row, because rows with slow remote images can make one batch outlast the
	 * lock's stale timeout. The check limits this to one write per `REFRESH_INTERVAL`.
	 */
	public function refresh_if_due(): void {
		$age = $this->get_age();

		if ( null !== $age && $age >= self::REFRESH_INTERVAL ) {
			$this->refresh();
		}
	}

	/**
	 * Release the product import lock.
	 */
	public function release(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", self::IMPORT_LOCK_OPTION ) );
	}

	/**
	 * Get how long ago the product import lock was taken or last refreshed.
	 *
	 * @return int|null Seconds since the lock was taken or last refreshed, or null when no import is running.
	 */
	public function get_age(): ?int {
		$value = $this->get_value();

		if ( null === $value ) {
			return null;
		}

		return max( 0, time() - (int) $value );
	}

	/**
	 * Get who holds the product import lock.
	 *
	 * @return string|null One of the `HOLDER_*` constants, or null when no import is running.
	 */
	private function get_holder(): ?string {
		return explode( ':', (string) $this->get_value() )[1] ?? null;
	}

	/**
	 * Read the lock's stored value directly, since the option cache cannot see another process taking it.
	 *
	 * @return string|null The `<timestamp>:<holder>` value, or null when no import is running.
	 */
	private function get_value(): ?string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::IMPORT_LOCK_OPTION )
		);
	}
}
