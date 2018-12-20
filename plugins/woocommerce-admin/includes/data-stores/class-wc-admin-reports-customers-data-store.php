<?php
/**
 * WC_Admin_Reports_Customers_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Customers_Data_Store.
 */
class WC_Admin_Reports_Customers_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_customer_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'customer_id'      => 'intval',
		'user_id'          => 'intval',
		'date_registered'  => 'strval',
		'date_last_active' => 'strval',
		'orders_count'     => 'intval',
		'total_spend'      => 'floatval',
		'avg_order_value'  => 'floatval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'customer_id'      => 'customer_id',
		'user_id'          => 'user_id',
		'username'         => 'username',
		'name'             => "CONCAT_WS( ' ', first_name, last_name ) as name", // TODO: what does this mean for RTL?
		'email'            => 'email',
		'country'          => 'country',
		'city'             => 'city',
		'postcode'         => 'postcode',
		'date_registered'  => 'date_registered',
		'date_last_active' => 'date_last_active',
		'orders_count'     => 'orders_count',
		'total_spend'      => 'total_spend',
		'avg_order_value'  => 'avg_order_value',
	);

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'name' === $order_by ) {
			return "CONCAT_WS( ' ', first_name, last_name )";
		}

		return $order_by;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_order_by_sql_params( $query_args ) {
		$sql_query['order_by_clause'] = '';

		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
		}

		return $sql_query;
	}

	/**
	 * Fills WHERE clause of SQL request with date-related constraints.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return array
	 */
	protected function get_time_period_sql_params( $query_args, $table_name ) {
		$sql_query           = array(
			'from_clause'       => '',
			'where_time_clause' => '',
			'where_clause'      => '',
		);
		$date_param_mapping  = array(
			'registered'  => 'date_registered',
			'last_active' => 'date_last_active',
			'last_order'  => 'date_last_order',
		);
		$match_operator      = $this->get_match_operator( $query_args );
		$where_time_clauses  = array();

		foreach ( $date_param_mapping as $query_param => $column_name ) {
			$subclauses = array();
			$before_arg = $query_param . '_before';
			$after_arg  = $query_param . '_after';

			if ( ! empty( $query_args[ $before_arg ] ) ) {
				$datetime     = new DateTime( $query_args[ $before_arg ] );
				$datetime_str = $datetime->format( WC_Admin_Reports_Interval::$sql_datetime_format );
				$subclauses[] = "{$table_name}.{$column_name} <= '$datetime_str'";
			}

			if ( ! empty( $query_args[ $after_arg ] ) ) {
				$datetime     = new DateTime( $query_args[ $after_arg ] );
				$datetime_str = $datetime->format( WC_Admin_Reports_Interval::$sql_datetime_format );
				$subclauses[] = "{$table_name}.{$column_name} <= '$datetime_str'";
			}

			if ( $subclauses ) {
				$where_time_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}
		}

		if ( $where_time_clauses ) {
			$sql_query['where_time_clause'] = implode( " {$match_operator} ", $where_time_clauses );
		}

		return $sql_query;
	}

	/**
	 * Updates the database query with parameters used for Customers report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_product_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$match_operator = $this->get_match_operator( $query_args );
		$where_clauses  = array();

		$exact_match_params = array(
			'name',
			'username',
			'email',
			'country',
		);

		foreach ( $exact_match_params as $exact_match_param ) {
			if ( ! empty( $query_args[ $exact_match_param ] ) ) {
				$where_clauses[] = $wpdb->prepare(
					"{$order_product_lookup_table}.{$exact_match_param} = %s",
					$query_args[ $exact_match_param ]
				); // WPCS: unprepared SQL ok.
			}
		}

		$numeric_params = array(
			'orders_count'    => '%d',
			'total_spend'     => '%f',
			'avg_order_value' => '%f',
		);

		foreach ( $numeric_params as $numeric_param => $sql_format ) {
			$subclauses = array();
			$min_param  = $numeric_param . '_min';
			$max_param  = $numeric_param . '_max';

			if ( isset( $query_args[ $min_param ] ) ) {
				$subclauses[] = $wpdb->prepare(
					"{$order_product_lookup_table}.{$numeric_param} >= {$sql_format}",
					$query_args[ $min_param ]
				); // WPCS: unprepared SQL ok.
			}

			if ( isset( $query_args[ $max_param ] ) ) {
				$subclauses[] = $wpdb->prepare(
					"{$order_product_lookup_table}.{$numeric_param} <= {$sql_format}",
					$query_args[ $max_param ]
				); // WPCS: unprepared SQL ok.
			}

			if ( $subclauses ) {
				$where_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}
		}

		if ( $where_clauses ) {
			$preceding_match = empty( $sql_query_params['where_time_clause'] ) ? '' : " {$match_operator} ";
			$sql_query_params['where_clause'] = $preceding_match . implode( " {$match_operator} ", $where_clauses );
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
		$defaults = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date_registered',
			'fields'   => '*',
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
				"SELECT COUNT(*)
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
				"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$customer_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						customer_id
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $customer_data ) {
				return $data;
			}

			$customer_data = array_map( array( $this, 'cast_numbers' ), $customer_data );
			$data          = (object) array(
				'data'    => $customer_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Update the database with customer data.
	 *
	 * @param int $user_id WP User ID to update customer data for.
	 * @return int|bool|null Number or rows modified or false on failure.
	 */
	public static function update_registered_customer( $user_id ) {
		global $wpdb;

		$customer    = new WC_Customer( $user_id );
		$order_count = $customer->get_order_count( 'edit' );
		$total_spend = $customer->get_total_spent( 'edit' );
		$last_order  = $customer->get_last_order();
		$last_active = $customer->get_meta( 'wc_last_active', true, 'edit' );

		// TODO: try to preserve customer_id for existing user_id?
		return $wpdb->replace(
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'user_id'          => $user_id,
				'username'         => $customer->get_username( 'edit' ),
				'first_name'       => $customer->get_first_name( 'edit' ),
				'last_name'        => $customer->get_last_name( 'edit' ),
				'email'            => $customer->get_email( 'edit' ),
				'city'             => $customer->get_billing_city( 'edit' ),
				'postcode'         => $customer->get_billing_postcode( 'edit' ),
				'country'          => $customer->get_billing_country( 'edit' ),
				'orders_count'     => $order_count,
				'total_spend'      => $total_spend,
				'avg_order_value'  => $order_count ? ( $total_spend / $order_count ) : 0,
				'date_last_order'  => $last_order ? date( 'Y-m-d H:i:s', $last_order->get_date_created( 'edit' )->getTimestamp() ) : '',
				'date_registered'  => date( 'Y-m-d H:i:s', $customer->get_date_created( 'edit' )->getTimestamp() ),
				'date_last_active' => $last_active ? date( 'Y-m-d H:i:s', $last_active ) : '',
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%f',
				'%f',
				'%s',
				'%s',
				'%s',
			)
		);
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
