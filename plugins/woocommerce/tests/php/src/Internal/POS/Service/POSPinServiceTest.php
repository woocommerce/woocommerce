<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Tests for the POSPinService class.
 */
class POSPinServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var POSPinService
	 */
	private $sut;

	/**
	 * Test user id.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut     = new POSPinService();
		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->user_id, POSPreset::CASHIER );
	}

	/**
	 * Extra users created mid-test that should be cleaned up.
	 *
	 * @var int[]
	 */
	private array $extra_user_ids = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->extra_user_ids as $extra_id ) {
			wp_delete_user( $extra_id );
		}
		$this->extra_user_ids = array();
		wp_delete_user( $this->user_id );
		parent::tearDown();
	}

	/**
	 * @testdox Should accept exactly four numeric digits as valid PIN format.
	 */
	public function test_validate_pin_format_accepts_four_digits(): void {
		$this->assertTrue( $this->sut->validate_pin_format( '0000' ) );
		$this->assertTrue( $this->sut->validate_pin_format( '1234' ) );
		$this->assertTrue( $this->sut->validate_pin_format( '9999' ) );
	}

	/**
	 * @testdox Should reject non-numeric, wrong-length, or empty PIN formats.
	 */
	public function test_validate_pin_format_rejects_invalid(): void {
		$this->assertFalse( $this->sut->validate_pin_format( '' ) );
		$this->assertFalse( $this->sut->validate_pin_format( '123' ) );
		$this->assertFalse( $this->sut->validate_pin_format( '12345' ) );
		$this->assertFalse( $this->sut->validate_pin_format( 'abcd' ) );
		$this->assertFalse( $this->sut->validate_pin_format( '12 4' ) );
		$this->assertFalse( $this->sut->validate_pin_format( ' 1234 ' ) );
	}

	/**
	 * @testdox Should store a PIN record and report has_pin as true.
	 */
	public function test_set_pin_persists_record(): void {
		$this->assertFalse( $this->sut->has_pin( $this->user_id ), 'No PIN should be set initially.' );

		$result = $this->sut->set_pin( $this->user_id, '4242' );

		$this->assertTrue( $result, 'set_pin should return true on success.' );
		$this->assertTrue( $this->sut->has_pin( $this->user_id ) );

		$record = $this->sut->get_public_pin_record( $this->user_id );
		$this->assertIsArray( $record );
		$this->assertSame( POSPinService::ALGO, $record['algo'] );
		$this->assertSame( POSPinService::ITERATIONS, $record['iterations'] );
		$this->assertNotEmpty( $record['salt'] );
		$this->assertNotEmpty( $record['hash'] );
	}

	/**
	 * @testdox Should reject set_pin when the PIN format is invalid.
	 */
	public function test_set_pin_rejects_bad_format(): void {
		$result = $this->sut->set_pin( $this->user_id, '12' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_pin_format', $result->get_error_code() );
		$this->assertFalse( $this->sut->has_pin( $this->user_id ) );
	}

	/**
	 * @testdox Should reject set_pin for a user without POS access.
	 *
	 * Uniqueness is scoped to POS-access users, so a PIN stored on a non-POS user would be a
	 * latent collision once they gain POS caps. set_pin must refuse to create that record.
	 */
	public function test_set_pin_rejects_user_without_pos_access(): void {
		$non_pos_user           = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $non_pos_user;

		$result = $this->sut->set_pin( $non_pos_user, '1234' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_no_pos_access', $result->get_error_code() );
		$this->assertFalse( $this->sut->has_pin( $non_pos_user ) );
	}

	/**
	 * @testdox Should produce a unique salt per set_pin call.
	 */
	public function test_set_pin_generates_unique_salt_each_call(): void {
		$this->sut->set_pin( $this->user_id, '1234' );
		$first = $this->sut->get_public_pin_record( $this->user_id );

		$this->sut->set_pin( $this->user_id, '1234' );
		$second = $this->sut->get_public_pin_record( $this->user_id );

		$this->assertNotSame( $first['salt'], $second['salt'], 'Salts must differ across set_pin calls.' );
		$this->assertNotSame( $first['hash'], $second['hash'], 'Hashes must differ because of the new salt.' );
	}

	/**
	 * @testdox Should clear the PIN record on delete_pin.
	 */
	public function test_delete_pin_removes_record(): void {
		$this->sut->set_pin( $this->user_id, '5678' );
		$this->assertTrue( $this->sut->has_pin( $this->user_id ) );

		$this->sut->delete_pin( $this->user_id );

		$this->assertFalse( $this->sut->has_pin( $this->user_id ) );
		$this->assertNull( $this->sut->get_public_pin_record( $this->user_id ) );
	}

	/**
	 * @testdox Should verify a matching PIN against a stored record.
	 */
	public function test_verify_pin_accepts_matching_pin(): void {
		$this->sut->set_pin( $this->user_id, '4321' );
		$record = $this->sut->get_public_pin_record( $this->user_id );

		$this->assertTrue( $this->sut->verify_pin( '4321', $record ) );
	}

	/**
	 * @testdox Should reject a non-matching PIN against a stored record.
	 */
	public function test_verify_pin_rejects_mismatch(): void {
		$this->sut->set_pin( $this->user_id, '4321' );
		$record = $this->sut->get_public_pin_record( $this->user_id );

		$this->assertFalse( $this->sut->verify_pin( '4322', $record ) );
		$this->assertFalse( $this->sut->verify_pin( '0000', $record ) );
	}

	/**
	 * @testdox Should reject verify_pin when the input format is bad or the record is malformed.
	 */
	public function test_verify_pin_rejects_invalid_input(): void {
		$this->sut->set_pin( $this->user_id, '4321' );
		$record = $this->sut->get_public_pin_record( $this->user_id );

		$this->assertFalse( $this->sut->verify_pin( '12', $record ), 'Bad format input must be rejected.' );
		$this->assertFalse( $this->sut->verify_pin( '4321', array() ), 'Empty record must be rejected.' );
		$this->assertFalse(
			$this->sut->verify_pin(
				'4321',
				array(
					'algo'       => 'unknown',
					'iterations' => 10000,
					'salt'       => 'x',
					'hash'       => 'y',
				)
			),
			'Unknown algo must be rejected.'
		);
	}

	/**
	 * @testdox Should reject a record whose iteration count is not the canonical PBKDF2 cost.
	 */
	public function test_verify_pin_rejects_non_canonical_iterations(): void {
		$this->sut->set_pin( $this->user_id, '4321' );
		$record = $this->sut->get_public_pin_record( $this->user_id );

		// set_pin() only ever writes ITERATIONS. A corrupted or hostile count could inflate the
		// PBKDF2 cost (hanging the scan or a client) or downgrade it below the intended cost —
		// both must be treated as malformed and rejected before any hashing work is done.
		$record['iterations'] = POSPinService::ITERATIONS + 1;
		$this->assertFalse( $this->sut->verify_pin( '4321', $record ), 'Inflated iterations must be rejected.' );

		$record['iterations'] = 1;
		$this->assertFalse( $this->sut->verify_pin( '4321', $record ), 'Downgraded iterations must be rejected.' );

		$record['iterations'] = 0;
		$this->assertFalse( $this->sut->verify_pin( '4321', $record ), 'Non-positive iterations must be rejected.' );
	}

	/**
	 * @testdox Should reject a record whose decoded salt or hash length is wrong.
	 */
	public function test_verify_pin_rejects_wrong_length_salt_or_hash(): void {
		$this->sut->set_pin( $this->user_id, '4321' );
		$record = $this->sut->get_public_pin_record( $this->user_id );

		// Valid base64, but the decoded salt/hash are the wrong size for the declared format.
		$short_salt         = $record;
		$short_salt['salt'] = base64_encode( random_bytes( POSPinService::SALT_BYTES - 1 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$this->assertFalse( $this->sut->verify_pin( '4321', $short_salt ), 'Wrong salt length must be rejected.' );

		$short_hash         = $record;
		$short_hash['hash'] = base64_encode( random_bytes( POSPinService::HASH_BYTES - 1 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$this->assertFalse( $this->sut->verify_pin( '4321', $short_hash ), 'Wrong hash length must be rejected.' );
	}

	/**
	 * @testdox Should return null from get_public_pin_record when no PIN is set.
	 */
	public function test_get_public_pin_record_returns_null_when_unset(): void {
		$this->assertNull( $this->sut->get_public_pin_record( $this->user_id ) );
	}

	/**
	 * @testdox Should treat a malformed stored record as no PIN: has_pin false, public record null.
	 * @dataProvider provider_malformed_pin_records
	 *
	 * A record verify_pin() would reject must never be reported as a set PIN nor shipped
	 * to mobile clients (e.g. a hostile iteration count would hang the client's PBKDF2).
	 *
	 * @param mixed $record The malformed meta value to plant.
	 */
	public function test_malformed_record_reads_as_no_pin( $record ): void {
		Users::update_site_user_meta( $this->user_id, POSPinService::PIN_META_KEY, $record );

		$this->assertFalse( $this->sut->has_pin( $this->user_id ), 'Malformed record must read as no PIN.' );
		$this->assertNull( $this->sut->get_public_pin_record( $this->user_id ), 'Malformed record must not be exposed to clients.' );
	}

	/**
	 * Malformed stored PIN records that must read as "no PIN".
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provider_malformed_pin_records(): array {
		$valid = array(
			'algo'       => POSPinService::ALGO,
			'iterations' => POSPinService::ITERATIONS,
			'salt'       => base64_encode( random_bytes( POSPinService::SALT_BYTES ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'hash'       => base64_encode( random_bytes( POSPinService::HASH_BYTES ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);

		return array(
			'not an array'          => array( 'garbage' ),
			'missing salt'          => array( array_diff_key( $valid, array( 'salt' => true ) ) ),
			'unknown algo'          => array( array_merge( $valid, array( 'algo' => 'md5' ) ) ),
			'downgraded iterations' => array( array_merge( $valid, array( 'iterations' => 1 ) ) ),
			'invalid base64 salt'   => array( array_merge( $valid, array( 'salt' => '!!!not-base64!!!' ) ) ),
			'wrong hash length'     => array( array_merge( $valid, array( 'hash' => base64_encode( random_bytes( POSPinService::HASH_BYTES - 1 ) ) ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);
	}

	/**
	 * @testdox Should return WP_Error from set_pin when the meta write fails.
	 *
	 * A silently failed write would leave the previous PIN live while the caller
	 * believes it changed, so the failure must be propagated.
	 */
	public function test_set_pin_returns_error_when_meta_write_fails(): void {
		add_filter( 'update_user_metadata', '__return_false' );

		$result = $this->sut->set_pin( $this->user_id, '4242' );

		remove_filter( 'update_user_metadata', '__return_false' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_pin_save_failed', $result->get_error_code() );
		$this->assertFalse( $this->sut->has_pin( $this->user_id ), 'No PIN should be reported after a failed write.' );
	}

	/**
	 * @testdox Should reject set_pin when the PIN is already used by another staff member.
	 */
	public function test_set_pin_rejects_pin_in_use_by_another_user(): void {
		$other_id               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $other_id;
		Capabilities::set_pos_preset( $other_id, POSPreset::CASHIER );

		$this->assertTrue( $this->sut->set_pin( $other_id, '1234' ) );

		$result = $this->sut->set_pin( $this->user_id, '1234' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_pin_in_use', $result->get_error_code() );
		$this->assertFalse(
			$this->sut->has_pin( $this->user_id ),
			'No PIN should have been persisted when the candidate is taken.'
		);
	}

	/**
	 * @testdox Should ignore PINs on users who do not have POS access.
	 *
	 * A non-POS user with a stale `woocommerce_pos_pin` meta entry (e.g. left over
	 * from v1 or a manual edit) must not block a real POS staff member from using
	 * the same plaintext PIN. Uniqueness is scoped to users with POS access — holders
	 * of any `woocommerce_pos_*` capability — so non-POS users are invisible to the scan.
	 */
	public function test_set_pin_ignores_non_pos_staff_users_with_stale_pin_meta(): void {
		$non_pos_user           = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $non_pos_user;

		// Plant a *verifiable* stale PIN record (consistent salt/hash) directly, bypassing set_pin's
		// POS-access check. The record genuinely matches '1234', so this test only passes because the
		// uniqueness scan is scoped to POS-access users — a non-POS user is invisible to it.
		$this->plant_pin_meta( $non_pos_user, '1234' );

		$this->assertTrue( $this->sut->set_pin( $this->user_id, '1234' ) );
	}

	/**
	 * @testdox Should allow set_pin to re-save the same PIN for the same user (idempotent update).
	 */
	public function test_set_pin_allows_same_user_to_resave_same_pin(): void {
		$this->assertTrue( $this->sut->set_pin( $this->user_id, '1234' ) );

		// Re-saving the same PIN for the same user must succeed — the user is excluded
		// from the uniqueness scan so their own existing hash does not falsely collide.
		$this->assertTrue( $this->sut->set_pin( $this->user_id, '1234' ) );
	}

	/**
	 * @testdox is_pin_used_by_other_user is true when another POS user already holds the PIN.
	 */
	public function test_is_pin_used_by_other_user_true_for_collision(): void {
		$this->make_pos_user_with_pin( '1234' );

		$this->assertTrue( $this->sut->is_pin_used_by_other_user( '1234' ) );
	}

	/**
	 * @testdox is_pin_used_by_other_user is false when no POS user holds the PIN.
	 */
	public function test_is_pin_used_by_other_user_false_when_unique(): void {
		$this->make_pos_user_with_pin( '1234' );

		$this->assertFalse( $this->sut->is_pin_used_by_other_user( '5678' ) );
	}

	/**
	 * @testdox is_pin_used_by_other_user excludes the candidate, but a create-time (exclude 0) scan sees every record.
	 */
	public function test_is_pin_used_by_other_user_excludes_candidate(): void {
		$this->sut->set_pin( $this->user_id, '1234' );

		$this->assertFalse(
			$this->sut->is_pin_used_by_other_user( '1234', $this->user_id ),
			'Excluding the candidate allows an idempotent re-set.'
		);
		$this->assertTrue(
			$this->sut->is_pin_used_by_other_user( '1234', 0 ),
			'A create-time scan (exclude 0) sees the existing PIN.'
		);
	}

	/**
	 * @testdox is_pin_used_by_other_user ignores stale PINs on users without POS access.
	 */
	public function test_is_pin_used_by_other_user_ignores_non_pos_users(): void {
		$non_pos                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $non_pos;
		$this->plant_pin_meta( $non_pos, '1234' );

		$this->assertFalse( $this->sut->is_pin_used_by_other_user( '1234' ) );
	}

	/**
	 * Create a POS-access user (Cashier preset) with the given PIN, tracked for cleanup.
	 *
	 * @param string $pin Plaintext PIN to assign.
	 * @return int The created user id.
	 */
	private function make_pos_user_with_pin( string $pin ): int {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;
		Capabilities::set_pos_preset( $user_id, POSPreset::CASHIER );
		$this->assertTrue(
			$this->sut->set_pin( $user_id, $pin ),
			'Fixture: set_pin() must succeed for the collision scenarios to be meaningful.'
		);
		return $user_id;
	}

	/**
	 * Plant a well-formed PIN record directly in per-site user meta (bypassing set_pin's
	 * POS-access check), matching how POSPinService stores it via Users::*_site_user_meta().
	 *
	 * @param int    $user_id Target user.
	 * @param string $pin     Plaintext PIN to store.
	 */
	private function plant_pin_meta( int $user_id, string $pin ): void {
		$salt = random_bytes( POSPinService::SALT_BYTES );
		Users::update_site_user_meta(
			$user_id,
			POSPinService::PIN_META_KEY,
			array(
				'algo'       => POSPinService::ALGO,
				'iterations' => POSPinService::ITERATIONS,
				'salt'       => base64_encode( $salt ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'hash'       => base64_encode( hash_pbkdf2( 'sha256', $pin, $salt, POSPinService::ITERATIONS, POSPinService::HASH_BYTES, true ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			)
		);
	}
}
