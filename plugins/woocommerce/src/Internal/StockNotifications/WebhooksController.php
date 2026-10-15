<?php
/**
 * Webhook topics for the customer stock notifications feature.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `stock_notification` webhook resource and its topics.
 *
 * This is the first Internal feature to expose webhook topics, so it doubles as the
 * reference for the next one. Core webhooks extend through four filters, all handled here:
 *
 * - `woocommerce_valid_webhook_resources` / `woocommerce_valid_webhook_events` let
 *   `wc_is_webhook_valid_topic()` accept the topics when a webhook is saved or delivered.
 * - `woocommerce_webhook_topic_hooks` maps each topic to the actions that trigger it. Every
 *   action must pass the resource ID as its first argument: `WC_Webhook::process()` only
 *   receives that argument and hands it to Action Scheduler, which later asks the REST API
 *   for the payload by ID.
 * - `woocommerce_webhook_topics` adds the labels to the webhook edit screen's dropdown.
 *
 * `WC_Webhook::get_wp_api_payload()` also needs a `case` for the resource, because the REST
 * route (`/wc/v3/stock-notifications/{id}`) does not follow the `{$resource}s` naming of the
 * core resources.
 *
 * Timing: core enqueues the active webhooks from `WC()->init()` on `init` priority 0, so the
 * topic-hooks filter must already be attached by then. That is earlier than the feature's
 * other services, which `StockNotifications::maybe_init_services()` wires on `init` priority
 * 1, so this class is registered from `StockNotifications::register()` instead and each
 * callback checks the feature itself. With the feature off, nothing is added: existing
 * `stock_notification.*` webhooks fail `wc_is_webhook_valid_topic()` and stay silent.
 *
 * The data store fires one generic `woocommerce_stock_notification_status_changed` action for
 * every transition, but `verified`, `sent` and `cancelled` are separate topics and a webhook
 * cannot see the transition. This class listens to the generic action and re-fires one
 * narrow, ID-first action per topic, which integrations can use directly as well.
 *
 * @since 11.3.0
 */
class WebhooksController {

	/**
	 * The webhook resource name, as it appears in topics (`stock_notification.created`).
	 */
	public const RESOURCE = 'stock_notification';

	/**
	 * Constructor.
	 *
	 * Hooks unconditionally; see the class docblock for why the feature check lives in the callbacks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_valid_webhook_resources', array( $this, 'add_resource' ) );
		add_filter( 'woocommerce_valid_webhook_events', array( $this, 'add_events' ) );
		add_filter( 'woocommerce_webhook_topic_hooks', array( $this, 'add_topic_hooks' ) );
		add_filter( 'woocommerce_webhook_topics', array( $this, 'add_topic_labels' ) );
		add_action( 'woocommerce_stock_notification_status_changed', array( $this, 'fire_status_actions' ), 10, 4 );
	}

	/**
	 * Get the topics and the actions that trigger each.
	 *
	 * @return array<string, string[]>
	 */
	public static function get_topic_hooks(): array {
		return array(
			self::RESOURCE . '.created'   => array( 'woocommerce_new_stock_notification' ),
			self::RESOURCE . '.verified'  => array( 'woocommerce_stock_notification_verified' ),
			self::RESOURCE . '.sent'      => array( 'woocommerce_stock_notification_sent' ),
			self::RESOURCE . '.cancelled' => array( 'woocommerce_stock_notification_cancelled' ),
			self::RESOURCE . '.deleted'   => array( 'woocommerce_delete_stock_notification' ),
		);
	}

	/**
	 * Accept `stock_notification` as a webhook resource.
	 *
	 * Params are untyped and coerced in the body because any third-party callback
	 * can pass a different value along the filter chain.
	 *
	 * @internal
	 *
	 * @param mixed $resources Valid resources.
	 * @return mixed
	 */
	public function add_resource( $resources ) {
		if ( ! is_array( $resources ) || ! StockNotifications::is_feature_option_enabled() ) {
			return $resources;
		}

		$resources[] = self::RESOURCE;

		return array_values( array_unique( $resources ) );
	}

	/**
	 * Accept the events the topics use that core does not already know.
	 *
	 * `wc_is_webhook_valid_topic()` validates resources and events independently, so this also
	 * lets a core resource save with one of these events (e.g. `order.sent`). Such a topic has
	 * no hooks and never delivers; that is a limitation of the core extension point.
	 *
	 * @internal
	 *
	 * @param mixed $events Valid events.
	 * @return mixed
	 */
	public function add_events( $events ) {
		if ( ! is_array( $events ) || ! StockNotifications::is_feature_option_enabled() ) {
			return $events;
		}

		return array_values( array_unique( array_merge( $events, array( 'verified', 'sent', 'cancelled' ) ) ) );
	}

