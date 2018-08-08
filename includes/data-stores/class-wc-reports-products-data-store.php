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
class WC_Reports_Products_Data_Store extends WC_Reports_Data_Store implements WC_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_product_lookup';

	protected $column_types = array(
		'date_start'    => 'strval',
		'date_end'      => 'strval',
		'product_id'    => 'intval',
		'items_sold'    => 'intval',
		'gross_revenue' => 'floatval',
		'orders_count'  => 'intval',
	);

	protected $report_columns = array(
		'product_id'    => 'product_id',
		'items_sold'    => 'SUM(product_qty) as items_sold',
		'gross_revenue' => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'  => 'COUNT(DISTINCT order_id) as orders_count',
	);

	/**
	 * Returns an array of products belonging to given categories.
	 *
	 * @param array $categories List of categories IDs.
	 * @return array|stdClass
	 */
	protected function get_products_by_cat_ids( $categories ) {
		$product_categories = get_categories( array(
			'hide_empty' => 0,
			'taxonomy'   => 'product_cat',
		) );
		$cat_slugs          = array();
		$categories         = array_flip( $categories );
		foreach ( $product_categories as $product_cat ) {
			if ( key_exists( $product_cat->cat_ID, $categories ) ) {
				$cat_slugs[] = $product_cat->slug;
			}
		}
		$args = array(
			'category' => $cat_slugs,
			'limit'    => -1,
		);
		return wc_get_products( $args );
	}

	/**
	 * Updates the database queriy with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$sql_query_params = $this->get_time_period_sql_params( $query_args );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;
		$allowed_products           = array();

		if ( is_array( $query_args['categories'] ) && count( $query_args['categories'] ) > 0 ) {
			$allowed_products = $this->get_products_by_cat_ids( $query_args['categories'] );
			$allowed_products = wp_list_pluck( $allowed_products, 'id' );
		}

		if ( is_array( $query_args['products'] ) && count( $query_args['products'] ) > 0 ) {
			// If both categories and product ids are specified, only use product ids present in both.
			if ( count( $allowed_products ) > 0 ) {
				$allowed_products = array_intersect( $allowed_products, $query_args['products'] );
			} else {
				$allowed_products = $query_args['products'];
			}
		}

		if ( count( $allowed_products ) > 0 ) {
			$allowed_products_str              = implode( ',', $allowed_products );
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$allowed_products_str})";
		}

		if ( is_array( $query_args['order_status'] ) && count( $query_args['order_status'] ) > 0 ) {
			$statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['order_status'] );

			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}posts ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$sql_query_params['where_clause'] .= " AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $statuses ) . "' ) ";
		}

		return $sql_query_params;
	}

	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'date_created';
		}

		return $order_by;
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
			'products'     => array(),
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
