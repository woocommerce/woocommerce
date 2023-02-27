<?php

/**
 * WC_Order_Factory_Test Class.
 */
class WC_Order_Factory_Test extends WC_Unit_Test_Case {

	/**
	 * Store COT state at the start of the test so we can restore it later.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Disable COT before the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->cot_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot( false );
	}

	/**
	 * Restore COT state after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_cache_flush();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot( $this->cot_state );
	}

	/**
	 * @testDox get_orders should be able to return multiple orders of different types.
	 */
	public function test_get_orders_with_multiple_order_type() {
		$order1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_complex_wp_post_order();
		$order2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_complex_wp_post_order();

		assert( $order1 > 0 );
		assert( $order2 > 0 );

		$refund_args = array(
			'amount'   => 10,
			'reason'   => 'test',
			'order_id' => $order1,
		);
		$refund1     = wc_create_refund( $refund_args );
		assert( $refund1->get_id() > 0 );

		$refund_args['order_id'] = $order2;
		$refund2                 = wc_create_refund( $refund_args );
		assert( $refund2->get_id() > 0 );

		$order_ids = array( $order1, $order2, $refund1->get_id(), $refund2->get_id() );

		$orders_with_diff_types = WC_Order_Factory::get_orders( $order_ids );
		$this->assertEquals( 4, count( $orders_with_diff_types ) );

		// Assert IDs.
		$this->assertEquals( $order1, $orders_with_diff_types[0]->get_id() );
		$this->assertEquals( $order2, $orders_with_diff_types[1]->get_id() );
		$this->assertEquals( $refund1->get_id(), $orders_with_diff_types[2]->get_id() );
		$this->assertEquals( $refund2->get_id(), $orders_with_diff_types[3]->get_id() );

		// Assert class types.
		$this->assertInstanceOf( WC_Order::class, $orders_with_diff_types[0] );
		$this->assertInstanceOf( WC_Order::class, $orders_with_diff_types[1] );
		$this->assertInstanceOf( WC_Order_Refund::class, $orders_with_diff_types[2] );
		$this->assertInstanceOf( WC_Order_Refund::class, $orders_with_diff_types[3] );
	}

}
