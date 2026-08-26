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
 * A shared fixture of 36 orders is created once for the class: for each of 3 primary
 * products, 3 coupon options (none, the small coupon, the large coupon) and 2 order
 * statuses (completed, processing), one order with just that product and one order with
 * that product plus a shared add-on product. Each order contains 4 items of each of its
 * products and $10 shipping.
 *
 * Every filter scenario describes the orders it matches as per-product-mix order counts
 * (the 6 "mixes" are: each primary product alone, then each with the add-on product),
 * from which all expected totals derive.
 */
class DataStoreFilterQueriesTest extends OrdersStatsTestCase {

	// The two fixture coupons differ only in their fixed discount amount.
	const SMALL_COUPON_AMOUNT = 1;
	const LARGE_COUPON_AMOUNT = 2;

	// The add-on product is the second product in every two-product order.
	const ADD_ON_PRODUCT_PRICE = 1;

	/**
	 * Prices of the three primary products.
	 *
	 * @var array
	 */
	private static $primary_product_prices = array( 25, 10, 13 );

	/**
	 * IDs of the three primary products.
	 *
	 * @var array
	 */
	private static $primary_product_ids = array();

	/**
	 * ID of the add-on product.
	 *
	 * @var int
	 */
	private static $add_on_product_id;

	/**
	 * ID of the small fixture coupon.
	 *
	 * @var int
	 */
	private static $small_coupon_id;

	/**
	 * ID of the large fixture coupon.
	 *
	 * @var int
	 */
	private static $large_coupon_id;

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
	 * IDs of the fixture orders.
	 *
	 * @var array
	 */
	private static $fixture_order_ids = array();

