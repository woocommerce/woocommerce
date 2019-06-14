<?php
/**
 * WC_Admin_Reports_Taxes_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Taxes_Data_Store.
 */
class WC_Admin_Reports_Taxes_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_tax_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'tax_rate_id'  => 'intval',
		'name'         => 'strval',
		'tax_rate'     => 'floatval',
		'country'      => 'strval',
		'state'        => 'strval',
		'priority'     => 'intval',
		'total_tax'    => 'floatval',
		'order_tax'    => 'floatval',
		'shipping_tax' => 'floatval',
		'orders_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'tax_rate_id'  => 'tax_rate_id',
		'name'         => 'tax_rate_name as name',
		'tax_rate'     => 'tax_rate',
		'country'      => 'tax_rate_country as country',
		'state'        => 'tax_rate_state as state',
		'priority'     => 'tax_rate_priority as priority',
		'total_tax'    => 'SUM(total_tax) as total_tax',
		'order_tax'    => 'SUM(order_tax) as order_tax',
		'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
		'orders_count' => 'COUNT( DISTINCT ( CASE WHEN total_tax >= 0 THEN order_id END ) ) as orders_count',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious columns in SQL query.
		$this->report_columns['tax_rate_id']  = $table_name . '.' . $this->report_columns['tax_rate_id'];
		$this->report_columns['orders_count'] = str_replace( 'order_id', $table_name . '.order_id', $this->report_columns['orders_count'] );
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_reports_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 15 );
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args          Query arguments supplied by the user.
	 * @param string $order_status_filter Order status subquery.
	 * @return array                      Array of parameters used for SQL query.
	 */
	protected function get_from_sql_params( $query_args, $order_status_filter ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$sql_query['from_clause']       = '';
		$sql_query['outer_from_clause'] = '';

		if ( $order_status_filter ) {
			$sql_query['from_clause'] .= " JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
		}

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$sql_query['outer_from_clause'] .= " JOIN {$wpdb->prefix}woocommerce_tax_rates ON default_results.tax_rate_id = {$wpdb->prefix}woocommerce_tax_rates.tax_rate_id";
		} else {
			$sql_query['from_clause'] .= " JOIN {$wpdb->prefix}woocommerce_tax_rates ON {$table_name}.tax_rate_id = {$wpdb->prefix}woocommerce_tax_rates.tax_rate_id";
		}

		return $sql_query;
	}

	/**
	 * Updates the database query with parameters used for Taxes report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$order_tax_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params    = $this->get_time_period_sql_params( $query_args, $order_tax_lookup_table );
		$sql_query_params    = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params    = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );
		$order_status_filter = $this->get_status_subquery( $query_args );
		$sql_query_params    = array_merge( $sql_query_params, $this->get_from_sql_params( $query_args, $order_status_filter ) );

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes                     = implode( ',', $query_args['taxes'] );
			$sql_query_params['where_clause'] .= " AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})";
		}

		if ( $order_status_filter ) {
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
		}

		return $sql_query_params;
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
			'orderby'  => 'tax_rate_id',
			'before'   => WC_Admin_Reports_Interval::default_before(),
			'after'    => WC_Admin_Reports_Interval::default_after(),
			'fields'   => '*',
			'taxes'    => array(),
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

			$sql_query_params = $this->get_sql_query_params( $query_args );

			if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
				$total_results = count( $query_args['taxes'] );
				$total_pages   = (int) ceil( $total_results / $sql_query_params['per_page'] );

				$inner_selections = array( 'tax_rate_id', 'total_tax', 'order_tax', 'shipping_tax', 'orders_count' );
				$outer_selections = array( 'name', 'tax_rate', 'country', 'state', 'priority' );

				$selections      = $this->selected_columns( array( 'fields' => $inner_selections ) );
				$fields          = $this->get_fields( $query_args );
				$join_selections = $this->format_join_selections( $fields, array( 'tax_rate_id' ), $outer_selections );
				$ids_table       = $this->get_ids_table( $query_args['taxes'], 'tax_rate_id' );
				$prefix          = "SELECT {$join_selections} FROM (";
				$suffix          = ") AS {$table_name}";
				$right_join      = "RIGHT JOIN ( {$ids_table} ) AS default_results
					ON default_results.tax_rate_id = {$table_name}.tax_rate_id";
			} else {
				$db_records_count = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM (
								SELECT
									{$table_name}.tax_rate_id
								FROM
									{$table_name}
									{$sql_query_params['from_clause']}
								WHERE
									1=1
									{$sql_query_params['where_time_clause']}
									{$sql_query_params['where_clause']}
								GROUP BY
									{$table_name}.tax_rate_id
								) AS tt"
				); // WPCS: cache ok, DB call ok, unprepared SQL ok.

				$total_results = $db_records_count;
				$total_pages   = (int) ceil( $db_records_count / $sql_query_params['per_page'] );

				if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
					return $data;
				}

				$selections = $this->selected_columns( $query_args );

				$prefix     = '';
				$suffix     = '';
				$right_join = '';
			}

			$tax_data = $wpdb->get_results(
				"{$prefix}
						SELECT
							{$selections}
						FROM
							{$table_name}
							{$sql_query_params['from_clause']}
						WHERE
							1=1
							{$sql_query_params['where_time_clause']}
							{$sql_query_params['where_clause']}
						GROUP BY
							{$table_name}.tax_rate_id
					{$suffix}
					{$right_join}
					{$sql_query_params['outer_from_clause']}
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $tax_data ) {
				return $data;
			}

			$tax_data = array_map( array( $this, 'cast_numbers' ), $tax_data );
			$data     = (object) array(
				'data'    => $tax_data,
				'total'   => $total_results,
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

		if ( 'tax_code' === $order_by ) {
			return 'CONCAT_WS( "-", NULLIF(tax_rate_country, ""), NULLIF(tax_rate_state, ""), NULLIF(tax_rate_name, ""), NULLIF(tax_rate_priority, "") )';
		} elseif ( 'rate' === $order_by ) {
			return "CAST({$wpdb->prefix}woocommerce_tax_rates.tax_rate as DECIMAL(7,4))";
		}

		return $order_by;
	}

	/**
	 * Create or update an entry in the wc_order_tax_lookup table for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return int|bool Returns -1 if order won't be processed, or a boolean indicating processing success.
	 */
	public static function sync_order_taxes( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return -1;
		}

		$tax_items   = $order->get_items( 'tax' );
		$num_updated = 0;

		foreach ( $tax_items as $tax_item ) {
			$result = $wpdb->replace(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'order_id'     => $order->get_id(),
					'date_created' => $order->get_date_created( 'edit' )->date( WC_Admin_Reports_Interval::$sql_datetime_format ),
					'tax_rate_id'  => $tax_item->get_rate_id(),
					'shipping_tax' => $tax_item->get_shipping_tax_total(),
					'order_tax'    => $tax_item->get_tax_total(),
					'total_tax'    => $tax_item->get_tax_total() + $tax_item->get_shipping_tax_total(),
				),
				array(
					'%d',
					'%s',
					'%d',
					'%f',
					'%f',
					'%f',
				)
			);

			/**
			 * Fires when tax's reports are updated.
			 *
			 * @param int $tax_rate_id Tax Rate ID.
			 * @param int $order_id    Order ID.
			 */
			do_action( 'woocommerce_reports_update_tax', $tax_item->get_rate_id(), $order->get_id() );

			// Sum the rows affected. Using REPLACE can affect 2 rows if the row already exists.
			$num_updated += 2 === intval( $result ) ? 1 : intval( $result );
		}

		return ( count( $tax_items ) === $num_updated );
	}

	/**
	 * Clean taxes data when an order is deleted.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function sync_on_order_delete( $order_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ${table_name} WHERE order_id = %d",
				$order_id
			)
		);

		/**
		 * Fires when tax's reports are removed from database.
		 *
		 * @param int $tax_rate_id Tax Rate ID.
		 * @param int $order_id    Order ID.
		 */
		do_action( 'woocommerce_reports_delete_tax', 0, $order_id );
	}
}
