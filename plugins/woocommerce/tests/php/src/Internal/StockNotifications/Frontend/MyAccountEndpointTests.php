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
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
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
		$_GET = array();
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
	 * Build an endpoint wired to the given management service, or the real one.
	 *
	 * @param NotificationManagementService|null $service Service to inject; defaults to the container's.
	 * @return MyAccountEndpoint
	 */
	private function make_endpoint( ?NotificationManagementService $service = null ): MyAccountEndpoint {
		$endpoint = new MyAccountEndpoint();
		$endpoint->init( $service ?? wc_get_container()->get( NotificationManagementService::class ) );

		return $endpoint;
	}

	/**
	 * Run the action handler expecting it to redirect, and assert the target.
	 *
	 * `capture_redirect()` throws in place of the `exit` that follows the redirect
	 * in production, so the call has to be wrapped.
	 *
	 * @param NotificationManagementService|null $service Service to inject; defaults to the container's.
	 */
	private function run_action_expecting_redirect( ?NotificationManagementService $service = null ): void {
		try {
			$this->make_endpoint( $service )->maybe_handle_action();
			$this->fail( 'Expected the action handler to redirect.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame(
			\wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, '', \wc_get_page_permalink( 'myaccount' ) ),
			$this->redirect_location
		);
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
		$b_notification = $this->create_notification( $user_b, NotificationStatus::ACTIVE );

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
	 * Only ACTIVE notifications render in the main My Account table. PENDING rows
	 * have their own table, and SENT / CANCELLED rows are filtered out entirely.
	 */
	public function test_get_current_user_notifications_page_lists_active_only(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$active = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		$this->create_notification( $user_id, NotificationStatus::PENDING );
		$this->create_notification( $user_id, NotificationStatus::SENT );
		$this->create_notification( $user_id, NotificationStatus::CANCELLED );

		$endpoint = new MyAccountEndpoint();
		$page     = $endpoint->get_current_user_notifications_page( 1, MyAccountEndpoint::DEFAULT_PER_PAGE );

		$this->assertSame( 1, $page['total_items'] );
		$this->assertSame( array( $active->get_id() ), array_map( static fn ( $n ) => $n->get_id(), $page['notifications'] ) );
	}

	/**
	 * Pagination counts only ACTIVE rows, so pending sign-ups never shift the active pages.
	 */
	public function test_get_current_user_notifications_page_paginates_active_only(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_notification( $user_id, NotificationStatus::ACTIVE );
			$this->create_notification( $user_id, NotificationStatus::PENDING );
		}

		$endpoint = new MyAccountEndpoint();
		$page     = $endpoint->get_current_user_notifications_page( 1, 2 );

		$this->assertSame( 3, $page['total_items'] );
		$this->assertSame( 2, $page['total_pages'] );
		$this->assertCount( 2, $page['notifications'] );
		foreach ( $page['notifications'] as $notification ) {
			$this->assertSame( NotificationStatus::ACTIVE, $notification->get_status() );
		}
	}

	/**
	 * The pending fetcher returns only the current user's PENDING rows, newest first.
	 */
	public function test_get_current_user_pending_notifications_returns_users_pending_rows_newest_first(): void {
		$user_a = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$first  = $this->create_notification( $user_a, NotificationStatus::PENDING );
		$second = $this->create_notification( $user_a, NotificationStatus::PENDING );
		$this->create_notification( $user_a, NotificationStatus::ACTIVE );
		$this->create_notification( $user_a, NotificationStatus::SENT );
		$this->create_notification( $user_a, NotificationStatus::CANCELLED );
		$this->create_notification( $user_b, NotificationStatus::PENDING );

		\wp_set_current_user( $user_a );
		$endpoint = new MyAccountEndpoint();
		$pending  = $endpoint->get_current_user_pending_notifications( MyAccountEndpoint::DEFAULT_PER_PAGE );

		$this->assertSame( array( $second->get_id(), $first->get_id() ), array_map( static fn ( $n ) => $n->get_id(), $pending ) );
		foreach ( $pending as $notification ) {
			$this->assertSame( $user_a, (int) $notification->get_user_id() );
			$this->assertSame( NotificationStatus::PENDING, $notification->get_status() );
		}
	}

	/**
	 * The pending fetcher caps the result set at the requested limit.
	 */
	public function test_get_current_user_pending_notifications_respects_limit(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		for ( $i = 0; $i < 4; $i++ ) {
			$this->create_notification( $user_id, NotificationStatus::PENDING );
		}

		$endpoint = new MyAccountEndpoint();

		$this->assertCount( 2, $endpoint->get_current_user_pending_notifications( 2 ) );
		// A non-positive limit is clamped to 1 rather than dropping the LIMIT clause.
		$this->assertCount( 1, $endpoint->get_current_user_pending_notifications( 0 ) );
	}

	/**
	 * Anonymous visitors get no pending rows without querying by user-supplied ids.
	 */
	public function test_get_current_user_pending_notifications_returns_empty_for_anonymous(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$this->create_notification( $user_id, NotificationStatus::PENDING );

		\wp_set_current_user( 0 );

		$endpoint = new MyAccountEndpoint();
		$this->assertSame( array(), $endpoint->get_current_user_pending_notifications( MyAccountEndpoint::DEFAULT_PER_PAGE ) );
	}

	/**
	 * The rendered endpoint splits pending and active rows into their own tables, links
	 * each pending row to a resend URL that returns to the endpoint, and honours the
	 * pending-limit filter.
	 */
	public function test_render_endpoint_splits_pending_and_active_tables(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );

		$active    = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		$pending_1 = $this->create_notification( $user_id, NotificationStatus::PENDING );
		$pending_2 = $this->create_notification( $user_id, NotificationStatus::PENDING );

		$limit_filter = static fn () => 1;
		add_filter( 'woocommerce_account_customer_stock_notifications_pending_limit', $limit_filter );
		try {
			ob_start();
			wc_get_container()->get( MyAccountEndpoint::class )->render_endpoint( 1 );
			$html = ob_get_clean();
		} finally {
			remove_filter( 'woocommerce_account_customer_stock_notifications_pending_limit', $limit_filter );
		}

		$this->assertStringContainsString( 'woocommerce-customer-stock-notifications-table--pending', $html );
		$this->assertStringContainsString( 'woocommerce-customer-stock-notifications-table--active', $html );
		$this->assertStringContainsString( 'Awaiting confirmation', $html );
		$this->assertStringContainsString( 'woocommerce-customer-stock-notifications-heading--active', $html );
		$this->assertStringNotContainsString( "You haven't signed up", $html );

		// Only the newest pending row survives the limit of 1.
		$this->assertStringContainsString( 'notification_id=' . $pending_2->get_id() . '&', $html );
		$this->assertStringNotContainsString( 'notification_id=' . $pending_1->get_id() . '&', $html );
		$this->assertStringContainsString( 'notification_id=' . $active->get_id() . '&', $html );

		// Only the pending row offers a Resend link; both actions point back at the endpoint.
		$this->assertSame( 1, substr_count( $html, 'woocommerce-customer-stock-notifications-action-link--resend' ) );
		$this->assertSame( 2, substr_count( $html, 'woocommerce-customer-stock-notifications-action-link--cancel' ) );
		$this->assertStringContainsString( esc_url( MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_RESEND, $pending_2->get_id() ) ), $html );
		$this->assertStringContainsString( esc_url( MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_CANCEL, $active->get_id() ) ), $html );
		$this->assertStringNotContainsString( MyAccountEndpoint::ACTION_FIELD . '=' . MyAccountEndpoint::ACTION_RESEND . '&#038;notification_id=' . $active->get_id(), $html );
		$this->assertStringContainsString( 'aria-label="Resend email for ', $html );
		$this->assertStringNotContainsString( 'wc_bis_resend_notification=', $html );
	}

	/**
	 * A customer with only pending sign-ups sees the pending table, not the empty state.
	 */
	public function test_render_endpoint_with_only_pending_rows_hides_empty_state(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$this->create_notification( $user_id, NotificationStatus::PENDING );

		ob_start();
		wc_get_container()->get( MyAccountEndpoint::class )->render_endpoint( 1 );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'woocommerce-customer-stock-notifications-table--pending', $html );
		$this->assertStringNotContainsString( 'woocommerce-customer-stock-notifications-table--active', $html );
		$this->assertStringNotContainsString( 'woocommerce-customer-stock-notifications-heading--active', $html );
		$this->assertStringNotContainsString( "You haven't signed up", $html );
	}

	/**
	 * With no pending rows the active table renders without the "Active" heading,
	 * so a store without double opt-in sees the single table it always had.
	 */
	public function test_render_endpoint_without_pending_rows_omits_active_heading(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$this->create_notification( $user_id, NotificationStatus::ACTIVE );

		ob_start();
		wc_get_container()->get( MyAccountEndpoint::class )->render_endpoint( 1 );
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'woocommerce-customer-stock-notifications-table--pending', $html );
		$this->assertStringContainsString( 'woocommerce-customer-stock-notifications-table--active', $html );
		$this->assertStringNotContainsString( 'Awaiting confirmation', $html );
		$this->assertStringNotContainsString( 'woocommerce-customer-stock-notifications-heading--active', $html );
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

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), true );

		$this->run_action_expecting_redirect();

		$this->assertEmpty( \wc_get_notices( 'error' ) );

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertSame( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
	}

	/**
	 * A notification that is already sent or cancelled can no longer be cancelled,
	 * even with a valid nonce — the My Account view never offers the button for it.
	 * The customer gets an error notice rather than a page that looks unchanged.
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

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), true );

		$this->run_action_expecting_redirect();

		$this->assertCount( 1, \wc_get_notices( 'error' ) );

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( $status, $updated->get_status() );
	}

	/**
	 * Both PENDING and ACTIVE rows keep their Cancel button; SENT and CANCELLED never get one.
	 */
	public function test_is_cancellable_matches_listed_statuses(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );

		$this->assertTrue( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::PENDING ) ) );
		$this->assertTrue( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::ACTIVE ) ) );
		$this->assertFalse( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::SENT ) ) );
		$this->assertFalse( MyAccountEndpoint::is_cancellable( $this->create_notification( $user_id, NotificationStatus::CANCELLED ) ) );
	}

	/**
	 * An invalid nonce does not modify the notification. Nonces expire after 12-24
	 * hours, so a stale tab has to surface a recoverable error rather than nothing.
	 */
	public function test_cancel_with_invalid_nonce_does_not_cancel(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), false );

		$this->run_action_expecting_redirect();

		$this->assertCount( 1, \wc_get_notices( 'error' ) );

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

		// Nonce was minted against A's id, but the request carries B's id.
		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification_b->get_id(), true );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['_wpnonce'] = \wp_create_nonce( MyAccountEndpoint::get_nonce_action( MyAccountEndpoint::ACTION_CANCEL, $notification_a->get_id() ) );

		$this->run_action_expecting_redirect();

		$this->assertCount( 1, \wc_get_notices( 'error' ) );

		$updated_a = Factory::get_notification( $notification_a->get_id() );
		$updated_b = Factory::get_notification( $notification_b->get_id() );

		$this->assertInstanceOf( Notification::class, $updated_a );
		$this->assertInstanceOf( Notification::class, $updated_b );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_a->get_status() );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_b->get_status() );
	}

	/**
	 * A cancel for a notification that no longer exists reports an error rather than
	 * re-rendering the page unchanged.
	 */
	public function test_cancel_for_missing_notification_reports_an_error(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );
		$id           = $notification->get_id();

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $id, true );
		$notification->delete( true );

		$this->run_action_expecting_redirect();

		$this->assertCount( 1, \wc_get_notices( 'error' ) );
	}

	/**
	 * A failed database write reports an error instead of telling the customer the
	 * notification was cancelled.
	 */
	public function test_cancel_reports_an_error_when_the_save_fails(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), true );

		$neutralize_update = array( $this, 'neutralize_notification_update' );
		add_filter( 'query', $neutralize_update );
		try {
			$this->run_action_expecting_redirect();
		} finally {
			remove_filter( 'query', $neutralize_update );
		}

		$this->assertCount( 1, \wc_get_notices( 'error' ) );
		$this->assertEmpty( \wc_get_notices( 'success' ) );

		$reloaded = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $reloaded );
		$this->assertSame( NotificationStatus::ACTIVE, $reloaded->get_status() );
	}

	/**
	 * Make an UPDATE against the stock notifications table match no rows, so the data
	 * store reports the failure the way a real database error would.
	 *
	 * @param string $query The query about to run.
	 * @return string
	 */
	public function neutralize_notification_update( $query ) {
		global $wpdb;

		if ( is_string( $query ) && 0 === stripos( $query, 'UPDATE' ) && false !== stripos( $query, $wpdb->prefix . 'wc_stock_notifications' ) ) {
			return $query . ' AND 1 = 0';
		}

		return $query;
	}

	/**
	 * User A cannot cancel user B's notification even when the nonce validates against B's action name
	 * (WordPress nonces bind to the current user, so this effectively asserts the ownership check).
	 *
	 * The response is the same "no longer exists" error a missing id gets, so it
	 * doesn't confirm that the notification exists.
	 */
	public function test_cancel_does_not_touch_other_users_notification(): void {
		$user_a = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$notification_b = $this->create_notification( $user_b, NotificationStatus::ACTIVE );

		// User A logs in and tries to cancel B's notification.
		\wp_set_current_user( $user_a );
		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification_b->get_id(), true );

		$this->run_action_expecting_redirect();

		$this->assertCount( 1, \wc_get_notices( 'error' ) );

		$updated_b = Factory::get_notification( $notification_b->get_id() );
		$this->assertInstanceOf( Notification::class, $updated_b );
		$this->assertSame( NotificationStatus::ACTIVE, $updated_b->get_status() );
	}

	/**
	 * An anonymous request carrying a cancel link's query args is silently dropped.
	 */
	public function test_cancel_ignored_when_anonymous(): void {
		$user_id      = $this->factory->user->create( array( 'role' => 'customer' ) );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		\wp_set_current_user( 0 );
		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), true );

		$this->make_endpoint()->maybe_handle_action();

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $updated );
		$this->assertSame( NotificationStatus::ACTIVE, $updated->get_status() );
	}

	/**
	 * A valid resend link from the owner hands the notification to the service and reports success.
	 */
	public function test_resend_with_valid_nonce_sends_verification_email(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::PENDING );

		$service = $this->createMock( NotificationManagementService::class );
		$service
			->expects( $this->once() )
			->method( 'resend_verification_email' )
			->with(
				$this->callback(
					static function ( $arg ) use ( $notification ) {
						return $arg instanceof Notification && $arg->get_id() === $notification->get_id();
					}
				)
			)
			->willReturn( true );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_RESEND, $notification->get_id(), true );

		$this->run_action_expecting_redirect( $service );

		$this->assertEmpty( \wc_get_notices( 'error' ) );
		$this->assertCount( 1, \wc_get_notices( 'success' ) );
	}

	/**
	 * A resend the service refuses (already verified, rate limited) surfaces its message as an error notice.
	 */
	public function test_resend_reports_service_error(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::PENDING );

		$service = $this->createMock( NotificationManagementService::class );
		$service
			->method( 'resend_verification_email' )
			->willReturn( new \WP_Error( NotificationManagementService::RESEND_ERROR_RATE_LIMITED, 'Please wait.' ) );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_RESEND, $notification->get_id(), true );

		$this->run_action_expecting_redirect( $service );

		$this->assertEmpty( \wc_get_notices( 'success' ) );
		$errors = \wc_get_notices( 'error' );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Please wait.', $errors[0]['notice'] );
	}

	/**
	 * A nonce minted for the row's Cancel link does not validate its Resend link.
	 */
	public function test_resend_rejects_nonce_minted_for_cancel(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::PENDING );

		$service = $this->createMock( NotificationManagementService::class );
		$service
			->expects( $this->never() )
			->method( 'resend_verification_email' );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_RESEND, $notification->get_id(), true );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['_wpnonce'] = \wp_create_nonce( MyAccountEndpoint::get_nonce_action( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id() ) );

		$this->run_action_expecting_redirect( $service );

		$this->assertCount( 1, \wc_get_notices( 'error' ) );
	}

	/**
	 * User A cannot trigger a verification email for user B's notification.
	 */
	public function test_resend_does_not_touch_other_users_notification(): void {
		$user_a = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$notification_b = $this->create_notification( $user_b, NotificationStatus::PENDING );

		$service = $this->createMock( NotificationManagementService::class );
		$service
			->expects( $this->never() )
			->method( 'resend_verification_email' );

		\wp_set_current_user( $user_a );
		$this->simulate_action_request( MyAccountEndpoint::ACTION_RESEND, $notification_b->get_id(), true );

		$this->run_action_expecting_redirect( $service );

		$this->assertCount( 1, \wc_get_notices( 'error' ) );
	}

	/**
	 * A valid action link that lands on any page other than the stock notifications
	 * endpoint is dropped, so the handler never widens to the whole front end.
	 */
	public function test_action_ignored_off_the_endpoint(): void {
		global $wp;

		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_action_request( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), true );
		unset( $wp->query_vars[ MyAccountEndpoint::ENDPOINT ] );

		$this->make_endpoint()->maybe_handle_action();

		$this->assertNull( $this->redirect_location );
		$this->assertEmpty( \wc_get_notices() );

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertSame( NotificationStatus::ACTIVE, $updated->get_status() );
	}

	/**
	 * An unknown action value is dropped before any nonce or database work.
	 */
	public function test_unknown_action_is_ignored(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		\wp_set_current_user( $user_id );
		$notification = $this->create_notification( $user_id, NotificationStatus::ACTIVE );

		$this->simulate_action_request( 'delete', $notification->get_id(), true );

		$this->make_endpoint()->maybe_handle_action();

		$this->assertNull( $this->redirect_location );
		$this->assertEmpty( \wc_get_notices() );

		$updated = Factory::get_notification( $notification->get_id() );
		$this->assertSame( NotificationStatus::ACTIVE, $updated->get_status() );
	}

	/**
	 * Action links point at the endpoint and carry the action, the id, and a nonce scoped to both.
	 */
	public function test_get_action_url_carries_action_id_and_scoped_nonce(): void {
		$url = MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_RESEND, 42 );

		$this->assertStringStartsWith( MyAccountEndpoint::get_endpoint_url(), $url );
		$this->assertStringContainsString( MyAccountEndpoint::ACTION_FIELD . '=' . MyAccountEndpoint::ACTION_RESEND, $url );
		$this->assertStringContainsString( 'notification_id=42', $url );

		// wp_nonce_url() returns an HTML-escaped URL (`&amp;`), so decode it before parsing the query.
		$query = array();
		\wp_parse_str( (string) \wp_parse_url( html_entity_decode( $url ), PHP_URL_QUERY ), $query );
		$this->assertNotFalse( \wp_verify_nonce( $query['_wpnonce'], MyAccountEndpoint::get_nonce_action( MyAccountEndpoint::ACTION_RESEND, 42 ) ) );
		$this->assertFalse( \wp_verify_nonce( $query['_wpnonce'], MyAccountEndpoint::get_nonce_action( MyAccountEndpoint::ACTION_CANCEL, 42 ) ) );
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
	 * Helper: fake the global state needed for `maybe_handle_action()` to proceed past guards,
	 * as if the customer followed a row action link.
	 *
	 * @param string $action          One of the `MyAccountEndpoint::ACTION_*` values.
	 * @param int    $notification_id Notification id.
	 * @param bool   $valid_nonce     Whether to mint a nonce that validates for this action and id.
	 */
	private function simulate_action_request( string $action, int $notification_id, bool $valid_nonce ): void {
		$this->set_endpoint_query_var();

		$nonce = $valid_nonce
			? \wp_create_nonce( MyAccountEndpoint::get_nonce_action( $action, $notification_id ) )
			: 'clearly-not-a-valid-nonce';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.WP.GlobalVariablesOverride.Prohibited
		$_GET = array(
			MyAccountEndpoint::ACTION_FIELD => $action,
			'notification_id'               => (string) $notification_id,
			'_wpnonce'                      => $nonce,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.WP.GlobalVariablesOverride.Prohibited
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
