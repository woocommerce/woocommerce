<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use WC_Unit_Test_Case;

/**
 * Tests for implicit email verification triggered by password reset and set-password flows.
 */
class ImplicitVerificationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var EmailVerificationService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( EmailVerificationService::class );

		// Suppress auth-cookie and reset-password-cookie header calls that fail in
		// the PHPUnit environment because output has already been sent.
		add_filter( 'send_auth_cookies', '__return_false' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: swallow "headers already sent" notices from cookie sends during CLI test runs.
		set_error_handler(
			function ( int $errno, string $errstr ): bool {
				// Allow "headers already sent" warnings through as a no-op so that
				// setcookie() inside set_reset_password_cookie() does not become a
				// PHPUnit error (convertWarningsToExceptions="true").
				return str_contains( $errstr, 'headers already sent' );
			},
			E_WARNING
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		restore_error_handler();
		remove_filter( 'send_auth_cookies', '__return_false' );
		parent::tearDown();
	}

	/**
	 * @testdox Completing the lost-password reset flow should mark the customer's email as verified.
	 */
	public function test_password_reset_marks_email_verified(): void {
		$user_id = wc_create_new_customer( 'reset@example.com', 'resetuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'New customers should not be verified by default' );

		\WC_Shortcode_My_Account::reset_password( $user, 'newpassword123' );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'Customer should be verified after password reset' );
	}
}
