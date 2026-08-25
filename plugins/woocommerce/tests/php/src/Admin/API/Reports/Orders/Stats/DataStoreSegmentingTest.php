<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use DateTime;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Tests for product segmentation in the Orders Stats DataStore.
 *
 * Migrated from the legacy WC_Admin_Tests_Reports_Orders_Stats class.
 */
class DataStoreSegmentingTest extends OrdersStatsTestCase {

	/**
	 * @testdox Segmenting by product id reports per-product subtotals in totals and intervals.
	 */
	public function test_segmenting_by_product_and_variation(): void {
		// Simple product.
		$product_1_price = 25;
		$product_1       = new WC_Product_Simple();
		$product_1->set_name( 'Simple Product' );
		$product_1->set_regular_price( $product_1_price );
		$product_1->save();

		// Variable product.
		$product_2 = new WC_Product_Variable();
		$product_2->set_name( 'Variable Product' );
		$product_2->save();

		$child_1 = new WC_Product_Variation();
		$child_1->set_parent_id( $product_2->get_id() );
		$child_1->set_regular_price( 23 );
		$child_1->save();

		$child_2 = new WC_Product_Variation();
		$child_2->set_parent_id( $product_2->get_id() );
		$child_2->set_regular_price( 27 );
		$child_2->save();

		$product_2->set_children( array( $child_1->get_id(), $child_2->get_id() ) );

		$child_1->set_stock_status( ProductStockStatus::IN_STOCK );
		$child_1->save();
		$child_2->set_stock_status( ProductStockStatus::IN_STOCK );
		$child_2->save();
		WC_Product_Variable::sync( $product_2 );

		// Simple product, not used.
		$product_3 = new WC_Product_Simple();
		$product_3->set_name( 'Simple Product not used' );
		$product_3->set_regular_price( 17 );
		$product_3->save();

		$order_status = OrderStatus::COMPLETED;

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$order_1_time = time();
		$order_3_time = $order_1_time - 1 * HOUR_IN_SECONDS;

		// Order 3: 4 x product 1, done one hour earlier.
		$order_3 = WC_Helper_Order::create_order( $customer_1->get_id(), $product_1 );
		$order_3->set_date_created( $order_3_time );
		$order_3->set_date_paid( $order_3_time );
		$order_3->set_status( $order_status );
		$order_3->calculate_totals();
		$order_3->save();

		// Order 1: 4 x product 1 & 3 x product 2-child 1.
		$order_1 = WC_Helper_Order::create_order( $customer_1->get_id(), $product_1 );
		$item    = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $product_2->get_id(),
				'variation_id' => $child_1->get_id(),
				'quantity'     => 3,
				'subtotal'     => 3 * floatval( $child_1->get_price() ),
				'total'        => 3 * floatval( $child_1->get_price() ),
			)
		);
		$item->save();
		$order_1->add_item( $item );
		$order_1->set_status( $order_status );
		$order_1->calculate_totals();
		$order_1->save();

		// Order 2: 4 x product 2-child 1 & 1 x product 2-child 2.
		$order_2 = WC_Helper_Order::create_order( $customer_1->get_id(), $child_1 );
		$item    = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $product_2->get_id(),
				'variation_id' => $child_2->get_id(),
				'quantity'     => 1,
				'subtotal'     => floatval( $child_2->get_price() ),
				'total'        => floatval( $child_2->get_price() ),
			)
		);
		$item->save();
		$order_2->add_item( $item );
		$order_2->set_status( $order_status );
		$order_2->calculate_totals();
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Three hourly intervals: the partial hour two hours back, one full hour, and the partial current hour.
		$now = new DateTime();

		$two_hours_back     = new DateTime();
		$i1_start_timestamp = $order_1_time - 2 * HOUR_IN_SECONDS;
		$two_hours_back->setTimestamp( $i1_start_timestamp );
		$i1_end_timestamp = $i1_start_timestamp + ( 3600 - ( $i1_start_timestamp % 3600 ) ) - 1;
		$i1_start         = new DateTime();
		$i1_start->setTimestamp( $i1_start_timestamp );
		$i1_end = new DateTime();
		$i1_end->setTimestamp( $i1_end_timestamp );

		$i2_start_timestamp = $i1_end_timestamp + 1;
		$i2_end_timestamp   = $i1_end_timestamp + 3600;
		$i2_start           = new DateTime();
		$i2_start->setTimestamp( $i2_start_timestamp );
		$i2_end = new DateTime();
		$i2_end->setTimestamp( $i2_end_timestamp );

		$i3_start_timestamp = $i2_end_timestamp + 1;
		$i3_end_timestamp   = (int) $now->format( 'U' );
		$i3_start           = new DateTime();
		$i3_start->setTimestamp( $i3_start_timestamp );
		$i3_end = new DateTime();
		$i3_end->setTimestamp( $i3_end_timestamp );

		$query_args = array(
			'after'            => $two_hours_back->format( TimeInterval::$sql_datetime_format ),
			'before'           => $now->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'segmentby'        => 'product',
			'product_includes' => array( $product_1->get_id(), $product_2->get_id(), $product_3->get_id() ),
		);

		$shipping_amnt  = 10;
		$o1_net_revenue = 4 * $product_1_price + 3 * intval( $child_1->get_price() );
		$o2_net_revenue = 4 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );
		$o3_net_revenue = 4 * $product_1_price;
		$o1_num_items   = 4 + 3;
		$o2_num_items   = 4 + 1;
		$o3_num_items   = 4;

		// Totals.
		$orders_count    = 3;
		$num_items_sold  = 7 + 5 + 4;
		$shipping        = $orders_count * $shipping_amnt;
		$net_revenue     = $o1_net_revenue + $o2_net_revenue + $o3_net_revenue;
		$total_customers = 1;

		// Totals segments.
		$p1_orders_count = 2;
		$p1_shipping     = round( $shipping_amnt / $o1_num_items * 4, 6 ) + round( $shipping_amnt / $o3_num_items * 4, 6 );
		$p1_net_revenue  = 8 * $product_1_price;

		$p2_orders_count = 2;
		$p2_shipping     = round( $shipping_amnt / $o1_num_items * 3, 6 ) + $shipping_amnt;
		$p2_net_revenue  = 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );

		// Interval 3 subtotals and segments.
		$i3_tot_orders_count   = 2;
		$i3_tot_num_items_sold = 4 + 3 + 4 + 1;
		$i3_tot_shipping       = $i3_tot_orders_count * $shipping_amnt;
		$i3_tot_net_revenue    = 4 * $product_1_price + 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );

		$i3_p1_shipping    = round( $shipping_amnt / $o1_num_items * 4, 6 );
		$i3_p1_net_revenue = 4 * $product_1_price;

		$i3_p2_orders_count = 2;
		$i3_p2_shipping     = round( $shipping_amnt / $o1_num_items * 3, 6 ) + $shipping_amnt;
		$i3_p2_net_revenue  = 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );

		// Interval 2 subtotals and segments.
		$i2_tot_orders_count = 1;
		$i2_tot_net_revenue  = 4 * $product_1_price;

		$expected_stats = array(
			'totals'    => $this->expected_totals(
				array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $net_revenue + $shipping,
					'gross_sales'         => $net_revenue,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
					'avg_order_value'     => $net_revenue / $orders_count,
					'total_customers'     => $total_customers,
					'products'            => 2,
					'segments'            => array(
						$this->build_segment(
							$product_1,
							array(
								'orders_count'        => $p1_orders_count,
								'num_items_sold'      => 8,
								'total_sales'         => $p1_net_revenue + $p1_shipping,
								'shipping'            => $p1_shipping,
								'net_revenue'         => $p1_net_revenue,
								'avg_items_per_order' => ( $o1_num_items + $o3_num_items ) / $p1_orders_count,
								'avg_order_value'     => ( $o1_net_revenue + $o3_net_revenue ) / $p1_orders_count,
								'total_customers'     => $total_customers,
							)
						),
						$this->build_segment(
							$product_2,
							array(
								'orders_count'        => $p2_orders_count,
								'num_items_sold'      => 8,
								'total_sales'         => $p2_net_revenue + $p2_shipping,
								'shipping'            => $p2_shipping,
								'net_revenue'         => $p2_net_revenue,
								'avg_items_per_order' => ( $o1_num_items + $o2_num_items ) / $p2_orders_count,
								'avg_order_value'     => ( $o1_net_revenue + $o2_net_revenue ) / $p2_orders_count,
								'total_customers'     => $total_customers,
							)
						),
						$this->build_segment( $product_3, array() ),
					),
				)
			),
			'intervals' => array(
				array(
					'interval'       => $i3_start->format( 'Y-m-d H' ),
					'date_start'     => $i3_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i3_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i3_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i3_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'orders_count'        => $i3_tot_orders_count,
							'num_items_sold'      => $i3_tot_num_items_sold,
							'total_sales'         => $i3_tot_net_revenue + $i3_tot_shipping,
							'gross_sales'         => $i3_tot_net_revenue,
							'shipping'            => $i3_tot_shipping,
							'net_revenue'         => $i3_tot_net_revenue,
							'avg_items_per_order' => $i3_tot_num_items_sold / $i3_tot_orders_count,
							'avg_order_value'     => $i3_tot_net_revenue / $i3_tot_orders_count,
							'total_customers'     => $total_customers,
							'segments'            => array(
								$this->build_segment(
									$product_1,
									array(
										'orders_count'    => 1,
										'num_items_sold'  => 4,
										'total_sales'     => $i3_p1_net_revenue + $i3_p1_shipping,
										'shipping'        => $i3_p1_shipping,
										'net_revenue'     => $i3_p1_net_revenue,
										'avg_items_per_order' => $o1_num_items,
										'avg_order_value' => $o1_net_revenue,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment(
									$product_2,
									array(
										'orders_count'    => $i3_p2_orders_count,
										'num_items_sold'  => 8,
										'total_sales'     => $i3_p2_net_revenue + $i3_p2_shipping,
										'shipping'        => $i3_p2_shipping,
										'net_revenue'     => $i3_p2_net_revenue,
										'avg_items_per_order' => ( $o1_num_items + $o2_num_items ) / $i3_p2_orders_count,
										'avg_order_value' => ( $o1_net_revenue + $o2_net_revenue ) / $i3_p2_orders_count,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment( $product_3, array() ),
							),
						)
					),
				),
				array(
					'interval'       => $i2_start->format( 'Y-m-d H' ),
					'date_start'     => $i2_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i2_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i2_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i2_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'orders_count'        => $i2_tot_orders_count,
							'num_items_sold'      => 4,
							'total_sales'         => $i2_tot_net_revenue + $shipping_amnt,
							'gross_sales'         => $i2_tot_net_revenue,
							'shipping'            => $shipping_amnt,
							'net_revenue'         => $i2_tot_net_revenue,
							'avg_items_per_order' => 4,
							'avg_order_value'     => $i2_tot_net_revenue,
							'total_customers'     => $total_customers,
							'segments'            => array(
								$this->build_segment(
									$product_1,
									array(
										'orders_count'    => 1,
										'num_items_sold'  => 4,
										'total_sales'     => 4 * $product_1_price + $shipping_amnt,
										'shipping'        => $shipping_amnt,
										'net_revenue'     => 4 * $product_1_price,
										'avg_items_per_order' => $o3_num_items,
										'avg_order_value' => $o3_net_revenue,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment( $product_2, array() ),
								$this->build_segment( $product_3, array() ),
							),
						)
					),
				),
				array(
					'interval'       => $i1_start->format( 'Y-m-d H' ),
					'date_start'     => $i1_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i1_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i1_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i1_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'segments' => array(
								$this->build_segment( $product_1, array() ),
								$this->build_segment( $product_2, array() ),
								$this->build_segment( $product_3, array() ),
							),
						)
					),
				),
			),
			'total'     => 3,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );

		$this->assertEquals( $expected_stats, $actual, 'Unexpected result for segmenting by product' );
	}

	/**
	 * @testdox Product segmentation without product_includes enumerates the whole catalog.
	 */
	public function test_segmenting_by_product_without_includes(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$sold_product = new WC_Product_Simple();
		$sold_product->set_name( 'Segmented Sold Product' );
		$sold_product->set_regular_price( 10 );
		$sold_product->save();

		$unsold_product = new WC_Product_Simple();
		$unsold_product->set_name( 'Segmented Unsold Product' );
		$unsold_product->set_regular_price( 15 );
		$unsold_product->save();

		$order = WC_Helper_Order::create_order( 1, $sold_product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		// Without product_includes the segmenter must return a segment for
		// every store product, not only the ones with orders.
		$data = json_decode(
			wp_json_encode(
				$data_store->get_data(
					array(
						'after'     => $start_time,
						'before'    => $end_time,
						'segmentby' => 'product',
					)
				)
			),
			true
		);

		$segments             = array_column( $data['totals']['segments'], 'subtotals', 'segment_id' );
		$expected_product_ids = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => -1,
			)
		);

		$this->assertEqualsCanonicalizing( $expected_product_ids, array_keys( $segments ) );
		$this->assertEquals( 1, $segments[ $sold_product->get_id() ]['orders_count'] );
		$this->assertEquals( 4, $segments[ $sold_product->get_id() ]['num_items_sold'] );
		$this->assertEquals( 0, $segments[ $unsold_product->get_id() ]['orders_count'] );
		$this->assertEquals( 0, $segments[ $unsold_product->get_id() ]['num_items_sold'] );
	}

	/**
	 * Build one expected segment entry, with zero values unless overridden.
	 *
	 * @param WC_Product $product   The product the segment belongs to.
	 * @param array      $subtotals Non-zero subtotal values.
	 * @return array
	 */
	private function build_segment( WC_Product $product, array $subtotals ): array {
		return array(
			'segment_id'    => $product->get_id(),
			'segment_label' => $product->get_name(),
			'subtotals'     => array_merge(
				array(
					'orders_count'        => 0,
					'num_items_sold'      => 0,
					'total_sales'         => 0,
					'coupons'             => 0,
					'coupons_count'       => 0,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => 0,
					'net_revenue'         => 0,
					'avg_items_per_order' => 0,
					'avg_order_value'     => 0,
					'total_customers'     => 0,
				),
				$subtotals
			),
		);
	}
}
