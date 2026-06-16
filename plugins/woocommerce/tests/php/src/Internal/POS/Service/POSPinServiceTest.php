<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
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
		Capabilities::set_pos_preset( $this->user_id, Capabilities::POS_PRESET_CASHIER );
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
	 * @testdox Should return null from get_public_pin_record when no PIN is set.
	 */
	public function test_get_public_pin_record_returns_null_when_unset(): void {
		$this->assertNull( $this->sut->get_public_pin_record( $this->user_id ) );
	}

	/**
	 * @testdox Should reject set_pin when the PIN is already used by another staff member.
	 */
	public function test_set_pin_rejects_pin_in_use_by_another_user(): void {
		$other_id               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $other_id;
		Capabilities::set_pos_preset( $other_id, Capabilities::POS_PRESET_CASHIER );

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
	 * the same plaintext PIN. Uniqueness is scoped to users with the POS preset
	 * meta set — non-POS users are invisible to the uniqueness scan.
	 */
	public function test_set_pin_ignores_non_pos_staff_users_with_stale_pin_meta(): void {
		$non_pos_user           = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $non_pos_user;

		// Plant a stale PIN record directly so we don't depend on set_pin's
		// preset check accepting a non-POS user.
		update_user_meta(
			$non_pos_user,
			POSPinService::PIN_META_KEY,
			array(
				'algo'       => POSPinService::ALGO,
				'iterations' => POSPinService::ITERATIONS,
				'salt'       => base64_encode( random_bytes( POSPinService::SALT_BYTES ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'hash'       => base64_encode( hash_pbkdf2( 'sha256', '1234', random_bytes( POSPinService::SALT_BYTES ), POSPinService::ITERATIONS, POSPinService::HASH_BYTES, true ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			)
		);

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
}
