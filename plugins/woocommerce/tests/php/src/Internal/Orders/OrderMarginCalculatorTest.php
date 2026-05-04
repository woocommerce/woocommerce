<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use Automattic\WooCommerce\Internal\Orders\OrderMarginCalculator;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Tests for OrderMarginCalculator.
 */
class OrderMarginCalculatorTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var OrderMarginCalculator
	 */
	private OrderMarginCalculator $sut;

	/**
	 * Mock CostOfGoodsSoldController.
	 *
	 * @var CostOfGoodsSoldController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $cogs_controller;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->cogs_controller = $this->createMock( CostOfGoodsSoldController::class );
		$this->sut             = new OrderMarginCalculator();
		$this->sut->init( $this->cogs_controller );
	}

	/**
	 * Creates a mock order with the given financial values.
	 *
	 * @param float $subtotal       Order subtotal.
	 * @param float $discount_total Discount total.
	 * @param float $cogs_value     COGS total value.
	 * @return WC_Order|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function make_order( float $subtotal, float $discount_total, float $cogs_value ) {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_subtotal' )->willReturn( $subtotal );
		$order->method( 'get_discount_total' )->willReturn( $discount_total );
		$order->method( 'get_cogs_total_value' )->willReturn( $cogs_value );
		$order->method( 'get_items' )->willReturn( array() );
		return $order;
	}

	/**
	 * Creates a mock product line item.
	 *
	 * @param float $total      Line item total.
	 * @param float $cogs_value COGS value for the item.
	 * @return WC_Order_Item_Product|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function make_item( float $total, float $cogs_value ) {
		$item = $this->createMock( WC_Order_Item_Product::class );
		$item->method( 'get_id' )->willReturn( 1 );
		$item->method( 'get_product_id' )->willReturn( 10 );
		$item->method( 'get_name' )->willReturn( 'Test Product' );
		$item->method( 'get_quantity' )->willReturn( 1.0 );
		$item->method( 'get_total' )->willReturn( $total );
		$item->method( 'get_cogs_value' )->willReturn( $cogs_value );
		return $item;
	}

	/**
	 * Test that margin data keys are always present.
	 */
	public function test_get_margin_for_order_returns_expected_keys(): void {
		$this->cogs_controller->method( 'feature_is_enabled' )->willReturn( true );
		$order = $this->make_order( 100.0, 0.0, 60.0 );

		$result = $this->sut->get_margin_for_order( $order );

		$this->assertArrayHasKey( 'net_revenue', $result );
		$this->assertArrayHasKey( 'cogs', $result );
		$this->assertArrayHasKey( 'gross_profit', $result );
		$this->assertArrayHasKey( 'margin', $result );
		$this->assertArrayHasKey( 'cogs_enabled', $result );
		$this->assertArrayHasKey( 'items', $result );
	}

	/**
	 * Test correct margin calculation when COGS is enabled.
	 */
	public function test_get_margin_for_order_calculates_correctly_when_cogs_enabled(): void {
		$this->cogs_controller->method( 'feature_is_enabled' )->willReturn( true );
		// subtotal=100, discount=10 → net_revenue=90; cogs=45 → gross_profit=45; margin=50%.
		$order = $this->make_order( 100.0, 10.0, 45.0 );

		$result = $this->sut->get_margin_for_order( $order );

		$this->assertSame( 90.0, $result['net_revenue'] );
		$this->assertSame( 45.0, $result['cogs'] );
		$this->assertSame( 45.0, $result['gross_profit'] );
		$this->assertSame( 50.0, $result['margin'] );
		$this->assertTrue( $result['cogs_enabled'] );
	}

	/**
	 * Test that COGS is zeroed out when the feature is disabled.
	 */
	public function test_get_margin_for_order_zeros_cogs_when_feature_disabled(): void {
		$this->cogs_controller->method( 'feature_is_enabled' )->willReturn( false );
		$order = $this->make_order( 100.0, 0.0, 60.0 );

		$result = $this->sut->get_margin_for_order( $order );

		$this->assertSame( 0.0, $result['cogs'] );
		$this->assertSame( 100.0, $result['gross_profit'] );
		$this->assertSame( 100.0, $result['margin'] );
		$this->assertFalse( $result['cogs_enabled'] );
	}

	/**
	 * Test that margin is 0 when net revenue is zero (no division by zero).
	 */
	public function test_get_margin_for_order_returns_zero_margin_when_net_revenue_is_zero(): void {
		$this->cogs_controller->method( 'feature_is_enabled' )->willReturn( true );
		$order = $this->make_order( 0.0, 0.0, 0.0 );

		$result = $this->sut->get_margin_for_order( $order );

		$this->assertSame( 0.0, $result['margin'] );
	}

	/**
	 * Test that item-level margin is included in the order result.
	 */
	public function test_get_margin_for_order_includes_item_breakdown(): void {
		$this->cogs_controller->method( 'feature_is_enabled' )->willReturn( true );

		$item  = $this->make_item( 50.0, 20.0 );
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_subtotal' )->willReturn( 50.0 );
		$order->method( 'get_discount_total' )->willReturn( 0.0 );
		$order->method( 'get_cogs_total_value' )->willReturn( 20.0 );
		$order->method( 'get_items' )->willReturn( array( $item ) );

		$result = $this->sut->get_margin_for_order( $order );

		$this->assertCount( 1, $result['items'] );
		$this->assertArrayHasKey( 'item_id', $result['items'][0] );
		$this->assertArrayHasKey( 'margin', $result['items'][0] );
		$this->assertSame( 60.0, $result['items'][0]['margin'] );
	}

	/**
	 * Test per-item margin calculation.
	 */
	public function test_get_margin_for_order_item_calculates_correctly(): void {
		// revenue=80, cogs=20 → gross_profit=60 → margin=75%.
		$item   = $this->make_item( 80.0, 20.0 );
		$result = $this->sut->get_margin_for_order_item( $item, true );

		$this->assertSame( 80.0, $result['revenue'] );
		$this->assertSame( 20.0, $result['cogs'] );
		$this->assertSame( 60.0, $result['gross_profit'] );
		$this->assertSame( 75.0, $result['margin'] );
	}

	/**
	 * Test that item COGS is zeroed when feature is disabled.
	 */
	public function test_get_margin_for_order_item_zeros_cogs_when_feature_disabled(): void {
		$item   = $this->make_item( 80.0, 20.0 );
		$result = $this->sut->get_margin_for_order_item( $item, false );

		$this->assertSame( 0.0, $result['cogs'] );
		$this->assertSame( 80.0, $result['gross_profit'] );
		$this->assertSame( 100.0, $result['margin'] );
	}
}