	/**
	 * Map the topics to their actions.
	 *
	 * @internal
	 *
	 * @param mixed $topic_hooks Topic => hook names.
	 * @return mixed
	 */
	public function add_topic_hooks( $topic_hooks ) {
		if ( ! is_array( $topic_hooks ) || ! StockNotifications::is_feature_option_enabled() ) {
			return $topic_hooks;
		}

		return array_merge( $topic_hooks, self::get_topic_hooks() );
	}

	/**
	 * Label the topics in the webhook edit screen's dropdown.
	 *
	 * The labels are inserted before the `action` entry so the custom-action option stays last.
	 *
	 * @internal
	 *
	 * @param mixed $topics Topic => label.
	 * @return mixed
	 */
	public function add_topic_labels( $topics ) {
		if ( ! is_array( $topics ) || ! StockNotifications::is_feature_option_enabled() ) {
			return $topics;
		}

		$labels = array(
			self::RESOURCE . '.created'   => __( 'Stock notification created', 'woocommerce' ),
			self::RESOURCE . '.verified'  => __( 'Stock notification verified', 'woocommerce' ),
			self::RESOURCE . '.sent'      => __( 'Stock notification sent', 'woocommerce' ),
			self::RESOURCE . '.cancelled' => __( 'Stock notification cancelled', 'woocommerce' ),
			self::RESOURCE . '.deleted'   => __( 'Stock notification deleted', 'woocommerce' ),
		);

		$action = $topics['action'] ?? null;
		unset( $topics['action'] );
		$topics = array_merge( $topics, $labels );

		if ( null !== $action ) {
			$topics['action'] = $action;
		}

		return $topics;
	}

	/**
	 * Re-fire a status transition as the narrow action its webhook topic listens to.
	 *
	 * Params are untyped and coerced in the body because any third-party code can
	 * fire the source action with fewer or differently-typed arguments.
	 *
	 * @internal
	 *
	 * @param mixed $notification_id The notification ID.
	 * @param mixed $status_from     The previous status.
	 * @param mixed $status_to       The new status.
	 * @param mixed $notification    The notification.
	 */
	public function fire_status_actions( $notification_id, $status_from = '', $status_to = '', $notification = null ): void {
		if ( ! $notification instanceof Notification ) {
			return;
		}

		$notification_id = (int) $notification_id;

		if ( NotificationStatus::PENDING === $status_from && NotificationStatus::ACTIVE === $status_to ) {
			/**
			 * Action: woocommerce_stock_notification_verified
			 *
			 * Fires when a pending stock notification becomes active: the shopper confirmed the
			 * double opt-in email, signed up again while double opt-in was off, or an admin activated
			 * it. Re-activating a cancelled notification is not a verification and only fires
			 * `woocommerce_stock_notification_status_changed`.
			 *
			 * @since 11.3.0
			 *
			 * @param int          $notification_id The notification ID.
			 * @param Notification $notification    The notification.
			 */
			do_action( 'woocommerce_stock_notification_verified', $notification_id, $notification );
			return;
		}

		if ( NotificationStatus::SENT === $status_to ) {
			/**
			 * Action: woocommerce_stock_notification_sent
			 *
			 * Fires when a stock notification's status becomes sent, after the back-in-stock email
			 * went out, whether the processor or an admin sent it.
			 *
			 * @since 11.3.0
			 *
			 * @param int          $notification_id The notification ID.
			 * @param Notification $notification    The notification.
			 */
			do_action( 'woocommerce_stock_notification_sent', $notification_id, $notification );
			return;
		}

		if ( NotificationStatus::CANCELLED === $status_to ) {
			/**
			 * Action: woocommerce_stock_notification_cancelled
			 *
			 * Fires when a stock notification is cancelled, whatever cancelled it: the shopper
			 * unsubscribed, an admin cancelled it, the send failed, or a privacy erasure request
			 * anonymized it. Read `$notification->get_cancellation_source()` to tell them apart.
			 * After a privacy erasure the notification no longer identifies the shopper.
			 *
			 * @since 11.3.0
			 *
			 * @param int          $notification_id The notification ID.
			 * @param Notification $notification    The notification.
			 */
			do_action( 'woocommerce_stock_notification_cancelled', $notification_id, $notification );
		}
	}
}
