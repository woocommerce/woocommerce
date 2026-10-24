<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;

/**
 * Deletes a user's push tokens and notification preferences when they are
 * deleted or removed from the site.
 *
 * WordPress does not do this for us. `wp_delete_user()` reassigns every post a
 * user owns when an administrator chooses "Attribute all content to", ignoring
 * the post type's `delete_with_user` setting, and `remove_user_from_blog()`
 * never deletes posts at all. A token that survives either route keeps the site
 * sending to a device whose owner has gone, under whichever eligible user now
 * owns the record.
 *
 * Both hooks used here fire before the reassignment, so deleting from them
 * applies whichever option the administrator picks.
 *
 * @internal
 *
 * @since 11.2.0
 */
class UserDataCleanupService {
	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $push_tokens_data_store;

	/**
	 * The notification preferences data store.
	 *
	 * @var NotificationPreferencesDataStore
	 */
	private NotificationPreferencesDataStore $preferences_data_store;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param PushTokensDataStore              $push_tokens_data_store The push tokens data store.
	 * @param NotificationPreferencesDataStore $preferences_data_store The notification preferences data store.
	 *
	 * @since 11.2.0
	 */
	final public function init(
		PushTokensDataStore $push_tokens_data_store,
		NotificationPreferencesDataStore $preferences_data_store
	): void {
		$this->push_tokens_data_store = $push_tokens_data_store;
		$this->preferences_data_store = $preferences_data_store;
	}

	/**
	 * Registers the WordPress hooks for user deletion and removal.
	 *
	 * `wpmu_delete_user` needs no handling of its own because it calls
	 * `remove_user_from_blog()` for every site the user belongs to.
	 *
	 * @return void
	 *
	 * @since 11.2.0
	 */
	public function register(): void {
		add_action( 'delete_user', array( $this, 'handle_delete_user' ) );
		add_action( 'remove_user_from_blog', array( $this, 'handle_remove_user_from_blog' ) );
	}

	/**
	 * Handles the delete_user hook.
	 *
	 * @internal
	 *
	 * @param int $user_id The ID of the user being deleted.
	 * @return void
	 *
	 * @since 11.2.0
	 */
	public function handle_delete_user( int $user_id ): void {
		$this->delete_data_for_user( $user_id );
	}

	/**
	 * Handles the remove_user_from_blog hook.
	 *
	 * WordPress fires this inside `switch_to_blog()`, so the site-scoped reads
	 * and writes below already target the site the user is leaving.
	 *
	 * @internal
	 *
	 * @param int $user_id The ID of the user being removed from the site.
	 * @return void
	 *
	 * @since 11.2.0
	 */
	public function handle_remove_user_from_blog( int $user_id ): void {
		$this->delete_data_for_user( $user_id );
	}

	/**
	 * Deletes both kinds of stored push notification data for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	private function delete_data_for_user( int $user_id ): void {
		$this->push_tokens_data_store->delete_for_user( $user_id );
		$this->preferences_data_store->delete( $user_id );
	}
}
