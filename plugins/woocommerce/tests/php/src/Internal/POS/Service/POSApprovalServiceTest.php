<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use WC_Unit_Test_Case;

/**
 * Tests for POSApprovalService.
 *
 * @since 10.8.0
 */
class POSApprovalServiceTest extends WC_Unit_Test_Case {

	/**
	 * @var POSApprovalService
	 */
	private POSApprovalService $service;

	/**
	 * @var int
	 */
	private int $approver_id;

	public function setUp(): void {
		parent::setUp();
		$this->service     = new POSApprovalService();
		$this->approver_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	public function tearDown(): void {
		wp_delete_user( $this->approver_id );
		parent::tearDown();
	}

	/**
	 * @testdox create_approval returns a 32-character string token.
	 */
	public function test_create_approval_returns_32_char_token(): void {
		$token = $this->service->create_approval( $this->approver_id, 'refund', array() );

		$this->assertIsString( $token );
		$this->assertSame( 32, strlen( $token ) );
	}

	/**
	 * @testdox validate_and_consume succeeds for valid token with matching action.
	 */
	public function test_validate_and_consume_succeeds_for_valid_token(): void {
		$token = $this->service->create_approval( $this->approver_id, 'refund', array( 'order_id' => 42 ) );

		$result = $this->service->validate_and_consume( $token, 'refund' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'approver_id', $result );
		$this->assertSame( $this->approver_id, $result['approver_id'] );
		$this->assertArrayHasKey( 'action', $result );
		$this->assertSame( 'refund', $result['action'] );
		$this->assertArrayHasKey( 'context', $result );
		$this->assertSame( array( 'order_id' => 42 ), $result['context'] );
		$this->assertArrayHasKey( 'created_at', $result );
		$this->assertIsInt( $result['created_at'] );
	}

	/**
	 * @testdox token is single-use - second validate returns false.
	 */
	public function test_token_is_single_use(): void {
		$token = $this->service->create_approval( $this->approver_id, 'refund', array() );

		$this->assertIsArray( $this->service->validate_and_consume( $token, 'refund' ) );
		$this->assertFalse( $this->service->validate_and_consume( $token, 'refund' ) );
	}

	/**
	 * @testdox validate_and_consume returns false for wrong action.
	 */
	public function test_wrong_action_returns_false(): void {
		$token = $this->service->create_approval( $this->approver_id, 'refund', array() );

		$this->assertFalse( $this->service->validate_and_consume( $token, 'discount' ) );
	}

	/**
	 * @testdox validate_and_consume returns false for invalid token.
	 */
	public function test_invalid_token_returns_false(): void {
		$this->assertFalse( $this->service->validate_and_consume( 'nonexistent_token_value_here_xx', 'refund' ) );
	}

	/**
	 * @testdox approval data contains correct approver_id, action, and context.
	 */
	public function test_approval_data_contains_correct_fields(): void {
		$context = array(
			'order_id' => 99,
			'amount'   => '25.00',
		);
		$token   = $this->service->create_approval( $this->approver_id, 'discount', $context );

		$result = $this->service->validate_and_consume( $token, 'discount' );

		$this->assertIsArray( $result );
		$this->assertSame( $this->approver_id, $result['approver_id'] );
		$this->assertSame( 'discount', $result['action'] );
		$this->assertSame( $context, $result['context'] );
	}
}
