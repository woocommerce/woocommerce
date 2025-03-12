<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\ListTable;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Tests related to order list table in admin.
 */
class ListTableTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * @var ListTable
	 */
	private $sut;

	/**
	 * Setup - enables HPOS.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->toggle_cot_authoritative( true );
		$this->sut      = new ListTable();
		$set_order_type = function ( $order_type ) {
			$this->order_type = $order_type;
		};
		$set_order_type->call( $this->sut, 'shop_order' );
	}

	/**
	 * Helper method to call protected get_and_maybe_update_months_filter_cache.
	 *
	 * @param ListTable $sut ListTable instance.
	 *
	 * @return array YearMonth Array.
	 */
	public function call_get_months_filter_options( ListTable $sut ) {
		$callable = function () {
			return $this->get_months_filter_options();
		};
		return $callable->call( $sut );
	}

	/**
	 * @testdox The months filter options are filled out for every month between the oldest order and the current month.
	 */
	public function test_get_months_filter_options() {
		$start_date     = new \WC_DateTime( '2020-03-01 00:00:00' );
		$current_date   = new \WC_DateTime();
		$expected_count = $this->get_months_count( $start_date, $current_date );

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $start_date );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( $expected_count, $year_months );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
		$this->assertEquals( end( $year_months )->year, 2020 );
		$this->assertEquals( end( $year_months )->month, 3 );
	}

	/**
	 * @testdox The months filter options works as expected when there are no orders.
	 */
	public function test_get_months_filter_options_no_orders() {
		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( 1, $year_months );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
	}

	/**
	 * @testdox The months filter options works as expected with only one month.
	 */
	public function test_get_months_filter_options_single_month() {
		\WC_Helper_Order::create_order();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( 1, $year_months );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
	}

	/**
	 * @testdox The months filter options works as expected when the oldest order has a future date.
	 */
	public function test_get_months_filter_options_only_future_orders() {
		$start_date     = new \WC_DateTime( '+ 1 year' );
		$current_date   = new \WC_DateTime();
		$expected_count = $this->get_months_count( $current_date, $start_date );

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $start_date );
		$order->save();

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( new \WC_DateTime( '+ 2 years' ) );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( $expected_count, $year_months );
		$this->assertEquals( $year_months[0]->year, $start_date->format( 'Y' ) );
		$this->assertEquals( $year_months[0]->month, $start_date->format( 'n' ) );
		$this->assertEquals( end( $year_months )->year, gmdate( 'Y', time() ) );
		$this->assertEquals( end( $year_months )->month, gmdate( 'n', time() ) );
	}

	/**
	 * Get the total number of year-month items there should be between two dates.
	 *
	 * Note that this is different from calculating the elapsed time between the two dates. For this we instead care
	 * about which year-months from the calendar are present.
	 *
	 * @param \DateTime $start The start of the date range.
	 * @param \DateTime $end   The end of the date range.
	 *
	 * @return int
	 */
	private function get_months_count( \DateTime $start, \DateTime $end ): int {
		$start_year  = (int) $start->format( 'Y' );
		$start_month = (int) $start->format( 'n' );
		$end_year    = (int) $end->format( 'Y' );
		$end_month   = (int) $end->format( 'n' );

		$months_from_years = ( $end_year - $start_year ) * 12;
		$start_month_diff  = $start_month - 1;

		return $months_from_years - $start_month_diff + $end_month;
	}
}
