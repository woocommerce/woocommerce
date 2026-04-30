<?php
/**
 * MyAccountEndpointTests class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use WC_Helper_Product;

/**
 * Tests for the customer-facing MyAccount back-in-stock notifications endpoint.
 */
class MyAccountEndpointTests extends \WC_Unit_Test_Case {

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		\wc_clear_notices();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		\wc_clear_notices();
		\wp_set_current_user( 0 );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_POST = array();
		global $wp;
		if ( isset( $wp->query_vars[ MyAccountEndpoint::ENDPOINT ] ) ) {
			unset( $wp->query_vars[ MyAccountEndpoint::ENDPOINT ] );
		}
		parent::tearDown();
	}

	/**
	 * Create a notification owned by the given user.
	 *
	 * @param int    $user_id User id.
	 * @param string $status  Notification status.
	 * @return Notification
	 */
	private function create_notification( int $user_id, string $status = NotificationStatus::ACTIVE ): Notification {
		$user    = \get_user_by( 'id', $user_id );
		$product = WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( $user_id );
		$notification->set_user_email( $user ? $user->user_email : 'nobody@example.com' );
		$notification->set_status( $status );
		$notification->save();

		return $notification;
	}

	/**
	 * The endpoint returns the logged-in user's notifications.
	 */
	public function test_get_current_user_notifications_returns_users_notifications(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications();

		$this->assertCount( 1, $results );
		$this->assertSame( $notification->get_id(), $results[0]->get_id() );
		$this->assertSame( $user_id, (int) $results[0]->get_user_id() );
	}

	/**
	 * User A cannot see user B's notifications via the endpoint helper.
	 */
	public function test_get_current_user_notifications_scopes_to_current_user(): void {
		$user_a = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$this->create_notification( $user_a, NotificationStatus::ACTIVE );
		$b_notification = $this->create_notification( $user_b, NotificationStatus::PENDING );

		\wp_set_current_user( $user_b );
		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications();

		$this->assertCount( 1, $results );
		$this->assertSame( $b_notification->get_id(), $results[0]->get_id() );
	}

	/**
	 * Anonymous visitors get an empty result set without querying by user-supplied ids.
	 */
	public function test_get_current_user_notifications_returns_empty_for_anonymous(): void {
		\wp_set_current_user( 0 );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications();

		$this->assertSame( array(), $results );
	}

	/**
	 * The empty state fires when the user has zero signups.
	 */
	public function test_get_current_user_notifications_empty_state(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications();

		$this->assertSame( array(), $results );
	}

	/**
	 * A valid nonce for the notification owner cancels the notification.
	 */
	public function test_cancel_with_valid_nonce_sets_status_cancelled(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_cancel_request( $notification->get_id(), true );

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertSame( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
	}

	/**
	 * An invalid nonce does not modify the notification.
	 */
	public function test_cancel_with_invalid_nonce_does_not_cancel(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_cancel_request( $notification->get_id(), false );

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::ACTIVE, $updated->get_status() );
	}

	/**
	 * A nonce scoped to notification A cannot be replayed on notification B.
	 */
	public function test_cancel_nonce_for_other_notification_does_not_validate(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$notification_a = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		$notification_b = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		// Nonce was minted against A's id, but we POST it alongside B's id.
		$nonce = \wp_create_nonce( MyAccountEndpoint::get_cancel_nonce_action( $notification_a->get_id() ) );

		$this->set_endpoint_query_var();
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.WP.GlobalVariablesOverride.Prohibited
		$_POST = array(
			MyAccountEndpoint::CANCEL_ACTION => '1',
			'notification_id'                => (string) $notification_b->get_id(),
			'_wpnonce'                       => $nonce,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.WP.GlobalVariablesOverride.Prohibited

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$updated_a = Factory::get_notification( $notification_a->get_id() );
		$updated_b = Factory::get_notification( $notification_b->get_id() );

		$this->assertInstanceOf( Notification::class, $updated_a );
		$this->assertInstanceOf( Notification::class, $updated_b );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_a->get_status() );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_b->get_status() );
	}

	/**
	 * User A cannot cancel user B's notification even when the nonce validates against B's action name
	 * (WordPress nonces bind to the current user, so this effectively asserts the ownership check).
	 */
	public function test_cancel_does_not_touch_other_users_notification(): void {
		$user_a = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$notification_b = $this->create_notification( $user_b, NotificationStatus::ACTIVE );

		// User A logs in and tries to cancel B's notification.
		\wp_set_current_user( $user_a );
		$this->simulate_cancel_request( $notification_b->get_id(), true );

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$updated_b = Factory::get_notification( $notification_b->get_id() );
		$this->assertInstanceOf( Notification::class, $updated_b );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_b->get_status() );
	}

	/**
	 * An anonymous POST with a cancel payload is silently dropped.
	 */
	public function test_cancel_ignored_when_anonymous(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		\wp_set_current_user( 0 );
		$this->simulate_cancel_request( $notification->get_id(), true );

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::ACTIVE, $updated->get_status() );
	}

	/**
	 * The menu filter adds the Stock notifications item.
	 */
	public function test_menu_item_is_registered(): void {
		$endpoint = new MyAccountEndpoint();

		$items = $endpoint->register_menu_item(
			array(
				'dashboard'       => 'Dashboard',
				'orders'          => 'Orders',
				'downloads'       => 'Downloads',
				'customer-logout' => 'Log out',
			),
			array()
		);

		$this->assertArrayHasKey( MyAccountEndpoint::ENDPOINT, $items );
		$this->assertSame( 'Stock notifications', $items[ MyAccountEndpoint::ENDPOINT ] );

		// Order preserved: after downloads, logout stays last.
		$keys = array_keys( $items );
		$this->assertSame( 'customer-logout', end( $keys ) );
		$this->assertGreaterThan(
			(int) array_search( 'downloads', $keys, true ),
			(int) array_search( MyAccountEndpoint::ENDPOINT, $keys, true )
		);
	}

	/**
	 * The query var filter registers the endpoint slug.
	 */
	public function test_query_var_is_registered(): void {
		$endpoint = new MyAccountEndpoint();
		$vars     = $endpoint->register_query_var( array( 'orders' => 'orders' ) );

		$this->assertArrayHasKey( MyAccountEndpoint::ENDPOINT, $vars );
		$this->assertSame( MyAccountEndpoint::ENDPOINT, $vars[ MyAccountEndpoint::ENDPOINT ] );
	}

	/**
	 * Helper: fake the global state needed for `maybe_handle_cancel()` to proceed past guards.
	 *
	 * @param int  $notification_id Notification id.
	 * @param bool $valid_nonce     Whether to mint a nonce that validates for this id.
	 */
	private function simulate_cancel_request( int $notification_id, bool $valid_nonce ): void {
		$this->set_endpoint_query_var();

		$nonce = $valid_nonce
			? \wp_create_nonce( MyAccountEndpoint::get_cancel_nonce_action( $notification_id ) )
			: 'clearly-not-a-valid-nonce';

		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.WP.GlobalVariablesOverride.Prohibited
		$_POST = array(
			MyAccountEndpoint::CANCEL_ACTION => '1',
			'notification_id'                => (string) $notification_id,
			'_wpnonce'                       => $nonce,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Helper: pretend we are on the My Account > BIS endpoint.
	 */
	private function set_endpoint_query_var(): void {
		global $wp;
		if ( ! $wp instanceof \WP ) {
			$wp = new \WP(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		$wp->query_vars[ MyAccountEndpoint::ENDPOINT ] = '';
	}
}
