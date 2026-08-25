<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Enums\OrderStatus;
use DateTime;
use WC_Helper_Coupon;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order_Item_Product;
use WC_Product_Simple;

/**
 * Tests for the Orders Stats DataStore filter handling ({@see OrdersStatsDataStore::get_data()}).
 *
 * Migrated from the legacy WC_Admin_Tests_Reports_Orders_Stats::test_populate_and_query_multiple_intervals().
 *
 * A shared fixture of 36 orders is created once for the class: for each of 3 products,
 * 3 coupon options (none, coupon 1, coupon 2) and 2 order statuses (completed, processing),
 * one order with just that product and one order with that product plus product 4.
 * Each order contains 4 items of each of its products and $10 shipping.
 *
 * Every filter scenario describes the orders it matches as per-product-mix order counts
 * (the 6 "mixes" are: product 1 alone, 2 alone, 3 alone, 1+4, 2+4, 3+4), from which all
 * expected totals derive.
 */
class DataStoreFilterQueriesTest extends OrdersStatsTestCase {

	// The coupon 1 amount is the WC_Helper_Coupon::create_coupon default.
	const COUPON_1_AMOUNT = 1;
	const COUPON_2_AMOUNT = 2;
	const ORDER_STATUS_1  = OrderStatus::COMPLETED;
	const ORDER_STATUS_2  = OrderStatus::PROCESSING;

	/**
	 * Prices of the 4 fixture products, keyed 1-4.
	 *
	 * @var array
	 */
	private static $product_prices = array(
		1 => 25,
		2 => 10,
		3 => 13,
		4 => 1,
	);

	/**
	 * IDs of the 4 fixture products, keyed 1-4.
	 *
	 * @var array
	 */
	private static $product_ids = array();

	/**
	 * IDs of the 2 fixture coupons, keyed 1-2.
	 *
	 * @var array
	 */
	private static $coupon_ids = array();

	/**
	 * Start of the hour all fixture orders were created in.
	 *
	 * @var DateTime
	 */
	private static $current_hour_start;

	/**
	 * End of the hour all fixture orders were created in.
	 *
	 * @var DateTime
	 */
	private static $current_hour_end;

