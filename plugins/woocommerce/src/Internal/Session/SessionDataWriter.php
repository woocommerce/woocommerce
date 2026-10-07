<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Session;

/**
 * Saves a session row without reverting changes another request saved after this one loaded the session.
 *
 * Each request loads the whole session when it starts. Writing all of it back on save reverts anything other
 * requests saved in the meantime, so only the keys this request changed are applied to the stored row. When two
 * requests change the same key, the last one to save wins.
 *
 * @since 11.3.0
 */
class SessionDataWriter {

	/**
	 * Save session data.
	 *
	 * Without a loaded snapshot the session overwrites the stored row. With one, only the keys that changed since it
	 * was loaded are applied to the stored row.
	 *
	 * @param string     $table       Sessions table name.
	 * @param string     $session_key Session key (customer ID).
	 * @param int        $expiry      Session expiry timestamp.
	 * @param array      $current     Session data in this request.
	 * @param array|null $loaded      Session data as this request loaded it, or null to overwrite the stored row.
	 * @return array The session data that was saved.
	 */
	public function save( string $table, string $session_key, int $expiry, array $current, ?array $loaded ): array {
		if ( null === $loaded ) {
			$this->upsert( $table, $session_key, $expiry, $current );
			return $current;
		}

		// Usually no other request saved in the meantime, so the row still holds what this request loaded.
		if ( $this->write_if_unchanged( $table, $session_key, $expiry, $current, $loaded ) ) {
			return $current;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
		$stored = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$table} WHERE session_key = %s", $session_key ) ) );
		$data   = $this->apply_changes( is_array( $stored ) ? $stored : array(), $current, $loaded );

		$this->upsert( $table, $session_key, $expiry, $data );
		return $data;
	}

	/**
	 * Apply the keys this request added, changed, or removed to the stored session data.
	 *
	 * @param array $stored  Session data in storage now.
	 * @param array $current Session data in this request.
	 * @param array $loaded  Session data as this request loaded it.
	 * @return array
	 */
	private function apply_changes( array $stored, array $current, array $loaded ): array {
		foreach ( array_keys( $current + $loaded ) as $key ) {
			$value = $current[ $key ] ?? null;

			if ( ( $loaded[ $key ] ?? null ) === $value ) {
				continue;
			}

			if ( null === $value ) {
				unset( $stored[ $key ] );
			} else {
				$stored[ $key ] = $value;
			}
		}

		return $stored;
	}

	/**
	 * Write the row only if it still holds the data this request loaded.
	 *
	 * @param string $table       Sessions table name.
	 * @param string $session_key Session key (customer ID).
	 * @param int    $expiry      Session expiry timestamp.
	 * @param array  $data        Session data to write.
	 * @param array  $loaded      Session data as this request loaded it. Empty if the row should not exist yet.
	 * @return bool Whether the row was written.
	 */
	private function write_if_unchanged( string $table, string $session_key, int $expiry, array $data, array $loaded ): bool {
		global $wpdb;

		if ( empty( $loaded ) ) {
			return (bool) $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
					"INSERT IGNORE INTO {$table} (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)",
					$session_key,
					maybe_serialize( $data ),
					$expiry
				)
			);
		}

		return (bool) $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
				"UPDATE {$table} SET `session_value` = %s, `session_expiry` = %d WHERE `session_key` = %s AND `session_value` = %s",
				maybe_serialize( $data ),
				$expiry,
				$session_key,
				maybe_serialize( $loaded )
			)
		);
	}

	/**
	 * Write the row, replacing whatever is stored.
	 *
	 * @param string $table       Sessions table name.
	 * @param string $session_key Session key (customer ID).
	 * @param int    $expiry      Session expiry timestamp.
	 * @param array  $data        Session data to write.
	 */
	private function upsert( string $table, string $session_key, int $expiry, array $data ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
				"INSERT INTO {$table} (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
				ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
				$session_key,
				maybe_serialize( $data ),
				$expiry
			)
		);
	}
}
