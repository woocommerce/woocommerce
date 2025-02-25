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
	public function call_get_and_maybe_update_months_filter_cache( ListTable $sut ) {
		$callable = function () {
			return $this->get_and_maybe_update_months_filter_cache();
		};
		return $callable->call( $sut );
	}

	/**
	 * @testDox Test that current month is returned even there's no order.
	 */
	public function test_get_and_maybe_update_months_filter_cache_always_return_current() {
		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );
		$this->assertEmpty( $year_months );
		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut ); // when loaded from cache, we always return current year month.
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
	}

	/**
	 * @testDox Test that current month is returned.
	 */
	public function test_get_and_maybe_update_months_filter_cache_always_return_current_with_order() {
		\WC_Helper_Order::create_order();
		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
	}

	/**
	 * @testDox Test that backfilled order is recognized.
	 */
	public function test_get_and_maybe_update_months_filter_cache_always_backfilled() {
		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( new \WC_DateTime( '1991-01-01 00:00:00' ) );
		$order->save();

		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );
		$this->assertEquals( end( $year_months )->year, 1991 );
		$this->assertEquals( end( $year_months )->month, 1 );
	}

	/**
	 * @testDox Test that reading from cache works as expected.
	 */
	public function test_get_and_maybe_update_months_filter_cache_always_return_current_and_backfilled() {
		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( new \WC_DateTime( '1991-01-01 00:00:00' ) );
		$order->save();

		\WC_Helper_Order::create_order();

		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
		$this->assertEquals( end( $year_months )->year, 1991 );
		$this->assertEquals( end( $year_months )->month, 1 );

		// Loading from cache doesn't alter the behavior.

		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
		$this->assertEquals( end( $year_months )->year, 1991 );
		$this->assertEquals( end( $year_months )->month, 1 );
	}

	/**
	 * @testdox Using the simplified option for the months filter works as expected.
	 */
	public function test_get_and_maybe_update_months_filter_cache_simplified() {
		update_option( 'wc_shop_order_list_table_months_filter_simplified', true );

		$start_date     = new \WC_DateTime( '2020-03-01 00:00:00' );
		$current_date   = new \WC_DateTime();
		$expected_count = $this->get_months_count( $start_date, $current_date );

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $start_date );
		$order->save();

		$year_months = $this->call_get_and_maybe_update_months_filter_cache( $this->sut );

		$this->assertCount( $expected_count, $year_months );
		$this->assertEquals( $year_months[0]->year, gmdate( 'Y', time() ) );
		$this->assertEquals( $year_months[0]->month, gmdate( 'n', time() ) );
		$this->assertEquals( end( $year_months )->year, 2020 );
		$this->assertEquals( end( $year_months )->month, 3 );

		delete_option( 'wc_shop_order_list_table_months_filter_simplified' );
	}

	/**
	 * Get the total number of year-month items there should be between two dates.
	 *
	 * Note that this is different than calculating the elapsed time between the two dates. For this we instead care
	 * about which year-months from the calendar are present.
	 *
	 * @param \DateTime $start
	 * @param \DateTime $end
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
