<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Enums\OrderStatus;
use DateTime;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Product_Simple;

/**
 * Tests for zero-filling of empty intervals in the Orders Stats DataStore.
 *
 * Migrated from the legacy WC_Admin_Tests_Reports_Orders_Stats zero-fill tests.
 *
 * The fixture spreads single-product orders over the current hour, the previous hour,
 * and one order each in the previous day, week, month and year. Queries then cover
 * windows of the last 1, 6, 10 and 11 hours, so all intervals beyond the two busy
 * hours must be zero-filled, in the position the ordering demands.
 */
class DataStoreZeroFillTest extends OrdersStatsTestCase {

	const PRODUCT_PRICE = 11;

	/**
	 * Orders created in the current hour.
	 *
	 * @var int
	 */
	private $orders_this_hour;

	/**
	 * Orders created in the previous hour.
	 *
	 * @var int
	 */
	private $orders_previous_hour;

	/**
	 * Start of the hour the newest fixture order was created in.
	 *
	 * @var DateTime
	 */
	private $current_hour_start;

	/**
	 * End of the hour the newest fixture order was created in.
	 *
	 * @var DateTime
	 */
	private $current_hour_end;

	/**
	 * @testdox A single-hour window returns one interval, identically for both sort directions.
	 * @dataProvider orderby_variants
	 *
	 * @param string $orderby              Report orderby argument.
	 * @param int    $orders_this_hour     Orders to create in the current hour.
	 * @param int    $orders_previous_hour Orders to create in the previous hour.
	 */
	public function test_single_hour_window( string $orderby, int $orders_this_hour, int $orders_previous_hour ): void {
		$this->create_fixture_orders( $orders_this_hour, $orders_previous_hour );

		$expected_stats = array(
			'totals'    => $this->build_totals( $orders_this_hour, true ),
			'intervals' => array(
				$this->build_interval( $this->current_hour_start, $this->current_hour_end, $orders_this_hour ),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		foreach ( array( 'desc', 'asc' ) as $order ) {
			$query_args = array(
				'after'    => $this->current_hour_start->format( TimeInterval::$sql_datetime_format ),
				'before'   => $this->current_hour_end->format( TimeInterval::$sql_datetime_format ),
				'interval' => 'hour',
				'orderby'  => $orderby,
				'order'    => $order,
			);
			$this->assert_report_data( $expected_stats, $query_args );
		}
	}

	/**
	 * @testdox A partial-page window zero-fills the empty hours in the position the ordering demands.
	 * @dataProvider orderby_variants
	 *
	 * @param string $orderby              Report orderby argument.
	 * @param int    $orders_this_hour     Orders to create in the current hour.
	 * @param int    $orders_previous_hour Orders to create in the previous hour.
	 */
	public function test_five_hour_window( string $orderby, int $orders_this_hour, int $orders_previous_hour ): void {
		$this->create_fixture_orders( $orders_this_hour, $orders_previous_hour );
		$this->assert_windowed_report( $orderby, 5 );
	}

	/**
	 * @testdox A window that exactly fills one page zero-fills the empty hours in the position the ordering demands.
	 * @dataProvider orderby_variants
	 *
	 * @param string $orderby              Report orderby argument.
	 * @param int    $orders_this_hour     Orders to create in the current hour.
	 * @param int    $orders_previous_hour Orders to create in the previous hour.
	 */
	public function test_nine_hour_window( string $orderby, int $orders_this_hour, int $orders_previous_hour ): void {
		$this->create_fixture_orders( $orders_this_hour, $orders_previous_hour );
		$this->assert_windowed_report( $orderby, 9 );
	}

	/**
	 * @testdox Zero-filled intervals paginate correctly in both sort directions.
	 * @dataProvider orderby_variants
	 *
	 * @param string $orderby              Report orderby argument.
	 * @param int    $orders_this_hour     Orders to create in the current hour.
	 * @param int    $orders_previous_hour Orders to create in the previous hour.
	 */
	public function test_pagination( string $orderby, int $orders_this_hour, int $orders_previous_hour ): void {
		$this->create_fixture_orders( $orders_this_hour, $orders_previous_hour );

		$hour_offset = 10;
		$per_page    = 10;
		$totals      = $this->build_totals( $orders_this_hour + $orders_previous_hour, true );

		foreach ( array( 'desc', 'asc' ) as $order ) {
			$expected_intervals = $this->build_expected_intervals( $hour_offset, $orderby, $order );

			foreach ( array( 1, 2 ) as $page ) {
				$query_args = array(
					'after'    => $this->window_start( $hour_offset )->format( TimeInterval::$sql_datetime_format ),
					'before'   => $this->current_hour_end->format( TimeInterval::$sql_datetime_format ),
					'interval' => 'hour',
					'orderby'  => $orderby,
					'order'    => $order,
					'page'     => $page,
					'per_page' => $per_page,
				);

				$expected_stats = array(
					'totals'    => $totals,
					'intervals' => array_slice( $expected_intervals, ( $page - 1 ) * $per_page, $per_page ),
					'total'     => $hour_offset + 1,
					'pages'     => 2,
					'page_no'   => $page,
				);

				$this->assert_report_data( $expected_stats, $query_args );
			}
		}
	}

	/**
	 * The orderby variants under test, with the fixture order distribution each expects.
	 *
	 * The orders_count variant puts 2 orders in the previous hour so ordering by count
	 * differs from ordering by date.
	 *
	 * @return array
	 */
	public function orderby_variants(): array {
		return array(
			'order by date'         => array( 'date', 2, 1 ),
			'order by orders_count' => array( 'orders_count', 1, 2 ),
		);
	}

	/**
	 * Assert the report for an N-hour window, in both sort directions.
	 *
	 * @param string $orderby     Report orderby argument.
	 * @param int    $hour_offset Hours before the current hour's end the window starts at.
	 */
	private function assert_windowed_report( string $orderby, int $hour_offset ): void {
		$totals = $this->build_totals( $this->orders_this_hour + $this->orders_previous_hour, true );

		foreach ( array( 'desc', 'asc' ) as $order ) {
			$query_args = array(
				'after'    => $this->window_start( $hour_offset )->format( TimeInterval::$sql_datetime_format ),
				'before'   => $this->current_hour_end->format( TimeInterval::$sql_datetime_format ),
				'interval' => 'hour',
				'orderby'  => $orderby,
				'order'    => $order,
			);

			$expected_stats = array(
				'totals'    => $totals,
				'intervals' => $this->build_expected_intervals( $hour_offset, $orderby, $order ),
				'total'     => $hour_offset + 1,
				'pages'     => 1,
				'page_no'   => 1,
			);

			$this->assert_report_data( $expected_stats, $query_args );
		}
	}

	/**
	 * Create the fixture orders and compute the hour window boundaries.
	 *
	 * Besides the orders in the current and previous hour, one order each is created in the
	 * previous day, week, month and year, all outside every window these tests query.
	 *
	 * @param int $orders_this_hour     Orders to create in the current hour.
	 * @param int $orders_previous_hour Orders to create in the previous hour.
	 */
	private function create_fixture_orders( int $orders_this_hour, int $orders_previous_hour ): void {
		WC_Helper_Reports::reset_stats_dbs();

		$this->orders_this_hour     = $orders_this_hour;
		$this->orders_previous_hour = $orders_previous_hour;

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( self::PRODUCT_PRICE );
		$product->save();

		$customer = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$newest_order_time = time();

		$order_times = array_merge(
			array(
				$newest_order_time - YEAR_IN_SECONDS,
				$newest_order_time - MONTH_IN_SECONDS,
				$newest_order_time - WEEK_IN_SECONDS,
				$newest_order_time - DAY_IN_SECONDS,
			),
			array_fill( 0, $orders_previous_hour, $newest_order_time - HOUR_IN_SECONDS ),
			array_fill( 0, $orders_this_hour, $newest_order_time )
		);

		foreach ( $order_times as $order_time ) {
			$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order->set_date_created( $order_time );
			$order->set_date_paid( $order_time );
			$order->set_status( OrderStatus::COMPLETED );
			$order->calculate_totals();
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$this->current_hour_start = new DateTime();
		$this->current_hour_start->setTimestamp( $newest_order_time - ( ( $newest_order_time % HOUR_IN_SECONDS ) - ( $newest_order_time % MINUTE_IN_SECONDS ) ) );

		$this->current_hour_end = new DateTime();
		$this->current_hour_end->setTimestamp( $newest_order_time + ( HOUR_IN_SECONDS - ( $newest_order_time % HOUR_IN_SECONDS ) ) - 1 );
	}

	/**
	 * The start of an N-hours-before-window-end query window.
	 *
	 * @param int $hour_offset Hours before the current hour's end the window starts at.
	 * @return DateTime
	 */
	private function window_start( int $hour_offset ): DateTime {
		$window_start = new DateTime();
		$window_start->setTimestamp( (int) $this->current_hour_end->format( 'U' ) - $hour_offset * HOUR_IN_SECONDS );

		return $window_start;
	}

	/**
	 * Build the expected intervals for an N-hour window in the requested ordering.
	 *
	 * Zero-filled intervals always sort among themselves by time ascending, so for
	 * orderby=orders_count the busy hours move to the front (desc) or back (asc) while
	 * the zero-filled block keeps its time-ascending order.
	 *
	 * @param int    $hour_offset Hours before the current hour's end the window starts at.
	 * @param string $orderby     Report orderby argument.
	 * @param string $order       'asc' or 'desc'.
	 * @return array
	 */
	private function build_expected_intervals( int $hour_offset, string $orderby, string $order ): array {
		$now_timestamp = (int) $this->current_hour_end->format( 'U' );

		// Time-descending: index 0 is the current hour, index $hour_offset the partial oldest hour.
		$intervals_by_recency = array();
		for ( $i = 0; $i <= $hour_offset; $i++ ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $this->current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $this->current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $this->window_start( $hour_offset );
				$date_end   = new DateTime( $date_start->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count = $this->orders_this_hour;
			} elseif ( 1 === $i ) {
				$orders_count = $this->orders_previous_hour;
			} else {
				$orders_count = 0;
			}

			$intervals_by_recency[] = $this->build_interval( $date_start, $date_end, $orders_count );
		}

		if ( 'date' === $orderby ) {
			return 'desc' === $order ? $intervals_by_recency : array_reverse( $intervals_by_recency );
		}

		// orderby=orders_count: previous hour (most orders) and current hour lead or trail
		// the time-ascending zero block, depending on direction.
		$busy_hours_by_count = array( $intervals_by_recency[1], $intervals_by_recency[0] );
		$zero_hours_by_time  = array_reverse( array_slice( $intervals_by_recency, 2 ) );

		return 'desc' === $order
			? array_merge( $busy_hours_by_count, $zero_hours_by_time )
			: array_merge( $zero_hours_by_time, array_reverse( $busy_hours_by_count ) );
	}

	/**
	 * Build one expected interval entry.
	 *
	 * @param DateTime $date_start   Interval start.
	 * @param DateTime $date_end     Interval end.
	 * @param int      $orders_count Orders inside the interval.
	 * @return array
	 */
	private function build_interval( DateTime $date_start, DateTime $date_end, int $orders_count ): array {
		return array(
			'interval'       => $date_start->format( 'Y-m-d H' ),
			'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
			'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
			'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
			'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
			'subtotals'      => $this->build_totals( $orders_count, false ),
		);
	}

	/**
	 * Build a totals (or subtotals) block for a number of single-product orders.
	 *
	 * @param int  $orders_count   Orders included.
	 * @param bool $include_products Whether to include the totals-only 'products' key.
	 * @return array
	 */
	private function build_totals( int $orders_count, bool $include_products ): array {
		$num_items_sold = $orders_count * self::QTY_PER_PRODUCT;
		$shipping       = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue    = self::PRODUCT_PRICE * self::QTY_PER_PRODUCT * $orders_count;

		$totals = $this->expected_totals(
			array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $net_revenue + $shipping,
				'gross_sales'         => $net_revenue,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $orders_count ? $num_items_sold / $orders_count : 0,
				'avg_order_value'     => $orders_count ? $net_revenue / $orders_count : 0,
				'total_customers'     => $orders_count ? 1 : 0,
			)
		);

		if ( $include_products ) {
			$totals['products'] = 1;
		}

		return $totals;
	}
}