	/**
	 * Create the shared order fixture once for the whole class.
	 */
	public static function wpSetUpBeforeClass(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$products = array();
		foreach ( self::$product_prices as $number => $price ) {
			$product = new WC_Product_Simple();
			$product->set_name( "Test Product {$number}" );
			$product->set_regular_price( $price );
			$product->save();
			$products[ $number ]          = $product;
			self::$product_ids[ $number ] = $product->get_id();
		}

		$coupon_1 = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2 = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( self::COUPON_2_AMOUNT );
		$coupon_2->save();

		self::$coupon_ids = array(
			1 => $coupon_1->get_id(),
			2 => $coupon_2->get_id(),
		);

		$customer = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$order_datetime = new DateTime();
		// Time near the top of the hour, so the +1s offsets below stay within it.
		$order_datetime->setTime( (int) $order_datetime->format( 'H' ), 10, 0 );
		$order_time = (int) $order_datetime->format( 'U' );

		$iterations = 1;
		foreach ( array( $products[1], $products[2], $products[3] ) as $product ) {
			foreach ( array( null, $coupon_1, $coupon_2 ) as $coupon ) {
				foreach ( array( self::ORDER_STATUS_1, self::ORDER_STATUS_2 ) as $order_status ) {
					$single_product_order = WC_Helper_Order::create_order( $customer->get_id(), $product );
					// Offset each order by 1 second.
					$single_product_order->set_date_created( $order_time + $iterations++ );
					$single_product_order->set_status( $order_status );

					if ( $coupon ) {
						$single_product_order->apply_coupon( $coupon );
					} else {
						$single_product_order->calculate_totals();
					}

					$two_product_order = WC_Helper_Order::create_order( $customer->get_id(), $products[4] );

					$item = new WC_Order_Item_Product();
					$item->set_props(
						array(
							'product'  => $product,
							'quantity' => self::QTY_PER_PRODUCT,
							'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => self::QTY_PER_PRODUCT ) ),
							'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => self::QTY_PER_PRODUCT ) ),
						)
					);
					$item->save();
					$two_product_order->add_item( $item );
					// Offset each order by 1 second.
					$two_product_order->set_date_created( $order_time + $iterations++ );
					$two_product_order->set_status( $order_status );

					if ( $coupon ) {
						$two_product_order->apply_coupon( $coupon );
					} else {
						$two_product_order->calculate_totals();
					}
				}
			}
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		self::$current_hour_start = new DateTime();
		self::$current_hour_start->setTimestamp( $order_time - ( (int) $order_datetime->format( 'i' ) * MINUTE_IN_SECONDS ) );

		self::$current_hour_end = new DateTime();
		self::$current_hour_end->setTimestamp( $order_time + ( HOUR_IN_SECONDS - ( $order_time % HOUR_IN_SECONDS ) ) - 1 );
	}

	/**
	 * Remove the persistent class fixture data.
	 */
	public static function wpTearDownAfterClass(): void {
		foreach ( wc_get_orders( array( 'limit' => -1 ) ) as $order ) {
			$order->delete( true );
		}
		WC_Helper_Reports::reset_stats_dbs();
	}

	/**
	 * @testdox Filter scenarios return the expected totals and intervals.
	 * @dataProvider filter_scenarios
	 *
	 * @param array $filter_args    Filter arguments added to the query, with placeholder values resolved by resolve_filter_args().
	 * @param array $mix_counts     Matched-order counts per product mix: ( p1, p2, p3, p1+p4, p2+p4, p3+p4 ).
	 * @param array $coupon_counts  Matched-order counts using coupon 1 and coupon 2, respectively.
	 * @param int   $products_count Expected count of distinct products in the matched orders.
	 */
	public function test_filter_scenario( array $filter_args, array $mix_counts, array $coupon_counts, int $products_count ): void {
		$query_args = array_merge( $this->base_query_args(), $this->resolve_filter_args( $filter_args ) );

		$expected_stats = $this->build_expected_stats( $mix_counts, $coupon_counts, $products_count );

		$this->assert_report_data( $expected_stats, $query_args );
	}

	/**
	 * Filter scenarios, as migrated from the legacy multiple-intervals test.
	 *
	 * In filter args, statuses are given as 'status_1' / 'status_2', products as
	 * 'product_1' ... 'product_4', and coupons as 'coupon_1' / 'coupon_2'; these are
	 * resolved to real fixture values by resolve_filter_args().
	 *
	 * @return array
	 */
	public function filter_scenarios(): array {
		return array(
			'no filters, all orders'                   => array(
				array(),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'status_is both statuses'                  => array(
				array( 'status_is' => array( 'status_1', 'status_2' ) ),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'status_is one status'                     => array(
				array( 'status_is' => array( 'status_1' ) ),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'status_is_not one status'                 => array(
				array( 'status_is_not' => array( 'status_2' ) ),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'status_is_not both statuses, no orders'   => array(
				array( 'status_is_not' => array( 'status_1', 'status_2' ) ),
				array( 0, 0, 0, 0, 0, 0 ),
				array( 0, 0 ),
				0,
			),
			'status_is with status_is_not'             => array(
				array(
					'status_is'     => array( 'status_1', 'status_2' ),
					'status_is_not' => array( 'status_2' ),
				),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'product_includes two products'            => array(
				array( 'product_includes' => array( 'product_1', 'product_2' ) ),
				array( 6, 6, 0, 6, 6, 0 ),
				array( 8, 8 ),
				3,
			),
			'product_includes one product'             => array(
				array( 'product_includes' => array( 'product_3' ) ),
				array( 0, 0, 6, 0, 0, 6 ),
				array( 4, 4 ),
				2,
			),
			'product_excludes one product'             => array(
				array( 'product_excludes' => array( 'product_1' ) ),
				array( 0, 6, 6, 0, 6, 6 ),
				array( 8, 8 ),
				3,
			),
			'product_excludes two products'            => array(
				array( 'product_excludes' => array( 'product_1', 'product_2' ) ),
				array( 0, 0, 6, 0, 0, 6 ),
				array( 4, 4 ),
				2,
			),
			'product_includes with product_excludes'   => array(
				array(
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'product_1' ),
				),
				array( 0, 6, 0, 0, 6, 0 ),
				array( 4, 4 ),
				2,
			),
			'coupon_includes both coupons'             => array(
				array( 'coupon_includes' => array( 'coupon_1', 'coupon_2' ) ),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 12, 12 ),
				4,
			),
			'coupon_includes one coupon'               => array(
				array( 'coupon_includes' => array( 'coupon_1' ) ),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 12, 0 ),
				4,
			),
			'coupon_excludes one coupon'               => array(
				array( 'coupon_excludes' => array( 'coupon_1' ) ),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 0, 12 ),
				4,
			),
			'coupon_excludes both coupons'             => array(
				array( 'coupon_excludes' => array( 'coupon_1', 'coupon_2' ) ),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 0, 0 ),
				4,
			),
			'coupon_includes with coupon_excludes'     => array(
				array(
					'coupon_includes' => array( 'coupon_1', 'coupon_2' ),
					'coupon_excludes' => array( 'coupon_2' ),
				),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 12, 0 ),
				4,
			),
			'customer_type new'                        => array(
				array( 'customer_type' => 'new' ),
				array( 1, 0, 0, 0, 0, 0 ),
				array( 0, 0 ),
				1,
			),
			'match all: status_is + product_includes'  => array(
				array(
					'status_is'        => array( 'status_1' ),
					'product_includes' => array( 'product_1' ),
				),
				array( 3, 0, 0, 3, 0, 0 ),
				array( 2, 2 ),
				2,
			),
			'match all: status_is + coupon_includes'   => array(
				array(
					'status_is'       => array( 'status_1' ),
					'coupon_includes' => array( 'coupon_1' ),
				),
				array( 1, 1, 1, 1, 1, 1 ),
				array( 6, 0 ),
				4,
			),
			'match all: product_includes + coupon_includes' => array(
				array(
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 2, 0, 0, 2, 0, 0 ),
				array( 4, 0 ),
				2,
			),
			'match all: status + product + coupon'     => array(
				array(
					'status_is'        => array( 'status_1' ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 1, 0, 0, 1, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: status_is + status_is_not + product + coupon' => array(
				array(
					'status_is'        => array( 'status_1', 'status_2' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 1, 0, 0, 1, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: statuses + product_includes + product_excludes' => array(
				array(
					'status_is'        => array( 'status_1', 'status_2' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'product_4' ),
				),
				array( 3, 3, 0, 0, 0, 0 ),
				array( 2, 2 ),
				2,
			),
			'match all: five filters'                  => array(
				array(
					'status_is'        => array( 'status_1', 'status_2' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'product_4' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 1, 1, 0, 0, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: six filters'                   => array(
				array(
					'status_is'        => array( 'status_1', 'status_2' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'product_4' ),
					'coupon_includes'  => array( 'coupon_1', 'coupon_2' ),
					'coupon_excludes'  => array( 'coupon_2' ),
				),
				array( 1, 1, 0, 0, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match any: status_is or status_is_not, all orders' => array(
				array(
					'match'         => 'any',
					'status_is'     => array( 'status_1' ),
					'status_is_not' => array( 'status_1' ),
				),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'match any: status_is or product_includes' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'product_includes' => array( 'product_1' ),
				),
				array( 6, 3, 3, 6, 3, 3 ),
				array( 8, 8 ),
				4,
			),
			'match any: status_is or coupon_includes'  => array(
				array(
					'match'           => 'any',
					'status_is'       => array( 'status_1' ),
					'coupon_includes' => array( 'coupon_1' ),
				),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 12, 6 ),
				4,
			),
			'match any: status_is or coupon_excludes'  => array(
				array(
					'match'           => 'any',
					'status_is'       => array( 'status_1' ),
					'coupon_excludes' => array( 'coupon_1' ),
				),
				array( 5, 5, 5, 5, 5, 5 ),
				array( 6, 12 ),
				4,
			),
			'match any: product_includes or coupon_includes' => array(
				array(
					'match'            => 'any',
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 6, 2, 2, 6, 2, 2 ),
				array( 12, 4 ),
				4,
			),
			'match any: status or product or coupon'   => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 6, 4, 4, 6, 4, 4 ),
				array( 12, 8 ),
				4,
			),
			'match any: status_is or status_is_not or product or coupon' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 6, 4, 4, 6, 4, 4 ),
				array( 12, 8 ),
				4,
			),
			'match any: statuses or product_includes or product_excludes' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
				),
				array( 6, 3, 6, 6, 3, 6 ),
				array( 10, 10 ),
				4,
			),
			'match any: five filters'                  => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
					'coupon_includes'  => array( 'coupon_1' ),
				),
				array( 6, 4, 6, 6, 4, 6 ),
				array( 12, 10 ),
				4,
			),
			'match any: six filters'                   => array(
				array(
					'match'            => 'any',
					'status_is'        => array( 'status_1' ),
					'status_is_not'    => array( 'status_2' ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
					'coupon_includes'  => array( 'coupon_1' ),
					'coupon_excludes'  => array( 'coupon_2' ),
				),
				array( 6, 5, 6, 6, 5, 6 ),
				array( 12, 10 ),
				4,
			),
		);
	}

	/**
	 * @testdox The returning-customer filter matches all fixture orders except the customer's first.
	 */
	public function test_customer_type_returning(): void {
		$query_args = array_merge(
			$this->base_query_args(),
			array( 'customer_type' => 'returning' )
		);

		// All 36 fixture orders match except the customer's first: half hold one product, half hold two.
		$total_orders_count     = 36;
		$returning_orders_count = 1;

		$orders_count   = $total_orders_count - $returning_orders_count;
		$num_items_sold = ( $total_orders_count / 2 ) * self::QTY_PER_PRODUCT
						+ ( $total_orders_count / 2 ) * self::QTY_PER_PRODUCT * 2
						- $returning_orders_count * self::QTY_PER_PRODUCT;
		$coupons        = 12 * self::COUPON_1_AMOUNT + 12 * self::COUPON_2_AMOUNT;
		$shipping       = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue    = $this->net_revenue_for_mix_counts( array( 6, 6, 6, 6, 6, 6 ) )
						- self::$product_prices[1] * self::QTY_PER_PRODUCT * $returning_orders_count
						- $coupons;

		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $net_revenue + $shipping,
					'gross_sales'         => $net_revenue + $coupons,
					'coupons'             => $coupons,
					'coupons_count'       => count( self::$coupon_ids ),
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
					'avg_order_value'     => $net_revenue / $orders_count,
					'total_customers'     => $returning_orders_count,
					'products'            => 4,
				)
			),
			self::$current_hour_start->format( 'Y-m-d H:i:s' ),
			self::$current_hour_end->format( 'Y-m-d H:i:s' )
		);

		$this->assert_report_data( $expected_stats, $query_args );
	}

	/**
	 * Query args shared by every scenario: the fixture hour with hourly intervals.
	 *
	 * @return array
	 */
	private function base_query_args(): array {
		return array(
			'after'    => self::$current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => self::$current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
		);
	}

	/**
	 * Replace status/product/coupon placeholders in filter args with fixture values.
	 *
	 * @param array $filter_args Filter args with placeholder values.
	 * @return array
	 */
	private function resolve_filter_args( array $filter_args ): array {
		$map = array(
			'status_1'  => self::ORDER_STATUS_1,
			'status_2'  => self::ORDER_STATUS_2,
			'product_1' => self::$product_ids[1] ?? null,
			'product_2' => self::$product_ids[2] ?? null,
			'product_3' => self::$product_ids[3] ?? null,
			'product_4' => self::$product_ids[4] ?? null,
			'coupon_1'  => self::$coupon_ids[1] ?? null,
			'coupon_2'  => self::$coupon_ids[2] ?? null,
		);

		$resolved = array();
		foreach ( $filter_args as $key => $value ) {
			if ( is_array( $value ) ) {
				$resolved[ $key ] = array_map(
					function ( $placeholder ) use ( $map ) {
						return $map[ $placeholder ];
					},
					$value
				);
			} else {
				$resolved[ $key ] = $value;
			}
		}

		return $resolved;
	}

	/**
	 * Sum of the matched orders' product revenue (before coupon deduction).
	 *
	 * @param array $mix_counts Matched-order counts per product mix: ( p1, p2, p3, p1+p4, p2+p4, p3+p4 ).
	 * @return float
	 */
	private function net_revenue_for_mix_counts( array $mix_counts ): float {
		$prices        = self::$product_prices;
		$mix_prices    = array(
			$prices[1],
			$prices[2],
			$prices[3],
			$prices[1] + $prices[4],
			$prices[2] + $prices[4],
			$prices[3] + $prices[4],
		);
		$total_revenue = 0;
		foreach ( $mix_prices as $index => $price ) {
			$total_revenue += $price * self::QTY_PER_PRODUCT * $mix_counts[ $index ];
		}

		return (float) $total_revenue;
	}

	/**
	 * Build the full expected get_data() result from a scenario description.
	 *
	 * @param array $mix_counts     Matched-order counts per product mix: ( p1, p2, p3, p1+p4, p2+p4, p3+p4 ).
	 * @param array $coupon_counts  Matched-order counts using coupon 1 and coupon 2, respectively.
	 * @param int   $products_count Expected count of distinct products in the matched orders.
	 * @return array
	 */
	private function build_expected_stats( array $mix_counts, array $coupon_counts, int $products_count ): array {
		$orders_count       = array_sum( $mix_counts );
		$single_item_orders = $mix_counts[0] + $mix_counts[1] + $mix_counts[2];
		$two_item_orders    = $mix_counts[3] + $mix_counts[4] + $mix_counts[5];
		$num_items_sold     = $single_item_orders * self::QTY_PER_PRODUCT + $two_item_orders * self::QTY_PER_PRODUCT * 2;
		$coupons            = $coupon_counts[0] * self::COUPON_1_AMOUNT + $coupon_counts[1] * self::COUPON_2_AMOUNT;
		$coupons_count      = ( $coupon_counts[0] ? 1 : 0 ) + ( $coupon_counts[1] ? 1 : 0 );
		$shipping           = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue        = $orders_count ? $this->net_revenue_for_mix_counts( $mix_counts ) - $coupons : 0;

		return $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $net_revenue + $shipping,
					'gross_sales'         => $net_revenue + $coupons,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => $orders_count ? $num_items_sold / $orders_count : 0,
					'avg_order_value'     => $orders_count ? $net_revenue / $orders_count : 0,
					'total_customers'     => $orders_count ? 1 : 0,
					'products'            => $products_count,
				)
			),
			self::$current_hour_start->format( 'Y-m-d H:i:s' ),
			self::$current_hour_end->format( 'Y-m-d H:i:s' )
		);
	}
}
