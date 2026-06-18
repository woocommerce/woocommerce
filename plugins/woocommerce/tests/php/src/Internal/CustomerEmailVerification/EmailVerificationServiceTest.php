<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Tests for the EmailVerificationService class.
 */
class EmailVerificationServiceTest extends WC_Unit_Test_Case {

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
	}

	/**
	 * @testdox A freshly created customer should not be verified by default.
	 */
	public function test_user_is_unverified_by_default(): void {
		$user_id = wc_create_new_customer( 'a@example.com', 'usera', 'pw' );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'New customers should not be verified by default' );
	}

	/**
	 * @testdox Marking a user as verified should set the meta and fire the hook exactly once.
	 */
	public function test_mark_verified_sets_meta_and_fires_hook(): void {
		$user_id    = wc_create_new_customer( 'b@example.com', 'userb', 'pw' );
		$hook_calls = 0;
		$hook_arg   = null;

		$listener = static function ( $id ) use ( &$hook_calls, &$hook_arg ) {
			++$hook_calls;
			$hook_arg = $id;
		};
		add_action( 'woocommerce_customer_email_verified', $listener );

		$this->sut->mark_verified( $user_id );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified after mark_verified()' );
		$this->assertSame( 1, $hook_calls, 'Hook should fire exactly once' );
		$this->assertSame( $user_id, $hook_arg, 'Hook should receive the correct user ID' );

		remove_action( 'woocommerce_customer_email_verified', $listener );
	}

	/**
	 * @testdox A valid key should be accepted and an invalid key should be rejected.
	 */
	public function test_token_round_trip(): void {
		$user_id = wc_create_new_customer( 'c@example.com', 'userc', 'pw' );

		$key = $this->sut->create_verification_key( $user_id );

		$this->assertTrue( $this->sut->check_verification_key( $user_id, $key ), 'Valid key should be accepted' );
		$this->assertFalse( $this->sut->check_verification_key( $user_id, 'wrong-key' ), 'Wrong key should be rejected' );
	}

	/**
	 * @testdox An expired token should be rejected.
	 */
	public function test_expired_token_is_rejected(): void {
		$user_id = wc_create_new_customer( 'd@example.com', 'userd', 'pw' );
		$key     = $this->sut->create_verification_key( $user_id );
		// Rewrite the stored value's timestamp to be older than the expiry window, keeping the same hash.
		$stored = (string) Users::get_site_user_meta( $user_id, '_wc_email_verification_key' );
		$parts  = explode( ':', $stored, 2 );
		$hash   = $parts[1];
		Users::update_site_user_meta( $user_id, '_wc_email_verification_key', ( time() - DAY_IN_SECONDS - 10 ) . ':' . $hash );
		$this->assertFalse( $this->sut->check_verification_key( $user_id, $key ) );
	}

	/**
	 * @testdox Clearing verification should reset the user's verified status.
	 */
	public function test_clear_verification_resets_status(): void {
		$user_id = wc_create_new_customer( 'e@example.com', 'usere', 'pw' );

		$this->sut->mark_verified( $user_id );
		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified before clearing' );

		$this->sut->clear_verification( $user_id );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'User should not be verified after clearing' );
	}

	/**
	 * @testdox A verified status self-invalidates when the account email changes.
	 */
	public function test_is_verified_false_after_email_change(): void {
		$user_id = wc_create_new_customer( 'before-change@example.com', 'changeuser', 'pw' );

		$this->sut->mark_verified( $user_id );
		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified for their current email' );

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'after-change@example.com',
			)
		);
		clean_user_cache( $user_id );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'Changing the account email must invalidate verification' );
	}

	/**
	 * @testdox A verified status is preserved across non-email profile changes.
	 */
	public function test_is_verified_preserved_after_non_email_change(): void {
		$user_id = wc_create_new_customer( 'keep-verified@example.com', 'keepuser', 'pw' );

		$this->sut->mark_verified( $user_id );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => 'Renamed Customer',
			)
		);
		clean_user_cache( $user_id );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'Non-email profile changes must not invalidate verification' );
	}
}
