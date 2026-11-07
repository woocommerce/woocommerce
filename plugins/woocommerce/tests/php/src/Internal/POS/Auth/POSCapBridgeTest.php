<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Auth;

use Automattic\WooCommerce\Internal\POS\Auth\POSAuthHandler;
use Automattic\WooCommerce\Internal\POS\Auth\POSCapBridge;
use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use WC_Unit_Test_Case;
use WP_User;

/**
 * Tests for POSCapBridge — grants real Woo caps only to the swapped-in staff member.
 */
class POSCapBridgeTest extends WC_Unit_Test_Case {

	/**
	 * The staff member a device-admin swap committed to.
	 *
	 * @var int
	 */
	private int $staff_id;

	/**
	 * A different user (e.g. directly authenticated, not the swap target).
	 *
	 * @var int
	 */
	private int $other_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->staff_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->other_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_delete_user( $this->staff_id );
		wp_delete_user( $this->other_id );
		parent::tearDown();
	}

	/**
	 * Build a bridge with a mocked write intent and a mocked committed-swap target.
	 *
	 * @param string|null $intent           The resolved write intent.
	 * @param int|null    $swapped_staff_id The staff id the device-admin swap committed to (null = no swap).
	 * @return POSCapBridge
	 */
	private function make_bridge( ?string $intent, ?int $swapped_staff_id ): POSCapBridge {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'get_intent' )->willReturn( $intent );

		$auth = $this->createMock( POSAuthHandler::class );
		$auth->method( 'get_swapped_staff_id' )->willReturn( $swapped_staff_id );

		$bridge = new POSCapBridge();
		$bridge->init( $ctx, $auth );
		return $bridge;
	}

	/**
	 * Run the bridge filter for the given user id, starting from an empty cap map.
	 *
	 * @param POSCapBridge $bridge  The bridge.
	 * @param int          $user_id The user being cap-checked.
	 * @return array<string, bool>
	 */
	private function granted_for( POSCapBridge $bridge, int $user_id ): array {
		return $bridge->grant_pos_caps( array(), array(), array(), new WP_User( $user_id ) );
	}

	/**
	 * @testdox Grants order caps to the swapped-in staff member on an order create.
	 */
	public function test_grants_order_caps_to_swapped_staff(): void {
		$bridge = $this->make_bridge( POSRequestContext::INTENT_ORDER_CREATE, $this->staff_id );

		$result = $this->granted_for( $bridge, $this->staff_id );

		$this->assertTrue( $result['publish_shop_orders'] ?? false );
		$this->assertTrue( $result['edit_shop_orders'] ?? false );
	}

	/**
	 * @testdox Grants refund caps to the swapped-in staff member on a refund create.
	 */
	public function test_grants_refund_caps_to_swapped_staff(): void {
		$bridge = $this->make_bridge( POSRequestContext::INTENT_REFUND_CREATE, $this->staff_id );

		$result = $this->granted_for( $bridge, $this->staff_id );

		$this->assertTrue( $result['publish_shop_orders'] ?? false, 'refund create grants publish_shop_orders (refund is a shop_order-type post)' );
		$this->assertTrue( $result['edit_shop_orders'] ?? false );
	}

	/**
	 * @testdox Grants nothing when there is no write intent.
	 */
	public function test_inert_when_no_intent(): void {
		$bridge = $this->make_bridge( null, $this->staff_id );

		$this->assertArrayNotHasKey( 'publish_shop_orders', $this->granted_for( $bridge, $this->staff_id ) );
	}

	/**
	 * @testdox Grants nothing without a committed swap, even on a POS write (no header self-escalation).
	 *
	 * The escalation flagged in review: a directly-authenticated user who holds an isolated POS cap and
	 * sends the POS headers must NOT get real Woo caps. With no committed swap (get_swapped_staff_id()
	 * is null), the bridge grants nothing even though the request looks like a POS write.
	 */
	public function test_no_grant_without_committed_swap(): void {
		$bridge = $this->make_bridge( POSRequestContext::INTENT_ORDER_CREATE, null );

		$this->assertArrayNotHasKey( 'edit_shop_orders', $this->granted_for( $bridge, $this->staff_id ) );
	}

	/**
	 * @testdox Grants nothing to a user other than the swapped-in staff member.
	 */
	public function test_no_grant_to_non_swapped_user(): void {
		// Swap committed to staff_id; a different user being cap-checked gets nothing.
		$bridge = $this->make_bridge( POSRequestContext::INTENT_ORDER_CREATE, $this->staff_id );

		$this->assertArrayNotHasKey( 'edit_shop_orders', $this->granted_for( $bridge, $this->other_id ) );
	}
}
