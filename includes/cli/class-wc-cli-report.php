<?php

/**
 * Show Reports.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Report extends WC_CLI_Command {

	/**
	 * List reports.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc report list
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $__, $assoc_args ) {
		$reports   = array( 'sales', 'sales/top_sellers' );
		$formatter = $this->get_formatter(
			array_merge(
				array( 'fields' => array_keys( $reports ) ),
				$assoc_args
			)
		);

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', $reports );
		} else {
			$formatter->display_item( $reports );
		}
	}

	/**
	 * View sales report.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole report fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the report's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * [--period=<period>]
	 * : The supported periods are: week, month, last_month, and year. If invalid
	 * period is supplied, week is used. If period is not specified, the current
	 * day is used.
	 *
	 * [--date_min]
	 * : Return sales for a specific start date. The date need to be in the YYYY-MM-AA format.
	 *
	 * [--date_max]
	 * : Return sales for a specific end date. The dates need to be in the YYYY-MM-AA format.
	 *
	 * [--limit]
	 * : Limit report result. Default: 12.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for get command:
	 *
	 * * total_sales
	 * * average_sales
	 * * total_orders
	 * * total_items
	 * * total_tax
	 * * total_shipping
	 * * total_discount
	 * * totals_grouped_by
	 * * totals
	 * * total_customers
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc report sales
	 *
	 *     wp wc report sales --period=last_month
	 *
	 * @since 2.5.0
	 */
	public function sales( $__, $assoc_args ) {
		$reporter = $this->get_reporter( $assoc_args );

		// new customers
		$users_query = new WP_User_Query(
			array(
				'fields' => array( 'user_registered' ),
				'role'   => 'customer',
			)
		);

		$customers = $users_query->get_results();

		foreach ( $customers as $key => $customer ) {
			if ( strtotime( $customer->user_registered ) < $reporter->start_date || strtotime( $customer->user_registered ) > $reporter->end_date ) {
				unset( $customers[ $key ] );
			}
		}

		$total_customers = count( $customers );
		$report_data     = $reporter->get_report_data();
		$period_totals   = array();

		// setup period totals by ensuring each period in the interval has data
		for ( $i = 0; $i <= $reporter->chart_interval; $i ++ ) {

			switch ( $reporter->chart_groupby ) {
				case 'day' :
					$time = date( 'Y-m-d', strtotime( "+{$i} DAY", $reporter->start_date ) );
					break;
				default :
					$time = date( 'Y-m', strtotime( "+{$i} MONTH", $reporter->start_date ) );
					break;
			}

			// set the customer signups for each period
			$customer_count = 0;
			foreach ( $customers as $customer ) {
				if ( date( ( 'day' == $reporter->chart_groupby ) ? 'Y-m-d' : 'Y-m', strtotime( $customer->user_registered ) ) == $time ) {
					$customer_count++;
				}
 			}

			$period_totals[ $time ] = array(
				'sales'     => wc_format_decimal( 0.00, 2 ),
				'orders'    => 0,
				'items'     => 0,
				'tax'       => wc_format_decimal( 0.00, 2 ),
				'shipping'  => wc_format_decimal( 0.00, 2 ),
				'discount'  => wc_format_decimal( 0.00, 2 ),
				'customers' => $customer_count,
			);
		}

		// add total sales, total order count, total tax and total shipping for each period
		foreach ( $report_data->orders as $order ) {
			$time = ( 'day' === $reporter->chart_groupby ) ? date( 'Y-m-d', strtotime( $order->post_date ) ) : date( 'Y-m', strtotime( $order->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['sales']    = wc_format_decimal( $order->total_sales, 2 );
			$period_totals[ $time ]['tax']      = wc_format_decimal( $order->total_tax + $order->total_shipping_tax, 2 );
			$period_totals[ $time ]['shipping'] = wc_format_decimal( $order->total_shipping, 2 );
		}

		foreach ( $report_data->order_counts as $order ) {
			$time = ( 'day' === $reporter->chart_groupby ) ? date( 'Y-m-d', strtotime( $order->post_date ) ) : date( 'Y-m', strtotime( $order->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['orders']   = (int) $order->count;
		}

		// add total order items for each period
		foreach ( $report_data->order_items as $order_item ) {
			$time = ( 'day' === $reporter->chart_groupby ) ? date( 'Y-m-d', strtotime( $order_item->post_date ) ) : date( 'Y-m', strtotime( $order_item->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['items'] = (int) $order_item->order_item_count;
		}

		// add total discount for each period
		foreach ( $report_data->coupons as $discount ) {
			$time = ( 'day' === $reporter->chart_groupby ) ? date( 'Y-m-d', strtotime( $discount->post_date ) ) : date( 'Y-m', strtotime( $discount->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['discount'] = wc_format_decimal( $discount->discount_amount, 2 );
		}

		$sales_data  = array(
			'total_sales'       => $report_data->total_sales,
			'net_sales'         => $report_data->net_sales,
			'average_sales'     => $report_data->average_sales,
			'total_orders'      => $report_data->total_orders,
			'total_items'       => $report_data->total_items,
			'total_tax'         => wc_format_decimal( $report_data->total_tax + $report_data->total_shipping_tax, 2 ),
			'total_shipping'    => $report_data->total_shipping,
			'total_refunds'     => $report_data->total_refunds,
			'total_discount'    => $report_data->total_coupons,
			'totals_grouped_by' => $reporter->chart_groupby,
			'totals'            => $period_totals,
			'total_customers'   => $total_customers,
		);

		$sales_data = apply_filters( 'woocommerce_cli_sales_report', $sales_data );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $sales_data );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $sales_data );
	}

	/**
	 * View report of top sellers.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter report based on report property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each seller.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific report fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * [--period=<period>]
	 * : The supported periods are: week, month, last_month, and year. If invalid
	 * period is supplied, week is used. If period is not specified, the current
	 * day is used.
	 *
	 * [--date_min]
	 * : Return sales for a specific start date. The date need to be in the YYYY-MM-AA format.
	 *
	 * [--date_max]
	 * : Return sales for a specific end date. The dates need to be in the YYYY-MM-AA format.
	 *
	 * [--limit]
	 * : Limit report result. Default: 12.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each row:
	 *
	 * * title
	 * * product_id
	 * * quantity
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc report top_sellers
	 *
	 *     wp wc report top_sellers --period=last_month
	 *
	 * @since 2.5.0
	 */
	public function top_sellers( $__, $assoc_args ) {
		$reporter    = $this->get_reporter( $assoc_args );
		$top_sellers = $reporter->get_order_report_data( array(
			'data' => array(
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id'
				),
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'order_by'     => 'order_item_qty DESC',
			'group_by'     => 'product_id',
			'limit'        => isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 12,
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$top_sellers_data = array();
		foreach ( $top_sellers as $top_seller ) {
			$product = wc_get_product( $top_seller->product_id );

			if ( $product ) {
				$top_sellers_data[] = array(
					'title'      => $product->get_title(),
					'product_id' => $top_seller->product_id,
					'quantity'   => $top_seller->order_item_qty,
				);
			}
		}
		$top_sellers_data = apply_filters( 'woocommerce_cli_top_sellers_report', $top_sellers_data );

		$formatter = $this->get_formatter( $assoc_args );
		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			echo implode( ' ', wp_list_pluck( $top_sellers_data, 'product_id' ) );
		} else {
			$formatter->display_items( $top_sellers_data );
		}
	}

	/**
	 * Setup the report object and parse any date filtering
	 *
	 * @since  2.5.0
	 * @param  array $assoc_args Arguments provided in when invoking the command
	 * @return WC_Report_Sales_By_Date
	 */
	private function get_reporter( $assoc_args ) {

		include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );
		include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-report-sales-by-date.php' );

		$report = new WC_Report_Sales_By_Date();

		if ( empty( $assoc_args['period'] ) ) {

			// custom date range
			$assoc_args['period'] = 'custom';

			if ( ! empty( $assoc_args['date_min'] ) || ! empty( $assoc_args['date_max'] ) ) {

				// overwrite _GET to make use of WC_Admin_Report::calculate_current_range() for custom date ranges
				$_GET['start_date'] = $this->parse_datetime( $assoc_args['date_min'] );
				$_GET['end_date'] = isset( $assoc_args['date_max'] ) ? $this->parse_datetime( $assoc_args['date_max'] ) : null;

			} else {

				// default custom range to today
				$_GET['start_date'] = $_GET['end_date'] = date( 'Y-m-d', current_time( 'timestamp' ) );
			}

		} else {

			// ensure period is valid
			if ( ! in_array( $assoc_args['period'], array( 'week', 'month', 'last_month', 'year' ) ) ) {
				$assoc_args['period'] = 'week';
			}

			// TODO: change WC_Admin_Report class to use "week" instead, as it's more consistent with other periods
			// allow "week" for period instead of "7day"
			if ( 'week' === $assoc_args['period'] ) {
				$assoc_args['period'] = '7day';
			}
		}

		$report->calculate_current_range( $assoc_args['period'] );

		return $report;
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'title,product_id,quantity';
	}
}
