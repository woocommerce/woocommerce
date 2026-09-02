<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification\Admin;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\Admin\UserProfileField;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use WC_Unit_Test_Case;

/**
 * Tests for the admin user-profile "Email address verified" checkbox.
 */
class UserProfileFieldTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var UserProfileField
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
		$this->sut     = wc_get_container()->get( UserProfileField::class );
		$this->service = wc_get_container()->get( EmailVerificationService::class );

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		unset( $_POST['wc_email_verified'], $_POST['wc_email_verified_nonce'] );
		parent::tearDown();
	}

	/**
	 * @testdox Saving the profile with the box checked (and a valid nonce) marks the user verified.
	 */
	public function test_save_marks_verified_when_checked(): void {
		$user_id = wc_create_new_customer( 'profile-check@example.com', 'profilecheck', 'pw' );

		$_POST['wc_email_verified_nonce'] = wp_create_nonce( 'wc_email_verified_' . $user_id );
		$_POST['wc_email_verified']       = '1';

		$this->sut->save( $user_id );

		$this->assertTrue( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox Saving the profile with the box unchecked (and a valid nonce) clears verification.
	 */
	public function test_save_clears_when_unchecked(): void {
		$user_id = wc_create_new_customer( 'profile-uncheck@example.com', 'profileuncheck', 'pw' );
		$this->service->mark_verified( $user_id );
		$this->assertTrue( $this->service->is_verified( $user_id ) );

		$_POST['wc_email_verified_nonce'] = wp_create_nonce( 'wc_email_verified_' . $user_id );
		// No 'wc_email_verified' key in POST means the checkbox was unticked.

		$this->sut->save( $user_id );

		$this->assertFalse( $this->service->is_verified( $user_id ) );
	}

	/**
	 * @testdox Saving without a valid nonce leaves verification untouched.
	 */
	public function test_save_does_nothing_without_valid_nonce(): void {
		$user_id = wc_create_new_customer( 'profile-nononce@example.com', 'profilenononce', 'pw' );

		$_POST['wc_email_verified'] = '1';
		// No nonce provided.

		$this->sut->save( $user_id );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'Without a valid nonce the checkbox must not take effect.' );
	}

	/**
	 * @testdox A non-privileged user cannot self-verify their own email via a profile save.
	 */
	public function test_save_does_not_allow_non_privileged_self_verify(): void {
		$user_id = wc_create_new_customer( 'self-verify@example.com', 'selfverify', 'pw' );
		wp_set_current_user( $user_id );

		$_POST['wc_email_verified_nonce'] = wp_create_nonce( 'wc_email_verified_' . $user_id );
		$_POST['wc_email_verified']       = '1';

		$this->sut->save( $user_id );

		$this->assertFalse( $this->service->is_verified( $user_id ), 'A customer without manage_woocommerce must not be able to verify their own email.' );
	}

	/**
	 * @testdox Changing the email and ticking verify in the same save verifies the new address.
	 */
	public function test_save_verifies_new_email_when_changed_and_checked_together(): void {
		$user_id = wc_create_new_customer( 'before-change@example.com', 'emailchange', 'pw' );

		// A fresh instance so its constructor registers the profile_update hook within this test:
		// the container caches a shared instance, so resolving it again would not re-add the hook.
		$field = new UserProfileField();
		$field->init( $this->service );

		$_POST['wc_email_verified_nonce'] = wp_create_nonce( 'wc_email_verified_' . $user_id );
		$_POST['wc_email_verified']       = '1';

		// Changing the email runs through wp_update_user, whose profile_update hook fires after the
		// new address is written, so verification must land on the new address, not the old one.
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'after-change@example.com',
			)
		);

		$this->assertTrue( $this->service->is_verified( $user_id ), 'The newly saved email address should be verified.' );
	}
}
