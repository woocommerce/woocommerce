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
 */
class DataStoreSegmentingTest extends OrdersStatsTestCase {

	/**
	 * @testdox Segmenting by product id reports per-product subtotals in totals and intervals.
	 */
	public function test_segmenting_by_product_and_variation(): void {
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$simple_product_price = 25;
		$simple_product       = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( $simple_product_price );
		$simple_product->save();

		// Variable product.
		$variable_product = new WC_Product_Variable();
		$variable_product->set_name( 'Variable Product' );
		$variable_product->save();

		$variation_1 = new WC_Product_Variation();
		$variation_1->set_parent_id( $variable_product->get_id() );
		$variation_1->set_regular_price( 23 );
		$variation_1->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_parent_id( $variable_product->get_id() );
		$variation_2->set_regular_price( 27 );
		$variation_2->save();

		$variable_product->set_children( array( $variation_1->get_id(), $variation_2->get_id() ) );

		$variation_1->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation_1->save();
		$variation_2->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation_2->save();
		WC_Product_Variable::sync( $variable_product );

		// A product no order contains, so its segments stay empty.
		$unused_product = new WC_Product_Simple();
		$unused_product->set_name( 'Simple Product not used' );
		$unused_product->set_regular_price( 17 );
		$unused_product->save();

		$order_status = OrderStatus::COMPLETED;

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$latest_orders_time = time();
		$earlier_order_time = $latest_orders_time - 1 * HOUR_IN_SECONDS;

		// The earlier order: 4 x the simple product, one hour before the others.
		$earlier_order = WC_Helper_Order::create_order( $customer_1->get_id(), $simple_product );
		$earlier_order->set_date_created( $earlier_order_time );
		$earlier_order->set_date_paid( $earlier_order_time );
		$earlier_order->set_status( $order_status );
		$earlier_order->calculate_totals();
		$earlier_order->save();

		// The mixed order: 4 x the simple product & 3 x variation 1.
		$mixed_order = WC_Helper_Order::create_order( $customer_1->get_id(), $simple_product );
		$mixed_order->set_date_created( $latest_orders_time );
		$mixed_order->set_date_paid( $latest_orders_time );
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $variable_product->get_id(),
				'variation_id' => $variation_1->get_id(),
				'quantity'     => 3,
				'subtotal'     => 3 * floatval( $variation_1->get_price() ),
				'total'        => 3 * floatval( $variation_1->get_price() ),
			)
		);
		$item->save();
		$mixed_order->add_item( $item );
		$mixed_order->set_status( $order_status );
		$mixed_order->calculate_totals();
		$mixed_order->save();

		// The variations order: 4 x variation 1 & 1 x variation 2.
		$variations_order = WC_Helper_Order::create_order( $customer_1->get_id(), $variation_1 );
		$variations_order->set_date_created( $latest_orders_time );
		$variations_order->set_date_paid( $latest_orders_time );
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $variable_product->get_id(),
				'variation_id' => $variation_2->get_id(),
				'quantity'     => 1,
				'subtotal'     => floatval( $variation_2->get_price() ),
				'total'        => floatval( $variation_2->get_price() ),
			)
		);
		$item->save();
		$variations_order->add_item( $item );
		$variations_order->set_status( $order_status );
		$variations_order->calculate_totals();
		$variations_order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Three hourly intervals ending at the captured order time; reading the clock here
		// could cross an hour boundary and add a fourth.
		$now = new DateTime();
		$now->setTimestamp( $latest_orders_time );

		$two_hours_back                  = new DateTime();
		$oldest_interval_start_timestamp = $latest_orders_time - 2 * HOUR_IN_SECONDS;
		$two_hours_back->setTimestamp( $oldest_interval_start_timestamp );
		$oldest_interval_end_timestamp = $oldest_interval_start_timestamp + ( 3600 - ( $oldest_interval_start_timestamp % 3600 ) ) - 1;
		$oldest_interval_start         = new DateTime();
		$oldest_interval_start->setTimestamp( $oldest_interval_start_timestamp );
		$oldest_interval_end = new DateTime();
		$oldest_interval_end->setTimestamp( $oldest_interval_end_timestamp );

		$middle_interval_start_timestamp = $oldest_interval_end_timestamp + 1;
		$middle_interval_end_timestamp   = $oldest_interval_end_timestamp + 3600;
		$middle_interval_start           = new DateTime();
		$middle_interval_start->setTimestamp( $middle_interval_start_timestamp );
		$middle_interval_end = new DateTime();
		$middle_interval_end->setTimestamp( $middle_interval_end_timestamp );

		$latest_interval_start_timestamp = $middle_interval_end_timestamp + 1;
		$latest_interval_end_timestamp   = (int) $now->format( 'U' );
		$latest_interval_start           = new DateTime();
		$latest_interval_start->setTimestamp( $latest_interval_start_timestamp );
		$latest_interval_end = new DateTime();
		$latest_interval_end->setTimestamp( $latest_interval_end_timestamp );

		$query_args = array(
			'after'            => $two_hours_back->format( TimeInterval::$sql_datetime_format ),
			'before'           => $now->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'segmentby'        => 'product',
			'product_includes' => array( $simple_product->get_id(), $variable_product->get_id(), $unused_product->get_id() ),
		);

		$mixed_order_revenue      = self::QTY_PER_PRODUCT * $simple_product_price + 3 * intval( $variation_1->get_price() );
		$variations_order_revenue = self::QTY_PER_PRODUCT * intval( $variation_1->get_price() ) + 1 * intval( $variation_2->get_price() );
		$earlier_order_revenue    = self::QTY_PER_PRODUCT * $simple_product_price;
		$mixed_order_items        = self::QTY_PER_PRODUCT + 3;
		$variations_order_items   = self::QTY_PER_PRODUCT + 1;
		$earlier_order_items      = self::QTY_PER_PRODUCT;

		// Totals.
		$orders_count    = 3;
		$num_items_sold  = $mixed_order_items + $variations_order_items + $earlier_order_items;
		$shipping        = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue     = $mixed_order_revenue + $variations_order_revenue + $earlier_order_revenue;
		$total_customers = 1;

		// Totals segments. The segmenter allocates shipping per item and rounds to 6 decimals.
		$simple_product_orders   = 2;
		$simple_product_shipping = round( self::SHIPPING_AMOUNT / $mixed_order_items * self::QTY_PER_PRODUCT, 6 ) + round( self::SHIPPING_AMOUNT / $earlier_order_items * self::QTY_PER_PRODUCT, 6 );
		$simple_product_revenue  = 2 * self::QTY_PER_PRODUCT * $simple_product_price;

		$variable_product_orders   = 2;
		$variable_product_shipping = round( self::SHIPPING_AMOUNT / $mixed_order_items * 3, 6 ) + self::SHIPPING_AMOUNT;
		$variable_product_revenue  = 7 * intval( $variation_1->get_price() ) + 1 * intval( $variation_2->get_price() );

		// Interval 3 subtotals and segments.
		$latest_interval_orders_count = 2;
		$latest_interval_items        = $mixed_order_items + $variations_order_items;
		$latest_interval_shipping     = $latest_interval_orders_count * self::SHIPPING_AMOUNT;
		$latest_interval_revenue      = self::QTY_PER_PRODUCT * $simple_product_price + 7 * intval( $variation_1->get_price() ) + 1 * intval( $variation_2->get_price() );

		$latest_interval_simple_shipping = round( self::SHIPPING_AMOUNT / $mixed_order_items * self::QTY_PER_PRODUCT, 6 );
		$latest_interval_simple_revenue  = self::QTY_PER_PRODUCT * $simple_product_price;

		$latest_interval_variable_orders   = 2;
		$latest_interval_variable_shipping = round( self::SHIPPING_AMOUNT / $mixed_order_items * 3, 6 ) + self::SHIPPING_AMOUNT;
		$latest_interval_variable_revenue  = 7 * intval( $variation_1->get_price() ) + 1 * intval( $variation_2->get_price() );

		// Interval 2 subtotals and segments.
		$middle_interval_orders_count = 1;
		$middle_interval_revenue      = self::QTY_PER_PRODUCT * $simple_product_price;

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
							$simple_product,
							array(
								'orders_count'        => $simple_product_orders,
								'num_items_sold'      => 8,
								'total_sales'         => $simple_product_revenue + $simple_product_shipping,
								'shipping'            => $simple_product_shipping,
								'net_revenue'         => $simple_product_revenue,
								'avg_items_per_order' => ( $mixed_order_items + $earlier_order_items ) / $simple_product_orders,
								'avg_order_value'     => ( $mixed_order_revenue + $earlier_order_revenue ) / $simple_product_orders,
								'total_customers'     => $total_customers,
							)
						),
						$this->build_segment(
							$variable_product,
							array(
								'orders_count'        => $variable_product_orders,
								'num_items_sold'      => 8,
								'total_sales'         => $variable_product_revenue + $variable_product_shipping,
								'shipping'            => $variable_product_shipping,
								'net_revenue'         => $variable_product_revenue,
								'avg_items_per_order' => ( $mixed_order_items + $variations_order_items ) / $variable_product_orders,
								'avg_order_value'     => ( $mixed_order_revenue + $variations_order_revenue ) / $variable_product_orders,
								'total_customers'     => $total_customers,
							)
						),
						$this->build_segment( $unused_product, array() ),
					),
				)
			),
			'intervals' => array(
				array(
					'interval'       => $latest_interval_start->format( 'Y-m-d H' ),
					'date_start'     => $latest_interval_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $latest_interval_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $latest_interval_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $latest_interval_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'orders_count'        => $latest_interval_orders_count,
							'num_items_sold'      => $latest_interval_items,
							'total_sales'         => $latest_interval_revenue + $latest_interval_shipping,
							'gross_sales'         => $latest_interval_revenue,
							'shipping'            => $latest_interval_shipping,
							'net_revenue'         => $latest_interval_revenue,
							'avg_items_per_order' => $latest_interval_items / $latest_interval_orders_count,
							'avg_order_value'     => $latest_interval_revenue / $latest_interval_orders_count,
							'total_customers'     => $total_customers,
							'segments'            => array(
								$this->build_segment(
									$simple_product,
									array(
										'orders_count'    => 1,
										'num_items_sold'  => 4,
										'total_sales'     => $latest_interval_simple_revenue + $latest_interval_simple_shipping,
										'shipping'        => $latest_interval_simple_shipping,
										'net_revenue'     => $latest_interval_simple_revenue,
										'avg_items_per_order' => $mixed_order_items,
										'avg_order_value' => $mixed_order_revenue,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment(
									$variable_product,
									array(
										'orders_count'    => $latest_interval_variable_orders,
										'num_items_sold'  => 8,
										'total_sales'     => $latest_interval_variable_revenue + $latest_interval_variable_shipping,
										'shipping'        => $latest_interval_variable_shipping,
										'net_revenue'     => $latest_interval_variable_revenue,
										'avg_items_per_order' => ( $mixed_order_items + $variations_order_items ) / $latest_interval_variable_orders,
										'avg_order_value' => ( $mixed_order_revenue + $variations_order_revenue ) / $latest_interval_variable_orders,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment( $unused_product, array() ),
							),
						)
					),
				),
				array(
					'interval'       => $middle_interval_start->format( 'Y-m-d H' ),
					'date_start'     => $middle_interval_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $middle_interval_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $middle_interval_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $middle_interval_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'orders_count'        => $middle_interval_orders_count,
							'num_items_sold'      => 4,
							'total_sales'         => $middle_interval_revenue + self::SHIPPING_AMOUNT,
							'gross_sales'         => $middle_interval_revenue,
							'shipping'            => self::SHIPPING_AMOUNT,
							'net_revenue'         => $middle_interval_revenue,
							'avg_items_per_order' => 4,
							'avg_order_value'     => $middle_interval_revenue,
							'total_customers'     => $total_customers,
							'segments'            => array(
								$this->build_segment(
									$simple_product,
									array(
										'orders_count'    => 1,
										'num_items_sold'  => 4,
										'total_sales'     => self::QTY_PER_PRODUCT * $simple_product_price + self::SHIPPING_AMOUNT,
										'shipping'        => self::SHIPPING_AMOUNT,
										'net_revenue'     => self::QTY_PER_PRODUCT * $simple_product_price,
										'avg_items_per_order' => $earlier_order_items,
										'avg_order_value' => $earlier_order_revenue,
										'total_customers' => $total_customers,
									)
								),
								$this->build_segment( $variable_product, array() ),
								$this->build_segment( $unused_product, array() ),
							),
						)
					),
				),
				array(
					'interval'       => $oldest_interval_start->format( 'Y-m-d H' ),
					'date_start'     => $oldest_interval_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $oldest_interval_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $oldest_interval_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $oldest_interval_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $this->expected_totals(
						array(
							'segments' => array(
								$this->build_segment( $simple_product, array() ),
								$this->build_segment( $variable_product, array() ),
								$this->build_segment( $unused_product, array() ),
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
