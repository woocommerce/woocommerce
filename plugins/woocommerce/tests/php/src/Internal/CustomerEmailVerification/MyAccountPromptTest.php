<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Unit_Test_Case;

/**
 * Tests for the My Account email-verification prompt and send-trigger.
 */
class MyAccountPromptTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var VerificationController
	 */
	private $sut;

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
		$this->sut     = wc_get_container()->get( VerificationController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		wc_clear_notices();
		parent::tearDown();
	}

	/**
	 * Render the My Account prompt and return its HTML.
	 *
	 * @return string
	 */
	private function render_prompt(): string {
		ob_start();
		$this->sut->render_prompt();
		return (string) ob_get_clean();
	}

	/**
	 * Invoke handle_send_request(), trapping the wp_safe_redirect() + exit it ends with.
	 *
	 * handle_send_request() always finishes with wp_safe_redirect() then exit;. A
	 * '__return_false' filter does NOT prevent that exit — it would terminate the whole
	 * PHPUnit run, silently skipping every later test. Throwing from the wp_redirect
	 * filter aborts control flow before exit so the test survives to assert.
	 */
	private function dispatch_send_request(): string {
		$location = '';
		$abort    = static function ( $loc ): void {
			throw new \RuntimeException( esc_html( (string) $loc ) );
		};
		add_filter( 'wp_redirect', $abort );
		try {
			$this->sut->handle_send_request();
		} catch ( \RuntimeException $e ) {
			// Expected: handle_send_request() redirects and exits.
			$location = $e->getMessage();
		} finally {
			remove_filter( 'wp_redirect', $abort );
		}

		return $location;
	}

	// -------------------------------------------------------------------------
	// should_show_prompt()
	// -------------------------------------------------------------------------

	/**
	 * @testdox should_show_prompt returns false when no user is logged in.
	 */
	public function test_should_show_prompt_returns_false_for_logged_out_visitor(): void {
		wp_set_current_user( 0 );

		$this->assertFalse( $this->sut->should_show_prompt(), 'Logged-out visitors should not see the prompt' );
	}

	/**
	 * @testdox should_show_prompt returns true for a logged-in unverified customer.
	 */
	public function test_should_show_prompt_returns_true_for_logged_in_unverified_customer(): void {
		$user_id = wc_create_new_customer( 'prompt-unverified@example.com', 'promptunverified', 'pw' );
		wp_set_current_user( $user_id );

		$this->assertTrue( $this->sut->should_show_prompt(), 'Unverified customers should see the prompt' );
	}

	/**
	 * @testdox should_show_prompt returns true for an unverified customer with no linkable guest orders.
	 */
	public function test_should_show_prompt_returns_true_without_linkable_orders(): void {
		$user_id = wc_create_new_customer( 'prompt-no-orders@example.com', 'promptnoorders', 'pw' );
		wp_set_current_user( $user_id );

		$this->assertTrue( $this->sut->should_show_prompt(), 'Prompt visibility must not reveal whether matching guest orders exist' );
	}

	/**
	 * @testdox should_show_prompt returns false for an account using a temporary password.
	 */
	public function test_should_show_prompt_returns_false_with_temporary_password(): void {
		$user_id = wc_create_new_customer( 'temp-pass@example.com', 'temppassuser', 'pw' );
		wp_set_current_user( $user_id );

		update_user_option( $user_id, 'default_password_nag', true, true );

		$this->assertFalse( $this->sut->should_show_prompt(), 'Temp-password accounts confirm via their set-password link, so the prompt is suppressed' );
	}

	/**
	 * @testdox should_show_prompt returns false for a logged-in customer whose email is verified.
	 */
	public function test_should_show_prompt_returns_false_for_verified_customer(): void {
		$user_id = wc_create_new_customer( 'prompt-verified@example.com', 'promptverified', 'pw' );
		wp_set_current_user( $user_id );
		$this->service->mark_verified( $user_id );

		$this->assertFalse( $this->sut->should_show_prompt(), 'Verified customers should not see the prompt' );
	}

	// -------------------------------------------------------------------------
	// render_prompt()
	// -------------------------------------------------------------------------

	/**
	 * @testdox The prompt shows a send-link call to action when no link is pending.
	 */
	public function test_prompt_renders_send_cta_when_no_key(): void {
		$user_id = wc_create_new_customer( 'cta-prompt@example.com', 'ctapromptuser', 'pw' );
		wp_set_current_user( $user_id );

		$html = $this->render_prompt();

		$this->assertStringContainsString( 'wc_send_verification', $html, 'A prompt with no pending link should carry the send-link action.' );
	}

	/**
	 * @testdox The prompt points the customer to their inbox (no resend CTA) while a link was just sent.
	 */
	public function test_prompt_points_to_inbox_when_recently_sent(): void {
		$user_id = wc_create_new_customer( 'inbox-prompt@example.com', 'inboxpromptuser', 'pw' );
		wp_set_current_user( $user_id );

		// A link was just sent (seconds_since_last_key < rate-limit window).
		$this->service->create_verification_key( $user_id );

		$html = $this->render_prompt();

		$this->assertStringContainsString( 'check your inbox', $html, 'A just-sent prompt should point the customer to their inbox.' );
		$this->assertStringNotContainsString( 'wc_send_verification', $html, 'A just-sent prompt must not offer an immediate resend.' );
	}

	// -------------------------------------------------------------------------
	// handle_send_request()
	// -------------------------------------------------------------------------

	/**
	 * @testdox handle_send_request dispatches the verify-email notification when called with a valid nonce.
	 */
	public function test_handle_send_request_dispatches_notification_for_valid_nonce(): void {
		$user_id = wc_create_new_customer( 'send-trigger@example.com', 'sendtrigger', 'pw' );
		wp_set_current_user( $user_id );

		$_GET['_wpnonce'] = wp_create_nonce( 'woocommerce-send-verification-email' );

		$notification_fired = false;
		$listener           = static function () use ( &$notification_fired ) {
			$notification_fired = true;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener );

		$this->dispatch_send_request();

		remove_action( 'woocommerce_customer_verify_email_notification', $listener );
		unset( $_GET['_wpnonce'] );

		$this->assertTrue( $notification_fired, 'Notification hook should fire for a valid send request' );
	}

	/**
	 * @testdox handle_send_request does not dispatch the notification when the nonce is invalid.
	 */
	public function test_handle_send_request_rejects_invalid_nonce(): void {
		$user_id = wc_create_new_customer( 'bad-nonce@example.com', 'badnonceuser', 'pw' );
		wp_set_current_user( $user_id );

		$_GET['_wpnonce'] = 'not-a-valid-nonce';

		$notification_fired = false;
		$listener           = static function () use ( &$notification_fired ) {
			$notification_fired = true;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener );

		$this->dispatch_send_request();

		remove_action( 'woocommerce_customer_verify_email_notification', $listener );
		unset( $_GET['_wpnonce'] );

		$this->assertFalse( $notification_fired, 'Notification hook should not fire when the nonce is invalid' );
	}

	/**
	 * @testdox handle_send_request suppresses a second send within the rate-limit window and tells the customer why.
	 */
	public function test_handle_send_request_suppresses_immediate_resend(): void {
		$user_id = wc_create_new_customer( 'rate-limit@example.com', 'ratelimituser', 'pw' );
		wp_set_current_user( $user_id );

		$notification_count = 0;
		$listener           = static function () use ( &$notification_count ) {
			++$notification_count;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener );

		// First send (no existing key).
		$_GET['_wpnonce'] = wp_create_nonce( 'woocommerce-send-verification-email' );
		$this->dispatch_send_request();

		// Second send — key was just created (seconds_since_last_key < 60).
		$_GET['_wpnonce'] = wp_create_nonce( 'woocommerce-send-verification-email' );
		$throttled        = $this->dispatch_send_request();

		remove_action( 'woocommerce_customer_verify_email_notification', $listener );
		unset( $_GET['_wpnonce'] );

		$this->assertSame( 1, $notification_count, 'Notification should fire exactly once despite two send attempts within the rate-limit window' );
		$this->assertStringContainsString( 'wc_verify_notice=throttled', $throttled, 'A rate-limited resend must surface the throttled result notice instead of failing silently.' );
	}

	/**
	 * @testdox print_result_notice prints the matching notice for a known result code and nothing for an unknown one.
	 */
	public function test_print_result_notice_renders_only_known_codes(): void {
		$_GET['wc_verify_notice'] = 'sent';
		ob_start();
		$this->sut->print_result_notice();
		$sent_html = (string) ob_get_clean();

		$_GET['wc_verify_notice'] = 'not-a-real-code';
		ob_start();
		$this->sut->print_result_notice();
		$unknown_html = (string) ob_get_clean();

		unset( $_GET['wc_verify_notice'] );

		$this->assertStringContainsString( 'check your inbox', $sent_html, 'A known result code should print its notice.' );
		$this->assertSame( '', $unknown_html, 'An unknown result code should print nothing (no stray notice).' );
	}

	// -------------------------------------------------------------------------
	// EmailVerificationService::seconds_since_last_key()
	// -------------------------------------------------------------------------

	/**
	 * @testdox seconds_since_last_key returns null when no key has been issued.
	 */
	public function test_seconds_since_last_key_returns_null_with_no_key(): void {
		$user_id = wc_create_new_customer( 'nokey@example.com', 'nokeyuser', 'pw' );

		$this->assertNull( $this->service->seconds_since_last_key( $user_id ), 'Should return null when no key has been issued' );
	}

	/**
	 * @testdox seconds_since_last_key returns a small non-negative integer immediately after key creation.
	 */
	public function test_seconds_since_last_key_returns_small_value_after_key_creation(): void {
		$user_id = wc_create_new_customer( 'freshkey@example.com', 'freshkeyuser', 'pw' );
		$this->service->create_verification_key( $user_id );

		$elapsed = $this->service->seconds_since_last_key( $user_id );

		$this->assertNotNull( $elapsed, 'Should return an integer after key creation' );
		$this->assertGreaterThanOrEqual( 0, $elapsed, 'Elapsed time should never be negative' );
		// Generous upper bound: proves a real, recent elapsed value without being flaky on a slow runner.
		$this->assertLessThan( 60, $elapsed, 'Elapsed time should be well within the rate-limit window after key creation' );
	}
}
