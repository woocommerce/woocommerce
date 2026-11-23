<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Unit_Test_Case;

/**
 * Tests for the VerificationController class: confirmation links that need the account password.
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
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			// Request globals and the shared controller's state are process state the base class does not reset.
			unset( $_GET['wc_verify_email_user'], $_GET['wc_verify_email_key'] );
			$_POST    = array();
			$property = new \ReflectionProperty( VerificationController::class, 'confirm_form_user' );
			$property->setAccessible( true );
			$property->setValue( $this->ctrl, null );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox send_verification_email() emits a link; the owner confirms it with their password, which verifies and links orders.
	 */
	public function test_send_then_confirm_with_password_links_orders(): void {
		$email   = 'roundtrip@example.com';
		$user_id = wc_create_new_customer( $email, 'roundtripuser', 'pw' );
		$order   = $this->create_guest_order( $email );

		$verify_url = $this->capture_sent_url( $user_id );
		$this->assertStringContainsString( 'wc_verify_email_key=', $verify_url, 'The emailed value should be a verify URL carrying a key' );
		$key = $this->key_from_url( $verify_url );

		wp_set_current_user( $user_id );
		$redirect = $this->submit_verify_form( $user_id, $key, 'pw' );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The correct password should verify the address' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'Guest order should link to the verified customer' );
		$this->assertStringContainsString( 'wc_verify_notice=confirmed', $redirect );
	}

	/**
	 * @testdox Opening a link never verifies: logged out, as another account, or as the owner.
	 *
	 * A planted login session (login CSRF) or a mail scanner can only open the link, so it can never confirm it.
	 */
	public function test_opening_link_never_verifies(): void {
		$owner_id = wc_create_new_customer( 'lockdown-owner@example.com', 'lockdownowner', 'pw' );
		$other_id = wc_create_new_customer( 'lockdown-other@example.com', 'lockdownother', 'pw' );
		$key      = $this->service->create_verification_key( $owner_id );

		wp_set_current_user( 0 );
		$this->assertSame( '', $this->open_verify_link( $owner_id, $key ), 'A logged-out open should show the form, not a redirect' );

		wp_set_current_user( $other_id );
		$this->assertStringContainsString( 'wc_verify_notice=mismatch', $this->open_verify_link( $owner_id, $key ) );

		wp_set_current_user( $owner_id );
		$this->assertSame( '', $this->open_verify_link( $owner_id, $key ), 'The owner should see the form, not a redirect' );

		$this->assertFalse( $this->service->is_verified( $owner_id ), 'Opening the link must never verify' );
		$this->assertTrue( $this->service->has_pending_key( $owner_id ), 'Opening the link must never consume the key' );
	}

	/**
	 * @testdox The password form replaces the My Account page, and only shows the email address to the logged in customer.
	 */
	public function test_form_replaces_my_account_page(): void {
		$user_id = wc_create_new_customer( 'render@example.com', 'renderuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$logged_in = $this->render_my_account( $user_id, $key );

		$this->assertStringContainsString( 'render@example.com', $logged_in );
		$this->assertStringContainsString( 'name="wc_confirm_password"', $logged_in );
		$this->assertStringNotContainsString( 'woocommerce-MyAccount-navigation', $logged_in, 'The account navigation should be hidden.' );

		wp_set_current_user( 0 );
		$logged_out = $this->render_my_account( $user_id, $key );

		$this->assertStringContainsString( 'name="wc_confirm_password"', $logged_out );
		$this->assertStringNotContainsString( 'render@example.com', $logged_out, 'A logged out page should not show the email address.' );
		$this->assertStringNotContainsString( 'renderuser', $logged_out, 'A logged out page should not show the username.' );
		$this->assertStringNotContainsString( 'woocommerce-form-register', $logged_out, 'The registration form should not show.' );
		$this->assertStringNotContainsString( 'woocommerce-form-login', $logged_out, 'The standard login form should not show.' );
	}

	/**
	 * @testdox A logged out visitor with a wrong key sees neither the form nor the account's email address.
	 */
	public function test_logged_out_wrong_key_does_not_reveal_email(): void {
		$user_id = wc_create_new_customer( 'hidden@example.com', 'hiddenuser', 'pw' );
		$this->service->create_verification_key( $user_id );

		wp_set_current_user( 0 );
		$html = $this->render_my_account( $user_id, 'totally-wrong-key' );

		$this->assertStringNotContainsString( 'hidden@example.com', $html );
		$this->assertStringNotContainsString( 'wc_confirm_password', $html );
		$this->assertStringContainsString( 'invalid or has expired', $html );
	}

	/**
	 * @testdox A logged out customer confirms with their password, is logged in, and lands on the dashboard.
	 */
	public function test_logged_out_customer_confirms_and_logs_in(): void {
		$email   = 'login-confirm@example.com';
		$user_id = wc_create_new_customer( $email, 'loginconfirm', 'pw' );
		$order   = $this->create_guest_order( $email );
		$key     = $this->service->create_verification_key( $user_id );
		add_filter( 'send_auth_cookies', '__return_false' );
		$verified_at_login = null;
		add_action(
			'wp_login',
			function () use ( $user_id, &$verified_at_login ) {
				$verified_at_login = $this->service->is_verified( $user_id );
			}
		);

		wp_set_current_user( 0 );
		$redirect = $this->submit_verify_form( $user_id, $key, 'pw' );

		$this->assertStringContainsString( 'wc_verify_notice=confirmed', $redirect );
		$this->assertStringNotContainsString( 'orders', $redirect, 'The customer should land on the dashboard.' );
		$this->assertTrue( $this->service->is_verified( $user_id ) );
		$this->assertTrue( $verified_at_login, 'The customer should log in after verifying, because verifying ends other sessions.' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id() );
	}

	/**
	 * @testdox A logged out customer cannot confirm with a wrong password, or with a password and the wrong key.
	 */
	public function test_logged_out_customer_needs_password_and_key(): void {
		$user_id = wc_create_new_customer( 'wrong-details@example.com', 'wrongdetails', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );
		add_filter( 'send_auth_cookies', '__return_false' );

		wp_set_current_user( 0 );
		$this->assertSame( '', $this->submit_verify_form( $user_id, $key, 'not-the-password' ) );
		$this->assertStringContainsString( 'incorrect', wp_json_encode( wc_get_notices( 'error' ) ) );
		wc_clear_notices();

		$this->assertSame( '', $this->submit_verify_form( $user_id, 'totally-wrong-key', 'pw' ) );
		$this->assertStringContainsString( 'invalid or has expired', wp_json_encode( wc_get_notices( 'error' ) ) );
		$this->assertFalse( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox The password must belong to the link's account, even when its login matches another account's email address.
	 */
	public function test_password_must_belong_to_the_links_account(): void {
		$other_id = wc_create_new_customer( 'lookalike@example.com', 'lookalikeother', 'other-pw' );
		$user_id  = wc_create_new_customer( 'target@example.com', 'lookalike@example.com', 'pw' );
		$key      = $this->service->create_verification_key( $user_id );
		add_filter( 'send_auth_cookies', '__return_false' );

		wp_set_current_user( 0 );
		$this->assertSame( '', $this->submit_verify_form( $user_id, $key, 'other-pw' ) );

		$this->assertFalse( $this->service->is_verified( $user_id ) );
		$this->assertFalse( $this->service->is_verified( $other_id ) );
	}

	/**
	 * @testdox Three wrong passwords are allowed, then the next attempt is rate limited.
	 */
	public function test_wrong_passwords_are_rate_limited_after_three_attempts(): void {
		$user_id = wc_create_new_customer( 'wrongpw@example.com', 'wrongpwuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		foreach ( array( 1, 2, 3 ) as $attempt ) {
			$this->assertSame( '', $this->submit_verify_form( $user_id, $key, 'not-the-password' ), 'A wrong password should show the form again' );
			$this->assertStringContainsString( 'incorrect', wp_json_encode( wc_get_notices( 'error' ) ), "Attempt $attempt should be checked" );
			wc_clear_notices();
		}

		$this->submit_verify_form( $user_id, $key, 'pw' );

		$this->assertStringContainsString( 'wait a few seconds', wp_json_encode( wc_get_notices( 'error' ) ), 'The fourth attempt should be rate limited' );
		$this->assertFalse( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox Two wrong passwords do not stop the right one straight after.
	 */
	public function test_right_password_works_after_two_wrong_attempts(): void {
		$user_id = wc_create_new_customer( 'twowrong@example.com', 'twowronguser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_verify_form( $user_id, $key, 'not-the-password' );
		$this->submit_verify_form( $user_id, $key, 'still-not-the-password' );

		$this->assertStringContainsString( 'wc_verify_notice=confirmed', $this->submit_verify_form( $user_id, $key, 'pw' ) );
		$this->assertTrue( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox The password form needs a valid nonce.
	 */
	public function test_password_form_needs_nonce(): void {
		$user_id = wc_create_new_customer( 'nonce@example.com', 'nonceuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_verify_form( $user_id, $key, 'pw', 'bad-nonce' );

		$this->assertFalse( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox Opening a link with a wrong or expired key as the owner shows the expired notice instead of the form.
	 */
	public function test_invalid_key_shows_expired_notice(): void {
		$user_id = wc_create_new_customer( 'wrongkey@example.com', 'wrongkeyuser', 'pw' );
		$this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );

		$this->assertStringContainsString( 'wc_verify_notice=expired', $this->open_verify_link( $user_id, 'totally-wrong-key' ) );
		$this->assertFalse( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox Opening the link again once verified lands on the dashboard without the form or a success notice.
	 */
	public function test_reopening_link_after_verified_lands_on_dashboard(): void {
		$user_id = wc_create_new_customer( 'reopen@example.com', 'reopenuser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_verify_form( $user_id, $key, 'pw' );
		$second = $this->open_verify_link( $user_id, $key );

		$this->assertTrue( $this->service->is_verified( $user_id ) );
		$this->assertSame( wc_get_page_permalink( 'myaccount' ), $second );
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
	 * Submit the password form for a verify link; returns the redirect target, or '' when the form shows again.
	 *
	 * @param int    $user_id  Target user ID.
	 * @param string $key      Plaintext key.
	 * @param string $password Password to submit.
	 * @param string $nonce    Nonce to submit; a valid one when empty.
	 * @return string
	 */
	private function submit_verify_form( int $user_id, string $key, string $password, string $nonce = '' ): string {
		$_POST = array(
			'wc_confirm_email'                => '1',
			'wc_confirm_password'             => $password,
			'woocommerce-confirm-email-nonce' => '' !== $nonce ? $nonce : wp_create_nonce( 'woocommerce-confirm-email' ),
		);

		try {
			return $this->open_verify_link( $user_id, $key );
		} finally {
			$_POST = array();
		}
	}

	/**
	 * Open a verify link, then render the My Account page.
	 *
	 * @param int    $user_id Target user ID.
	 * @param string $key     Plaintext key.
	 * @return string The rendered page.
	 */
	private function render_my_account( int $user_id, string $key ): string {
		$this->open_verify_link( $user_id, $key );

		ob_start();
		\WC_Shortcode_My_Account::output( array() );

		return (string) ob_get_clean();
	}

	/**
	 * Simulate opening a verify link; returns the redirect target, or '' when no redirect happened.
	 *
	 * @param int    $user_id Target user ID.
	 * @param string $key     Plaintext key.
	 * @return string
	 */
	private function open_verify_link( int $user_id, string $key ): string {
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
