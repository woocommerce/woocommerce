<?php
/**
 * V2 report functionality
 *
 * @package WooCommerce\Admin\Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Admin_Report' ) ) {
	require_once dirname( __FILE__ ) . '/class-wc-admin-report.php';
}

/**
 * Class WC_Admin_Report_V2
 * Makes use of the WC Admin plugin endpoints if it is not disabled.
 */
class WC_Admin_Report_V2 extends WC_Admin_Report {

	/**
	 * Overwrites the original sparkline to use the new reports data if WooAdmin is enabled.
	 * Prepares a sparkline to show sales in the last X days.
	 *
	 * @param  int    $id ID of the product to show. Blank to get all orders.
	 * @param  int    $days Days of stats to get.
	 * @param  string $type Type of sparkline to get. Ignored if ID is not set.
	 * @return string
	 */
	public function sales_sparkline( $id = '', $days = 7, $type = 'sales' ) {
		$is_wc_admin_disabled = apply_filters( 'woocommerce_admin_disabled', false );
		if ( $is_wc_admin_disabled ) {
			return parent::sales_sparkline( $id, $days, $type );
		}
		$sales_endpoint = '/wc-analytics/reports/revenue/stats';
		$start_date     = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - ( ( $days - 1 ) * DAY_IN_SECONDS ) );
		$end_date       = gmdate( 'Y-m-d 23:59:59', current_time( 'timestamp' ) );
		$meta_key       = 'net_revenue';
		$params         = array(
			'order'    => 'asc',
			'interval' => 'day',
			'per_page' => 100,
			'before'   => $end_date,
			'after'    => $start_date,
		);
		if ( $id ) {
			$sales_endpoint     = '/wc-analytics/reports/products/stats';
			$meta_key           = ( 'sales' === $type ) ? 'net_revenue' : 'items_sold';
			$params['products'] = $id;
		}
		$request          = new \WP_REST_Request( 'GET', $sales_endpoint );
		$params['fields'] = array( $meta_key );
		$request->set_query_params( $params );

		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$resp_data = $response->get_data();
		$data      = $resp_data['intervals'];

		$sparkline_data = array();
		$total          = 0;
		foreach ( $data as $d ) {
			$total += $d['subtotals']->$meta_key;
			array_push( $sparkline_data, array( strval( strtotime( $d['interval'] ) * 1000 ), $d['subtotals']->$meta_key ) );
		}

		if ( 'sales' === $type ) {
			/* translators: 1: total income 2: days */
			$tooltip = sprintf( __( 'Sold %1$s worth in the last %2$d days', 'woocommerce' ), strip_tags( wc_price( $total ) ), $days );
		} else {
			/* translators: 1: total items sold 2: days */
			$tooltip = sprintf( _n( 'Sold %1$d item in the last %2$d days', 'Sold %1$d items in the last %2$d days', $total, 'woocommerce' ), $total, $days );
		}

		return '<span class="wc_sparkline ' . ( ( 'sales' === $type ) ? 'lines' : 'bars' ) . ' tips" data-color="#777" data-tip="' . esc_attr( $tooltip ) . '" data-barwidth="' . 60 * 60 * 16 * 1000 . '" data-sparkline="' . wc_esc_json( wp_json_encode( $sparkline_data ) ) . '"></span>';
	}

	/**
	 * Gets the sales performance data from the new WooAdmin store.
	 *
	 * @return stdClass|WP_Error|WP_REST_Response
	 */
	public function get_performance_data() {
		$request    = new \WP_REST_Request( 'GET', '/wc-analytics/reports/performance-indicators' );
		$start_date = gmdate( 'Y-m-01 00:00:00', current_time( 'timestamp' ) );
		$end_date   = gmdate( 'Y-m-d 23:59:59', current_time( 'timestamp' ) );
		$request->set_query_params(
			array(
				'before' => $end_date,
				'after'  => $start_date,
				'stats'  => 'revenue/total_sales,revenue/net_revenue,orders/orders_count,products/items_sold,variations/items_sold',
			)
		);
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response->get_status() ) {
			return new \WP_Error( 'woocommerce_analytics_performance_indicators_result_failed', __( 'Sorry, fetching performance indicators failed.', 'woocommerce' ) );
		}
		$report_keys      = array(
			'net_revenue' => 'net_sales',
		);
		$performance_data = new stdClass();
		foreach ( $response->get_data() as $indicator ) {
			if ( isset( $indicator['chart'] ) && isset( $indicator['value'] ) ) {
				$key                    = isset( $report_keys[ $indicator['chart'] ] ) ? $report_keys[ $indicator['chart'] ] : $indicator['chart'];
				$performance_data->$key = $indicator['value'];
			}
		}
		return $performance_data;
	}
}
