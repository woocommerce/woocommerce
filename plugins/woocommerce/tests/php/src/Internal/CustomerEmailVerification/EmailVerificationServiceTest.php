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

	private const KEY_META = '_wc_email_verification_key';

	private const ATTEMPTS_META = '_wc_email_verification_attempts';

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
	 * @testdox create_code() returns a fresh, pending six-digit numeric code.
	 */
	public function test_create_code_returns_six_digit_code(): void {
		$user_id = wc_create_new_customer( 'code@example.com', 'codeuser', 'pw' );

		$code = $this->sut->create_code( $user_id );

		$this->assertMatchesRegularExpression( '/^\d{6}$/', $code, 'A code must be six digits' );
		$this->assertTrue( $this->sut->has_pending_code( $user_id ), 'A freshly minted code should be pending' );
	}

	/**
	 * @testdox A correct code verifies the user and consumes the pending code.
	 */
	public function test_correct_code_verifies(): void {
		$user_id = wc_create_new_customer( 'c@example.com', 'userc', 'pw' );

		$code = $this->sut->create_code( $user_id );

		$this->assertSame( EmailVerificationService::RESULT_OK, $this->sut->verify_code( $user_id, $code ) );
		$this->assertTrue( $this->sut->is_verified( $user_id ), 'User should be verified after a correct code' );
		$this->assertFalse( $this->sut->has_pending_code( $user_id ), 'The code should be consumed on success' );
	}

	/**
	 * @testdox A wrong guess keeps the code pending until its attempts are exhausted, then burns it.
	 */
	public function test_wrong_code_burns_after_three_attempts(): void {
		$user_id = wc_create_new_customer( 'd@example.com', 'userd', 'pw' );

		$code  = $this->sut->create_code( $user_id );
		$wrong = $this->wrong_code( $code );

		$this->assertSame( EmailVerificationService::RESULT_WRONG, $this->sut->verify_code( $user_id, $wrong ) );
		$this->assertSame( EmailVerificationService::RESULT_WRONG, $this->sut->verify_code( $user_id, $wrong ) );
		$this->assertTrue( $this->sut->has_pending_code( $user_id ), 'Code should still be pending after two wrong guesses' );

		$this->assertSame( EmailVerificationService::RESULT_BURNED, $this->sut->verify_code( $user_id, $wrong ) );
		$this->assertFalse( $this->sut->has_pending_code( $user_id ), 'Code should be burned after the third wrong guess' );
		$this->assertFalse( $this->sut->is_verified( $user_id ) );
	}

	/**
	 * @testdox Minting a code seeds the attempts counter at the full budget so the compare-and-swap never inserts.
	 */
	public function test_create_code_seeds_attempts_counter(): void {
		$user_id = wc_create_new_customer( 'init@example.com', 'inituser', 'pw' );

		$this->assertSame( '', (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META ), 'No counter before the flow starts' );

		$this->sut->create_code( $user_id );

		$this->assertSame( '10', (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META ), 'The first code must seed the counter at the full budget' );
	}

	/**
	 * @testdox Resending a code preserves the remaining-attempts count (it does not lift the lockout budget).
	 */
	public function test_resending_a_code_preserves_remaining_attempts(): void {
		$user_id = wc_create_new_customer( 'resend@example.com', 'resenduser', 'pw' );
		$code    = $this->sut->create_code( $user_id );

		$this->sut->verify_code( $user_id, $this->wrong_code( $code ) );
		$this->assertSame( '9', (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META ), 'One wrong guess leaves nine of ten remaining' );

		$this->sut->create_code( $user_id );

		$this->assertSame( '9', (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META ), 'Resending must not reset the remaining-attempts count' );
	}

	/**
	 * @testdox Ten cumulative wrong guesses lock the user out permanently, even from a correct code.
	 */
	public function test_lockout_after_ten_cumulative_failures(): void {
		$user_id = wc_create_new_customer( 'lock@example.com', 'lockuser', 'pw' );

		$this->assertFalse( $this->sut->is_locked_out( $user_id ) );

		$current  = null;
		$failures = 0;
		while ( ! $this->sut->is_locked_out( $user_id ) && $failures < 15 ) {
			if ( ! $this->sut->has_pending_code( $user_id ) ) {
				$current = $this->sut->create_code( $user_id );
			}
			$this->sut->verify_code( $user_id, $this->wrong_code( (string) $current ) );
			++$failures;
		}

		$this->assertTrue( $this->sut->is_locked_out( $user_id ), 'User should be locked out' );
		$this->assertSame( 10, $failures, 'Lockout should take exactly ten cumulative failures' );

		// Once locked out, even the (now-deleted) correct code path returns LOCKED.
		$this->assertSame( EmailVerificationService::RESULT_LOCKED, $this->sut->verify_code( $user_id, '123456' ) );
	}

	/**
	 * @testdox Verifying a user (e.g. by the store owner) lifts an existing lockout.
	 */
	public function test_mark_verified_clears_lockout(): void {
		$user_id = wc_create_new_customer( 'unlock@example.com', 'unlockuser', 'pw' );
		$this->force_lockout( $user_id );

		$this->sut->mark_verified( $user_id );

		$this->assertFalse( $this->sut->is_locked_out( $user_id ), 'Marking verified must clear the lockout' );
		$this->assertTrue( $this->sut->is_verified( $user_id ) );
	}

	/**
	 * @testdox Clearing verification also lifts an existing lockout.
	 */
	public function test_clear_verification_clears_lockout(): void {
		$user_id = wc_create_new_customer( 'unlock2@example.com', 'unlockuser2', 'pw' );
		$this->force_lockout( $user_id );

		$this->sut->clear_verification( $user_id );

		$this->assertFalse( $this->sut->is_locked_out( $user_id ), 'Clearing verification must clear the lockout' );
	}

	/**
	 * @testdox An expired code is reported as expired and does not count as a failed guess.
	 */
	public function test_expired_code_not_counted_as_failure(): void {
		$user_id = wc_create_new_customer( 'exp@example.com', 'expuser', 'pw' );

		$code = $this->sut->create_code( $user_id );

		// Age the stored code past the 10-minute TTL, keeping its hash and attempt count intact.
		$parts    = explode( ':', (string) Users::get_site_user_meta( $user_id, self::KEY_META ), 4 );
		$parts[0] = (string) ( time() - 11 * MINUTE_IN_SECONDS );
		Users::update_site_user_meta( $user_id, self::KEY_META, implode( ':', $parts ) );

		$this->assertSame( EmailVerificationService::RESULT_EXPIRED, $this->sut->verify_code( $user_id, $code ) );
		$this->assertFalse( $this->sut->is_locked_out( $user_id ), 'An expiry must not move the user towards lockout' );
		$this->assertFalse( $this->sut->has_pending_code( $user_id ), 'An expired code is no longer pending' );
	}

	/**
	 * @testdox A code is void after the account email changes, so it can't verify a different address.
	 */
	public function test_code_void_after_email_change(): void {
		$user_id = wc_create_new_customer( 'issued-for@example.com', 'codechange', 'pw' );

		$code = $this->sut->create_code( $user_id );

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'changed-to@example.com',
			)
		);
		clean_user_cache( $user_id );

		$this->assertSame(
			EmailVerificationService::RESULT_NONE,
			$this->sut->verify_code( $user_id, $code ),
			'A code minted for the old email must not verify the new email'
		);
		$this->assertFalse( $this->sut->is_verified( $user_id ) );
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

	/**
	 * Return a six-digit code guaranteed to differ from the given one.
	 *
	 * @param string $code The code to avoid.
	 * @return string
	 */
	private function wrong_code( string $code ): string {
		return '000000' === $code ? '111111' : '000000';
	}

	/**
	 * Drive the service into a locked-out state for the given user.
	 *
	 * @param int $user_id User ID.
	 */
	private function force_lockout( int $user_id ): void {
		$current = null;
		$guard   = 0;
		while ( ! $this->sut->is_locked_out( $user_id ) && $guard < 15 ) {
			if ( ! $this->sut->has_pending_code( $user_id ) ) {
				$current = $this->sut->create_code( $user_id );
			}
			$this->sut->verify_code( $user_id, $this->wrong_code( (string) $current ) );
			++$guard;
		}
	}
}
