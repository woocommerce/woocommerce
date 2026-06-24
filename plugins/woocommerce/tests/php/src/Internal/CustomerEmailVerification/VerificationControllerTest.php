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
		$redirect        = $this->submit_confirm( $user_id, $key, wp_create_nonce( 'woocommerce-verify-email' ) );
		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'Confirming as the link owner should verify the address' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'Guest order should link to the verified customer' );
		$this->assertNotEmpty( $success_notices, 'A confirmation notice should be shown' );
		$this->assertStringContainsString( 'orders', $redirect, 'Should redirect to the Orders endpoint' );
	}

	/**
	 * @testdox LOGIN GATE: a logged-out confirm submission never verifies, never consumes the key, and bounces back to the verify-link (which shows the login).
	 *
	 * This is the property that makes a verify-link safe: a prefetcher (always logged out) hitting the
	 * interstitial's POST cannot complete verification. The redirect keeps the verify params so that,
	 * once signed in, the visitor returns and completes verification.
	 */
	public function test_logged_out_submission_does_not_verify_and_redirects_to_login(): void {
		$user_id = wc_create_new_customer( 'prefetch@example.com', 'prefetchuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( 0 );
		$redirect = $this->submit_confirm( $user_id, $key, wp_create_nonce( 'woocommerce-verify-email' ) );
		wc_clear_notices();

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A logged-out submission must not verify anyone' );
		$this->assertTrue( $this->service->has_pending_key( $user_id ), 'A logged-out submission must not consume the key' );
		$this->assertStringContainsString( 'wc_verify_email_key=', $redirect, 'The redirect should keep the key so verification can resume after login' );
		$this->assertStringContainsString( 'wc_verify_email_user=' . $user_id, $redirect, 'The redirect should keep the target user so verification can resume after login' );
	}

	/**
	 * @testdox LOGIN GATE: opening the verify-link while logged out renders the My Account login with a notice, and never verifies or consumes the key.
	 *
	 * The verify params stay in the page URL, so signing in on that login form returns the visitor here
	 * to complete verification — no separate redirect to wp-login is needed.
	 */
	public function test_logged_out_open_renders_login_with_notice(): void {
		$user_id = wc_create_new_customer( 'openlink@example.com', 'openlinkuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( 0 );
		$_SERVER['REQUEST_METHOD']    = 'GET';
		$_GET['wc_verify_email_user'] = (string) $user_id;
		$_GET['wc_verify_email_key']  = $key;

		// The gate returns (renders the normal My Account login) rather than echoing the interstitial and
		// exiting, so this call completes without output.
		$this->ctrl->maybe_process_request();

		$notice_notices = wc_get_notices( 'notice' );
		wc_clear_notices();
		unset( $_GET['wc_verify_email_user'], $_GET['wc_verify_email_key'] );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'Opening the link logged out must not verify anyone' );
		$this->assertTrue( $this->service->has_pending_key( $user_id ), 'Opening the link logged out must not consume the key' );
		$this->assertNotEmpty( $notice_notices, 'A logged-out open should explain that login is required' );
	}

	/**
	 * @testdox LOGIN GATE: confirming while logged in as a different user verifies no one and shows an error.
	 */
	public function test_cross_account_submission_does_not_verify(): void {
		$owner_id = wc_create_new_customer( 'owner@example.com', 'owneruser', 'pw' );
		$other_id = wc_create_new_customer( 'other@example.com', 'otheruser', 'pw' );
		$key      = $this->service->create_verification_key( $owner_id );

		wp_set_current_user( $other_id );
		$this->submit_confirm( $owner_id, $key, wp_create_nonce( 'woocommerce-verify-email' ) );
		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $owner_id ), 'The link owner must not be verified by another account' );
		$this->assertFalse( $this->service->is_verified( $other_id ), 'The logged-in different account must not be verified' );
		$this->assertTrue( $this->service->has_pending_key( $owner_id ), 'A cross-account submission must not consume the key' );
		$this->assertNotEmpty( $error_notices, 'A cross-account submission should produce an error notice' );
	}

	/**
	 * @testdox A submission with an invalid nonce does not verify or consume the key.
	 */
	public function test_submission_requires_valid_nonce(): void {
		$user_id = wc_create_new_customer( 'bad-nonce@example.com', 'badnonce', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_confirm( $user_id, $key, 'not-a-valid-nonce' );
		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'An invalid nonce must not verify the address' );
		$this->assertTrue( $this->service->has_pending_key( $user_id ), 'An invalid nonce must not consume the key' );
		$this->assertNotEmpty( $error_notices, 'An invalid request should produce an error notice' );
	}

	/**
	 * @testdox Confirming as the owner with a wrong/expired key errors and does not verify.
	 */
	public function test_invalid_key_errors_and_does_not_verify(): void {
		$user_id = wc_create_new_customer( 'wrongkey@example.com', 'wrongkeyuser', 'pw' );
		$this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_confirm( $user_id, 'totally-wrong-key', wp_create_nonce( 'woocommerce-verify-email' ) );
		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A wrong key must not verify the address' );
		$this->assertNotEmpty( $error_notices, 'A wrong key should produce an error notice' );
	}

	/**
	 * @testdox Confirming twice after success shows success again, not a stale error.
	 */
	public function test_double_submission_does_not_error(): void {
		$user_id = wc_create_new_customer( 'double@example.com', 'doubleuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$nonce = wp_create_nonce( 'woocommerce-verify-email' );
		$this->submit_confirm( $user_id, $key, $nonce );
		$this->submit_confirm( $user_id, $key, $nonce );
		$error_notices   = wc_get_notices( 'error' );
		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The first submission should verify the address' );
		$this->assertEmpty( $error_notices, 'A repeat submission once verified must not error' );
		$this->assertCount( 2, $success_notices, 'Each submission once verified should report success' );
	}

	/**
	 * @testdox The opened-link interstitial is an inert auto-submitting POST form: no verification on GET.
	 *
	 * The GET only renders this page (a prefetch gets nothing more), so the key is carried into a
	 * same-origin POST rather than being consumed on the GET.
	 */
	public function test_confirm_interstitial_is_inert_post_form(): void {
		$user_id = wc_create_new_customer( 'interstitial@example.com', 'interstitialuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		$reflection = new \ReflectionMethod( $this->ctrl, 'get_confirm_page_html' );
		$reflection->setAccessible( true );
		$html = (string) $reflection->invoke( $this->ctrl, $user_id, $key );

		$this->assertStringContainsString( 'method="post"', $html, 'The interstitial must submit via POST.' );
		$this->assertStringContainsString( 'name="wc_verify_email_submit"', $html, 'The POST must carry the confirm marker.' );
		$this->assertStringContainsString( 'name="_wpnonce"', $html, 'The POST must carry a nonce.' );
		$this->assertStringContainsString( esc_attr( $key ), $html, 'The key is reflected into the POST form.' );
		$this->assertStringContainsString( '.submit()', $html, 'The form auto-submits via JavaScript.' );
		$this->assertStringContainsString( 'no-referrer', $html, 'The interstitial should not leak the key via Referer.' );
		$this->assertTrue( $this->service->has_pending_key( $user_id ), 'Rendering the interstitial must not consume the key.' );
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
	 * Drive a confirm-form POST through the controller, returning the captured redirect target.
	 *
	 * handle_confirm_submission() ends in wp_safe_redirect()/exit; a filter throws the redirect target
	 * so the exit is never reached and the test can assert on the outcome.
	 *
	 * @param int    $user_id User ID to submit.
	 * @param string $key     Key to submit.
	 * @param string $nonce   Nonce value to submit.
	 * @return string The redirect location the handler attempted.
	 */
	private function submit_confirm( int $user_id, string $key, string $nonce ): string {
		$_SERVER['REQUEST_METHOD']       = 'POST';
		$_POST['wc_verify_email_submit'] = '1';
		$_POST['_wpnonce']               = $nonce;
		$_POST['wc_verify_email_user']   = (string) $user_id;
		$_POST['wc_verify_email_key']    = $key;

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
			$_SERVER['REQUEST_METHOD'] = 'GET';
			unset(
				$_POST['wc_verify_email_submit'],
				$_POST['_wpnonce'],
				$_POST['wc_verify_email_user'],
				$_POST['wc_verify_email_key']
			);
		}

		return $redirect;
	}
}
