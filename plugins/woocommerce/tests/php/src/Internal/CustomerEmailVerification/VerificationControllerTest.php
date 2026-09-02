<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Unit_Test_Case;

/**
 * Tests for the VerificationController class (login-gated magic-link flow).
 */
class VerificationControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var VerificationController
	 */
	private $ctrl;

	/**
	 * The verification service.
	 *
	 * @var EmailVerificationService
	 */
	private $service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service = wc_get_container()->get( EmailVerificationService::class );
		// Resolving the controller also triggers its constructor (hooks) and init() (deps).
		$this->ctrl = wc_get_container()->get( VerificationController::class );
		// Link guest orders on verification (the boot wires this in production; register it here for the test).
		add_action( 'woocommerce_customer_email_verified', 'wc_update_new_customer_past_orders' );
	}

	/**
	 * @testdox send_verification_email() emits a link carrying the key, and confirming it as the link owner verifies and links orders.
	 */
	public function test_send_then_confirm_as_owner_links_orders(): void {
		$email   = 'roundtrip@example.com';
		$user_id = wc_create_new_customer( $email, 'roundtripuser', 'pw' );
		$order   = $this->create_guest_order( $email );

		$verify_url = $this->capture_sent_url( $user_id );
		$this->assertStringContainsString( 'wc_verify_email_key=', $verify_url, 'The emailed value should be a verify URL carrying a key' );
		$key = $this->key_from_url( $verify_url );

		wp_set_current_user( $user_id );
		$redirect = $this->open_verify_link( $user_id, $key );
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'Opening the link as the owner should verify the address' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'Guest order should link to the verified customer' );
		$this->assertStringContainsString( 'wc_verify_notice=confirmed', $redirect, 'Should redirect to the Orders endpoint carrying the confirmed result notice' );
	}

	/**
	 * @testdox LOCKDOWN: the same valid link verifies ONLY when opened as the target — logged out (prefetch) and cross-account never consume the key.
	 *
	 * This is the core security property: verification is gated on being authenticated as the link's
	 * target user, so neither a prefetch (always logged out) nor any other account can spend the key.
	 */
	public function test_verify_link_requires_authentication_as_target(): void {
		$owner_id = wc_create_new_customer( 'lockdown-owner@example.com', 'lockdownowner', 'pw' );
		$other_id = wc_create_new_customer( 'lockdown-other@example.com', 'lockdownother', 'pw' );
		$key      = $this->service->create_verification_key( $owner_id );

		// Logged out (the prefetch case): inert — no verification, key untouched.
		wp_set_current_user( 0 );
		$this->open_verify_link( $owner_id, $key );
		wc_clear_notices();
		$this->assertFalse( $this->service->is_verified( $owner_id ), 'A logged-out open must not verify' );
		$this->assertTrue( $this->service->has_pending_key( $owner_id ), 'A logged-out open must not consume the key' );

		// Logged in as a different account: refused, key still intact.
		wp_set_current_user( $other_id );
		$this->open_verify_link( $owner_id, $key );
		$this->assertFalse( $this->service->is_verified( $owner_id ), 'A cross-account open must not verify the owner' );
		$this->assertTrue( $this->service->has_pending_key( $owner_id ), 'A cross-account open must not consume the key' );

		// Logged in as the target: the key (untouched by the prior attempts) now verifies and is spent.
		wp_set_current_user( $owner_id );
		$this->open_verify_link( $owner_id, $key );
		wp_set_current_user( 0 );
		$this->assertTrue( $this->service->is_verified( $owner_id ), 'Opening as the target should verify' );
		$this->assertFalse( $this->service->has_pending_key( $owner_id ), 'Verifying consumes the key' );
	}

	/**
	 * @testdox LOGIN GATE: opening the verify-link while logged out renders the My Account login with a notice, and never verifies or consumes the key.
	 *
	 * The verify params stay in the page URL, so signing in on that login form returns the visitor here
	 * to complete verification.
	 */
	public function test_logged_out_open_renders_login_with_notice(): void {
		$user_id = wc_create_new_customer( 'openlink@example.com', 'openlinkuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( 0 );
		$redirect       = $this->open_verify_link( $user_id, $key );
		$notice_notices = wc_get_notices( 'notice' );
		wc_clear_notices();

		$this->assertSame( '', $redirect, 'A logged-out open must render the login in place, not redirect or verify' );
		$this->assertFalse( $this->service->is_verified( $user_id ), 'Opening the link logged out must not verify anyone' );
		$this->assertTrue( $this->service->has_pending_key( $user_id ), 'Opening the link logged out must not consume the key' );
		$this->assertNotEmpty( $notice_notices, 'A logged-out open should explain that login is required' );
	}

	/**
	 * @testdox LOGIN GATE: opening the link while logged in as a different account verifies no one and shows the mismatch notice.
	 */
	public function test_cross_account_open_does_not_verify(): void {
		$owner_id = wc_create_new_customer( 'owner@example.com', 'owneruser', 'pw' );
		$other_id = wc_create_new_customer( 'other@example.com', 'otheruser', 'pw' );
		$key      = $this->service->create_verification_key( $owner_id );

		wp_set_current_user( $other_id );
		$redirect = $this->open_verify_link( $owner_id, $key );
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $owner_id ), 'The link owner must not be verified by another account' );
		$this->assertFalse( $this->service->is_verified( $other_id ), 'The logged-in different account must not be verified' );
		$this->assertTrue( $this->service->has_pending_key( $owner_id ), 'A cross-account open must not consume the key' );
		$this->assertStringContainsString( 'wc_verify_notice=mismatch', $redirect, 'A cross-account open should surface the mismatch result notice' );
	}

	/**
	 * @testdox Opening the link as the owner with a wrong/expired key errors and does not verify.
	 */
	public function test_invalid_key_errors_and_does_not_verify(): void {
		$user_id = wc_create_new_customer( 'wrongkey@example.com', 'wrongkeyuser', 'pw' );
		$this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$redirect = $this->open_verify_link( $user_id, 'totally-wrong-key' );
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A wrong key must not verify the address' );
		$this->assertStringContainsString( 'wc_verify_notice=expired', $redirect, 'A wrong key should surface the expired result notice' );
	}

	/**
	 * @testdox Re-opening the link after the address is already verified lands on Orders without repeating the success notice.
	 */
	public function test_reopening_link_after_verified_shows_no_success_notice(): void {
		$user_id = wc_create_new_customer( 'reopen@example.com', 'reopenuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$first  = $this->open_verify_link( $user_id, $key );
		$second = $this->open_verify_link( $user_id, $key );
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The first open should verify the address' );
		$this->assertStringContainsString( 'wc_verify_notice=confirmed', $first, 'The first open should report success' );
		$this->assertStringContainsString( 'orders', $second, 'A re-open once verified should still land on Orders' );
		$this->assertStringNotContainsString( 'wc_verify_notice=', $second, 'A re-open once verified must not repeat the success notice' );
	}

	/**
	 * Capture the verify URL emitted by send_verification_email().
	 *
	 * @param int $user_id User to send to.
	 * @return string The captured URL.
	 */
	private function capture_sent_url( int $user_id ): string {
		$captured = '';
		$listener = static function ( $uid, $url ) use ( &$captured ) {
			// The $uid arg is unused but required by the two-argument hook signature.
			unset( $uid );
			$captured = $url;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener, 10, 2 );
		$this->ctrl->send_verification_email( $user_id );
		remove_action( 'woocommerce_customer_verify_email_notification', $listener, 10 );

		return (string) $captured;
	}

	/**
	 * Extract the plaintext key from a verify URL.
	 *
	 * @param string $url Verify URL.
	 * @return string
	 */
	private function key_from_url( string $url ): string {
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		parse_str( $query, $args );

		return isset( $args['wc_verify_email_key'] ) ? (string) $args['wc_verify_email_key'] : '';
	}

	/**
	 * Create a guest order with the given billing email (linkable to a matching customer).
	 *
	 * @param string $email Billing email.
	 * @return \WC_Order
	 */
	private function create_guest_order( string $email ): \WC_Order {
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();

		return $order;
	}

	/**
	 * Open a verify-link (GET) through the controller, returning the captured redirect target.
	 *
	 * handle_verify_link() ends in wp_safe_redirect()/exit on every path except the logged-out one (which
	 * renders the login in place); a filter throws the redirect target so the exit is never reached. The
	 * returned value is the redirect location, or '' when the handler rendered without redirecting.
	 *
	 * @param int    $user_id User ID in the link.
	 * @param string $key     Key in the link.
	 * @return string The redirect location the handler attempted, or '' if it rendered in place.
	 */
	private function open_verify_link( int $user_id, string $key ): string {
		$_SERVER['REQUEST_METHOD']    = 'GET';
		$_GET['wc_verify_email_user'] = (string) $user_id;
		$_GET['wc_verify_email_key']  = $key;

		$redirect = '';
		$abort    = static function ( $location ) {
			throw new \RuntimeException( esc_html( (string) $location ) );
		};
		add_filter( 'wp_redirect', $abort );
		try {
			$this->ctrl->maybe_process_request();
		} catch ( \RuntimeException $e ) {
			$redirect = $e->getMessage();
		} finally {
			remove_filter( 'wp_redirect', $abort );
			unset( $_GET['wc_verify_email_user'], $_GET['wc_verify_email_key'] );
		}

		return $redirect;
	}
}
