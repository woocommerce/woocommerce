<?php
/**
 * WC_Admin_Reports_Copons_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Coupons_Data_Store.
 */
class WC_Admin_Reports_Coupons_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_coupon_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'coupon_id'      => 'intval',
		'gross_discount' => 'floatval',
		'orders_count'   => 'intval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'coupon_id'      => 'coupon_id',
		'gross_discount' => 'SUM(coupon_gross_discount) as gross_discount',
		'orders_count'   => 'COUNT(DISTINCT order_id) as orders_count',
	);

	/**
	 * Returns comma separated ids of included coupons, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_coupons( $query_args ) {
		$included_coupons_str = '';

		if ( isset( $query_args['code'] ) && is_array( $query_args['code'] ) && count( $query_args['code'] ) > 0 ) {
			$coupon_ids           = array_map( 'wc_get_coupon_id_by_code', $query_args['code'] );
			$included_coupons_str = implode( ',', $coupon_ids );
		}
		return $included_coupons_str;
	}

	/**
	 * Updates the database query with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_coupon_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_coupon_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$included_coupons = $this->get_included_coupons( $query_args );
		if ( $included_coupons ) {
			$sql_query_params['where_clause'] .= " AND {$order_coupon_lookup_table}.coupon_id IN ({$included_coupons})";
		}

		// TODO: questionable, I think we need order status filters, even though it's not specified.
		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}posts ON {$order_coupon_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
		}

		return $sql_query_params;
	}

	/**
	 * Enriches the coupon data with extra attributes.
	 *
	 * @param array $coupon_data Coupon data.
	 * @param array $query_args Query parameters.
	 */
	protected function include_extended_info( &$coupon_data, $query_args ) {
		if ( $query_args['extended_info'] ) {
			foreach ( $coupon_data as $idx => $coupon_datum ) {
				$coupon_id = $coupon_datum['coupon_id'];
				$coupon    = new WC_Coupon( $coupon_id );

				$gmt_timzone = new DateTimeZone( 'UTC' );

				$date_expires = $coupon->get_date_expires();
				if ( null === $date_expires ) {
					$date_expires     = '';
					$date_expires_gmt = '';
				} else {
					$date_expires     = $date_expires->format( WC_Admin_Reports_Interval::$iso_datetime_format );
					$date_expires_gmt = new DateTime( $date_expires );
					$date_expires_gmt->setTimezone( $gmt_timzone );
					$date_expires_gmt = $date_expires_gmt->format( WC_Admin_Reports_Interval::$iso_datetime_format );
				}

				$date_created = $coupon->get_date_created();
				if ( null === $date_created ) {
					$date_created     = '';
					$date_created_gmt = '';
				} else {
					$date_created     = $date_created->format( WC_Admin_Reports_Interval::$iso_datetime_format );
					$date_created_gmt = new DateTime( $date_created );
					$date_created_gmt->setTimezone( $gmt_timzone );
					$date_created_gmt = $date_created_gmt->format( WC_Admin_Reports_Interval::$iso_datetime_format );
				}

				$coupon_data[ $idx ]['extended_info'] = array(
					'code'             => $coupon->get_code(),
					'date_created'     => $date_created,
					'date_created_gmt' => $date_created_gmt,
					'date_expires'     => $date_expires,
					'date_expires_gmt' => $date_expires_gmt,
					'discount_type'    => $coupon->get_discount_type(),
				);
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
			'per_page'      => get_option( 'posts_per_page' ),
			'page'          => 1,
			'order'         => 'DESC',
			'orderby'       => 'date',
			'before'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'         => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'        => '*',
			'code'          => array(),
			'extended_info' => false,
			// This is not a parameter for coupons reports per se, but we want to only take into account selected order types.
			'order_status'  => parent::get_report_order_statuses(),

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
								coupon_id
							FROM
								{$table_name}
								{$sql_query_params['from_clause']}
							WHERE
								1=1
								{$sql_query_params['where_time_clause']}
								{$sql_query_params['where_clause']}
							GROUP BY
								coupon_id
					  		) AS tt"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$coupon_data = $wpdb->get_results(
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
						coupon_id
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $coupon_data ) {
				return $data;
			}

			$this->include_extended_info( $coupon_data, $query_args );

			$coupon_data = array_map( array( $this, 'cast_numbers' ), $coupon_data );
			$data         = (object) array(
				'data'    => $coupon_data,
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

}
