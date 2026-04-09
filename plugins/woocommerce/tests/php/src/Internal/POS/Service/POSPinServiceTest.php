<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Unit_Test_Case;

/**
 * Tests for POSPinService.
 *
 * @since 10.8.0
 */
class POSPinServiceTest extends WC_Unit_Test_Case {

	/**
	 * @var POSPinService
	 */
	private POSPinService $service;

	/**
	 * @var int
	 */
	private int $user_id;

	/**
	 * @var int
	 */
	private int $user_id_2;

	public function setUp(): void {
		parent::setUp();
		$this->service   = new POSPinService();
		$this->user_id   = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->user_id_2 = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
	}

	public function tearDown(): void {
		wp_delete_user( $this->user_id );
		wp_delete_user( $this->user_id_2 );
		parent::tearDown();
	}

	/**
	 * @testdox validate_pin_format accepts 4-digit PINs.
	 */
	public function test_validate_pin_format_accepts_4_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '4829' ) );
	}

	/**
	 * @testdox validate_pin_format accepts 5-digit PINs.
	 */
	public function test_validate_pin_format_accepts_5_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '48291' ) );
	}

	/**
	 * @testdox validate_pin_format accepts 6-digit PINs.
	 */
	public function test_validate_pin_format_accepts_6_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '482910' ) );
	}

	/**
	 * @testdox validate_pin_format rejects 3-digit PINs.
	 */
	public function test_validate_pin_format_rejects_3_digits(): void {
		$this->assertFalse( $this->service->validate_pin_format( '123' ) );
	}

	/**
	 * @testdox validate_pin_format rejects 7-digit PINs.
	 */
	public function test_validate_pin_format_rejects_7_digits(): void {
		$this->assertFalse( $this->service->validate_pin_format( '1234567' ) );
	}

	/**
	 * @testdox validate_pin_format rejects empty string.
	 */
	public function test_validate_pin_format_rejects_empty(): void {
		$this->assertFalse( $this->service->validate_pin_format( '' ) );
	}

	/**
	 * @testdox validate_pin_format rejects non-numeric input.
	 */
	public function test_validate_pin_format_rejects_non_numeric(): void {
		$this->assertFalse( $this->service->validate_pin_format( 'abcd' ) );
	}

	/**
	 * @testdox validate_pin_format rejects PINs with spaces.
	 */
	public function test_validate_pin_format_rejects_spaces(): void {
		$this->assertFalse( $this->service->validate_pin_format( '12 34' ) );
	}

	/**
	 * @testdox validate_pin_format rejects PINs with leading space.
	 */
	public function test_validate_pin_format_rejects_leading_space(): void {
		$this->assertFalse( $this->service->validate_pin_format( ' 1234' ) );
	}

	/**
	 * @testdox is_pin_blocked returns true for blocked PINs.
	 */
	public function test_is_pin_blocked_returns_true_for_blocked_pins(): void {
		$blocked = array( '0000', '1234', '1111', '2580', '6969' );
		foreach ( $blocked as $pin ) {
			$this->assertTrue(
				$this->service->is_pin_blocked( $pin ),
				"PIN {$pin} should be blocked"
			);
		}
	}

	/**
	 * @testdox is_pin_blocked returns false for normal PINs.
	 */
	public function test_is_pin_blocked_returns_false_for_normal_pins(): void {
		$normal = array( '4829', '7362', '91053', '482910' );
		foreach ( $normal as $pin ) {
			$this->assertFalse(
				$this->service->is_pin_blocked( $pin ),
				"PIN {$pin} should not be blocked"
			);
		}
	}

	/**
	 * @testdox hash_pin returns a bcrypt hash and verify_pin validates it.
	 */
	public function test_hash_and_verify_pin(): void {
		$pin  = '4829';
		$hash = $this->service->hash_pin( $pin );

		$this->assertNotEmpty( $hash );
		$this->assertNotSame( $pin, $hash );
		$this->assertTrue( $this->service->verify_pin( $pin, $hash ) );
	}

	/**
	 * @testdox verify_pin rejects wrong PIN.
	 */
	public function test_verify_pin_rejects_wrong_pin(): void {
		$hash = $this->service->hash_pin( '4829' );

		$this->assertFalse( $this->service->verify_pin( '9999', $hash ) );
	}

	/**
	 * @testdox compute_pin_index is deterministic.
	 */
	public function test_compute_pin_index_is_deterministic(): void {
		$index1 = $this->service->compute_pin_index( '4829' );
		$index2 = $this->service->compute_pin_index( '4829' );

		$this->assertSame( $index1, $index2 );
	}

	/**
	 * @testdox compute_pin_index returns different values for different PINs.
	 */
	public function test_compute_pin_index_differs_for_different_pins(): void {
		$index1 = $this->service->compute_pin_index( '4829' );
		$index2 = $this->service->compute_pin_index( '7362' );

		$this->assertNotSame( $index1, $index2 );
	}

	/**
	 * @testdox compute_pin_index returns a 64-character hex string.
	 */
	public function test_compute_pin_index_returns_64_char_hex(): void {
		$index = $this->service->compute_pin_index( '4829' );

		$this->assertSame( 64, strlen( $index ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $index );
	}

	/**
	 * @testdox set_pin stores hash and index in user meta.
	 */
	public function test_set_pin_stores_meta(): void {
		$result = $this->service->set_pin( $this->user_id, '4829' );

		$this->assertTrue( $result );

		$hash  = get_user_meta( $this->user_id, '_woocommerce_pos_pin', true );
		$index = get_user_meta( $this->user_id, '_woocommerce_pos_pin_index', true );

		$this->assertNotEmpty( $hash );
		$this->assertNotEmpty( $index );
		$this->assertTrue( $this->service->verify_pin( '4829', $hash ) );
		$this->assertSame( $this->service->compute_pin_index( '4829' ), $index );
	}

	/**
	 * @testdox set_pin rejects blocked PINs with generic error.
	 */
	public function test_set_pin_rejects_blocked_pin(): void {
		$result = $this->service->set_pin( $this->user_id, '1234' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_pin', $result->get_error_code() );
	}

	/**
	 * @testdox set_pin rejects invalid format with same generic error as blocked PINs.
	 */
	public function test_set_pin_rejects_invalid_format(): void {
		$result = $this->service->set_pin( $this->user_id, '12' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_pin', $result->get_error_code() );
	}

	/**
	 * @testdox set_pin rejects duplicate PIN used by another user.
	 */
	public function test_set_pin_rejects_duplicate(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$result = $this->service->set_pin( $this->user_id_2, '4829' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_pin', $result->get_error_code() );
	}

	/**
	 * @testdox set_pin allows updating own PIN to the same value.
	 */
	public function test_set_pin_allows_same_user_update(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$result = $this->service->set_pin( $this->user_id, '4829' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox set_pin allows updating own PIN to a new value.
	 */
	public function test_set_pin_allows_own_pin_change(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$result = $this->service->set_pin( $this->user_id, '7362' );

		$this->assertTrue( $result );

		$hash = get_user_meta( $this->user_id, '_woocommerce_pos_pin', true );
		$this->assertTrue( $this->service->verify_pin( '7362', $hash ) );
	}

	/**
	 * @testdox delete_pin removes both meta keys.
	 */
	public function test_delete_pin_removes_meta(): void {
		$this->service->set_pin( $this->user_id, '4829' );
		$this->service->delete_pin( $this->user_id );

		$this->assertEmpty( get_user_meta( $this->user_id, '_woocommerce_pos_pin', true ) );
		$this->assertEmpty( get_user_meta( $this->user_id, '_woocommerce_pos_pin_index', true ) );
	}

	/**
	 * @testdox has_pin returns true after set_pin.
	 */
	public function test_has_pin_returns_true_after_set(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$this->assertTrue( $this->service->has_pin( $this->user_id ) );
	}

	/**
	 * @testdox has_pin returns false after delete_pin.
	 */
	public function test_has_pin_returns_false_after_delete(): void {
		$this->service->set_pin( $this->user_id, '4829' );
		$this->service->delete_pin( $this->user_id );

		$this->assertFalse( $this->service->has_pin( $this->user_id ) );
	}

	/**
	 * @testdox has_pin returns false for user without PIN.
	 */
	public function test_has_pin_returns_false_for_no_pin(): void {
		$this->assertFalse( $this->service->has_pin( $this->user_id ) );
	}

	/**
	 * @testdox lookup_user_by_pin finds the correct user.
	 */
	public function test_lookup_user_by_pin_finds_correct_user(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$found = $this->service->lookup_user_by_pin( '4829' );

		$this->assertSame( $this->user_id, $found );
	}

	/**
	 * @testdox lookup_user_by_pin returns null for wrong PIN.
	 */
	public function test_lookup_user_by_pin_returns_null_for_wrong_pin(): void {
		$this->service->set_pin( $this->user_id, '4829' );

		$this->assertNull( $this->service->lookup_user_by_pin( '9999' ) );
	}

	/**
	 * @testdox lookup_user_by_pin returns null when no PINs are set.
	 */
	public function test_lookup_user_by_pin_returns_null_when_empty(): void {
		$this->assertNull( $this->service->lookup_user_by_pin( '4829' ) );
	}

	/**
	 * @testdox lookup_user_by_pin distinguishes between two users with different PINs.
	 */
	public function test_lookup_user_by_pin_distinguishes_users(): void {
		$this->service->set_pin( $this->user_id, '4829' );
		$this->service->set_pin( $this->user_id_2, '7362' );

		$this->assertSame( $this->user_id, $this->service->lookup_user_by_pin( '4829' ) );
		$this->assertSame( $this->user_id_2, $this->service->lookup_user_by_pin( '7362' ) );
	}

	/**
	 * @testdox All error types use the same error code and message for anti-enumeration.
	 */
	public function test_all_errors_use_same_code_and_message(): void {
		$format_result = $this->service->set_pin( $this->user_id, '12' );
		$this->assertSame( 'invalid_pin', $format_result->get_error_code() );

		$blocked_result = $this->service->set_pin( $this->user_id, '1234' );
		$this->assertSame( 'invalid_pin', $blocked_result->get_error_code() );

		$this->service->set_pin( $this->user_id, '4829' );
		$duplicate_result = $this->service->set_pin( $this->user_id_2, '4829' );
		$this->assertSame( 'invalid_pin', $duplicate_result->get_error_code() );

		$this->assertSame(
			$format_result->get_error_message(),
			$blocked_result->get_error_message()
		);
		$this->assertSame(
			$format_result->get_error_message(),
			$duplicate_result->get_error_message()
		);
	}

	/**
	 * @testdox After deleting a PIN, another user can use it.
	 */
	public function test_deleted_pin_can_be_reused_by_another_user(): void {
		$this->service->set_pin( $this->user_id, '4829' );
		$this->service->delete_pin( $this->user_id );

		$result = $this->service->set_pin( $this->user_id_2, '4829' );

		$this->assertTrue( $result );
	}
}
