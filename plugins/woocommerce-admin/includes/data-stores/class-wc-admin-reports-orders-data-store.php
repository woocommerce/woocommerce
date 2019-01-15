<?php
/**
 * WC_Admin_Reports_Orders_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Orders_Data_Store.
 */
class WC_Admin_Reports_Orders_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_stats';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'order_id'       => 'intval',
		'date_created'   => 'strval',
		'status'         => 'strval',
		'customer_id'    => 'intval',
		'net_total'      => 'floatval',
		'num_items_sold' => 'intval',
		'customer_type'  => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'order_id'       => 'order_id',
		'date_created'   => 'date_created',
		'status'         => 'status',
		'customer_id'    => 'customer_id',
		'net_total'      => 'net_total',
		'num_items_sold' => 'num_items_sold',
		'customer_type'  => '(CASE WHEN returning_customer <> 0 THEN "returning" ELSE "new" END) as customer_type',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious columns in SQL query.
		$this->report_columns['order_id']     = $table_name . '.' . $this->report_columns['order_id'];
		$this->report_columns['date_created'] = $table_name . '.' . $this->report_columns['date_created'];
		$this->report_columns['customer_id']  = $table_name . '.' . $this->report_columns['customer_id'];
	}

	/**
	 * Updates the database query with parameters used for orders report: coupons and products filters.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_stats_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_stats_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$status_subquery = $this->get_status_subquery( $query_args );
		if ( $status_subquery ) {
			$sql_query_params['where_clause'] .= " AND {$status_subquery}";
		}

		if ( $query_args['customer_type'] ) {
			$returning_customer                = 'returning' === $query_args['customer_type'] ? 1 : 0;
			$sql_query_params['where_clause'] .= " AND returning_customer = ${returning_customer}";
		}

		$included_coupons          = $this->get_included_coupons( $query_args );
		$excluded_coupons          = $this->get_excluded_coupons( $query_args );
		$order_coupon_lookup_table = $wpdb->prefix . 'wc_order_coupon_lookup';
		if ( $included_coupons || $excluded_coupons ) {
			$sql_query_params['from_clause'] .= " JOIN {$order_coupon_lookup_table} ON {$order_stats_lookup_table}.order_id = {$order_coupon_lookup_table}.order_id";
		}
		if ( $included_coupons ) {
			$sql_query_params['where_clause'] .= " AND {$order_coupon_lookup_table}.coupon_id IN ({$included_coupons})";
		}
		if ( $excluded_coupons ) {
			$sql_query_params['where_clause'] .= " AND {$order_coupon_lookup_table}.coupon_id NOT IN ({$excluded_coupons})";
		}

		$included_products          = $this->get_included_products( $query_args );
		$excluded_products          = $this->get_excluded_products( $query_args );
		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		if ( $included_products || $excluded_products ) {
			$sql_query_params['from_clause'] .= " JOIN {$order_product_lookup_table} ON {$order_stats_lookup_table}.order_id = {$order_product_lookup_table}.order_id";
		}
		if ( $included_products ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		}
		if ( $excluded_products ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id NOT IN ({$excluded_products})";
		}

		return $sql_query_params;
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
			'per_page'         => get_option( 'posts_per_page' ),
			'page'             => 1,
			'order'            => 'DESC',
			'orderby'          => 'date_created',
			'before'           => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'            => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'           => '*',
			'product_includes' => array(),
			'product_excludes' => array(),
			'coupon_includes'  => array(),
			'coupon_excludes'  => array(),
			'customer_type'    => null,
			'status_is'        => parent::get_report_order_statuses(),
			'extended_info'    => false,
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );

			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								{$table_name}.order_id
							FROM
								{$table_name}
								{$sql_query_params['from_clause']}
							WHERE
								1=1
								{$sql_query_params['where_time_clause']}
								{$sql_query_params['where_clause']}
					  		) AS tt"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$orders_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $orders_data ) {
				return $data;
			}

			if ( $query_args['extended_info'] ) {
				$this->include_extended_info( $orders_data, $query_args );
			}

			$orders_data = array_map( array( $this, 'cast_numbers' ), $orders_data );
			$data        = (object) array(
				'data'    => $orders_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @param string $order_by Order by option requeste by user.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'date_created';
		}

		return $order_by;
	}

	/**
	 * Returns order status subquery to be used in WHERE SQL query, based on query arguments from the user.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $operator   AND or OR, based on match query argument.
	 * @return string
	 */
	protected function get_status_subquery( $query_args, $operator = 'AND' ) {
		$subqueries = array();
		if ( isset( $query_args['status_is'] ) && is_array( $query_args['status_is'] ) && count( $query_args['status_is'] ) > 0 ) {
			$allowed_statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['status_is'] );
			if ( $allowed_statuses ) {
				$subqueries[] = "status IN ( '" . implode( "','", $allowed_statuses ) . "' )";
			}
		}

		if ( isset( $query_args['status_is_not'] ) && is_array( $query_args['status_is_not'] ) && count( $query_args['status_is_not'] ) > 0 ) {
			$forbidden_statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['status_is_not'] );
			if ( $forbidden_statuses ) {
				$subqueries[] = "status NOT IN ( '" . implode( "','", $forbidden_statuses ) . "' )";
			}
		}

		return implode( " $operator ", $subqueries );
	}

	/**
	 * Enriches the order data.
	 *
	 * @param array $orders_data Orders data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$orders_data, $query_args ) {
		$mapped_orders      = $this->map_array_by_key( $orders_data, 'order_id' );
		$products           = $this->get_products_by_order_ids( array_keys( $mapped_orders ) );
		$mapped_products    = $this->map_array_by_key( $products, 'product_id' );
		$product_categories = $this->get_product_categories_by_product_ids( array_keys( $mapped_products ) );

		$mapped_data = array();
		foreach ( $products as $product ) {
			if ( ! isset( $mapped_data[ $product['order_id'] ] ) ) {
				$mapped_data[ $product['order_id'] ]['products']   = array();
				$mapped_data[ $product['order_id'] ]['categories'] = array();
			}

			$mapped_data[ $product['order_id'] ]['products'][] = array(
				'id'   => $product['product_id'],
				'name' => $product['product_name'],
			);
			$mapped_data[ $product['order_id'] ]['categories'] = array_unique(
				array_merge(
					$mapped_data[ $product['order_id'] ]['categories'],
					$product_categories[ $product['product_id'] ]
				)
			);
		}

		foreach ( $orders_data as $key => $order_data ) {
			$orders_data[ $key ]['extended_info'] = $mapped_data[ $order_data['order_id'] ];
		}
	}

	/**
	 * Returns the same array index by a given key
	 *
	 * @param array  $array Array to be looped over.
	 * @param string $key Key of values used for new array.
	 * @return array
	 */
	protected function map_array_by_key( $array, $key ) {
		$mapped = array();
		foreach ( $array as $item ) {
			$mapped[ $item[ $key ] ] = $item;
		}
		return $mapped;
	}

	/**
	 * Get product Ids, names, and categories from order IDs.
	 *
	 * @param array $order_ids Array of order IDs.
	 * @return array
	 */
	protected function get_products_by_order_ids( $order_ids ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		$included_order_ids         = implode( ',', $order_ids );

		$products = $wpdb->get_results(
			"SELECT order_id, ID as product_id, post_title as product_name
				FROM {$wpdb->prefix}posts
				JOIN {$order_product_lookup_table} ON {$order_product_lookup_table}.product_id = {$wpdb->prefix}posts.ID
				WHERE 
					order_id IN ({$included_order_ids})
				",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		return $products;
	}

	/**
	 * Get product categories by array of product IDs
	 *
	 * @param array $product_ids Product IDs.
	 * @return array
	 */
	protected function get_product_categories_by_product_ids( $product_ids ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		$included_product_ids       = implode( ',', $product_ids );

		$product_categories = $wpdb->get_results(
			"SELECT term_id AS category_id, object_id AS product_id
				FROM {$wpdb->prefix}term_relationships
				JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
				WHERE
					object_id IN (${included_product_ids})
					AND taxonomy = 'product_cat'
				",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		$mapped_product_categories = array();
		foreach ( $product_categories as $category ) {
			$mapped_product_categories[ $category['product_id'] ][] = $category['category_id'];
		}

		return $mapped_product_categories;
	}


	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

}
