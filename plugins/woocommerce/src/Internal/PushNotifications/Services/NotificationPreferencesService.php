<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;

/**
 * Manages per-user push notification preferences.
 *
 * Owns the domain logic — the default preference values and how arbitrary
 * input is sanitized — and delegates persistence to
 * `NotificationPreferencesDataStore`.
 *
 * @since 10.8.0
 */
class NotificationPreferencesService {
	/**
	 * The data store used for persistence.
	 *
	 * @var NotificationPreferencesDataStore
	 */
	private NotificationPreferencesDataStore $data_store;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param NotificationPreferencesDataStore $data_store The data store.
	 *
	 * @since 10.8.0
	 */
	final public function init( NotificationPreferencesDataStore $data_store ): void {
		$this->data_store = $data_store;
	}

	/**
	 * Retrieve a user's notification preferences.
	 *
	 * Falls back to defaults for users with no stored preferences. Stored
	 * preferences are overlaid on top of the defaults so that any newer keys
	 * not yet on disk are filled in.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array<string, bool> Flat preferences map (preference key => enabled).
	 *
	 * @since 10.8.0
	 */
	public function get_preferences( int $user_id ): array {
		$envelope = $this->data_store->read( $user_id );

		if ( null === $envelope ) {
			return $this->get_defaults();
		}

		$stored = isset( $envelope['preferences'] ) && is_array( $envelope['preferences'] )
			? $envelope['preferences']
			: array();

		return $this->sanitize( array_merge( $this->get_defaults(), $stored ) );
	}

	/**
	 * Persist a partial update to a user's notification preferences.
	 *
	 * Unknown preference keys are dropped; values are coerced to boolean.
	 * The merged result is wrapped in the current versioned envelope and
	 * handed to the data store.
	 *
	 * @param int                 $user_id     The user ID.
	 * @param array<string, bool> $preferences Partial preferences to merge over existing values.
	 *
	 * @return array<string, bool> The merged, sanitized preferences map after the save.
	 *
	 * @throws \WC_Data_Exception Propagated from the data store on real persistence failure.
	 *
	 * @since 10.8.0
	 */
	public function save_preferences( int $user_id, array $preferences ): array {
		$current = $this->get_preferences( $user_id );
		$merged  = $this->sanitize( array_merge( $current, $preferences ) );

		// Data store throws WC_Data_Exception on real failure; let it propagate.
		$this->data_store->write(
			$user_id,
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => $merged,
			)
		);

		return $merged;
	}

	/**
	 * Return the default preferences for a new user.
	 *
	 * The keyset is derived from `Notification::NOTIFICATION_CLASSES` so that
	 * adding a new notification type automatically opts it into preferences
	 * with `true` as the default — no parallel list to keep in sync.
	 *
	 * @return array<string, bool> Flat defaults map.
	 *
	 * @since 10.8.0
	 */
	public function get_defaults(): array {
		return array_fill_keys( array_keys( Notification::NOTIFICATION_CLASSES ), true );
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
