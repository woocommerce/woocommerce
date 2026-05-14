<?php
/**
 * WC_Report_Sales_By_Date
 *
 * @package     WooCommerce\Admin\Reports
 * @version     2.1.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Report_Sales_By_Date
 */
class WC_Report_Sales_By_Date extends WC_Admin_Report {

	/**
	 * Chart colors.
	 *
	 * @var array
	 */
	public $chart_colours = array();

	/**
	 * The report data.
	 *
	 * @var stdClass
	 */
	private $report_data;

	/**
	 * Get report data.
	 *
	 * @return stdClass
	 */
	public function get_report_data() {
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}
		return $this->report_data;
	}

	/**
	 * Get all data needed for this report and store in the class.
	 */
	private function query_report_data() {
		// The legacy SQL-builder (`get_order_report_data`) targets `wp_posts`/`wp_postmeta`.
		// When HPOS is the authoritative datastore and order data is no longer synced
		// to the posts tables, those queries return no rows. Route through the
		// HPOS-aware implementation in that case so the legacy sales reports REST
		// endpoint (`/wc/v3/reports/sales`) keeps working.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->query_report_data_hpos();
			return;
		}

		$this->report_data = new stdClass();

		$this->report_data->order_counts = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'ID'        => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'count',
						'distinct' => true,
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ),
			)
		);

		$this->report_data->coupons = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'order_item_name' => array(
						'type'     => 'order_item',
						'function' => '',
						'name'     => 'order_item_name',
					),
					'discount_amount' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'coupon',
						'function'        => 'SUM',
						'name'            => 'discount_amount',
					),
					'post_date'       => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'where'        => array(
					array(
						'key'      => 'order_items.order_item_type',
						'value'    => 'coupon',
						'operator' => '=',
					),
				),
				'group_by'     => $this->group_by_query . ', order_item_name',
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ),
			)
		);

		// All items from orders - even those refunded.
		$this->report_data->order_items = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'_qty'      => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_count',
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'where'        => array(
					array(
						'key'      => 'order_items.order_item_type',
						'value'    => 'line_item',
						'operator' => '=',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ),
			)
		);

		/**
		 * Get total of fully refunded items.
		 */
		$this->report_data->refunded_order_items = absint(
			$this->get_order_report_data(
				array(
					'data'         => array(
						'_qty' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_count',
						),
					),
					'where'        => array(
						array(
							'key'      => 'order_items.order_item_type',
							'value'    => 'line_item',
							'operator' => '=',
						),
					),
					'query_type'   => 'get_var',
					'filter_range' => true,
					'order_types'  => wc_get_order_types( 'order-count' ),
					'order_status' => array( OrderStatus::REFUNDED ),
				)
			)
		);

		/**
		 * Order totals by date. Charts should show GROSS amounts to avoid going -ve.
		 */
		$this->report_data->orders = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_shipping',
					),
					'_order_tax'          => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_tax',
					),
					'_order_shipping_tax' => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_shipping_tax',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'sales-reports' ),
				'order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ),
			)
		);

		/**
		 * If an order is 100% refunded we should look at the parent's totals, but the refunds dates.
		 * We also need to ensure each parent order's values are only counted/summed once.
		 */
		$this->report_data->full_refunds = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'_order_total'        => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'_order_shipping'     => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_shipping',
					),
					'_order_tax'          => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_tax',
					),
					'_order_shipping_tax' => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_shipping_tax',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'            => 'posts.post_parent',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => array( OrderStatus::REFUNDED ),
			)
		);

		foreach ( $this->report_data->full_refunds as $key => $order ) {
			$total_refund       = is_numeric( $order->total_refund ) ? $order->total_refund : 0;
			$total_shipping     = is_numeric( $order->total_shipping ) ? $order->total_shipping : 0;
			$total_tax          = is_numeric( $order->total_tax ) ? $order->total_tax : 0;
			$total_shipping_tax = is_numeric( $order->total_shipping_tax ) ? $order->total_shipping_tax : 0;

			$this->report_data->full_refunds[ $key ]->net_refund = $total_refund - ( $total_shipping + $total_tax + $total_shipping_tax );
		}

		/**
		 * Partial refunds. This includes line items, shipping and taxes. Not grouped by date.
		 */
		$this->report_data->partial_refunds = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'ID'                  => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'refund_id',
					),
					'_refund_amount'      => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
					'order_item_type'     => array(
						'type'      => 'order_item',
						'function'  => '',
						'name'      => 'item_type',
						'join_type' => 'LEFT',
					),
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping',
						'join_type' => 'LEFT',
					),
					'_order_tax'          => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_tax',
						'join_type' => 'LEFT',
					),
					'_order_shipping_tax' => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping_tax',
						'join_type' => 'LEFT',
					),
					'_qty'                => array(
						'type'      => 'order_item_meta',
						'function'  => 'SUM',
						'name'      => 'order_item_count',
						'join_type' => 'LEFT',
					),
				),
				'group_by'            => 'refund_id',
				'order_by'            => 'post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD ),
			)
		);

		foreach ( $this->report_data->partial_refunds as $key => $order ) {
			$this->report_data->partial_refunds[ $key ]->net_refund = (float) $order->total_refund - ( (float) $order->total_shipping + (float) $order->total_tax + (float) $order->total_shipping_tax );
		}

		/**
		 * Refund lines - all partial refunds on all order types so we can plot full AND partial refunds on the chart.
		 */
		$this->report_data->refund_lines = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'ID'                  => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'refund_id',
					),
					'_refund_amount'      => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
					'order_item_type'     => array(
						'type'      => 'order_item',
						'function'  => '',
						'name'      => 'item_type',
						'join_type' => 'LEFT',
					),
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping',
						'join_type' => 'LEFT',
					),
					'_order_tax'          => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_tax',
						'join_type' => 'LEFT',
					),
					'_order_shipping_tax' => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping_tax',
						'join_type' => 'LEFT',
					),
					'_qty'                => array(
						'type'      => 'order_item_meta',
						'function'  => 'SUM',
						'name'      => 'order_item_count',
						'join_type' => 'LEFT',
					),
				),
				'group_by'            => 'refund_id',
				'order_by'            => 'post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ),
			)
		);

		/**
		 * Total up refunds. Note: when an order is fully refunded, a refund line will be added.
		 */
		$this->report_data->total_tax_refunded          = 0;
		$this->report_data->total_shipping_refunded     = 0;
		$this->report_data->total_shipping_tax_refunded = 0;
		$this->report_data->total_refunds               = 0;

		$this->report_data->refunded_orders = array_merge( $this->report_data->partial_refunds, $this->report_data->full_refunds );

		foreach ( $this->report_data->refunded_orders as $key => $value ) {
			$this->report_data->total_tax_refunded          += floatval( $value->total_tax < 0 ? $value->total_tax * -1 : $value->total_tax );
			$this->report_data->total_refunds               += floatval( $value->total_refund );
			$this->report_data->total_shipping_tax_refunded += floatval( $value->total_shipping_tax < 0 ? $value->total_shipping_tax * -1 : $value->total_shipping_tax );
			$this->report_data->total_shipping_refunded     += floatval( $value->total_shipping < 0 ? $value->total_shipping * -1 : $value->total_shipping );

			// Only applies to partial.
			if ( isset( $value->order_item_count ) ) {
				$this->report_data->refunded_order_items += floatval( $value->order_item_count < 0 ? $value->order_item_count * -1 : $value->order_item_count );
			}
		}

		// Totals from all orders - including those refunded. Subtract refunded amounts.
		$this->report_data->total_tax          = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_tax' ) ) - $this->report_data->total_tax_refunded, 2 );
		$this->report_data->total_shipping     = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping' ) ) - $this->report_data->total_shipping_refunded, 2 );
		$this->report_data->total_shipping_tax = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping_tax' ) ) - $this->report_data->total_shipping_tax_refunded, 2 );

		// Total the refunds and sales amounts. Sales subtract refunds. Note - total_sales also includes shipping costs.
		$this->report_data->total_sales = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_sales' ) ) - $this->report_data->total_refunds, 2 );
		$this->report_data->net_sales   = wc_format_decimal( $this->report_data->total_sales - $this->report_data->total_shipping - max( 0, $this->report_data->total_tax ) - max( 0, $this->report_data->total_shipping_tax ), 2 );

		// Calculate average based on net.
		$this->report_data->average_sales       = wc_format_decimal( $this->report_data->net_sales / ( $this->chart_interval + 1 ), 2 );
		$this->report_data->average_total_sales = wc_format_decimal( $this->report_data->total_sales / ( $this->chart_interval + 1 ), 2 );

		// Total orders and discounts also includes those which have been refunded at some point.
		$this->report_data->total_coupons         = number_format( array_sum( wp_list_pluck( $this->report_data->coupons, 'discount_amount' ) ), 2, '.', '' );
		$this->report_data->total_refunded_orders = absint( count( $this->report_data->full_refunds ) );

		// Total orders in this period, even if refunded.
		$this->report_data->total_orders = absint( array_sum( wp_list_pluck( $this->report_data->order_counts, 'count' ) ) );

		// Item items ordered in this period, even if refunded.
		$this->report_data->total_items = absint( array_sum( wp_list_pluck( $this->report_data->order_items, 'order_item_count' ) ) );

		// 3rd party filtering of report data
		$this->report_data = apply_filters( 'woocommerce_admin_report_data', $this->report_data );
	}

	/**
	 * HPOS-aware implementation of {@see self::query_report_data()}.
	 *
	 * Builds the same `$this->report_data` shape consumed by the legacy charts and
	 * the `wc/v3/reports/sales` REST endpoint, but sources data from `wc_get_orders()`
	 * instead of the legacy `wp_posts`/`wp_postmeta` SQL queries.
	 *
	 * @since 10.9.0
	 *
	 * @return void
	 */
	private function query_report_data_hpos() {
		$this->report_data = new stdClass();

		$count_statuses    = array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED );
		$count_order_types = wc_get_order_types( 'order-count' );
		$sales_order_types = wc_get_order_types( 'sales-reports' );

		$date_after  = gmdate( 'Y-m-d H:i:s', $this->start_date );
		$date_before = gmdate( 'Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date ) - 1 );
		$date_query  = $date_after . '...' . $date_before;

		$grouping = $this->chart_groupby;

		$bucket_for = function ( $order ) use ( $grouping ) {
			$date = $order->get_date_created();
			if ( ! $date ) {
				return null;
			}
			$ts = $date->getOffsetTimestamp();
			return 'day' === $grouping ? gmdate( 'Y-m-d', $ts ) : gmdate( 'Y-m', $ts );
		};

		$post_date_for = function ( $order ) {
			$date = $order->get_date_created();
			return $date ? $date->date( 'Y-m-d H:i:s' ) : '';
		};

		// 1) Counted orders (used for order_counts, order_items, coupons).
		$counted_orders = $this->fetch_orders_for_report(
			array(
				'types'        => $count_order_types,
				'status'       => $count_statuses,
				'date_created' => $date_query,
			)
		);

		// 2) Sales orders (used for total_sales/shipping/tax aggregation).
		// Sales-report order types are typically a subset of order-count types, so reuse where possible.
		if ( $count_order_types === $sales_order_types ) {
			$sales_orders = $counted_orders;
		} else {
			$sales_orders = $this->fetch_orders_for_report(
				array(
					'types'        => $sales_order_types,
					'status'       => $count_statuses,
					'date_created' => $date_query,
				)
			);
		}

		// order_counts: one row per bucket with {count, post_date}.
		$order_counts = array();
		foreach ( $counted_orders as $order ) {
			$bucket = $bucket_for( $order );
			if ( null === $bucket ) {
				continue;
			}
			if ( ! isset( $order_counts[ $bucket ] ) ) {
				$order_counts[ $bucket ] = (object) array(
					'count'     => 0,
					'post_date' => $post_date_for( $order ),
				);
			}
			++$order_counts[ $bucket ]->count;
		}
		ksort( $order_counts );
		$this->report_data->order_counts = array_values( $order_counts );

		// order_items: per-bucket sum of line-item quantities; coupons: per-bucket+code discount totals.
		$order_items_buckets = array();
		$coupon_buckets      = array();
		foreach ( $counted_orders as $order ) {
			$bucket = $bucket_for( $order );
			if ( null === $bucket ) {
				continue;
			}
			$pd = $post_date_for( $order );

			if ( ! isset( $order_items_buckets[ $bucket ] ) ) {
				$order_items_buckets[ $bucket ] = (object) array(
					'order_item_count' => 0,
					'post_date'        => $pd,
				);
			}
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$order_items_buckets[ $bucket ]->order_item_count += (int) $item->get_quantity();
			}

			foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
				$code = $coupon_item->get_name();
				$key  = $bucket . '|' . $code;
				if ( ! isset( $coupon_buckets[ $key ] ) ) {
					$coupon_buckets[ $key ] = (object) array(
						'order_item_name' => $code,
						'discount_amount' => 0.0,
						'post_date'       => $pd,
					);
				}
				if ( $coupon_item instanceof WC_Order_Item_Coupon ) {
						$coupon_buckets[ $key ]->discount_amount += (float) $coupon_item->get_discount();
				}
			}
		}
		ksort( $order_items_buckets );
		$this->report_data->order_items = array_values( $order_items_buckets );
		$this->report_data->coupons     = array_values( $coupon_buckets );

		// orders: per-bucket aggregates of order totals/shipping/tax/shipping-tax.
		$orders_buckets = array();
		foreach ( $sales_orders as $order ) {
			$bucket = $bucket_for( $order );
			if ( null === $bucket ) {
				continue;
			}
			if ( ! isset( $orders_buckets[ $bucket ] ) ) {
				$orders_buckets[ $bucket ] = (object) array(
					'total_sales'        => 0.0,
					'total_shipping'     => 0.0,
					'total_tax'          => 0.0,
					'total_shipping_tax' => 0.0,
					'post_date'          => $post_date_for( $order ),
				);
			}
			$orders_buckets[ $bucket ]->total_sales        += (float) $order->get_total();
			$orders_buckets[ $bucket ]->total_shipping     += (float) $order->get_shipping_total();
			$orders_buckets[ $bucket ]->total_tax          += (float) $order->get_cart_tax();
			$orders_buckets[ $bucket ]->total_shipping_tax += (float) $order->get_shipping_tax();
		}
		ksort( $orders_buckets );
		$this->report_data->orders = array_values( $orders_buckets );

		// Refund-side data: iterate refunds in range and split into full vs. partial.
		// Full refund = refund whose parent's status is 'refunded' (entire order refunded).
		// Partial refund = refund whose parent is still completed/processing/on-hold.
		$refunds = $this->fetch_orders_for_report(
			array(
				'types'        => array( 'shop_order_refund' ),
				'status'       => array(),
				'date_created' => $date_query,
			)
		);

		$full_refunds    = array();
		$partial_refunds = array();
		$refund_lines    = array();
		$seen_parents    = array();

		foreach ( $refunds as $refund ) {
			if ( ! $refund instanceof WC_Order_Refund ) {
				continue;
			}
			$parent_id = (int) $refund->get_parent_id();
			// wc_get_order() is backed by the order cache, so repeated lookups within this loop are cheap.
			$parent = $parent_id ? wc_get_order( $parent_id ) : null;
			if ( ! $parent ) {
				continue;
			}

			$pd            = $post_date_for( $refund );
			$parent_status = $parent->get_status();

			// Refund lines covers partials on COMPLETED|PROCESSING|ON_HOLD|REFUNDED parents.
			if ( in_array( $parent_status, array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD, OrderStatus::REFUNDED ), true ) ) {
				$item_count = 0;
				foreach ( $refund->get_items( 'line_item' ) as $line ) {
					$item_count += (int) $line->get_quantity();
				}

				$refund_line    = (object) array(
					'refund_id'          => $refund->get_id(),
					'total_refund'       => (float) $refund->get_amount(),
					'post_date'          => $pd,
					'item_type'          => $item_count > 0 ? 'line_item' : null,
					'total_sales'        => (float) $parent->get_total(),
					'total_shipping'     => (float) $refund->get_shipping_total(),
					'total_tax'          => (float) $refund->get_cart_tax(),
					'total_shipping_tax' => (float) $refund->get_shipping_tax(),
					'order_item_count'   => $item_count,
				);
				$refund_lines[] = $refund_line;
			}

			if ( OrderStatus::REFUNDED === $parent_status ) {
				if ( isset( $seen_parents[ $parent_id ] ) ) {
					// Only count each fully-refunded parent once.
					continue;
				}
				$seen_parents[ $parent_id ] = true;

				$total_refund       = (float) $parent->get_total();
				$total_shipping     = (float) $parent->get_shipping_total();
				$total_tax          = (float) $parent->get_cart_tax();
				$total_shipping_tax = (float) $parent->get_shipping_tax();
				$full_refunds[]     = (object) array(
					'total_refund'       => $total_refund,
					'total_shipping'     => $total_shipping,
					'total_tax'          => $total_tax,
					'total_shipping_tax' => $total_shipping_tax,
					'post_date'          => $pd,
					'net_refund'         => $total_refund - ( $total_shipping + $total_tax + $total_shipping_tax ),
				);
			} elseif ( in_array( $parent_status, array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::ON_HOLD ), true ) ) {
				$item_count = 0;
				foreach ( $refund->get_items( 'line_item' ) as $line ) {
					$item_count += (int) $line->get_quantity();
				}

				$total_refund       = (float) $refund->get_amount();
				$total_shipping     = (float) $refund->get_shipping_total();
				$total_tax          = (float) $refund->get_cart_tax();
				$total_shipping_tax = (float) $refund->get_shipping_tax();
				$partial            = (object) array(
					'refund_id'          => $refund->get_id(),
					'total_refund'       => $total_refund,
					'post_date'          => $pd,
					'item_type'          => $item_count > 0 ? 'line_item' : null,
					'total_sales'        => (float) $parent->get_total(),
					'total_shipping'     => $total_shipping,
					'total_tax'          => $total_tax,
					'total_shipping_tax' => $total_shipping_tax,
					'order_item_count'   => $item_count,
					'net_refund'         => $total_refund - ( $total_shipping + $total_tax + $total_shipping_tax ),
				);
				$partial_refunds[]  = $partial;
			}
		}

		$this->report_data->full_refunds    = $full_refunds;
		$this->report_data->partial_refunds = $partial_refunds;
		$this->report_data->refund_lines    = $refund_lines;

		// Refunded items aggregate: items in a fully-refunded order plus item counts on partial refunds.
		$this->report_data->refunded_order_items = 0;
		$refunded_only_orders                    = $this->fetch_orders_for_report(
			array(
				'types'        => $count_order_types,
				'status'       => array( OrderStatus::REFUNDED ),
				'date_created' => $date_query,
			)
		);
		foreach ( $refunded_only_orders as $order ) {
			foreach ( $order->get_items( 'line_item' ) as $line ) {
				$this->report_data->refunded_order_items += (int) $line->get_quantity();
			}
		}

		$refunded_orders                                = array_merge( $partial_refunds, $full_refunds );
		$this->report_data->refunded_orders             = $refunded_orders;
		$this->report_data->total_tax_refunded          = 0;
		$this->report_data->total_shipping_refunded     = 0;
		$this->report_data->total_shipping_tax_refunded = 0;
		$this->report_data->total_refunds               = 0;

		foreach ( $refunded_orders as $value ) {
			$this->report_data->total_tax_refunded          += floatval( $value->total_tax < 0 ? $value->total_tax * -1 : $value->total_tax );
			$this->report_data->total_refunds               += floatval( $value->total_refund );
			$this->report_data->total_shipping_tax_refunded += floatval( $value->total_shipping_tax < 0 ? $value->total_shipping_tax * -1 : $value->total_shipping_tax );
			$this->report_data->total_shipping_refunded     += floatval( $value->total_shipping < 0 ? $value->total_shipping * -1 : $value->total_shipping );

			if ( isset( $value->order_item_count ) ) {
				$this->report_data->refunded_order_items += floatval( $value->order_item_count < 0 ? $value->order_item_count * -1 : $value->order_item_count );
			}
		}

		// Aggregate totals (mirrors logic in query_report_data()).
		$sum_tax          = (float) array_sum( wp_list_pluck( $this->report_data->orders, 'total_tax' ) );
		$sum_shipping     = (float) array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping' ) );
		$sum_shipping_tax = (float) array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping_tax' ) );
		$sum_sales        = (float) array_sum( wp_list_pluck( $this->report_data->orders, 'total_sales' ) );

		$this->report_data->total_tax          = wc_format_decimal( $sum_tax - (float) $this->report_data->total_tax_refunded, 2 );
		$this->report_data->total_shipping     = wc_format_decimal( $sum_shipping - (float) $this->report_data->total_shipping_refunded, 2 );
		$this->report_data->total_shipping_tax = wc_format_decimal( $sum_shipping_tax - (float) $this->report_data->total_shipping_tax_refunded, 2 );

		$this->report_data->total_sales = wc_format_decimal( $sum_sales - (float) $this->report_data->total_refunds, 2 );
		$this->report_data->net_sales   = wc_format_decimal(
			(float) $this->report_data->total_sales
			- (float) $this->report_data->total_shipping
			- max( 0.0, (float) $this->report_data->total_tax )
			- max( 0.0, (float) $this->report_data->total_shipping_tax ),
			2
		);

		$interval                               = (int) $this->chart_interval + 1;
		$this->report_data->average_sales       = wc_format_decimal( (float) $this->report_data->net_sales / $interval, 2 );
		$this->report_data->average_total_sales = wc_format_decimal( (float) $this->report_data->total_sales / $interval, 2 );

		$this->report_data->total_coupons         = number_format( array_sum( wp_list_pluck( $this->report_data->coupons, 'discount_amount' ) ), 2, '.', '' );
		$this->report_data->total_refunded_orders = absint( count( $this->report_data->full_refunds ) );

		$this->report_data->total_orders = absint( array_sum( wp_list_pluck( $this->report_data->order_counts, 'count' ) ) );
		$this->report_data->total_items  = absint( array_sum( wp_list_pluck( $this->report_data->order_items, 'order_item_count' ) ) );

		/**
		 * Filters the assembled admin report data.
		 *
		 * Mirrors the filter triggered in {@see self::query_report_data()} so callers can hook
		 * a single filter regardless of whether HPOS or the legacy datastore is authoritative.
		 *
		 * @since 2.6.0
		 *
		 * @param stdClass $report_data The aggregated report data.
		 */
		$this->report_data = apply_filters( 'woocommerce_admin_report_data', $this->report_data );
	}

	/**
	 * Page through `wc_get_orders` to fetch every order matching the supplied filters.
	 *
	 * @since 10.9.0
	 *
	 * @param array $args Filter args. Required keys: `types` (string[]), `status` (string[]), `date_created` (string).
	 * @return WC_Order[]|WC_Abstract_Order[]
	 */
	private function fetch_orders_for_report( array $args ): array {
		$collected = array();
		$page      = 1;
		$per_page  = 200;

		$base_query = array(
			'type'         => $args['types'],
			'limit'        => $per_page,
			'orderby'      => 'date',
			'order'        => 'ASC',
			'date_created' => $args['date_created'],
			'return'       => 'objects',
			'paginate'     => false,
		);

		if ( ! empty( $args['status'] ) ) {
			$base_query['status'] = $args['status'];
		} else {
			$base_query['status'] = 'any';
		}

		do {
			$query         = $base_query;
			$query['page'] = $page;

			$batch = wc_get_orders( $query );
			if ( empty( $batch ) || ! is_array( $batch ) ) {
				break;
			}

			foreach ( $batch as $order ) {
				$collected[] = $order;
			}

			$batch_size = count( $batch );
			++$page;
		} while ( $batch_size === $per_page );

		return $collected;
	}

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @return array
	 */
	public function get_chart_legend() {
		$legend = array();
		$data   = $this->get_report_data();

		switch ( $this->chart_groupby ) {
			case 'day':
				$average_total_sales_title = sprintf(
					/* translators: %s: average total sales */
					__( '%s average gross daily sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
				);
				$average_sales_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average net daily sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_sales ) . '</strong>'
				);
				break;
			case 'month':
			default:
				$average_total_sales_title = sprintf(
					/* translators: %s: average total sales */
					__( '%s average gross monthly sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
				);
				$average_sales_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average net monthly sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_sales ) . '</strong>'
				);
				break;
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total sales */
				__( '%s gross sales in this period', 'woocommerce' ),
				'<strong>' . wc_price( $data->total_sales ) . '</strong>'
			),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and including shipping and taxes.', 'woocommerce' ),
			'color'            => $this->chart_colours['sales_amount'],
			'highlight_series' => 6,
		);
		if ( $data->average_total_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_total_sales_title,
				'color'            => $this->chart_colours['average'],
				'highlight_series' => 2,
			);
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: net sales */
				__( '%s net sales in this period', 'woocommerce' ),
				'<strong>' . wc_price( $data->net_sales ) . '</strong>'
			),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and excluding shipping and taxes.', 'woocommerce' ),
			'color'            => $this->chart_colours['net_sales_amount'],
			'highlight_series' => 7,
		);
		if ( $data->average_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_sales_title,
				'color'            => $this->chart_colours['net_average'],
				'highlight_series' => 3,
			);
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total orders */
				__( '%s orders placed', 'woocommerce' ),
				'<strong>' . $data->total_orders . '</strong>'
			),
			'color'            => $this->chart_colours['order_count'],
			'highlight_series' => 1,
		);

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total items */
				__( '%s items purchased', 'woocommerce' ),
				'<strong>' . $data->total_items . '</strong>'
			),
			'color'            => $this->chart_colours['item_count'],
			'highlight_series' => 0,
		);
		$legend[] = array(
			'title'            => sprintf(
				/* translators: 1: total refunds 2: total refunded orders 3: refunded items */
				_n( '%1$s refunded %2$d order (%3$d item)', '%1$s refunded %2$d orders (%3$d items)', $this->report_data->total_refunded_orders, 'woocommerce' ),
				'<strong>' . wc_price( $data->total_refunds ) . '</strong>',
				$this->report_data->total_refunded_orders,
				$this->report_data->refunded_order_items
			),
			'color'            => $this->chart_colours['refund_amount'],
			'highlight_series' => 8,
		);
		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total shipping */
				__( '%s charged for shipping', 'woocommerce' ),
				'<strong>' . wc_price( $data->total_shipping ) . '</strong>'
			),
			'color'            => $this->chart_colours['shipping_amount'],
			'highlight_series' => 5,
		);
		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total coupons */
				__( '%s worth of coupons used', 'woocommerce' ),
				'<strong>' . wc_price( $data->total_coupons ) . '</strong>'
			),
			'color'            => $this->chart_colours['coupon_amount'],
			'highlight_series' => 4,
		);

		return $legend;
	}

	/**
	 * Output the report.
	 */
	public function output_report() {
		$ranges = array(
			'year'       => __( 'Year', 'woocommerce' ),
			'last_month' => __( 'Last month', 'woocommerce' ),
			'month'      => __( 'This month', 'woocommerce' ),
			'7day'       => __( 'Last 7 days', 'woocommerce' ),
		);

		$this->chart_colours = array(
			'sales_amount'     => '#b1d4ea',
			'net_sales_amount' => '#3498db',
			'average'          => '#b1d4ea',
			'net_average'      => '#3498db',
			'order_count'      => '#dbe1e3',
			'item_count'       => '#ecf0f1',
			'shipping_amount'  => '#5cc488',
			'coupon_amount'    => '#f1c40f',
			'refund_amount'    => '#e74c3c',
		);

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ), true ) ) {
			$current_range = '7day';
		}

		$this->check_current_range_nonce( $current_range );
		$this->calculate_current_range( $current_range );

		include WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php';
	}

	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_attr( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
			data-exclude_series="2"
			data-groupby="<?php echo esc_attr( $this->chart_groupby ); ?>"
		>
			<?php esc_html_e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Round our totals correctly.
	 *
	 * @param array|string $amount Chart total.
	 *
	 * @return array|string
	 */
	private function round_chart_totals( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}

	/**
	 * Get the main chart.
	 */
	public function get_main_chart() {
		global $wp_locale;

		// Prepare data for report.
		$data = array(
			'order_counts'         => $this->prepare_chart_data( $this->report_data->order_counts, 'post_date', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'order_item_counts'    => $this->prepare_chart_data( $this->report_data->order_items, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'order_amounts'        => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'coupon_amounts'       => $this->prepare_chart_data( $this->report_data->coupons, 'post_date', 'discount_amount', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'shipping_amounts'     => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'refund_amounts'       => $this->prepare_chart_data( $this->report_data->refund_lines, 'post_date', 'total_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'net_refund_amounts'   => $this->prepare_chart_data( $this->report_data->refunded_orders, 'post_date', 'net_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'shipping_tax_amounts' => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'tax_amounts'          => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'net_order_amounts'    => array(),
			'gross_order_amounts'  => array(),
		);

		foreach ( $data['order_amounts'] as $order_amount_key => $order_amount_value ) {
			$data['gross_order_amounts'][ $order_amount_key ]     = $order_amount_value;
			$data['gross_order_amounts'][ $order_amount_key ][1] -= $data['refund_amounts'][ $order_amount_key ][1];

			$data['net_order_amounts'][ $order_amount_key ] = $order_amount_value;
			// Subtract the sum of the values from net order amounts.
			$data['net_order_amounts'][ $order_amount_key ][1] -=
				$data['net_refund_amounts'][ $order_amount_key ][1] +
				$data['shipping_amounts'][ $order_amount_key ][1] +
				$data['shipping_tax_amounts'][ $order_amount_key ][1] +
				$data['tax_amounts'][ $order_amount_key ][1];
		}

		// 3rd party filtering of report data.
		$data = apply_filters( 'woocommerce_admin_report_chart_data', $data );

		// Encode in json format.
		$chart_data = wp_json_encode(
			array(
				'order_counts'        => array_values( $data['order_counts'] ),
				'order_item_counts'   => array_values( $data['order_item_counts'] ),
				'order_amounts'       => array_map( array( $this, 'round_chart_totals' ), array_values( $data['order_amounts'] ) ),
				'gross_order_amounts' => array_map( array( $this, 'round_chart_totals' ), array_values( $data['gross_order_amounts'] ) ),
				'net_order_amounts'   => array_map( array( $this, 'round_chart_totals' ), array_values( $data['net_order_amounts'] ) ),
				'shipping_amounts'    => array_map( array( $this, 'round_chart_totals' ), array_values( $data['shipping_amounts'] ) ),
				'coupon_amounts'      => array_map( array( $this, 'round_chart_totals' ), array_values( $data['coupon_amounts'] ) ),
				'refund_amounts'      => array_map( array( $this, 'round_chart_totals' ), array_values( $data['refund_amounts'] ) ),
			)
		);
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( $chart_data ); ?>' ) );
				var drawGraph = function( highlight ) {
					var series = [
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ); ?>",
							data: order_data.order_item_counts,
							color: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>',
							bars: { fillColor: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce' ) ); ?>",
							data: order_data.order_counts,
							color: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>',
							bars: { fillColor: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average gross sales amount', 'woocommerce' ) ); ?>",
							data: [ [ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ] ],
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['average'] ); ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average net sales amount', 'woocommerce' ) ); ?>",
							data: [ [ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ] ],
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['net_average'] ); ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Coupon amount', 'woocommerce' ) ); ?>",
							data: order_data.coupon_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['coupon_amount'] ); ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip();  // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
						},
						{
							label: "<?php echo esc_js( __( 'Shipping amount', 'woocommerce' ) ); ?>",
							data: order_data.shipping_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['shipping_amount'] ); ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
						},
						{
							label: "<?php echo esc_js( __( 'Gross sales amount', 'woocommerce' ) ); ?>",
							data: order_data.gross_order_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['sales_amount'] ); ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
						},
						{
							label: "<?php echo esc_js( __( 'Net sales amount', 'woocommerce' ) ); ?>",
							data: order_data.net_order_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['net_sales_amount'] ); ?>',
							points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 5, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
						},
						{
							label: "<?php echo esc_js( __( 'Refund amount', 'woocommerce' ) ); ?>",
							data: order_data.refund_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['refund_amount'] ); ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
						},
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars ) {
							highlight_series.bars.fillColor = '#9c5d90';
						}

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
							grid: {
								color: '#aaa',
								borderColor: 'transparent',
								borderWidth: 0,
								hoverable: true
							},
							xaxes: [ {
								color: '#aaa',
								position: "bottom",
								tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php echo ( 'day' === $this->chart_groupby ) ? '%d %b' : '%b'; ?>",
								monthNames: JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( array_values( $wp_locale->month_abbrev ) ) ); ?>' ) ),
								tickLength: 1,
								minTickSize: [1, "<?php echo esc_js( $this->chart_groupby ); ?>"],
								font: {
									color: "#aaa"
								}
							} ],
							yaxes: [
								{
									min: 0,
									minTickSize: 1,
									tickDecimals: 0,
									color: '#d4d9dc',
									font: { color: "#aaa" }
								},
								{
									position: "right",
									min: 0,
									tickDecimals: 2,
									alignTicksWithAxis: 1,
									color: 'transparent',
									font: { color: "#aaa" }
								}
							],
						}
					);

					jQuery('.chart-placeholder').trigger( 'resize' );
				}

				drawGraph();

				jQuery('.highlight_series').on( 'mouseenter',
					function() {
						drawGraph( jQuery(this).data('series') );
					} ).on( 'mouseleave',
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}
}
