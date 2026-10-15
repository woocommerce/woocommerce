<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotificationsRestController;
use Automattic\WooCommerce\Internal\StockNotifications\WebhooksController;
use WC_Webhook;

/**
 * WebhooksController tests.
 *
 * The controller is registered at bootstrap from `StockNotifications::register()` and its
 * callbacks check the feature option themselves, so these tests exercise the live wiring
 * instead of instantiating a second controller.
 */
class WebhooksControllerTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Every topic validates when the feature is on.
	 */
	public function test_topics_are_valid_when_feature_is_enabled() {
		foreach ( array_keys( WebhooksController::get_topic_hooks() ) as $topic ) {
			$this->assertTrue( wc_is_webhook_valid_topic( $topic ), $topic );
		}
	}

	/**
	 * Nothing is registered when the feature is off.
	 */
	public function test_nothing_is_registered_when_feature_is_disabled() {
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$this->assertFalse( wc_is_webhook_valid_topic( 'stock_notification.created' ) );
		$this->assertArrayNotHasKey( 'stock_notification.created', WC_Webhook::get_default_topic_hooks() );
		$this->assertArrayNotHasKey( 'stock_notification.created', apply_filters( 'woocommerce_webhook_topics', array( 'action' => 'Action' ) ) );
	}

	/**
	 * Each topic maps to one ID-first lifecycle action.
	 */
	public function test_topic_hooks_are_registered() {
		$topic_hooks = WC_Webhook::get_default_topic_hooks();

		$this->assertSame( array( 'woocommerce_new_stock_notification' ), $topic_hooks['stock_notification.created'] );
		$this->assertSame( array( 'woocommerce_stock_notification_verified' ), $topic_hooks['stock_notification.verified'] );
		$this->assertSame( array( 'woocommerce_stock_notification_sent' ), $topic_hooks['stock_notification.sent'] );
		$this->assertSame( array( 'woocommerce_stock_notification_cancelled' ), $topic_hooks['stock_notification.cancelled'] );
		$this->assertSame( array( 'woocommerce_delete_stock_notification' ), $topic_hooks['stock_notification.deleted'] );
		$this->assertSame( array( 'woocommerce_new_order' ), $topic_hooks['order.created'], 'Core topics are untouched' );
	}

	/**
	 * The dropdown labels are added before the custom-action entry.
	 */
	public function test_topic_labels_keep_action_last() {
		$topics = apply_filters(
			'woocommerce_webhook_topics',
			array(
				'order.created' => 'Order created',
				'action'        => 'Action',
			)
		);

		$this->assertSame( 'Stock notification created', $topics['stock_notification.created'] );
		$this->assertSame( 'Stock notification verified', $topics['stock_notification.verified'] );
		$this->assertSame( 'Stock notification sent', $topics['stock_notification.sent'] );
		$this->assertSame( 'Stock notification cancelled', $topics['stock_notification.cancelled'] );
		$this->assertSame( 'Stock notification deleted', $topics['stock_notification.deleted'] );
		$this->assertSame( 'action', array_key_last( $topics ) );
	}

	/**
	 * The payload is fetched from the v3 route whatever API version the webhook uses.
	 */
	public function test_payload_is_requested_from_v3_route() {
		$routes = array();
		add_filter(
			'rest_pre_dispatch',
			function ( $result, $server, $request ) use ( &$routes ) {
				$routes[] = $request->get_route();
				return new \WP_REST_Response( array( 'id' => 123 ) );
			},
			10,
			3
		);

		$webhook = new WC_Webhook();
		$webhook->set_topic( 'stock_notification.created' );
		$webhook->set_api_version( 1 );
		$webhook->set_user_id( 1 );

		$this->assertSame( array( 'id' => 123 ), $webhook->build_payload( 123 ) );
		$this->assertSame( array( '/wc/v3/stock-notifications/123' ), $routes );
	}

	/**
	 * The payload of a created notification is its REST representation, built as the webhook's user.
	 */
	public function test_payload_is_the_rest_representation() {
		// WC_Unit_Test_Case keeps a server from an earlier test; rebuild it so `rest_api_init` fires again.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Rebuilt for this test only; the next setUp resets it again.
		$GLOBALS['wp_rest_server'] = null;
		add_action(
			'rest_api_init',
			function () {
				( new StockNotificationsRestController() )->register_routes();
			}
		);
		$notification = $this->create_notification( NotificationStatus::ACTIVE );

		$webhook = new WC_Webhook();
		$webhook->set_topic( 'stock_notification.created' );
		$webhook->set_api_version( 3 );
		$webhook->set_user_id( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );

		$payload = $webhook->build_payload( $notification->get_id() );

		$this->assertSame( $notification->get_id(), $payload['id'] );
		$this->assertSame( NotificationStatus::ACTIVE, $payload['status'] );
		$this->assertSame( $notification->get_user_email(), $payload['user_email'] );
	}

	/**
	 * A deleted notification's payload is just its ID, without a REST lookup.
	 */
	public function test_deleted_payload_is_the_id() {
		$webhook = new WC_Webhook();
		$webhook->set_topic( 'stock_notification.deleted' );
		$webhook->set_api_version( 3 );
		$webhook->set_user_id( 1 );

		$this->assertSame( array( 'id' => 456 ), $webhook->build_payload( 456 ) );
	}

	/**
	 * Only a pending -> active transition is a verification.
	 */
	public function test_verified_fires_only_for_pending_to_active() {
		$fired = $this->record_action( 'woocommerce_stock_notification_verified' );

		$notification = $this->create_notification( NotificationStatus::PENDING );
		$this->set_status( $notification, NotificationStatus::ACTIVE );
		$this->assertSame( array( $notification->get_id() ), $fired() );

		$this->set_status( $notification, NotificationStatus::CANCELLED );
		$this->set_status( $notification, NotificationStatus::ACTIVE );
		$this->assertCount( 1, $fired(), 'Re-activating a cancelled notification is not a verification' );
	}

	/**
	 * Sent and cancelled fire once per transition, with the ID first; creating fires neither.
	 */
	public function test_sent_and_cancelled_fire_once_per_transition() {
		$sent      = $this->record_action( 'woocommerce_stock_notification_sent' );
		$cancelled = $this->record_action( 'woocommerce_stock_notification_cancelled' );

		$notification = $this->create_notification( NotificationStatus::ACTIVE );
		$this->assertSame( array(), $sent() );
		$this->assertSame( array(), $cancelled() );

		$this->set_status( $notification, NotificationStatus::SENT );
		$this->assertSame( array( $notification->get_id() ), $sent() );

		$this->set_status( $notification, NotificationStatus::CANCELLED );
		$this->assertSame( array( $notification->get_id() ), $cancelled() );
		$this->assertCount( 1, $sent() );
	}

	/**
	 * An active webhook on a topic is queued with the notification ID when the event happens.
	 */
	public function test_webhook_is_queued_with_the_notification_id() {
		$delivered = array();
		$webhook   = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'stock_notification.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );

		remove_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10 );
		add_action(
			'woocommerce_webhook_process_delivery',
			function ( $delivering_webhook, $arg ) use ( $webhook, &$delivered ) {
				if ( $webhook === $delivering_webhook ) {
					$delivered[] = $arg;
				}
			},
			10,
			2
		);
		$webhook->enqueue();

		$notification = $this->create_notification( NotificationStatus::ACTIVE );
		$this->set_status( $notification, NotificationStatus::SENT );

		$this->assertSame( array( $notification->get_id() ), $delivered );
	}

	/**
	 * Create and save a notification.
	 *
	 * @param string $status The initial status.
	 * @return Notification
	 */
	private function create_notification( string $status ): Notification {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( uniqid( 'shopper' ) . '@example.com' );
		$notification->set_status( $status );
		$notification->save();

		return $notification;
	}

	/**
	 * Change and save a notification's status.
	 *
	 * @param Notification $notification The notification.
	 * @param string       $status       The new status.
	 */
	private function set_status( Notification $notification, string $status ): void {
		$notification->set_status( $status );
		$notification->save();
	}

	/**
	 * Record the first argument of every call to an action.
	 *
	 * @param string $action The action name.
	 * @return callable Returns the recorded arguments.
	 */
	private function record_action( string $action ): callable {
		$args = array();
		add_action(
			$action,
			function ( $id ) use ( &$args ) {
				$args[] = $id;
			}
		);

		return function () use ( &$args ) {
			return $args;
		};
	}
}
