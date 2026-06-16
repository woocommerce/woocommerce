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
		parent::tearDown();
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
	 * @testdox should_show_prompt returns true for an unverified customer with linkable guest orders.
	 */
	public function test_should_show_prompt_returns_true_for_logged_in_unverified_customer(): void {
		$email   = 'prompt-unverified@example.com';
		$user_id = wc_create_new_customer( $email, 'promptunverified', 'pw' );
		wp_set_current_user( $user_id );

		// A linkable guest order must exist for the prompt to appear.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();

		$this->assertTrue( $this->sut->should_show_prompt(), 'Unverified customers with linkable guest orders should see the prompt' );
	}

	/**
	 * @testdox should_show_prompt returns false for an unverified customer with no linkable guest orders.
	 */
	public function test_should_show_prompt_returns_false_without_linkable_orders(): void {
		$user_id = wc_create_new_customer( 'prompt-no-orders@example.com', 'promptnoorders', 'pw' );
		wp_set_current_user( $user_id );

		$this->assertFalse( $this->sut->should_show_prompt(), 'Unverified customers with nothing to link should not see the prompt' );
	}

	/**
	 * @testdox should_show_prompt returns false while a verification email was just sent.
	 */
	public function test_should_show_prompt_returns_false_when_recently_sent(): void {
		$email   = 'recent-send@example.com';
		$user_id = wc_create_new_customer( $email, 'recentsenduser', 'pw' );
		wp_set_current_user( $user_id );

		// A linkable guest order exists, so only the recent-send suppression should hide the prompt.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();

		// Simulate a verification email having just been sent.
		$this->service->create_verification_key( $user_id );

		$this->assertFalse( $this->sut->should_show_prompt(), 'Prompt should be hidden while a freshly-sent verification link is still valid' );
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
		add_action(
			'woocommerce_customer_verify_email_notification',
			function ( $uid ) use ( &$notification_fired ) {
				unset( $uid );
				$notification_fired = true;
			}
		);

		// handle_send_request() calls wp_safe_redirect() and exit; catch the redirect with a filter.
		add_filter( 'wp_redirect', '__return_false' );

		try {
			$this->sut->handle_send_request();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Swallow exit() equivalents if any slip through.
		}

		remove_all_filters( 'wp_redirect' );
		remove_all_actions( 'woocommerce_customer_verify_email_notification' );
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
		add_action(
			'woocommerce_customer_verify_email_notification',
			function () use ( &$notification_fired ) {
				$notification_fired = true;
			}
		);

		add_filter( 'wp_redirect', '__return_false' );

		try {
			$this->sut->handle_send_request();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		remove_all_filters( 'wp_redirect' );
		remove_all_actions( 'woocommerce_customer_verify_email_notification' );
		unset( $_GET['_wpnonce'] );

		$this->assertFalse( $notification_fired, 'Notification hook should not fire when the nonce is invalid' );
	}

	/**
	 * @testdox handle_send_request suppresses a second send when issued within the rate-limit window.
	 */
	public function test_handle_send_request_suppresses_immediate_resend(): void {
		$user_id = wc_create_new_customer( 'rate-limit@example.com', 'ratelimituser', 'pw' );
		wp_set_current_user( $user_id );

		$notification_count = 0;
		add_action(
			'woocommerce_customer_verify_email_notification',
			function () use ( &$notification_count ) {
				$notification_count++;
			}
		);

		add_filter( 'wp_redirect', '__return_false' );

		// First send (no existing key).
		$_GET['_wpnonce'] = wp_create_nonce( 'woocommerce-send-verification-email' );
		try {
			$this->sut->handle_send_request();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		// Second send — key was just created (seconds_since_last_key < 60).
		$_GET['_wpnonce'] = wp_create_nonce( 'woocommerce-send-verification-email' );
		try {
			$this->sut->handle_send_request();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		remove_all_filters( 'wp_redirect' );
		remove_all_actions( 'woocommerce_customer_verify_email_notification' );
		unset( $_GET['_wpnonce'] );

		$this->assertSame( 1, $notification_count, 'Notification should fire exactly once despite two send attempts within the rate-limit window' );
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
		$this->assertLessThan( 5, $elapsed, 'Elapsed time should be under 5 seconds immediately after key creation' );
	}
}
