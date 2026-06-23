<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
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
		// Link guest orders on verification (the boot wires this in production; register it here for the test).
		add_action( 'woocommerce_customer_email_verified', 'wc_update_new_customer_past_orders' );
	}

	/**
	 * @testdox send_verification_email() emits a six-digit code, and submitting it verifies and links orders.
	 */
	public function test_send_then_submit_code_links_orders(): void {
		$email   = 'roundtrip@example.com';
		$user_id = wc_create_new_customer( $email, 'roundtripuser', 'pw' );
		$order   = $this->create_guest_order( $email );

		$code = $this->capture_sent_code( $user_id );
		$this->assertMatchesRegularExpression( '/^\d{6}$/', $code, 'The emailed value should be a six-digit code' );

		wp_set_current_user( $user_id );
		$redirect        = $this->submit_code( $code, wp_create_nonce( 'woocommerce-verify-email' ) );
		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'A correct code should verify the address' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'Guest order should link to the verified customer' );
		$this->assertNotEmpty( $success_notices, 'A confirmation notice should be shown' );
		$this->assertStringContainsString( 'orders', $redirect, 'Should redirect to the Orders endpoint' );
	}

	/**
	 * @testdox A wrong code shows an error, does not verify, and leaves the code pending.
	 */
	public function test_wrong_code_errors_and_keeps_pending(): void {
		$user_id = wc_create_new_customer( 'wrong@example.com', 'wronguser', 'pw' );
		$code    = $this->service->create_code( $user_id );
		$wrong   = '000000' === $code ? '111111' : '000000';

		wp_set_current_user( $user_id );
		$this->submit_code( $wrong, wp_create_nonce( 'woocommerce-verify-email' ) );
		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A wrong code must not verify' );
		$this->assertTrue( $this->service->has_pending_code( $user_id ), 'A wrong code (attempts remaining) must stay pending' );
		$this->assertNotEmpty( $error_notices, 'A wrong code should produce an error notice' );
	}

	/**
	 * @testdox A submission with an invalid nonce does not verify or consume the code.
	 */
	public function test_submission_requires_valid_nonce(): void {
		$user_id = wc_create_new_customer( 'bad-nonce@example.com', 'badnonce', 'pw' );
		$code    = $this->service->create_code( $user_id );

		wp_set_current_user( $user_id );
		$this->submit_code( $code, 'not-a-valid-nonce' );
		$error_notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'An invalid nonce must not verify the address' );
		$this->assertTrue( $this->service->has_pending_code( $user_id ), 'An invalid nonce must not consume the code' );
		$this->assertNotEmpty( $error_notices, 'An invalid request should produce an error notice' );
	}

	/**
	 * @testdox A code submission with no logged-in user does not verify.
	 */
	public function test_submission_without_session_does_not_verify(): void {
		$user_id = wc_create_new_customer( 'nosession@example.com', 'nosession', 'pw' );
		$code    = $this->service->create_code( $user_id );

		wp_set_current_user( 0 );
		$redirect = $this->submit_code( $code, wp_create_nonce( 'woocommerce-verify-email' ) );
		wc_clear_notices();

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A logged-out submission must not verify anyone' );
		$this->assertStringContainsString( 'orders', $redirect, 'Should redirect to the Orders endpoint' );
	}

	/**
	 * @testdox Submitting twice after success shows success again, not a stale error.
	 */
	public function test_double_submission_does_not_error(): void {
		$user_id = wc_create_new_customer( 'double@example.com', 'doubleuser', 'pw' );
		$code    = $this->service->create_code( $user_id );

		wp_set_current_user( $user_id );
		$nonce = wp_create_nonce( 'woocommerce-verify-email' );
		$this->submit_code( $code, $nonce );
		$this->submit_code( $code, $nonce );
		$error_notices   = wc_get_notices( 'error' );
		$success_notices = wc_get_notices( 'success' );
		wc_clear_notices();
		wp_set_current_user( 0 );

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The first submission should verify the address' );
		$this->assertEmpty( $error_notices, 'A repeat submission once verified must not error' );
		$this->assertCount( 2, $success_notices, 'Each submission once verified should report success' );
	}

	/**
	 * @testdox The orders endpoint renders the verification form for the "verify" value and nothing otherwise.
	 */
	public function test_orders_endpoint_renders_form_on_verify_value(): void {
		$user_id = wc_create_new_customer( 'orders-verify@example.com', 'ordersverify', 'pw' );
		wp_set_current_user( $user_id );
		$this->service->create_code( $user_id );

		// A page-number value is a no-op (the orders list renders as usual).
		ob_start();
		$this->ctrl->maybe_render_on_orders_endpoint( '2' );
		$this->assertSame( '', ob_get_clean(), 'A normal page value must not render the verification form.' );

		// The reserved "verify" value renders the form.
		ob_start();
		$this->ctrl->maybe_render_on_orders_endpoint( 'verify' );
		$html = (string) ob_get_clean();

		// Restore the core orders-list callback the verify branch removed.
		add_action( 'woocommerce_account_orders_endpoint', 'woocommerce_account_orders' );
		wp_set_current_user( 0 );

		$this->assertStringContainsString( 'name="wc_verify_email_code"', $html, 'The /orders/verify/ sub-page must render the entry form.' );
	}

	/**
	 * @testdox The verify sub-page title is normalised to "Orders" instead of the default "Orders (Page 0)".
	 */
	public function test_orders_title_on_verify_subpage(): void {
		// The sub-page reuses the orders pagination slot, so WooCommerce titles it "Orders (Page 0)";
		// the filter forces a clean "Orders".
		set_query_var( 'orders', 'verify' );
		$this->assertSame( 'Orders', $this->ctrl->maybe_filter_orders_title( 'Orders (Page 0)' ) );

		set_query_var( 'orders', '2' );
		$this->assertSame( 'Orders (Page 2)', $this->ctrl->maybe_filter_orders_title( 'Orders (Page 2)' ), 'A normal orders page keeps its title.' );

		set_query_var( 'orders', '' );
	}

	/**
	 * @testdox A verified customer who lands on the /orders/verify/ sub-page is redirected to orders.
	 */
	public function test_verify_subpage_redirects_verified_user_to_orders(): void {
		$user_id = wc_create_new_customer( 'verified-endpoint@example.com', 'verifiedendpoint', 'pw' );
		$this->service->mark_verified( $user_id );
		wp_set_current_user( $user_id );

		// Simulate a request on /orders/verify/ (get_query_var reads from $wp_query).
		set_query_var( 'orders', 'verify' );

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
			set_query_var( 'orders', '' );
			wp_set_current_user( 0 );
		}

		$this->assertStringContainsString( 'orders', $redirect, 'A verified user with nothing to verify should be bounced to orders.' );
	}

	/**
	 * Capture the code emitted by send_verification_email().
	 *
	 * @param int $user_id User to send to.
	 * @return string The captured code.
	 */
	private function capture_sent_code( int $user_id ): string {
		$captured = '';
		$listener = static function ( $uid, $code ) use ( &$captured ) {
			// The $uid arg is unused but required by the two-argument hook signature.
			unset( $uid );
			$captured = $code;
		};
		add_action( 'woocommerce_customer_verify_email_notification', $listener, 10, 2 );
		$this->ctrl->send_verification_email( $user_id );
		remove_action( 'woocommerce_customer_verify_email_notification', $listener, 10 );

		return (string) $captured;
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
	 * Drive a code-form POST through the controller, returning the captured redirect target.
	 *
	 * handle_code_submission() ends in wp_safe_redirect()/exit; a filter throws the redirect target
	 * so the exit is never reached and the test can assert on the outcome.
	 *
	 * @param string $code  Code to submit.
	 * @param string $nonce Nonce value to submit.
	 * @return string The redirect location the handler attempted.
	 */
	private function submit_code( string $code, string $nonce ): string {
		$_SERVER['REQUEST_METHOD']       = 'POST';
		$_POST['wc_verify_email_submit'] = '1';
		$_POST['_wpnonce']               = $nonce;
		$_POST['wc_verify_email_code']   = $code;

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
				$_POST['wc_verify_email_code']
			);
		}

		return $redirect;
	}
}
