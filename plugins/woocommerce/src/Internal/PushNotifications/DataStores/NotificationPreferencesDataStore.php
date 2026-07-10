<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Data_Exception;
use WP_Http;

/**
 * Persistence layer for per-user push notification preferences.
 *
 * Stores a single versioned envelope per user under the `wc_push_notification_preferences`
 * user meta key. The key is automatically scoped to the current site by `Users::*_site_user_meta`,
 * which prefixes the underlying meta key with the blog ID so preferences set on one site in a
 * multisite network do not leak to other sites the same user belongs to. Owns schema migration
 * on read and surfaces real DB write failures via `WC_Data_Exception`.
 *
 * @since 10.8.0
 */
class NotificationPreferencesDataStore {
	/**
	 * User meta key under which the preferences envelope is stored.
	 */
	const META_KEY = 'wc_push_notification_preferences';

	/**
	 * Current preferences schema version.
	 *
	 * Bump when the envelope's `preferences` shape changes, and add a
	 * corresponding branch to `migrate()`.
	 */
	const CURRENT_SCHEMA_VERSION = 1;

	/**
	 * Read the stored envelope for a user.
	 *
	 * Migrates older schema versions on the fly and persists the upgrade so
	 * callers always receive a current-version envelope. Returns null when
	 * nothing is stored for the user.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array|null Envelope with `schema_version` and `preferences` keys, or null if unstored.
	 *
	 * @throws WC_Data_Exception When persisting a migrated envelope fails.
	 *
	 * @since 10.8.0
	 */
	public function read( int $user_id ): ?array {
		$stored = Users::get_site_user_meta( $user_id, self::META_KEY );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return null;
		}

		$stored_version = isset( $stored['schema_version'] ) ? (int) $stored['schema_version'] : 0;

		if ( $stored_version < self::CURRENT_SCHEMA_VERSION ) {
			$stored = $this->migrate( $stored, $stored_version );
			$this->write( $user_id, $stored );
		}

		return $stored;
	}

	/**
	 * Persist an envelope for a user.
	 *
	 * No-ops when the stored value already matches the supplied envelope.
	 * Throws `WC_Data_Exception` when the underlying user meta write fails
	 * for a reason other than the value being unchanged.
	 *
	 * @param int   $user_id  The user ID.
	 * @param array $envelope The envelope to persist (must have `schema_version` and `preferences` keys).
	 *
	 * @return void
	 *
	 * @throws WC_Data_Exception When the user meta write fails for a non-no-op reason.
	 *
	 * @since 10.8.0
	 */
	public function write( int $user_id, array $envelope ): void {
		// Skip the write when the stored envelope already matches. This avoids
		// the ambiguous `false` return from update_user_meta() that means
		// either "value unchanged" or "DB write failed" — by short-circuiting
		// the no-op case, a `false` from the call below unambiguously means
		// the write itself failed and we can surface it.
		$stored = Users::get_site_user_meta( $user_id, self::META_KEY );
		if ( $stored === $envelope ) {
			return;
		}

		$result = Users::update_site_user_meta( $user_id, self::META_KEY, $envelope );

		if ( false === $result ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new WC_Data_Exception(
				'woocommerce_push_notification_preferences_save_failed',
				'Failed to save push notification preferences.',
				WP_Http::INTERNAL_SERVER_ERROR
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Upgrade an envelope to the current schema version.
	 *
	 * Pure transformation — does not persist. Missing or malformed
	 * `preferences` entries are replaced with an empty array; the service
	 * layer is responsible for filling in defaults from the empty case.
	 *
	 * @param array $data         The stored envelope (expected keys: `schema_version`, `preferences`).
	 * @param int   $from_version The schema version currently on disk.
	 *
	 * @return array Envelope upgraded to `self::CURRENT_SCHEMA_VERSION`.
	 *
	 * @since 10.8.0
	 */
	public function migrate( array $data, int $from_version ): array {
		// Parameter reserved for future schema migrations.
		unset( $from_version );

		$preferences = isset( $data['preferences'] ) && is_array( $data['preferences'] )
			? $data['preferences']
			: array();

		// For v1 the envelope shape is stable; we only normalize the version tag.

		return array(
			'schema_version' => self::CURRENT_SCHEMA_VERSION,
			'preferences'    => $preferences,
		);
	}
}
