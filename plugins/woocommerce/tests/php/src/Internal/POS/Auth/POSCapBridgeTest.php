<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Auth;

use Automattic\WooCommerce\Internal\POS\Auth\POSCapBridge;
use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use WC_Unit_Test_Case;
use WP_User;

/**
 * Tests for POSCapBridge — the request-scoped pos_* -> Woo capability bridge.
 */
class POSCapBridgeTest extends WC_Unit_Test_Case {

	/**
	 * Build a mocked request context.
	 *
	 * @param bool        $is_pos Whether the request is POS-originated.
	 * @param string|null $intent The resolved intent.
	 * @return POSRequestContext
	 */
	private function mock_context( bool $is_pos, ?string $intent ): POSRequestContext {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'is_pos_request' )->willReturn( $is_pos );
		$ctx->method( 'get_intent' )->willReturn( $intent );
		return $ctx;
	}

	/**
	 * Build a bridge around a context.
	 *
	 * @param POSRequestContext $ctx The (mocked) request context.
	 * @return POSCapBridge
	 */
	private function make_bridge( POSRequestContext $ctx ): POSCapBridge {
		$bridge = new POSCapBridge();
		$bridge->init( $ctx );
		return $bridge;
	}

	/**
	 * Invoke the bridge filter with a capability map.
	 *
	 * @param POSCapBridge        $bridge  The bridge.
	 * @param array<string, bool> $allcaps The user's caps.
	 * @return array<string, bool>
	 */
	private function run_filter( POSCapBridge $bridge, array $allcaps ): array {
		return $bridge->grant_pos_caps( $allcaps, array(), array(), new WP_User( 0 ) );
	}

	/**
	 * @testdox Grants order caps when the user holds process_sales on an order create.
	 */
	public function test_grants_order_caps_for_process_sales(): void {
		$bridge = $this->make_bridge( $this->mock_context( true, POSRequestContext::INTENT_ORDER_CREATE ) );

		$result = $this->run_filter( $bridge, array( Capabilities::CAP_PROCESS_SALES => true ) );

		$this->assertTrue( $result['publish_shop_orders'] ?? false, 'order create should grant publish_shop_orders' );
		$this->assertTrue( $result['edit_shop_orders'] ?? false, 'order create should grant edit_shop_orders' );
	}

	/**
	 * @testdox Grants refund caps when the user holds issue_refunds on a refund create.
	 */
	public function test_grants_refund_caps_for_issue_refunds(): void {
		$bridge = $this->make_bridge( $this->mock_context( true, POSRequestContext::INTENT_REFUND_CREATE ) );

		$result = $this->run_filter( $bridge, array( Capabilities::CAP_ISSUE_REFUNDS => true ) );

		$this->assertTrue( $result['publish_shop_orders'] ?? false, 'refund create should grant publish_shop_orders (refund is a shop_order-type post)' );
		$this->assertTrue( $result['edit_shop_orders'] ?? false, 'refund create should grant edit_shop_orders' );
	}

	/**
	 * @testdox Is inert when the request is not POS-originated.
	 */
	public function test_inert_when_not_pos_request(): void {
		$bridge = $this->make_bridge( $this->mock_context( false, POSRequestContext::INTENT_ORDER_CREATE ) );

		$result = $this->run_filter( $bridge, array( Capabilities::CAP_PROCESS_SALES => true ) );

		$this->assertArrayNotHasKey( 'publish_shop_orders', $result, 'No Woo caps may be granted off a POS request' );
	}

	/**
	 * @testdox Is inert when there is no write intent.
	 */
	public function test_inert_when_no_intent(): void {
		$bridge = $this->make_bridge( $this->mock_context( true, null ) );

		$result = $this->run_filter( $bridge, array( Capabilities::CAP_PROCESS_SALES => true ) );

		$this->assertArrayNotHasKey( 'publish_shop_orders', $result );
	}

	/**
	 * @testdox Does not grant when the user does not hold the matching POS capability.
	 */
	public function test_no_grant_without_matching_pos_cap(): void {
		// Refund intent, but the user only holds process_sales (a cashier) — no refund grant.
		$bridge = $this->make_bridge( $this->mock_context( true, POSRequestContext::INTENT_REFUND_CREATE ) );

		$result = $this->run_filter( $bridge, array( Capabilities::CAP_PROCESS_SALES => true ) );

		$this->assertArrayNotHasKey( 'edit_shop_orders', $result, 'A user without issue_refunds gets no refund bridge' );
	}
}
