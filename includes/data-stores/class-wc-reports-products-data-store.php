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

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'    => 'strval',
		'date_end'      => 'strval',
		'product_id'    => 'intval',
		'items_sold'    => 'intval',
		'gross_revenue' => 'floatval',
		'orders_count'  => 'intval',
		// Extended attributes.
		'name'          => 'strval',
		'price'         => 'floatval',
		'image'         => 'strval',
		'permalink'     => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'product_id'    => 'product_id',
		'items_sold'    => 'SUM(product_qty) as items_sold',
		'gross_revenue' => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'  => 'COUNT(DISTINCT order_id) as orders_count',
	);

	/**
	 * Extended product attributes to include in the data.
	 *
	 * @var array
	 */
	protected $extended_attributes = array(
		'name',
		'price',
		'image',
		'permalink',
	);


	/**
	 * Updates the database query with parameters used for Products report: categories and order status.
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
		$allowed_products           = $this->get_allowed_products( $query_args );

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

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'date_created';
		}

		return $order_by;
	}

	/**
	 * Enriches the product data with attributes specified by the extended_attributes.
	 *
	 * @param array $products_data Product data.
	 */
	protected function include_extended_product_info( &$products_data ) {
		foreach ( $products_data as $key => $product_data ) {
			$product = wc_get_product( $product_data['product_id'] );
			$extended_attributes = apply_filters( 'woocommerce_rest_reports_products_extended_attributes', $this->extended_attributes, $product_data );
			foreach ( $extended_attributes as $extended_attribute ) {
				$function = 'get_' . $extended_attribute;
				if ( is_callable( array( $product, $function ) ) ) {
					$value                                        = $product->{$function}();
					$products_data[ $key ][ $extended_attribute ] = $value;
				}
			}
		}
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'              => get_option( 'posts_per_page' ),
			'page'                  => 1,
			'order'                 => 'DESC',
			'orderby'               => 'date',
			'before'                => date( WC_Reports_Interval::$iso_datetime_format, $now ),
			'after'                 => date( WC_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'                => '*',
			'categories'            => array(),
			'products'              => array(),
			'extended_product_info' => false,
			// This is not a parameter for products reports per se, but we want to only take into account selected order types.
			'order_status'          => parent::get_report_order_statuses(),

		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {

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

			if ( $query_args['extended_product_info'] ) {
				$this->include_extended_product_info( $product_data );
			}
			$product_data = array_map( array( $this, 'cast_numbers' ), $product_data );
			$data         = (object) array(
				'data'    => $product_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

}
