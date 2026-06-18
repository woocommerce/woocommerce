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
	 * @testdox A valid link is rejected without verifying or switching accounts when another user is logged in.
	 */
	public function test_verification_rejected_when_logged_in_as_other_user(): void {
		$link_owner = wc_create_new_customer( 'link-owner@example.com', 'linkowner', 'pw' );
		$other_user = wc_create_new_customer( 'other-user@example.com', 'otheruser', 'pw' );
		$key        = $this->service->create_verification_key( $link_owner );

		wp_set_current_user( $other_user );

		// maybe_process_request() reads the verify args from the URL and ends in wp_safe_redirect()/exit;
		// throw from the redirect filter so the exit is never reached and the test can assert.
		$_GET['wc_verify_email_key']  = $key;
		$_GET['wc_verify_email_user'] = (string) $link_owner;
		$abort                        = static function ( $location ): void {
			throw new \RuntimeException( esc_html( (string) $location ) );
		};
		add_filter( 'wp_redirect', $abort );
		try {
			$this->ctrl->maybe_process_request();
		} catch ( \RuntimeException $e ) {
			// Expected: the controller redirects and exits.
			unset( $e );
		} finally {
			remove_filter( 'wp_redirect', $abort );
		}

		$error_notices = wc_get_notices( 'error' );

		unset( $_GET['wc_verify_email_key'], $_GET['wc_verify_email_user'] );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $link_owner ), 'A link opened while logged in as a different user must not verify the link owner' );
		$this->assertNotEmpty( $error_notices, 'An error notice should explain that verification is blocked while logged in elsewhere' );
	}
}
