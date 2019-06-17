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
		'order_id'         => 'intval',
		'parent_id'        => 'intval',
		'date_created'     => 'strval',
		'date_created_gmt' => 'strval',
		'status'           => 'strval',
		'customer_id'      => 'intval',
		'net_total'        => 'floatval',
		'gross_total'      => 'floatval',
		'num_items_sold'   => 'intval',
		'customer_type'    => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious columns in SQL query.
		$this->report_columns = array(
			'order_id'         => "{$table_name}.order_id",
			'parent_id'        => "{$table_name}.parent_id",
			'date_created'     => "{$table_name}.date_created",
			'date_created_gmt' => "{$table_name}.date_created_gmt",
			'status'           => "REPLACE({$table_name}.status, 'wc-', '') as status",
			'customer_id'      => "{$table_name}.customer_id",
			'net_total'        => "{$table_name}.net_total",
			'gross_total'      => "{$table_name}.gross_total",
			'num_items_sold'   => "{$table_name}.num_items_sold",
			'customer_type'    => "(CASE WHEN {$table_name}.returning_customer = 1 THEN 'returning' WHEN {$table_name}.returning_customer = 0 THEN 'new' ELSE '' END) as customer_type",
		);
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
		$operator                 = $this->get_match_operator( $query_args );
		$where_subquery           = array();

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_stats_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$status_subquery = $this->get_status_subquery( $query_args );
		if ( $status_subquery ) {
			if ( empty( $query_args['status_is'] ) && empty( $query_args['status_is_not'] ) ) {
				$sql_query_params['where_clause'] .= " AND {$status_subquery}";
			} else {
				$where_subquery[] = $status_subquery;
			}
		}

		if ( $query_args['customer_type'] ) {
			$returning_customer = 'returning' === $query_args['customer_type'] ? 1 : 0;
			$where_subquery[]   = "{$order_stats_lookup_table}.returning_customer = ${returning_customer}";
		}

		$refund_subquery                  = $this->get_refund_subquery( $query_args );
		$sql_query_params['from_clause'] .= $refund_subquery['from_clause'] ? $refund_subquery['from_clause'] : '';
		if ( $refund_subquery['where_clause'] ) {
			$where_subquery[] = $refund_subquery['where_clause'];
		}

		$included_coupons          = $this->get_included_coupons( $query_args );
		$excluded_coupons          = $this->get_excluded_coupons( $query_args );
		$order_coupon_lookup_table = $wpdb->prefix . 'wc_order_coupon_lookup';
		if ( $included_coupons || $excluded_coupons ) {
			$sql_query_params['from_clause'] .= " JOIN {$order_coupon_lookup_table} ON {$order_stats_lookup_table}.order_id = {$order_coupon_lookup_table}.order_id";
		}
		if ( $included_coupons ) {
			$where_subquery[] = "{$order_coupon_lookup_table}.coupon_id IN ({$included_coupons})";
		}
		if ( $excluded_coupons ) {
			$where_subquery[] = "{$order_coupon_lookup_table}.coupon_id NOT IN ({$excluded_coupons})";
		}

		$included_products          = $this->get_included_products( $query_args );
		$excluded_products          = $this->get_excluded_products( $query_args );
		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		if ( $included_products || $excluded_products ) {
			$sql_query_params['from_clause'] .= " JOIN {$order_product_lookup_table} ON {$order_stats_lookup_table}.order_id = {$order_product_lookup_table}.order_id";
		}
		if ( $included_products ) {
			$where_subquery[] = "{$order_product_lookup_table}.product_id IN ({$included_products})";
		}
		if ( $excluded_products ) {
			$where_subquery[] = "{$order_product_lookup_table}.product_id NOT IN ({$excluded_products})";
		}

		if ( 0 < count( $where_subquery ) ) {
			$sql_query_params['where_clause'] .= ' AND (' . implode( " {$operator} ", $where_subquery ) . ')';
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

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'         => get_option( 'posts_per_page' ),
			'page'             => 1,
			'order'            => 'DESC',
			'orderby'          => 'date_created',
			'before'           => WC_Admin_Reports_Interval::default_before(),
			'after'            => WC_Admin_Reports_Interval::default_after(),
			'fields'           => '*',
			'product_includes' => array(),
			'product_excludes' => array(),
			'coupon_includes'  => array(),
			'coupon_excludes'  => array(),
			'customer_type'    => null,
			'status_is'        => array(),
			'extended_info'    => false,
			'refunds'          => null,
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

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

			if ( 0 === $sql_query_params['per_page'] ) {
				$total_pages = 0;
			} else {
				$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			}
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				$data = (object) array(
					'data'    => array(),
					'total'   => $db_records_count,
					'pages'   => 0,
					'page_no' => 0,
				);
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
	 * Enriches the order data.
	 *
	 * @param array $orders_data Orders data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$orders_data, $query_args ) {
		$mapped_orders    = $this->map_array_by_key( $orders_data, 'order_id' );
		$products         = $this->get_products_by_order_ids( array_keys( $mapped_orders ) );
		$mapped_products  = $this->map_array_by_key( $products, 'product_id' );
		$coupons          = $this->get_coupons_by_order_ids( array_keys( $mapped_orders ) );
		$customers        = $this->get_customers_by_orders( $orders_data );
		$mapped_customers = $this->map_array_by_key( $customers, 'customer_id' );

		$mapped_data = array();
		foreach ( $products as $product ) {
			if ( ! isset( $mapped_data[ $product['order_id'] ] ) ) {
				$mapped_data[ $product['order_id'] ]['products'] = array();
			}

			$mapped_data[ $product['order_id'] ]['products'][] = array(
				'id'       => $product['product_id'],
				'name'     => $product['product_name'],
				'quantity' => $product['product_quantity'],
			);
		}

		foreach ( $coupons as $coupon ) {
			if ( ! isset( $mapped_data[ $coupon['order_id'] ] ) ) {
				$mapped_data[ $product['order_id'] ]['coupons'] = array();
			}

			$mapped_data[ $coupon['order_id'] ]['coupons'][] = array(
				'id'   => $coupon['coupon_id'],
				'code' => wc_format_coupon_code( $coupon['coupon_code'] ),
			);
		}

		foreach ( $orders_data as $key => $order_data ) {
			$defaults                             = array(
				'products' => array(),
				'coupons'  => array(),
				'customer' => array(),
			);
			$orders_data[ $key ]['extended_info'] = isset( $mapped_data[ $order_data['order_id'] ] ) ? array_merge( $defaults, $mapped_data[ $order_data['order_id'] ] ) : $defaults;
			if ( $order_data['customer_id'] && isset( $mapped_customers[ $order_data['customer_id'] ] ) ) {
				$orders_data[ $key ]['extended_info']['customer'] = $mapped_customers[ $order_data['customer_id'] ];
			}
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
	 * Get product IDs, names, and quantity from order IDs.
	 *
	 * @param array $order_ids Array of order IDs.
	 * @return array
	 */
	protected function get_products_by_order_ids( $order_ids ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		$included_order_ids         = implode( ',', $order_ids );

		$products = $wpdb->get_results(
			"SELECT order_id, ID as product_id, post_title as product_name, product_qty as product_quantity
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
	 * Get customer data from Order data.
	 *
	 * @param array $orders Array of orders data.
	 * @return array
	 */
	protected function get_customers_by_orders( $orders ) {
		global $wpdb;

		$customer_lookup_table = $wpdb->prefix . 'wc_customer_lookup';
		$customer_ids          = array();

		foreach ( $orders as $order ) {
			if ( $order['customer_id'] ) {
				$customer_ids[] = intval( $order['customer_id'] );
			}
		}

		if ( empty( $customer_ids ) ) {
			return array();
		}

		$customer_ids = implode( ',', $customer_ids );
		$customers    = $wpdb->get_results(
			"SELECT * FROM {$customer_lookup_table} WHERE customer_id IN ({$customer_ids})",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		return $customers;
	}

	/**
	 * Get coupon information from order IDs.
	 *
	 * @param array $order_ids Array of order IDs.
	 * @return array
	 */
	protected function get_coupons_by_order_ids( $order_ids ) {
		global $wpdb;
		$order_coupon_lookup_table = $wpdb->prefix . 'wc_order_coupon_lookup';
		$included_order_ids        = implode( ',', $order_ids );

		$coupons = $wpdb->get_results(
			"SELECT order_id, coupon_id, post_title as coupon_code
				FROM {$wpdb->prefix}posts
				JOIN {$order_coupon_lookup_table} ON {$order_coupon_lookup_table}.coupon_id = {$wpdb->prefix}posts.ID
				WHERE
					order_id IN ({$included_order_ids})
				",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		return $coupons;
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
