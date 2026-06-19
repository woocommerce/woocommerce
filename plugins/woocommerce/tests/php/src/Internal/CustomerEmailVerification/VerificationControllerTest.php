<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\OrderLinker;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Unit_Test_Case;

/**
 * Tests for the VerificationController class.
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
		// Resolve OrderLinker so its woocommerce_customer_email_verified hook is registered.
		wc_get_container()->get( OrderLinker::class );
	}

	/**
	 * @testdox A valid key should mark the user as verified.
	 */
	public function test_valid_key_marks_verified(): void {
		$user_id = wc_create_new_customer( 'verify-valid@example.com', 'verifyvaliduser', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		$result = $this->ctrl->process_verification( $user_id, $key );

		$this->assertTrue( $result, 'process_verification() should return true for a valid key' );
		$this->assertTrue( $this->service->is_verified( $user_id ), 'User should be marked verified after a valid key' );
	}

	/**
	 * @testdox An invalid key should not mark the user as verified.
	 */
	public function test_invalid_key_does_not_verify(): void {
		$user_id = wc_create_new_customer( 'verify-invalid@example.com', 'verifyinvaliduser', 'pw' );
		$this->service->create_verification_key( $user_id );

		$result = $this->ctrl->process_verification( $user_id, 'bogus' );

		$this->assertFalse( $result, 'process_verification() should return false for a bogus key' );
		$this->assertFalse( $this->service->is_verified( $user_id ), 'User should not be verified after a bad key' );
	}

	/**
	 * @testdox Sending a verification email then verifying the link should mark the user verified and link their guest orders.
	 */
	public function test_send_then_verify_round_trip_links_orders(): void {
		$email   = 'roundtrip@example.com';
		$user_id = wc_create_new_customer( $email, 'roundtripuser', 'pw' );

		// Create a matching guest order.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();
		$order_id = $order->get_id();

		// Capture the verify URL emitted by send_verification_email().
		$captured_url = '';
		$listener     = static function ( $uid, $url ) use ( &$captured_url ) {
			// The $uid arg is unused but required by the two-argument hook signature.
			unset( $uid );
			$captured_url = $url;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener, 10, 2 );

		$this->ctrl->send_verification_email( $user_id );

		remove_action( 'woocommerce_customer_verify_email_notification', $listener, 10 );

		$this->assertNotEmpty( $captured_url, 'send_verification_email() should fire the notification action with a URL' );

		// Extract key and user from the URL and process the verification.
		parse_str( (string) wp_parse_url( $captured_url, PHP_URL_QUERY ), $args );

		$this->assertArrayHasKey( 'wc_verify_email_key', $args, 'URL should contain wc_verify_email_key' );
		$this->assertArrayHasKey( 'wc_verify_email_user', $args, 'URL should contain wc_verify_email_user' );

		$verified = $this->ctrl->process_verification( (int) $args['wc_verify_email_user'], $args['wc_verify_email_key'] );

		$this->assertTrue( $verified, 'process_verification() should return true for the emailed key' );
		$this->assertTrue( $this->service->is_verified( $user_id ), 'User should be verified after the round-trip' );

		// Confirm OrderLinker linked the guest order.
		$linked_order = wc_get_order( $order_id );
		$this->assertSame( $user_id, $linked_order->get_customer_id(), 'Guest order should be linked to the verified customer' );
	}

	/**
	 * @testdox A GET on the emailed link renders an auto-submitting confirm form without consuming the key.
	 */
	public function test_get_link_renders_form_without_consuming_key(): void {
		$user_id = wc_create_new_customer( 'get-landing@example.com', 'getlanding', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );

		// get_confirm_page_html() is the pure builder behind the render-and-exit path.
		$build = new \ReflectionMethod( $this->ctrl, 'get_confirm_page_html' );
		$build->setAccessible( true );
		$html = (string) $build->invoke( $this->ctrl, $user_id, $key );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'Rendering the landing page must not verify the user' );
		$this->assertTrue( $this->service->check_verification_key( $user_id, $key ), 'Rendering the landing page must not consume the one-time key' );
		// Must be a hidden field, not the button's value: HTMLFormElement.submit() (the JS auto-submit)
		// omits submit-button name/value, so a button-only marker silently breaks verification when JS is on.
		$this->assertStringContainsString( '<input type="hidden" name="wc_verify_email_submit"', $html, 'The submit marker that gates verification must be a hidden field so the JS auto-submit sends it' );
		$this->assertStringContainsString( '_wpnonce', $html, 'The form must include a nonce field' );
		$this->assertStringContainsString( '.submit()', $html, 'The page must auto-submit via JavaScript' );
		$this->assertStringContainsString( 'type="submit"', $html, 'A manual fallback button must be present for when JS is unavailable' );
	}

	/**
	 * @testdox Submitting the confirm form with a valid key verifies the address and links guest orders.
	 */
	public function test_confirm_submission_verifies_and_links_orders(): void {
		$email   = 'confirm-post@example.com';
		$user_id = wc_create_new_customer( $email, 'confirmpost', 'pw' );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();

		$key = $this->service->create_verification_key( $user_id );
		wp_set_current_user( $user_id );

		$redirect = $this->submit_confirmation( $user_id, $key, wp_create_nonce( 'woocommerce-verify-email' ) );

		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'A valid confirm submission should verify the address' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'Guest order should link to the verified customer' );
		$this->assertNotEmpty( $success_notices, 'A confirmation notice should be shown' );
		$this->assertStringContainsString( 'orders', $redirect, 'Should redirect to the Orders endpoint' );
	}

	/**
	 * @testdox A confirm submission with an invalid nonce does not verify or consume the key.
	 */
	public function test_confirm_submission_requires_valid_nonce(): void {
		$user_id = wc_create_new_customer( 'bad-nonce@example.com', 'badnonce', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );
		wp_set_current_user( $user_id );

		$this->submit_confirmation( $user_id, $key, 'not-a-valid-nonce' );

		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'An invalid nonce must not verify the address' );
		$this->assertTrue( $this->service->check_verification_key( $user_id, $key ), 'An invalid nonce must not consume the key' );
		$this->assertNotEmpty( $error_notices, 'An invalid request should produce an error notice' );
	}

	/**
	 * @testdox A confirm submission while logged in as a different user is rejected without verifying.
	 */
	public function test_confirm_submission_rejected_when_logged_in_as_other_user(): void {
		$link_owner = wc_create_new_customer( 'link-owner@example.com', 'linkowner', 'pw' );
		$other_user = wc_create_new_customer( 'other-user@example.com', 'otheruser', 'pw' );
		$key        = $this->service->create_verification_key( $link_owner );

		wp_set_current_user( $other_user );

		$redirect = $this->submit_confirmation( $link_owner, $key, wp_create_nonce( 'woocommerce-verify-email' ) );

		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $link_owner ), 'A submission while logged in as a different user must not verify the link owner' );
		$this->assertNotEmpty( $error_notices, 'An error notice should explain that verification is blocked while logged in elsewhere' );
		$this->assertStringContainsString( 'orders', $redirect, 'Should redirect back to the Orders endpoint' );
	}

	/**
	 * @testdox Submitting the confirm form twice shows success, not a contradictory error.
	 */
	public function test_double_confirm_submission_does_not_error(): void {
		$user_id = wc_create_new_customer( 'double-confirm@example.com', 'doubleconfirm', 'pw' );
		$key     = $this->service->create_verification_key( $user_id );
		wp_set_current_user( $user_id );

		$nonce = wp_create_nonce( 'woocommerce-verify-email' );
		$this->submit_confirmation( $user_id, $key, $nonce );
		$this->submit_confirmation( $user_id, $key, $nonce );

		$error_notices   = wc_get_notices( 'error' );
		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The first submission should verify the address' );
		$this->assertEmpty( $error_notices, 'A repeat submission of a consumed key must not produce an error once verified' );
		$this->assertCount( 1, $success_notices, 'The customer should see exactly one confirmation notice, not a duplicate' );
	}

	/**
	 * Drive a confirm-form POST through the controller, returning the captured redirect target.
	 *
	 * handle_confirm_submission() ends in wp_safe_redirect()/exit; a filter throws the redirect target
	 * so the exit is never reached and the test can assert on the outcome.
	 *
	 * @param int    $user_id User ID to submit.
	 * @param string $key     Verification key to submit.
	 * @param string $nonce   Nonce value to submit.
	 * @return string The redirect location the handler attempted.
	 */
	private function submit_confirmation( int $user_id, string $key, string $nonce ): string {
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
