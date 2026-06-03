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
	 * @return array<string, array<string, mixed>> Map of preference key => sub-options.
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

		return $this->sanitize( array_replace_recursive( $this->get_defaults(), $stored ) );
	}

	/**
	 * Persist a partial update to a user's notification preferences.
	 *
	 * Unknown top-level keys and unknown sub-fields per key are dropped.
	 * The merged result is wrapped in the current versioned envelope and
	 * handed to the data store.
	 *
	 * @param int                                 $user_id     The user ID.
	 * @param array<string, array<string, mixed>> $preferences Partial preferences to merge over existing values.
	 *
	 * @return array<string, array<string, mixed>> The merged, sanitized preferences map after the save.
	 *
	 * @throws \WC_Data_Exception Propagated from the data store on real persistence failure.
	 *
	 * @since 10.8.0
	 */
	public function save_preferences( int $user_id, array $preferences ): array {
		$current = $this->get_preferences( $user_id );
		$merged  = $this->sanitize( array_replace_recursive( $current, $preferences ) );

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
	 * Each preference is a small object so future fields (thresholds, sub-toggles)
	 * can be added without bumping the schema version. The keyset is derived from
	 * `Notification::NOTIFICATION_CLASSES` so adding a new notification type
	 * automatically opts it into preferences — no parallel list to keep in sync.
	 *
	 * @return array<string, array<string, mixed>> Map of preference key => default sub-options.
	 *
	 * @since 10.8.0
	 */
	public function get_defaults(): array {
		$defaults = array();
		foreach ( array_keys( Notification::NOTIFICATION_CLASSES ) as $type ) {
			$defaults[ $type ] = array( 'enabled' => true );
		}

		$defaults['store_order']['min_amount']   = null;
		$defaults['store_review']['max_rating']  = null;
		$defaults['store_stock']['low_stock']    = true;
		$defaults['store_stock']['out_of_stock'] = true;
		$defaults['store_stock']['on_backorder'] = true;

		return $defaults;
	}

	/**
	 * Drop unknown top-level keys and unknown sub-fields per key, coercing
	 * known sub-fields to their expected types.
	 *
	 * @param array $preferences Arbitrary preferences map.
	 *
	 * @return array<string, array<string, mixed>> Sanitized preferences.
	 */
	private function sanitize( array $preferences ): array {
		$allowed   = $this->get_defaults();
		$sanitized = array();

		foreach ( $allowed as $key => $default_shape ) {
			$value             = $preferences[ $key ] ?? array();
			$value             = is_array( $value ) ? $value : array();
			$sanitized[ $key ] = $this->sanitize_value( $key, $value, $default_shape );
		}

		return $sanitized;
	}

	/**
	 * Apply per-key sanitization to a single preference's sub-options.
	 *
	 * Unknown sub-keys are dropped; missing sub-keys fall back to their default.
	 * Today only `enabled` is recognized; future preference types extend this method
	 * (or its dispatch) to validate their additional sub-fields.
	 *
	 * @param string               $key           Preference key (e.g. `store_order`).
	 * @param array                $value         Submitted sub-options for the key.
	 * @param array<string, mixed> $default_shape Default sub-options for the key.
	 *
	 * @return array<string, mixed>
	 */
	protected function sanitize_value( string $key, array $value, array $default_shape ): array {
		unset( $key );

		$sanitized = array();

		foreach ( $default_shape as $sub_key => $sub_default ) {
			if ( 'enabled' === $sub_key ) {
				$sanitized[ $sub_key ] = array_key_exists( $sub_key, $value )
					? (bool) $value[ $sub_key ]
					: (bool) $sub_default;
				continue;
			}

			if ( 'min_amount' === $sub_key ) {
				if ( ! array_key_exists( $sub_key, $value ) || null === $value[ $sub_key ] ) {
					$sanitized[ $sub_key ] = null;
					continue;
				}
				$amount                = (float) $value[ $sub_key ];
				$sanitized[ $sub_key ] = $amount > 0 ? $amount : null;
				continue;
			}

			if ( 'max_rating' === $sub_key ) {
				if ( ! array_key_exists( $sub_key, $value ) || null === $value[ $sub_key ] ) {
					$sanitized[ $sub_key ] = null;
					continue;
				}
				$rating                = (int) $value[ $sub_key ];
				$sanitized[ $sub_key ] = ( $rating >= 1 && $rating <= 5 ) ? $rating : null;
				continue;
			}

			if ( in_array( $sub_key, array( 'low_stock', 'out_of_stock', 'on_backorder' ), true ) ) {
				$sanitized[ $sub_key ] = array_key_exists( $sub_key, $value )
					? (bool) $value[ $sub_key ]
					: (bool) $sub_default;
				continue;
			}
		}//end foreach

		return $sanitized;
	}
}