	/**
	 * Create the shared order fixture once for the whole class.
	 */
	public static function wpSetUpBeforeClass(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$primary_products = array();
		foreach ( self::$primary_product_prices as $index => $price ) {
			$product = new WC_Product_Simple();
			$product->set_name( 'Test Product ' . ( $index + 1 ) );
			$product->set_regular_price( $price );
			$product->save();
			$primary_products[]          = $product;
			self::$primary_product_ids[] = $product->get_id();
		}

		$add_on_product = new WC_Product_Simple();
		$add_on_product->set_name( 'Test Add-on Product' );
		$add_on_product->set_regular_price( self::ADD_ON_PRODUCT_PRICE );
		$add_on_product->save();
		self::$add_on_product_id = $add_on_product->get_id();

		$small_coupon = WC_Helper_Coupon::create_coupon( 'small_coupon' );
		$small_coupon->set_amount( self::SMALL_COUPON_AMOUNT );
		$small_coupon->save();

		$large_coupon = WC_Helper_Coupon::create_coupon( 'large_coupon' );
		$large_coupon->set_amount( self::LARGE_COUPON_AMOUNT );
		$large_coupon->save();

		self::$small_coupon_id = $small_coupon->get_id();
		self::$large_coupon_id = $large_coupon->get_id();

		$customer = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$order_datetime = new DateTime();
		// Time near the top of the hour, so the +1s offsets below stay within it.
		$order_datetime->setTime( (int) $order_datetime->format( 'H' ), 10, 0 );
		$order_time = (int) $order_datetime->format( 'U' );

		$iterations = 1;
		foreach ( $primary_products as $product ) {
			foreach ( array( null, $small_coupon, $large_coupon ) as $coupon ) {
				foreach ( array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) as $order_status ) {
					$single_product_order = WC_Helper_Order::create_order( $customer->get_id(), $product );
					// Offset each order by 1 second.
					$single_product_order->set_date_created( $order_time + $iterations++ );
					$single_product_order->set_status( $order_status );

					if ( $coupon ) {
						$single_product_order->apply_coupon( $coupon );
					} else {
						$single_product_order->calculate_totals();
					}

					$two_product_order = WC_Helper_Order::create_order( $customer->get_id(), $add_on_product );

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

					self::$fixture_order_ids[] = $single_product_order->get_id();
					self::$fixture_order_ids[] = $two_product_order->get_id();
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
		foreach ( self::$fixture_order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->delete( true );
			}
		}
		self::$fixture_order_ids = array();

		// Delete the products through CRUD so their wc_product_meta_lookup rows go too.
		foreach ( array_merge( self::$primary_product_ids, array( self::$add_on_product_id ) ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product->delete( true );
			}
		}
		self::$primary_product_ids = array();

		WC_Helper_Reports::reset_stats_dbs();
	}

	/**
	 * @testdox Filter scenarios return the expected totals and intervals.
	 * @dataProvider filter_scenarios
	 *
	 * @param array $filter_args    Filter arguments added to the query, with placeholder values resolved by resolve_filter_args().
	 * @param array $mix_counts     Matched-order counts per product mix: ( p1, p2, p3, p1+p4, p2+p4, p3+p4 ).
	 * @param array $coupon_counts  Matched-order counts using the small and large coupon, respectively.
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
	 * In filter args, products are given as 'product_1' ... 'product_3' or
	 * 'add_on_product', and coupons as 'small_coupon' / 'large_coupon'; these placeholders are
	 * resolved to the fixture IDs by resolve_filter_args().
	 *
	 * @return array
	 */
	public function filter_scenarios(): array {
		return array(
			'no filters matches all 36 orders'             => array(
				array(),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'status_is with both statuses matches all orders' => array(
				array( 'status_is' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'status_is completed matches the completed half' => array(
				array( 'status_is' => array( OrderStatus::COMPLETED ) ),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'status_is_not processing matches the completed half' => array(
				array( 'status_is_not' => array( OrderStatus::PROCESSING ) ),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'status_is_not with both statuses matches nothing' => array(
				array( 'status_is_not' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ),
				array( 0, 0, 0, 0, 0, 0 ),
				array( 0, 0 ),
				0,
			),
			'status_is both minus processing matches the completed half' => array(
				array(
					'status_is'     => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
					'status_is_not' => array( OrderStatus::PROCESSING ),
				),
				array( 3, 3, 3, 3, 3, 3 ),
				array( 6, 6 ),
				4,
			),
			'product_includes products 1 and 2 matches two thirds' => array(
				array( 'product_includes' => array( 'product_1', 'product_2' ) ),
				array( 6, 6, 0, 6, 6, 0 ),
				array( 8, 8 ),
				3,
			),
			'product_includes product 3 matches its third of orders' => array(
				array( 'product_includes' => array( 'product_3' ) ),
				array( 0, 0, 6, 0, 0, 6 ),
				array( 4, 4 ),
				2,
			),
			'product_excludes product 1 matches the other two thirds' => array(
				array( 'product_excludes' => array( 'product_1' ) ),
				array( 0, 6, 6, 0, 6, 6 ),
				array( 8, 8 ),
				3,
			),
			'product_excludes products 1 and 2 matches the remaining third' => array(
				array( 'product_excludes' => array( 'product_1', 'product_2' ) ),
				array( 0, 0, 6, 0, 0, 6 ),
				array( 4, 4 ),
				2,
			),
			'include products 1 and 2 minus product 1 leaves product 2 orders' => array(
				array(
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'product_1' ),
				),
				array( 0, 6, 0, 0, 6, 0 ),
				array( 4, 4 ),
				2,
			),
			'coupon_includes both matches the couponed two thirds' => array(
				array( 'coupon_includes' => array( 'small_coupon', 'large_coupon' ) ),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 12, 12 ),
				4,
			),
			'coupon_includes the small coupon matches its third' => array(
				array( 'coupon_includes' => array( 'small_coupon' ) ),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 12, 0 ),
				4,
			),
			'coupon_excludes small matches uncouponed and large-coupon orders' => array(
				array( 'coupon_excludes' => array( 'small_coupon' ) ),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 0, 12 ),
				4,
			),
			'coupon_excludes both matches the uncouponed third' => array(
				array( 'coupon_excludes' => array( 'small_coupon', 'large_coupon' ) ),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 0, 0 ),
				4,
			),
			'coupon_includes both minus the large leaves the small third' => array(
				array(
					'coupon_includes' => array( 'small_coupon', 'large_coupon' ),
					'coupon_excludes' => array( 'large_coupon' ),
				),
				array( 2, 2, 2, 2, 2, 2 ),
				array( 12, 0 ),
				4,
			),
			'customer_type new matches only the very first order' => array(
				array( 'customer_type' => 'new' ),
				array( 1, 0, 0, 0, 0, 0 ),
				array( 0, 0 ),
				1,
			),
			'match all: completed orders containing product 1' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED ),
					'product_includes' => array( 'product_1' ),
				),
				array( 3, 0, 0, 3, 0, 0 ),
				array( 2, 2 ),
				2,
			),
			'match all: completed orders using the small coupon' => array(
				array(
					'status_is'       => array( OrderStatus::COMPLETED ),
					'coupon_includes' => array( 'small_coupon' ),
				),
				array( 1, 1, 1, 1, 1, 1 ),
				array( 6, 0 ),
				4,
			),
			'match all: product 1 orders using the small coupon' => array(
				array(
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 2, 0, 0, 2, 0, 0 ),
				array( 4, 0 ),
				2,
			),
			'match all: completed product 1 orders using the small coupon' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 1, 0, 0, 1, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: status_is_not processing keeps the same orders' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 1, 0, 0, 1, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: excluding the add-on leaves single-product orders' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'add_on_product' ),
				),
				array( 3, 3, 0, 0, 0, 0 ),
				array( 2, 2 ),
				2,
			),
			'match all: five filters leave two small-coupon orders' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'add_on_product' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 1, 1, 0, 0, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match all: six filters keep the same two orders' => array(
				array(
					'status_is'        => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1', 'product_2' ),
					'product_excludes' => array( 'add_on_product' ),
					'coupon_includes'  => array( 'small_coupon', 'large_coupon' ),
					'coupon_excludes'  => array( 'large_coupon' ),
				),
				array( 1, 1, 0, 0, 0, 0 ),
				array( 2, 0 ),
				2,
			),
			'match any: a status and its negation together match all orders' => array(
				array(
					'match'         => 'any',
					'status_is'     => array( OrderStatus::COMPLETED ),
					'status_is_not' => array( OrderStatus::COMPLETED ),
				),
				array( 6, 6, 6, 6, 6, 6 ),
				array( 12, 12 ),
				4,
			),
			'match any: completed or containing product 1' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'product_includes' => array( 'product_1' ),
				),
				array( 6, 3, 3, 6, 3, 3 ),
				array( 8, 8 ),
				4,
			),
			'match any: completed or using the small coupon' => array(
				array(
					'match'           => 'any',
					'status_is'       => array( OrderStatus::COMPLETED ),
					'coupon_includes' => array( 'small_coupon' ),
				),
				array( 4, 4, 4, 4, 4, 4 ),
				array( 12, 6 ),
				4,
			),
			'match any: completed or not using the small coupon' => array(
				array(
					'match'           => 'any',
					'status_is'       => array( OrderStatus::COMPLETED ),
					'coupon_excludes' => array( 'small_coupon' ),
				),
				array( 5, 5, 5, 5, 5, 5 ),
				array( 6, 12 ),
				4,
			),
			'match any: product 1 orders or small-coupon orders' => array(
				array(
					'match'            => 'any',
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 6, 2, 2, 6, 2, 2 ),
				array( 12, 4 ),
				4,
			),
			'match any: completed, product 1 or small-coupon orders' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 6, 4, 4, 6, 4, 4 ),
				array( 12, 8 ),
				4,
			),
			'match any: status_is_not processing adds no new orders' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 6, 4, 4, 6, 4, 4 ),
				array( 12, 8 ),
				4,
			),
			'match any: orders without product 2 widen the union' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
				),
				array( 6, 3, 6, 6, 3, 6 ),
				array( 10, 10 ),
				4,
			),
			'match any: adding the small coupon widens the union further' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
					'coupon_includes'  => array( 'small_coupon' ),
				),
				array( 6, 4, 6, 6, 4, 6 ),
				array( 12, 10 ),
				4,
			),
			'match any: excluding the large coupon widens the union again' => array(
				array(
					'match'            => 'any',
					'status_is'        => array( OrderStatus::COMPLETED ),
					'status_is_not'    => array( OrderStatus::PROCESSING ),
					'product_includes' => array( 'product_1' ),
					'product_excludes' => array( 'product_2' ),
					'coupon_includes'  => array( 'small_coupon' ),
					'coupon_excludes'  => array( 'large_coupon' ),
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
		$coupons        = 12 * self::SMALL_COUPON_AMOUNT + 12 * self::LARGE_COUPON_AMOUNT;
		$shipping       = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue    = $this->net_revenue_for_mix_counts( array( 6, 6, 6, 6, 6, 6 ) )
						- self::$primary_product_prices[0] * self::QTY_PER_PRODUCT * $returning_orders_count
						- $coupons;

		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $net_revenue + $shipping,
					'gross_sales'         => $net_revenue + $coupons,
					'coupons'             => $coupons,
					// Both fixture coupons appear across the matched orders.
					'coupons_count'       => 2,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
					'avg_order_value'     => $net_revenue / $orders_count,
					// All fixture orders belong to the one fixture customer.
					'total_customers'     => 1,
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
			'product_1'      => self::$primary_product_ids[0] ?? null,
			'product_2'      => self::$primary_product_ids[1] ?? null,
			'product_3'      => self::$primary_product_ids[2] ?? null,
			'add_on_product' => self::$add_on_product_id,
			'small_coupon'   => self::$small_coupon_id,
			'large_coupon'   => self::$large_coupon_id,
		);

		$resolved = array();
		foreach ( $filter_args as $key => $value ) {
			if ( is_array( $value ) ) {
				$resolved[ $key ] = array_map(
					function ( $placeholder ) use ( $map ) {
						return $map[ $placeholder ] ?? $placeholder;
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
		$prices        = self::$primary_product_prices;
		$mix_prices    = array(
			$prices[0],
			$prices[1],
			$prices[2],
			$prices[0] + self::ADD_ON_PRODUCT_PRICE,
			$prices[1] + self::ADD_ON_PRODUCT_PRICE,
			$prices[2] + self::ADD_ON_PRODUCT_PRICE,
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
	 * @param array $coupon_counts  Matched-order counts using the small and large coupon, respectively.
	 * @param int   $products_count Expected count of distinct products in the matched orders.
	 * @return array
	 */
	private function build_expected_stats( array $mix_counts, array $coupon_counts, int $products_count ): array {
		$orders_count       = array_sum( $mix_counts );
		$single_item_orders = $mix_counts[0] + $mix_counts[1] + $mix_counts[2];
		$two_item_orders    = $mix_counts[3] + $mix_counts[4] + $mix_counts[5];
		$num_items_sold     = $single_item_orders * self::QTY_PER_PRODUCT + $two_item_orders * self::QTY_PER_PRODUCT * 2;
		$coupons            = $coupon_counts[0] * self::SMALL_COUPON_AMOUNT + $coupon_counts[1] * self::LARGE_COUPON_AMOUNT;
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
