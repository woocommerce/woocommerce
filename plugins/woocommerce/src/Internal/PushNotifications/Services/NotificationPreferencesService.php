<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Manages per-user push notification preferences.
 *
 * Preferences are stored in user meta under a single versioned envelope,
 * enabling forward-compatible schema migrations without losing merchant choices.
 *
 * @since 10.8.0
 */
class NotificationPreferencesService {
	/**
	 * User meta key under which the preferences envelope is stored.
	 */
	const META_KEY = 'wc_push_notification_preferences';

	/**
	 * Current preferences schema version.
	 *
	 * Bump when the `preferences` shape changes, and add a corresponding
	 * branch to `migrate()`.
	 */
	const CURRENT_SCHEMA_VERSION = 1;

	/**
	 * Retrieve a user's notification preferences.
	 *
	 * Falls back to defaults for users with no stored preferences. If the
	 * stored envelope is older than the current schema version, it is migrated
	 * and persisted before being returned.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array<string, bool> Flat preferences map (preference key => enabled).
	 *
	 * @since 10.8.0
	 */
	public function get_preferences( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return $this->get_defaults();
		}

		$stored_version = isset( $stored['schema_version'] ) ? (int) $stored['schema_version'] : 0;

		if ( $stored_version < self::CURRENT_SCHEMA_VERSION ) {
			$stored = $this->migrate( $stored, $stored_version );
			update_user_meta( $user_id, self::META_KEY, $stored );
		}

		$preferences = isset( $stored['preferences'] ) && is_array( $stored['preferences'] )
			? $stored['preferences']
			: array();

		return $this->sanitize( array_merge( $this->get_defaults(), $preferences ) );
	}

	/**
	 * Persist a partial update to a user's notification preferences.
	 *
	 * Unknown preference keys are dropped; values are coerced to boolean.
	 * The merged result is stored inside the current versioned envelope.
	 *
	 * @param int                 $user_id     The user ID.
	 * @param array<string, bool> $preferences Partial preferences to merge over existing values.
	 *
	 * @return bool True when the user meta value changed, false otherwise.
	 *
	 * @since 10.8.0
	 */
	public function save_preferences( int $user_id, array $preferences ): bool {
		$current = $this->get_preferences( $user_id );
		$merged  = $this->sanitize( array_merge( $current, $preferences ) );

		$envelope = array(
			'schema_version' => self::CURRENT_SCHEMA_VERSION,
			'preferences'    => $merged,
		);

		return (bool) update_user_meta( $user_id, self::META_KEY, $envelope );
	}

	/**
	 * Return the default preferences for a new user.
	 *
	 * @return array<string, bool> Flat defaults map.
	 *
	 * @since 10.8.0
	 */
	public function get_defaults(): array {
		return array(
			'store_order'  => true,
			'store_review' => true,
		);
	}

	/**
	 * Migrate a stored preferences envelope up to the current schema version.
	 *
	 * Intended to be a pure transformation: callers are responsible for
	 * persisting the returned envelope if needed. Missing or malformed
	 * `preferences` entries are replaced with defaults.
	 *
	 * @param array $data         The stored envelope (expected keys: `schema_version`, `preferences`).
	 * @param int   $from_version The schema version currently on disk.
	 *
	 * @return array The envelope upgraded to `self::CURRENT_SCHEMA_VERSION`.
	 *
	 * @since 10.8.0
	 */
	public function migrate( array $data, int $from_version ): array {
		$preferences = isset( $data['preferences'] ) && is_array( $data['preferences'] )
			? $data['preferences']
			: $this->get_defaults();

		// Future schema bumps add cases here (e.g. `if ( $from_version < 2 ) { ... }`).
		// For v1 the envelope shape is stable; we only normalize the version tag.

		return array(
			'schema_version' => self::CURRENT_SCHEMA_VERSION,
			'preferences'    => $preferences,
		);
	}

	/**
	 * Drop unknown keys and coerce values to boolean.
	 *
	 * @param array $preferences Arbitrary preferences map.
	 *
	 * @return array<string, bool> Sanitized preferences restricted to known default keys.
	 */
	private function sanitize( array $preferences ): array {
		$allowed   = $this->get_defaults();
		$sanitized = array();

		foreach ( $allowed as $key => $default ) {
			$sanitized[ $key ] = array_key_exists( $key, $preferences )
				? (bool) $preferences[ $key ]
				: $default;
		}

		return $sanitized;
	}
}
