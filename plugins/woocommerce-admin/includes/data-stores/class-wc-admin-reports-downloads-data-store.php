<?php
/**
 * WC_Admin_Reports_Downloads_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Downloads_Data_Store.
 */
class WC_Admin_Reports_Downloads_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_download_log';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'id'          => 'intval',
		'date'        => 'strval',
		'date_gmt'    => 'strval',
		'download_id' => 'strval', // String because this can sometimes be a hash.
		'file_name'   => 'strval',
		'product_id'  => 'intval',
		'order_id'    => 'intval',
		'user_id'     => 'intval',
		'ip_address'  => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'id'          => 'download_log_id as id',
		'date'        => 'timestamp as date_gmt',
		'download_id' => 'product_permissions.download_id',
		'product_id'  => 'product_permissions.product_id',
		'order_id'    => 'product_permissions.order_id',
		'user_id'     => 'product_permissions.user_id',
		'ip_address'  => 'user_ip_address as ip_address',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
	}

	/**
	 * Updates the database query with parameters used for downloads report.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$lookup_table  = $wpdb->prefix . self::TABLE_NAME;
		$operator      = $this->get_match_operator( $query_args );
		$where_filters = array();

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );

		$included_products = $this->get_included_products( $query_args );
		$excluded_products = $this->get_excluded_products( $query_args );
		if ( $included_products ) {
			$where_filters[] = " {$lookup_table}.permission_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.product_id IN ({$included_products})
			)";
		}

		if ( $excluded_products ) {
			$where_filters[] = " {$lookup_table}.permission_id NOT IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.product_id IN ({$excluded_products})
			)";
		}

		$included_orders = $this->get_included_orders( $query_args );
		$excluded_orders = $this->get_excluded_orders( $query_args );
		if ( $included_orders ) {
			$where_filters[] = " {$lookup_table}.permission_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.order_id IN ({$included_orders})
			)";
		}

		if ( $excluded_orders ) {
			$where_filters[] = " {$lookup_table}.permission_id NOT IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.order_id IN ({$excluded_orders})
			)";
		}

		$customer_lookup_table = $wpdb->prefix . 'wc_customer_lookup';
		$included_customers    = $this->get_included_customers( $query_args );
		$excluded_customers    = $this->get_excluded_customers( $query_args );
		if ( $included_customers ) {
			$where_filters[] = " {$lookup_table}.permission_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.user_id IN (
					SELECT {$customer_lookup_table}.user_id FROM {$customer_lookup_table} WHERE {$customer_lookup_table}.customer_id IN ({$included_customers})
				)
			)";
		}

		if ( $excluded_customers ) {
			$where_filters[] = " {$lookup_table}.permission_id NOT IN (
			SELECT
				DISTINCT {$wpdb->prefix}woocommerce_downloadable_product_permissions.permission_id
			FROM
				{$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE
				{$wpdb->prefix}woocommerce_downloadable_product_permissions.user_id IN (
					SELECT {$customer_lookup_table}.user_id FROM {$customer_lookup_table} WHERE {$customer_lookup_table}.customer_id IN ({$excluded_customers})
				)
			)";
		}

		$included_ip_addresses = $this->get_included_ip_addresses( $query_args );
		$excluded_ip_addresses = $this->get_excluded_ip_addresses( $query_args );
		if ( $included_ip_addresses ) {
			$where_filters[] = " {$lookup_table}.user_ip_address IN ('{$included_ip_addresses}')";
		}

		if ( $excluded_ip_addresses ) {
			$where_filters[] = " {$lookup_table}.user_ip_address NOT IN ('{$excluded_ip_addresses}')";
		}

		$where_subclause = implode( " $operator ", $where_filters );
		if ( $where_subclause ) {
			$sql_query_params['where_clause'] .= " AND ( $where_subclause )";
		}

		$sql_query_params['from_clause'] .= " JOIN {$wpdb->prefix}woocommerce_downloadable_product_permissions as product_permissions ON {$lookup_table}.permission_id = product_permissions.permission_id";
		$sql_query_params                 = $this->get_order_by( $query_args, $sql_query_params );

		return $sql_query_params;
	}

	/**
	 * Returns comma separated ids of included ip address, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_ip_addresses( $query_args ) {
		$included_ips_str = '';

		if ( isset( $query_args['ip_address_includes'] ) && is_array( $query_args['ip_address_includes'] ) && count( $query_args['ip_address_includes'] ) > 0 ) {
			$ip_includes = array();
			foreach ( $query_args['ip_address_includes'] as $ip ) {
				$ip_includes[] = esc_sql( $ip );
			}
			$included_ips_str = implode( "','", $ip_includes );
		}
		return $included_ips_str;
	}

	/**
	 * Returns comma separated ids of excluded ip address, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_excluded_ip_addresses( $query_args ) {
		$excluded_ips_str = '';

		if ( isset( $query_args['ip_address_excludes'] ) && is_array( $query_args['ip_address_excludes'] ) && count( $query_args['ip_address_excludes'] ) > 0 ) {
			$ip_excludes = array();
			foreach ( $query_args['ip_address_excludes'] as $ip ) {
				$ip_excludes[] = esc_sql( $ip );
			}
			$excluded_ips_str = implode( ',', $ip_excludes );
		}
		return $excluded_ips_str;
	}

	/**
	 * Returns comma separated ids of included customers, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_customers( $query_args ) {
		$included_customers_str = '';

		if ( isset( $query_args['customer_includes'] ) && is_array( $query_args['customer_includes'] ) && count( $query_args['customer_includes'] ) > 0 ) {
			$included_customers_str = implode( ',', $query_args['customer_includes'] );
		}
		return $included_customers_str;
	}

	/**
	 * Returns comma separated ids of excluded customers, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_excluded_customers( $query_args ) {
		$excluded_customer_str = '';

		if ( isset( $query_args['customer_excludes'] ) && is_array( $query_args['customer_excludes'] ) && count( $query_args['customer_excludes'] ) > 0 ) {
			$excluded_customer_str = implode( ',', $query_args['customer_excludes'] );
		}
		return $excluded_customer_str;
	}

	/**
	 * Fills WHERE clause of SQL request with date-related constraints.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return array
	 */
	protected function get_time_period_sql_params( $query_args, $table_name ) {
		$sql_query = array(
			'from_clause'       => '',
			'where_time_clause' => '',
			'where_clause'      => '',
		);

		if ( $query_args['before'] ) {
			$datetime_str                    = $query_args['before']->format( WC_Admin_Reports_Interval::$sql_datetime_format );
			$sql_query['where_time_clause'] .= " AND {$table_name}.timestamp <= '$datetime_str'";

		}

		if ( $query_args['after'] ) {
			$datetime_str                    = $query_args['after']->format( WC_Admin_Reports_Interval::$sql_datetime_format );
			$sql_query['where_time_clause'] .= " AND {$table_name}.timestamp >= '$datetime_str'";
		}

		return $sql_query;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @param array $sql_query Current SQL query array.
	 * @return array
	 */
	protected function get_order_by( $query_args, $sql_query ) {
		global $wpdb;
		$sql_query['order_by_clause'] = '';
		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		if ( false !== strpos( $sql_query['order_by_clause'], '_products' ) ) {
			$sql_query['from_clause'] .= " JOIN {$wpdb->prefix}posts AS _products ON product_permissions.product_id = _products.ID";
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
		}

		return $sql_query;
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
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'timestamp',
			'before'   => WC_Admin_Reports_Interval::default_before(),
			'after'    => WC_Admin_Reports_Interval::default_after(),
			'fields'   => '*',
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
								{$table_name}.download_log_id
							FROM
								{$table_name}
								{$sql_query_params['from_clause']}
							WHERE
								1=1
								{$sql_query_params['where_time_clause']}
								{$sql_query_params['where_clause']}
							GROUP BY
								{$table_name}.download_log_id
					  		) AS tt"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$download_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						{$table_name}.download_log_id
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $download_data ) {
				return $data;
			}

			$download_data = array_map( array( $this, 'cast_numbers' ), $download_data );
			$data          = (object) array(
				'data'    => $download_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
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

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;

		if ( 'date' === $order_by ) {
			return $wpdb->prefix . 'wc_download_log.timestamp';
		}

		if ( 'product' === $order_by ) {
			return '_products.post_title';
		}

		return $order_by;
	}

}
