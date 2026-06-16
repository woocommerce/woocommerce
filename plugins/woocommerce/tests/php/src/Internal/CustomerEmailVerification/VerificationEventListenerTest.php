<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationEventListener;
use WC_Unit_Test_Case;

/**
 * Tests for VerificationEventListener.
 */
class VerificationEventListenerTest extends WC_Unit_Test_Case {

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

		// Resolve the listener so its hooks are registered.
		wc_get_container()->get( VerificationEventListener::class );
	}

	/**
	 * @testdox Changing a customer's account email clears their verified status.
	 */
	public function test_email_change_clears_verified_status(): void {
		$user_id = wc_create_new_customer( 'old@example.com', 'emailchangeuser', 'pw' );
		$this->sut->mark_verified( $user_id );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified before the email change' );

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'new@example.com',
			)
		);

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'Verified status should be cleared after an email change' );
	}

	/**
	 * @testdox Updating a non-email profile field does not clear the verified status.
	 */
	public function test_non_email_profile_update_preserves_verified_status(): void {
		$user_id = wc_create_new_customer( 'noemail@example.com', 'noemailchangeuser', 'pw' );
		$this->sut->mark_verified( $user_id );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified before the profile update' );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => 'New Display Name',
			)
		);

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'Verified status should remain after a non-email profile update' );
	}
}
