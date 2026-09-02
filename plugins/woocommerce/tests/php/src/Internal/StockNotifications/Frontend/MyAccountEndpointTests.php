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
	 * Location passed to the last suppressed redirect, or null if none happened.
	 *
	 * @var string|null
	 */
	private $redirect_location = null;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		\wc_clear_notices();
		$this->redirect_location = null;
		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
		$this->redirect_location = null;
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
	 * Record the redirect target and abort the request the way `exit` would.
	 *
	 * PHPUnit has already sent output by the time a test runs, so letting
	 * `wp_safe_redirect()` reach `header()` raises "headers already sent", and
	 * the `exit` that follows it in production would end the test run. Throwing
	 * from the filter stands in for both.
	 *
	 * The target is recorded in `$redirect_location` rather than in the exception
	 * message, which phpcs requires to be escaped.
	 *
	 * @param string $location The redirect target.
	 * @return never
	 * @throws \RuntimeException Always, to stand in for the `exit`.
	 */
	public function capture_redirect( $location ) {
		$this->redirect_location = (string) $location;
		throw new \RuntimeException( 'Redirected.' );
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
	 * The row label uses the parent title for a variation, so the attributes are
	 * not repeated by both the name and the variation list rendered beneath it.
	 */
	public function test_get_display_product_name_uses_parent_title_for_variations(): void {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		$this->assertNotEmpty( $variations );

		$variation = wc_get_product( $variations[0] );
		$this->assertInstanceOf( \WC_Product_Variation::class, $variation );

		$notification = new Notification();
		$notification->set_product_id( $variation->get_id() );
		$notification->set_user_email( 'nobody@example.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertSame( $variable->get_title(), MyAccountEndpoint::get_display_product_name( $notification ) );
		// The Notification getter still returns the attribute-carrying variation name.
		$this->assertSame( $variation->get_name(), $notification->get_product_name() );
	}

	/**
	 * A notification whose product no longer exists has no name to show.
	 */
	public function test_get_display_product_name_is_empty_without_a_product(): void {
		$notification = new Notification();
		$notification->set_product_id( 999999 );
		$notification->set_user_email( 'nobody@example.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertSame( '', MyAccountEndpoint::get_display_product_name( $notification ) );
	}

	/**
	 * The endpoint returns the logged-in user's notifications.
	 */
	public function test_get_current_user_notifications_returns_users_notifications(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE )['notifications'];

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
		$results  = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE )['notifications'];

		$this->assertCount( 1, $results );
		$this->assertSame( $b_notification->get_id(), $results[0]->get_id() );
	}

	/**
	 * Anonymous visitors get an empty result set without querying by user-supplied ids.
	 */
	public function test_get_current_user_notifications_returns_empty_for_anonymous(): void {
		\wp_set_current_user( 0 );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE )['notifications'];

		$this->assertSame( array(), $results );
	}

	/**
	 * The empty state fires when the user has zero signups.
	 */
	public function test_get_current_user_notifications_empty_state(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$endpoint = new MyAccountEndpoint();
		$results  = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE )['notifications'];

		$this->assertSame( array(), $results );
	}

	/**
	 * Page size is honoured and total counts reflect the full set, not just the page.
	 */
	public function test_get_current_user_notifications_page_paginates(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		// 7 notifications, fetch page 2 with per_page=3 → expect rows 4-6.
		$created = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$created[] = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		}
		// `id` DESC ordering — newest-first — so page 1 = ids[6..4], page 2 = ids[3..1], page 3 = id[0].
		$ids_desc = array_reverse( array_map( static fn ( $n ) => $n->get_id(), $created ) );

		$endpoint = new MyAccountEndpoint();
		$page     = $endpoint->get_current_user_notifications_page( 2, 3 );

		$this->assertSame( 7, $page['total_items'] );
		$this->assertSame( 3, $page['total_pages'] );
		$this->assertSame( 2, $page['current_page'] );
		$this->assertCount( 3, $page['notifications'] );
		$this->assertSame( array_slice( $ids_desc, 3, 3 ), array_map( static fn ( $n ) => $n->get_id(), $page['notifications'] ) );
	}

	/**
	 * SENT and CANCELLED notifications are filtered out — only ACTIVE/PENDING render in the My Account view.
	 */
	public function test_get_current_user_notifications_page_excludes_sent_and_cancelled(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$active  = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		$pending = $this->create_notification( $user_id, NotificationStatus::PENDING );
		$this->create_notification( $user_id, NotificationStatus::SENT );
		$this->create_notification( $user_id, NotificationStatus::CANCELLED );

		$endpoint = new MyAccountEndpoint();
		$page     = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE );

		$this->assertSame( 2, $page['total_items'] );
		$visible_ids = array_map( static fn ( $n ) => $n->get_id(), $page['notifications'] );
		$this->assertEqualsCanonicalizing( array( $active->get_id(), $pending->get_id() ), $visible_ids );
	}

	/**
	 * Out-of-range page numbers clamp to the last page so a stale link doesn't render an empty table.
	 */
	public function test_get_current_user_notifications_page_clamps_out_of_range(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		for ( $i = 0; $i < 5; $i++ ) {
			$this->create_notification( $user_id, NotificationStatus::ACTIVE );
		}

		$endpoint = new MyAccountEndpoint();
		// Per-page 2 → 3 pages exist (rows 1-2, 3-4, 5). Asking for page 99 should clamp to 3.
		$page = $endpoint->get_current_user_notifications_page( 99, 2 );

		$this->assertSame( 3, $page['current_page'] );
		$this->assertCount( 1, $page['notifications'] );
	}

	/**
	 * A valid nonce for the notification owner cancels the notification.
	 */
	public function test_cancel_with_valid_nonce_sets_status_cancelled(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_cancel_request( $notification->get_id(), true );

		// The handler redirects and exits once the cancellation is recorded.
		try {
			( new MyAccountEndpoint() )->maybe_handle_cancel();
			$this->fail( 'Expected the cancel handler to redirect.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame(
			\wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, '', \wc_get_page_permalink( 'myaccount' ) ),
			$this->redirect_location
		);

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertSame( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
	}

	/**
	 * A notification that is already sent or cancelled can no longer be cancelled,
	 * even with a valid nonce — the My Account view never offers the button for it.
	 *
	 * @testWith ["sent"]
	 *           ["cancelled"]
	 *
	 * @param string $status Non-cancellable notification status.
	 */
	public function test_cancel_ignored_for_non_cancellable_status( string $status ): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, $status );

		$this->simulate_cancel_request( $notification->get_id(), true );

		( new MyAccountEndpoint() )->maybe_handle_cancel();

		$this->assertNull( $this->redirect_location );

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( $status, $updated->get_status() );
	}

	/**
	 * The cancellable statuses are the ones the My Account view lists.
	 */
	public function test_is_cancellable_matches_listed_statuses(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );

		$this->assertTrue( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::PENDING ) ) );
		$this->assertTrue( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::ACTIVE ) ) );
		$this->assertFalse( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::SENT ) ) );
		$this->assertFalse( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::CANCELLED ) ) );
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
		$user_id      = $this->factory->user->create( array( 'role' => 'customer' ) );
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
