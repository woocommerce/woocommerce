<?php
/**
 * WC_Reports_Products_Data_Store class file.
 *
 * @package WooCommerce/Classes
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC_Reports_Products_Data_Store.
 *
 * @version  3.5.0
 */
class WC_Reports_Products_Stats_Data_Store extends WC_Reports_Products_Data_Store implements WC_Reports_Data_Store_Interface {

	// @todo update this for interval data.
	protected $report_columns = array(
		'product_id'    => 'product_id',
		'items_sold'    => 'SUM(product_qty) as items_sold',
		'gross_revenue' => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'  => 'COUNT(DISTINCT order_id) as orders_count',
	);

	/**
	 * Updates the database queriy with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		// @todo update this for interval data.

		return $sql_query_params;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @since 3.5.0
	 * @param array $query_args Query parameters.
	 * @return array|WP_Error   Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'       => '*',
			'categories'   => array(),
			'interval'     => 'week',
			// TODO: This is not a parameter for products reports per se, but maybe we should restricts order statuses here, too?
			'order_status' => parent::get_report_order_statuses(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key    = $this->get_cache_key( $query_args );
		$product_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $product_data ) {

			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );

			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								product_id
							FROM
								{$table_name}
								{$sql_query_params['from_clause']}
							WHERE
								1=1
								{$sql_query_params['where_clause']}
							GROUP BY
								product_id
					  		) AS tt"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			$product_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_clause']}
					GROUP BY
						product_id
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $product_data ) {
				return new WP_Error( 'woocommerce_reports_products_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			$product_data = array_map( array( $this, 'cast_numbers' ), $product_data );

			wp_cache_set( $cache_key, $product_data, $this->cache_group );
		}

		return $product_data;
	}

}
