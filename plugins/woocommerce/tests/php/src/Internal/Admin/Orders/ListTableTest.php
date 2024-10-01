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
}
