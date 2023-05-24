<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrdersTableQueryTests.
 */
class OrdersTableQueryTests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Stores the original COT state.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Setup - enable COT.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->toggle_cot( true );
	}

	/**
	 * Restore the original COT state.
	 */
	public function tearDown(): void {
		$this->toggle_cot( $this->cot_state );
		parent::tearDown();
	}

	/**
	 * Helper function to create different orders with different dates for testing.
	 *
	 * @return array Array of WC_Order objects.
	 */
	private function create_orders_with_different_dates() {
		$order1 = OrderHelper::create_order();
		$order2 = OrderHelper::create_order();
		$order3 = OrderHelper::create_order();

		$order1->set_date_created( '2000-01-01T10:00:00' );
		$order1->set_date_modified( '2001-02-01T10:00:00' );
		$order1->set_date_paid( '2002-03-01T10:00:00' );
		$order1->save();

		$order2->set_date_created( '2000-02-01T10:00:00' );
		$order2->set_date_modified( '2001-01-01T10:00:00' );
		$order2->set_date_paid( '2002-03-01T10:00:00' );
		$order2->save();

		$order3->set_date_created( '2001-01-01T10:00:00' );
		$order3->set_date_modified( '2001-02-01T10:00:00' );
		$order3->set_date_paid( '2002-03-01T10:00:00' );
		$order3->save();

		return array( $order1, $order2, $order3 );
	}

	/**
	 * @testDox Nested date queries works as expected.
	 */
	public function test_nested_date_queries_single() {
		$orders = $this->create_orders_with_different_dates();

		$date_query_created_in_2000 = array(
			array(
				'relation' => 'AND',
				array(
					'column'    => 'date_created',
					'inclusive' => true,
					'after'     => '2000-01-01T00:00:00',
				),
				array(
					'column'    => 'date_created',
					'inclusive' => false,
					'before'    => '2001-01-01T10:00:00',
				),
			),
		);

		$queried_orders = wc_get_orders(
			array(
				'return'     => 'ids',
				'date_query' => $date_query_created_in_2000,
			)
		);

		$this->assertEquals( 2, count( $queried_orders ) );
		$this->assertContains( $orders[0]->get_id(), $queried_orders );
		$this->assertContains( $orders[1]->get_id(), $queried_orders );
	}

	/**
	 * @testDox Multiple nested date queries works as expected.
	 */
	public function test_nested_date_queries_multi() {
		$orders = $this->create_orders_with_different_dates();

		$date_query_created_in_2000_and_modified_in_2001 = array(
			array(
				'relation' => 'AND',
				array(
					'column'    => 'date_created',
					'inclusive' => true,
					'after'     => '2000-01-01T00:00:00',
				),
				array(
					'column'    => 'post_date',
					'inclusive' => false,
					'before'    => '2001-01-01T10:00:00',
				),
			),
			array(
				'column' => 'date_modified',
				'before' => '2001-01-02T10:00:00',
			),
		);

		$queried_orders = wc_get_orders(
			array(
				'return'     => 'ids',
				'date_query' => $date_query_created_in_2000_and_modified_in_2001,
			)
		);

		$this->assertEquals( 1, count( $queried_orders ) );
		$this->assertContains( $orders[1]->get_id(), $queried_orders );
	}
}
