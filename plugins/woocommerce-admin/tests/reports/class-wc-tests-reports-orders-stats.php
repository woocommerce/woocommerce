<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Tests\Orders
 */

/**
 * Class WC_Tests_Reports_Orders_Stats
 */
class WC_Tests_Reports_Orders_Stats extends WC_Unit_Test_Case {

	/**
	 * Test the calculations and querying works correctly for the base case of 1 order.
	 *
	 * @since 3.5.0
	 */
	public function test_populate_and_query() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		$refund = wc_create_refund(
			array(
				'amount'         => 12,
				'order_id'       => $order->get_id(),
			)
		);

		$data_store = new WC_Admin_Reports_Orders_Stats_Data_Store();

		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => 1,
				'num_items_sold'          => 4,
				'avg_items_per_order'     => 4,
				'avg_order_value'         => 68,
				'gross_revenue'           => 97,
				'coupons'                 => 20,
				'refunds'                 => 12,
				'taxes'                   => 7,
				'shipping'                => 10,
				'net_revenue'             => 68,
				'num_returning_customers' => 0,
				'num_new_customers'       => 1,
				'products'                => 1,
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'gross_revenue'           => 97,
						'net_revenue'             => 68,
						'coupons'                 => 20,
						'shipping'                => 10,
						'taxes'                   => 7,
						'refunds'                 => 12,
						'orders_count'            => 1,
						'num_items_sold'          => 4,
						'avg_items_per_order'     => 4,
						'avg_order_value'         => 68,
						'num_returning_customers' => 0,
						'num_new_customers'       => 1,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		// Test retrieving the stats from the data store.
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $args ) ), true ) );

		// Test retrieving the stats through the query class.
		$query          = new WC_Admin_Reports_Orders_Stats_Query( $args );
		$expected_stats = array(
			'totals'    => array(
				'net_revenue'             => 68,
				'avg_order_value'         => 68,
				'orders_count'            => 1,
				'avg_items_per_order'     => 4,
				'num_items_sold'          => 4,
				'coupons'                 => 20,
				'num_returning_customers' => 0,
				'num_new_customers'       => 1,
				'products'                => '1',
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'net_revenue'             => 68,
						'avg_order_value'         => 68,
						'orders_count'            => 1,
						'avg_items_per_order'     => 4,
						'num_items_sold'          => 4,
						'coupons'                 => 20,
						'num_returning_customers' => 0,
						'num_new_customers'       => 1,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $query->get_data() ), true ) );
	}

	/**
	 * Test the calculations and querying works correctly for the case of multiple orders.
	 */
	public function test_populate_and_query_multiple_intervals() {
		global $wpdb;

		// 2 different products.
		$product_1_price = 25;
		$product_1       = new WC_Product_Simple();
		$product_1->set_name( 'Test Product' );
		$product_1->set_regular_price( $product_1_price );
		$product_1->save();

		$product_2_price = 10;
		$product_2       = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( $product_2_price );
		$product_2->save();

		$product_3_price = 13;
		$product_3       = new WC_Product_Simple();
		$product_3->set_name( 'Test Product 3' );
		$product_3->set_regular_price( $product_3_price );
		$product_3->save();

		$product_4_price = 1;
		$product_4       = new WC_Product_Simple();
		$product_4->set_name( 'Test Product 4' );
		$product_4->set_regular_price( $product_4_price );
		$product_4->save();

		// 2 different coupons
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		$order_status_1 = 'completed';
		$order_status_2 = 'processing';

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'cust_2', 'pwd_2', 'user_2@mail.com' );

		$order_1_time = time();
		$order_2_time = $order_1_time;

		$this_['hour']  = array( 1, 2 );
		$this_['day']   = array( 1, 2 );
		$this_['week']  = array( 1, 2 );
		$this_['month'] = array( 1, 2 );
		$this_['year']  = array( 1, 2 );

		$order_1_datetime = new DateTime();
		$order_1_datetime = $order_1_datetime->setTimestamp( $order_1_time );

		$order[1]['year']  = (int) $order_1_datetime->format( 'Y' );
		$order[1]['month'] = (int) $order_1_datetime->format( 'm' );
		$order[1]['week']  = (int) $order_1_datetime->format( 'W' );
		$order[1]['day']   = (int) $order_1_datetime->format( 'd' );
		$order[1]['hour']  = (int) $order_1_datetime->format( 'H' );

		// same day, different hour.
		$order_3_datetime  = new DateTime();
		$order_3_datetime  = $order_3_datetime->setTimestamp( $order_1_time - HOUR_IN_SECONDS );
		$order_3_time      = $order_3_datetime->format( 'U' );
		$order[3]['year']  = (int) $order_3_datetime->format( 'Y' );
		$order[3]['month'] = (int) $order_3_datetime->format( 'm' );
		$order[3]['week']  = (int) $order_3_datetime->format( 'W' );
		$order[3]['day']   = (int) $order_3_datetime->format( 'd' );

		// Previous day.
		$order_4_datetime  = new DateTime();
		$order_4_datetime  = $order_4_datetime->setTimestamp( $order_1_time - DAY_IN_SECONDS );
		$order_4_time      = $order_4_datetime->format( 'U' );
		$order[4]['year']  = (int) $order_4_datetime->format( 'Y' );
		$order[4]['month'] = (int) $order_4_datetime->format( 'm' );
		$order[4]['week']  = (int) $order_4_datetime->format( 'W' );
		$order[4]['day']   = (int) $order_4_datetime->format( 'd' );

		// Previous week.
		$order_5_datetime  = new DateTime();
		$order_5_datetime  = $order_5_datetime->setTimestamp( $order_1_time - WEEK_IN_SECONDS );
		$order_5_time      = $order_5_datetime->format( 'U' );
		$order[5]['year']  = (int) $order_5_datetime->format( 'Y' );
		$order[5]['month'] = (int) $order_5_datetime->format( 'm' );
		$order[5]['week']  = (int) $order_5_datetime->format( 'W' );
		$order[5]['day']   = (int) $order_5_datetime->format( 'd' );

		// Previous month.
		$order_6_datetime  = new DateTime();
		$order_6_datetime  = $order_6_datetime->setTimestamp( $order_1_time - MONTH_IN_SECONDS );
		$order_6_time      = $order_6_datetime->format( 'U' );
		$order[6]['year']  = (int) $order_6_datetime->format( 'Y' );
		$order[6]['month'] = (int) $order_6_datetime->format( 'm' );
		$order[6]['week']  = (int) $order_6_datetime->format( 'W' );
		$order[6]['day']   = (int) $order_6_datetime->format( 'd' );

		// Previous year.
		$order_7_datetime  = new DateTime();
		$order_7_datetime  = $order_7_datetime->setTimestamp( $order_1_time - YEAR_IN_SECONDS );
		$order_7_time      = $order_7_datetime->format( 'U' );
		$order[7]['year']  = (int) $order_7_datetime->format( 'Y' );
		$order[7]['month'] = (int) $order_7_datetime->format( 'm' );
		$order[7]['week']  = (int) $order_7_datetime->format( 'W' );
		$order[7]['day']   = (int) $order_7_datetime->format( 'd' );

		foreach ( array( 3, 4, 5, 6, 7 ) as $order_no ) {
			if ( $order[ $order_no ]['day'] === $order[1]['day'] && $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['day'][] = $order_no;
			}
			if ( $order[ $order_no ]['week'] === $order[1]['week'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['week'][] = $order_no;
			}
			if ( $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['month'][] = $order_no;
			}
			if ( $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['year'][] = $order_no;
			}
		}

		$orders = array();
		// 2 different order statuses, plus new vs returning customer
		$qty_per_product = 4; // Hardcoded in WC_Helper_Order::create_order.

		foreach ( array( $product_1, $product_2, $product_3 ) as $product ) {
			foreach ( array( null, $coupon_1, $coupon_2 ) as $coupon ) {
				foreach ( array( $order_status_1, $order_status_2 ) as $order_status ) {
					foreach ( array( $customer_1, $customer_2 ) as $customer ) {
						foreach (
							array(
								$order_1_time,
								$order_2_time,
							) as $order_time
						) { // As there are no tests for different timeframes, ignore these for now: $order_3_time, $order_4_time, $order_5_time, $order_6_time, $order_7_time
							// One order with only 1 product.
							$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
							$order->set_date_created( $order_time );
							$order->set_status( $order_status );

							if ( $coupon ) {
								$order->apply_coupon( $coupon );
							}

							$order->calculate_totals();
							$order->save();

							$orders[] = $order;

							// One order with 2 products: product_4 and selected product.
							$order_2 = WC_Helper_Order::create_order( $customer->get_id(), $product_4 );

							$item = new WC_Order_Item_Product();
							$item->set_props(
								array(
									'product'  => $product,
									'quantity' => 4,
									'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
									'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
								)
							);
							$item->save();
							$order_2->add_item( $item );
							$order_2->set_date_created( $order_time );
							$order_2->set_status( $order_status );

							if ( $coupon ) {
								$order_2->apply_coupon( $coupon );
							}

							$order_2->calculate_totals();
							$order_2->save();

							$orders[] = $order_2;
						}
					}
				}
			}
		}

		$data_store = new WC_Admin_Reports_Orders_Stats_Data_Store();

		// Tests for before & after set to current hour.
		$current_hour = new DateTime();
		$current_hour->setTimestamp( $order_1_time );
		$current_hour_minutes = (int) $current_hour->format( 'i' );
		$current_hour->setTimestamp( $order_1_time - $current_hour_minutes * MINUTE_IN_SECONDS );

		$now = new DateTime();

		// All orders, no filters.
		// 72 orders in one batch (3 products * 3 coupon options * 2 order statuses * 2 customers * 2 orders), 4 items of each product per order
		// 24 orders without coupons, 48 with coupons: 24 with $1 coupon and 24 with $2 coupon.
		// shipping is $10 per order.
		$query_args = array(
			'after'    => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'   => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval' => 'hour',
		);

		$order_permutations     = 72;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product + $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;
		$new_customers  = 2;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// * Order status filter
		// ** Status is, positive filter for 2 statuses, i.e. all orders.
		$query_args = array(
			'after'     => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'    => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'  => 'hour',
			'status_is' => array(
				$order_status_1,
				$order_status_2,
			),
		);

		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Status is, positive filter for 2 statuses' );

		// ** Status is, positive filter for 1 status -> half orders.
		$query_args = array(
			'after'     => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'    => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'  => 'hour',
			'status_is' => array(
				$order_status_1,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Status is, positive filter for 1 status' );

		// ** Status is not, negative filter for 1 status -> half orders.
		$query_args = array(
			'after'         => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'        => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is_not' => array(
				$order_status_2,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Status is not, negative filter for 1 status ' );

		// ** Status is not, negative filter for 2 statuses -> no orders.
		$query_args = array(
			'after'         => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'        => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is_not' => array(
				$order_status_1,
				$order_status_2,
			),
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => 0,
				'num_items_sold'          => 0,
				'gross_revenue'           => 0,
				'coupons'                 => 0,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => 0,
				'net_revenue'             => 0,
				'avg_items_per_order'     => 0,
				'avg_order_value'         => 0,
				'num_returning_customers' => 0,
				'num_new_customers'       => 0,
				'products'                => 0,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => 0,
						'num_items_sold'          => 0,
						'gross_revenue'           => 0,
						'coupons'                 => 0,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => 0,
						'net_revenue'             => 0,
						'avg_items_per_order'     => 0,
						'avg_order_value'         => 0,
						'num_returning_customers' => 0,
						'num_new_customers'       => 0,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Status is not, negative filter for 2 statuses' );

		// ** Status is + Status is not, positive filter for 2 statuses, negative for 1 -> half of orders.
		$query_args = array(
			'after'         => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'        => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is'     => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not' => array(
				$order_status_2,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Status is + Status is not, positive filter for 2 statuses, negative for 1' );

		// * Product filter
		// ** Product includes, positive filter for 2 products, i.e. 2 orders out of 3.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 3,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, positive filter for 2 products: ' . $wpdb->last_query );

		// ** Product includes, positive filter for 1 product, 1/3 of orders
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_3->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 2,
				// product 3 and product 4 (that is sometimes included in the orders with product 3).
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, positive filter for 1 product: ' . $wpdb->last_query );

		// ** Product excludes, negative filter for 1 product, 2/3 of orders.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_excludes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 3,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 1 product: ' . $wpdb->last_query );

		// ** Product excludes, negative filter for 2 products, 1/3 of orders
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_excludes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Product includes + product excludes, positive filter for 2 products, negative for 1 -> 1/3 of orders, only orders with product 2 and product 2 + product 4
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_2_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// * Coupon filters
		// ** Coupon includes, positive filter for 2 coupons, i.e. 2/3 of orders.
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Coupon includes, positive filter for 1 coupon, 1/3 of orders
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => 2,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => 2,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Coupon excludes, negative filter for 1 coupon, 2/3 of orders
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_excludes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Coupon excludes, negative filter for 2 coupons, 1/3 of orders
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_excludes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 0;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Coupon includes + coupon excludes, positive filter for 2 coupon, negative for 1, 1/3 orders
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
			'coupon_excludes' => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// * Customer filters
		// ** Customer new
		$query_args = array(
			'after'    => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'   => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval' => 'hour',
			'customer' => 'new',
		);

		$orders_count   = 144;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = $orders_count;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Product includes, negative filter for 2 product: ' . $wpdb->last_query );

		// ** Customer returning
		$returning_order = WC_Helper_Order::create_order( $customer_1->get_id(), $product );
		$returning_order->set_status( 'completed' );
		$returning_order->set_shipping_total( 10 );
		$returning_order->set_total( 110 ); // $25x4 products + $10 shipping.
		$returning_order->save();

		$query_args = array(
			'after'    => date( 'Y-m-d H:i:s', $orders[0]->get_date_created()->getOffsetTimestamp() + 1 ), // Date after initial order to get a returning customer.
			'before'   => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval' => 'hour',
			'customer' => 'returning',
		);

		$order_permutations     = 72;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = 1;
		$num_items_sold = 4;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = 100;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 1,
				'num_new_customers'       => 0,
				'products'                => 1,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => date( 'Y-m-d H:i:s', $orders[0]->get_date_created()->getOffsetTimestamp() + 1 ),
					'date_start_gmt' => date( 'Y-m-d H:i:s', $orders[0]->get_date_created()->getOffsetTimestamp() + 1 ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 1,
						'num_new_customers'       => 0,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'Orders from returning customers: ' . $wpdb->last_query );
		wp_delete_post( $returning_order->get_id(), true );

		// Combinations: match all
		// status_is + product_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 4;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + coupon_includes.
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// product_includes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 8;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + product_includes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + status_is_not + product_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + status_is_not + product_includes + product_excludes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 4;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product; // No 2-item-orders here.
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				// Prod_1, status_1, no coupon orders included here, so 2 new cust orders.
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + status_is_not + product_includes + product_excludes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is + status_is_not + product_includes + product_excludes + coupon_includes + coupon_excludes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
			'coupon_excludes'  => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// Combinations: match any
		// status_is + status_is_not, all orders.
		$query_args = array(
			'after'         => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'        => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'      => 'hour',
			'match'         => 'any',
			'status_is'     => array(
				$order_status_1,
			),
			'status_is_not' => array(
				$order_status_1,
			),
		);

		$order_permutations     = 72;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR product_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 8 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 8 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 8 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 8 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR coupon_includes.
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'match'           => 'any',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR coupon_excludes.
		$query_args = array(
			'after'           => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'          => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'        => 'hour',
			'match'           => 'any',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_excludes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 60;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// product_includes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 40;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 10 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 10 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR product_includes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 56;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 14 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 14 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR status_is_not OR product_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 56;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 14 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 14 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR status_is_not OR product_includes OR product_excludes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
		);

		$order_permutations     = 60;
		$order_w_coupon_1_perms = 20;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 1 / 5 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 5 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 5 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 5 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR status_is_not OR product_includes OR product_excludes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 64;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 16 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 8 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 3 / 16 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 16 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 8 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 16 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

		// status_is OR status_is_not OR product_includes OR product_excludes OR coupon_includes OR coupon_excludes.
		$query_args = array(
			'after'            => $current_hour->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'before'           => $now->format( WC_Admin_Reports_Interval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
			'coupon_excludes'  => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 68;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 17 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 5 / 34 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 3 / 17 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 17 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 5 / 34 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 17 )
						- $coupons;
		$gross_revenue  = $net_revenue + $shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => $orders_count,
				'num_items_sold'          => $num_items_sold,
				'gross_revenue'           => $gross_revenue,
				'coupons'                 => $coupons,
				'refunds'                 => 0,
				'taxes'                   => 0,
				'shipping'                => $shipping,
				'net_revenue'             => $net_revenue,
				'avg_items_per_order'     => $num_items_sold / $orders_count,
				'avg_order_value'         => $net_revenue / $orders_count,
				'num_returning_customers' => 0,
				'num_new_customers'       => $new_customers,
				'products'                => 4,
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour->format( 'Y-m-d H' ),
					'date_start'     => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour->format( 'Y-m-d H:i:s' ),
					'date_end'       => $now->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $now->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'            => $orders_count,
						'num_items_sold'          => $num_items_sold,
						'gross_revenue'           => $gross_revenue,
						'coupons'                 => $coupons,
						'refunds'                 => 0,
						'taxes'                   => 0,
						'shipping'                => $shipping,
						'net_revenue'             => $net_revenue,
						'avg_items_per_order'     => $num_items_sold / $orders_count,
						'avg_order_value'         => $net_revenue / $orders_count,
						'num_returning_customers' => 0,
						'num_new_customers'       => $new_customers,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $query_args ) ), true ), 'No filters' );

	}

}
