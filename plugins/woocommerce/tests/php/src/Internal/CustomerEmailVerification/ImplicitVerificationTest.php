<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationEventListener;
use WC_Unit_Test_Case;

/**
 * Tests for implicit email verification triggered by completed password resets.
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

		// Resolve the listener so its after_password_reset hook is registered.
		wc_get_container()->get( VerificationEventListener::class );
	}

	/**
	 * Completing a password reset fires the core after_password_reset action, which both
	 * WordPress core and WooCommerce dispatch. The listener should mark the email verified.
	 *
	 * @testdox A completed password reset marks the customer's email as verified.
	 */
	public function test_after_password_reset_marks_email_verified(): void {
		$user_id = wc_create_new_customer( 'reset@example.com', 'resetuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'New customers should not be verified by default' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing a core WordPress hook to exercise the listener, not defining a new hook.
		do_action( 'after_password_reset', $user, 'newpassword123' );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'Customer should be verified after a password reset' );
	}
}
