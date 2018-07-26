<?php
/**
 * WC_Reports_Revenue_Store class file.
 *
 * @package WooCommerce/Classes
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Data Store: Stored in CPT.
 * //extends WC_Data_Store_WP.
 *
 * @version  3.5.0
 */
class WC_Reports_Revenue_Data_Store extends WC_Reports_Data_Store implements WC_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	protected $table_name = 'order_stats';

	/**
	 * Report name.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	protected $report_name = 'revenue_report';

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @since 3.5.0
	 * @param array $query_args Query parameters.
	 * @return array            Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$totals_query    = $this->get_totals_sql_params( $query_args );
			$intervals_query = $this->get_intervals_sql_params( $query_args );

			$table_name = $wpdb->prefix . $this->table_name;
			$totals     = $wpdb->get_results(
				"SELECT
							SUM(orders_gross_total) AS gross_revenue, 
							SUM(orders_coupon_total) AS coupons, 
							SUM(orders_shipping_total) AS shipping, 
							SUM(orders_tax_total) AS taxes, 
							SUM(orders_refund_total) AS refunds,
							SUM(orders_gross_total) - SUM(orders_coupon_total) - SUM(orders_shipping_total) - SUM(orders_tax_total) - SUM(orders_refund_total) AS net_revenue,
							SUM(num_orders) AS orders_count,
							SUM(num_items_sold) AS items_sold
						FROM 
							{$table_name}
						WHERE 
							1=1 
							{$totals_query['where_clause']}"); // WPCS: cache ok, DB call ok.

			$db_records_within_interval = $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								{$intervals_query['select_clause']} AS time_interval
							FROM 
								{$table_name}
							WHERE 
								1 = 1
								{$intervals_query['where_clause']}
							GROUP BY
								time_interval
							LIMIT 0, {$intervals_query['per_page']}
							) AS tt"
			); // WPCS: cache ok, DB call ok.

			$this->update_intervals_sql_params( $intervals_query, $db_records_within_interval );

			$intervals = $wpdb->get_results(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval,
							MIN(hour) AS date_start,
							MAX(hour) AS date_end,
							SUM(orders_gross_total) AS gross_revenue, 
							SUM(orders_coupon_total) AS coupons, 
							SUM(orders_shipping_total) AS shipping, 
							SUM(orders_tax_total) AS taxes, 
							SUM(orders_refund_total) AS refunds,
							SUM(orders_gross_total) - SUM(orders_coupon_total) - SUM(orders_shipping_total) - SUM(orders_tax_total) - SUM(orders_refund_total) AS net_revenue,
							SUM(num_orders) AS orders_count,
							SUM(num_items_sold) AS items_sold
						FROM 
							{$table_name}
						WHERE 
							1 = 1
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval
						ORDER BY 
							{$intervals_query['order_by_clause']}
						LIMIT {$intervals_query['offset']}, {$intervals_query['per_page']}"
			); // WPCS: cache ok, DB call ok.

			if ( ! $totals || ! $intervals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			$data = (object) array(
				'totals'    => $totals[0],
				'intervals' => $intervals,
			);

			if ( $db_records_within_interval < $intervals_query['per_page'] ) {
				$this->fill_in_missing_intervals( $query_args['after'], $query_args['before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'] - 1, $intervals_query['per_page'] );
			} else {
				$this->update_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data );
			}

			wp_cache_set( $cache_key, $data, $this->cache_group, 3600 );
		}

		return $data;
	}
}
