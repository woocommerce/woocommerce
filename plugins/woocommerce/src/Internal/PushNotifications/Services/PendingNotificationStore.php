<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;

/**
 * Store that collects notifications during a request and dispatches them all on
 * on shutdown. Should be accessed from the container (`wc_get_container`) to
 * ensure store is shared by all usage.
 *
 * Notifications are keyed by `{type}_{resource_id}` (with blog ID from
 * `get_current_blog_id()`) to prevent duplicates within a single request.
 *
 * @since 10.7.0
 */
class PendingNotificationStore {
	/**
	 * Whether the store is enabled and accepting notifications.
	 *
	 * @var bool
	 */
	private bool $enabled = false;

	/**
	 * Pending notifications keyed by identifier.
	 *
	 * @var array<string, Notification>
	 */
	private array $pending = array();

	/**
	 * Whether the shutdown hook has been registered.
	 *
	 * @var bool
	 */
	private bool $shutdown_registered = false;

	/**
	 * Enables the store so it accepts notifications.
	 *
	 * Called from PushNotifications::on_init() after enablement checks pass.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		$this->enabled = true;
	}

	/**
	 * Adds a notification to the pending store.
	 *
	 * Duplicate notifications (same type and resource ID) within a single
	 * request are silently ignored. The shutdown hook is registered on the
	 * first call.
	 *
	 * @param Notification $notification The notification to add.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function add( Notification $notification ): void {
		if ( ! $this->enabled ) {
			return;
		}

		$key = $notification->get_identifier();

		if ( isset( $this->pending[ $key ] ) ) {
			return;
		}

		$this->pending[ $key ] = $notification;

		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', array( $this, 'dispatch_all' ) );
			$this->shutdown_registered = true;
		}
	}

	/**
	 * Dispatches all pending notifications by firing an action hook.
	 *
	 * Called on shutdown. Fires the `wc_push_notifications_dispatch` action
	 * with the array of pending notifications, then clears the store.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function dispatch_all(): void {
		if ( empty( $this->pending ) ) {
			return;
		}

		$notifications = array_values( $this->pending );

		/**
		 * Fires when pending push notifications are ready to be dispatched.
		 *
		 * @param Notification[] $notifications The notifications to dispatch.
		 *
		 * @since 10.7.0
		 *
		 * The call to dispatch the notifications will go here.
		 */

		/**
		 * Store is single-use per request lifecycle, so disable it and clear
		 * pending notifications.
		 */
		$this->enabled = false;
		$this->pending = array();
	}

	/**
	 * Returns the number of pending notifications.
	 *
	 * @return int
	 *
	 * @since 10.7.0
	 */
	public function count(): int {
		return count( $this->pending );
	}

	/**
	 * Returns all pending notifications.
	 *
	 * @return Notification[]
	 *
	 * @since 10.7.0
	 */
	public function get_all(): array {
		return array_values( $this->pending );
	}
}
