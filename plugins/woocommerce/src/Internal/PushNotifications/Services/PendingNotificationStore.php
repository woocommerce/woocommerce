<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Throwable;

/**
 * Store that collects notifications during a request and dispatches them all on
 * shutdown via the InternalNotificationDispatcher. Should be accessed from the
 * container (`wc_get_container`) to ensure store is shared by all usage.
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
	 * The dispatcher that will be used to send notifications on shutdown.
	 *
	 * @var InternalNotificationDispatcher
	 */
	private InternalNotificationDispatcher $dispatcher;

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
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param InternalNotificationDispatcher $dispatcher The dispatcher to use on shutdown.
	 *
	 * @since 10.7.0
	 */
	final public function init( InternalNotificationDispatcher $dispatcher ): void {
		$this->dispatcher = $dispatcher;
	}

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

		// Capture the trigger time now, but persist it on shutdown (see
		// dispatch_all()). Writing here would land inside the order-creation
		// call stack, where an HPOS meta save can escalate into a full
		// $order->save() on an order whose line items have not been written yet.
		$notification->set_triggered_at( time() );

		$this->schedule_safety_net( $notification );

		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', array( $this, 'dispatch_all' ) );
			$this->shutdown_registered = true;
		}
	}

	/**
	 * Schedules an ActionScheduler safety net job for the notification.
	 *
	 * If the shutdown hook never fires (OOM, SIGKILL, etc.), this job
	 * guarantees the notification is still processed.
	 *
	 * @param Notification $notification The notification to schedule.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	private function schedule_safety_net( Notification $notification ): void {
		// Canonical, identity-keyed args shared with NotificationProcessor::cancel_safety_net().
		// Action Scheduler matches stored args by exact equality, so both sides must derive
		// them from the same place; see Notification::get_safety_net_args().
		$args = $notification->get_safety_net_args();

		if ( as_has_scheduled_action( NotificationProcessor::SAFETY_NET_HOOK, $args, NotificationProcessor::ACTION_SCHEDULER_GROUP ) ) {
			return;
		}

		as_schedule_single_action(
			time() + NotificationProcessor::SAFETY_NET_DELAY,
			NotificationProcessor::SAFETY_NET_HOOK,
			$args,
			NotificationProcessor::ACTION_SCHEDULER_GROUP,
			true
		);
	}

	/**
	 * Dispatches all pending notifications via InternalNotificationDispatcher.
	 *
	 * Called on shutdown. Records each notification's trigger time, sends all
	 * pending notifications through the InternalNotificationDispatcher, then
	 * clears the store.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function dispatch_all(): void {
		if ( empty( $this->pending ) ) {
			return;
		}

		$this->record_trigger_times();

		$this->dispatcher->dispatch( array_values( $this->pending ) );

		$this->enabled = false;
		$this->pending = array();
	}

	/**
	 * Persists each pending notification's trigger time to its resource.
	 *
	 * Runs on shutdown rather than at trigger time so the write stays out of
	 * the order-creation call stack. WordPress fires `shutdown` on fatal errors
	 * too, so the only requests that lose the value are those killed outright
	 * (OOM, SIGKILL), the same requests the safety net exists for, and where
	 * the payload falls back to the current time.
	 *
	 * The recorded time stays that of the first trigger. Core fires the stock
	 * hooks on every reduction past the threshold, not only on the crossing, so
	 * a product that keeps selling while its notification is in flight would
	 * otherwise drag its own recorded time forward and understate its delivery
	 * age. The sent marker is checked as well because a send clears the trigger
	 * time, so a repeat trigger after one would recreate a value that nothing
	 * clears again.
	 *
	 * Failures are caught so that a third-party handler on the resource's save
	 * hooks cannot stop the dispatch below from running.
	 *
	 * @return void
	 *
	 * @since 11.2.0
	 */
	private function record_trigger_times(): void {
		foreach ( $this->pending as $notification ) {
			try {
				if ( $notification->has_meta( NotificationProcessor::SENT_META_KEY )
					|| $notification->has_meta( NotificationProcessor::TRIGGERED_META_KEY ) ) {
					continue;
				}

				$notification->write_meta(
					NotificationProcessor::TRIGGERED_META_KEY,
					$notification->get_triggered_at()
				);
			} catch ( Throwable $e ) {
				wc_get_logger()->warning(
					sprintf(
						'Failed to record push notification trigger time (type=%s, resource_id=%d): %s',
						$notification->get_type(),
						$notification->get_resource_id(),
						$e->getMessage()
					),
					array( 'source' => PushNotifications::FEATURE_NAME )
				);
			}
		}
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
