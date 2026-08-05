<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Tests for the EmailVerificationService class (magic-link key API).
 */
class EmailVerificationServiceTest extends WC_Unit_Test_Case {

	private const KEY_META = '_wc_email_verification_key';

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
	 * @testdox create_verification_key() returns a fresh, pending key that passes its own check.
	 */
	public function test_create_key_is_pending_and_checks_out(): void {
		$user_id = wc_create_new_customer( 'key@example.com', 'keyuser', 'pw' );

		$key = $this->sut->create_verification_key( $user_id );

		$this->assertNotEmpty( $key, 'A key must be returned' );
		$this->assertTrue( $this->sut->has_pending_key( $user_id ), 'A freshly minted key should be pending' );
		$this->assertTrue( $this->sut->check_verification_key( $user_id, $key ), 'The minted key should validate' );
	}

	/**
	 * @testdox A wrong or empty key fails the check.
	 */
	public function test_wrong_or_empty_key_fails(): void {
		$user_id = wc_create_new_customer( 'wrongkey@example.com', 'wrongkeyuser', 'pw' );
		$this->sut->create_verification_key( $user_id );

		$this->assertFalse( $this->sut->check_verification_key( $user_id, 'not-the-key' ), 'A wrong key must fail' );
		$this->assertFalse( $this->sut->check_verification_key( $user_id, '' ), 'An empty key must fail' );
		$this->assertFalse( $this->sut->is_verified( $user_id ), 'A failed check must not verify' );
	}

	/**
	 * @testdox The key is single-use: it is consumed once the user is verified.
	 */
	public function test_key_consumed_on_mark_verified(): void {
		$user_id = wc_create_new_customer( 'single@example.com', 'singleuser', 'pw' );
		$key     = $this->sut->create_verification_key( $user_id );

		$this->sut->mark_verified( $user_id );

		$this->assertFalse( $this->sut->has_pending_key( $user_id ), 'Verifying should consume the pending key' );
		$this->assertFalse( $this->sut->check_verification_key( $user_id, $key ), 'A consumed key must not re-validate' );
	}

	/**
	 * @testdox build_verification_url() carries the user ID and a key that validates.
	 */
	public function test_build_verification_url_carries_valid_key(): void {
		$user_id = wc_create_new_customer( 'url@example.com', 'urluser', 'pw' );

		$url = $this->sut->build_verification_url( $user_id );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

		$this->assertArrayHasKey( 'wc_verify_email_key', $args, 'URL must carry the key' );
		$this->assertArrayHasKey( 'wc_verify_email_user', $args, 'URL must carry the user ID' );
		$this->assertSame( (string) $user_id, (string) $args['wc_verify_email_user'], 'URL must carry the correct user ID' );
		$this->assertTrue( $this->sut->check_verification_key( $user_id, (string) $args['wc_verify_email_key'] ), 'The URL key should validate' );
	}

	/**
	 * @testdox An expired key fails the check and is no longer pending.
	 */
	public function test_expired_key_fails(): void {
		$user_id = wc_create_new_customer( 'exp@example.com', 'expuser', 'pw' );

		$key = $this->sut->create_verification_key( $user_id );

		// Age the stored token past the 24-hour TTL, keeping its hashes intact.
		$parts    = explode( ':', (string) Users::get_site_user_meta( $user_id, self::KEY_META ), 3 );
		$parts[0] = (string) ( time() - DAY_IN_SECONDS - HOUR_IN_SECONDS );
		Users::update_site_user_meta( $user_id, self::KEY_META, implode( ':', $parts ) );

		$this->assertFalse( $this->sut->check_verification_key( $user_id, $key ), 'An expired key must fail the check' );
		$this->assertFalse( $this->sut->has_pending_key( $user_id ), 'An expired key is no longer pending' );
	}

	/**
	 * @testdox A key is void after the account email changes, so it can't verify a different address.
	 */
	public function test_key_void_after_email_change(): void {
		$user_id = wc_create_new_customer( 'issued-for@example.com', 'keychange', 'pw' );

		$key = $this->sut->create_verification_key( $user_id );

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'changed-to@example.com',
			)
		);
		clean_user_cache( $user_id );

		$this->assertFalse(
			$this->sut->check_verification_key( $user_id, $key ),
			'A key minted for the old email must not verify the new email'
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
}
