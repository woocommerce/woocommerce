<?php
/**
 * WC_Admin_Reports_Orders_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Admin_Order_Stats_Background_Process', false ) ) {
	include_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-order-stats-background-process.php';
}

/**
 * WC_Admin_Reports_Orders_Data_Store.
 *
 * @version  3.5.0
 */
class WC_Admin_Reports_Orders_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_stats';

	/**
	 * Cron event name.
	 */
	const CRON_EVENT = 'wc_order_stats_update';

	/**
	 * Type for each column to cast values correctly later.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'orders_count'            => 'intval',
		'num_items_sold'          => 'intval',
		'gross_revenue'           => 'floatval',
		'coupons'                 => 'floatval',
		'refunds'                 => 'floatval',
		'taxes'                   => 'floatval',
		'shipping'                => 'floatval',
		'net_revenue'             => 'floatval',
		'avg_items_per_order'     => 'floatval',
		'avg_order_value'         => 'floatval',
		'num_returning_customers' => 'intval',
		'num_new_customers'       => 'intval',
		'products'                => 'intval',
	);

	/**
	 * SQL definition for each column.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'orders_count'            => 'COUNT(*) as orders_count',
		'num_items_sold'          => 'SUM(num_items_sold) as num_items_sold',
		'gross_revenue'           => 'SUM(gross_total) AS gross_revenue',
		'coupons'                 => 'SUM(coupon_total) AS coupons',
		'refunds'                 => 'SUM(refund_total) AS refunds',
		'taxes'                   => 'SUM(tax_total) AS taxes',
		'shipping'                => 'SUM(shipping_total) AS shipping',
		'net_revenue'             => '( SUM(net_total) - SUM(refund_total) ) AS net_revenue',
		'avg_items_per_order'     => 'AVG(num_items_sold) AS avg_items_per_order',
		'avg_order_value'         => '( SUM(net_total) - SUM(refund_total) ) / COUNT(*) AS avg_order_value',
		'num_returning_customers' => 'SUM(returning_customer = 1) AS num_returning_customers',
		'num_new_customers'       => 'SUM(returning_customer = 0) AS num_new_customers',
	);

	/**
	 * Background process to populate order stats.
	 *
	 * @var WC_Admin_Order_Stats_Background_Process
	 */
	protected static $background_process;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! self::$background_process ) {
			self::$background_process = new WC_Admin_Order_Stats_Background_Process();
		}
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'sync_order' ) );
		// TODO: this is required as order update skips save_post.
		add_action( 'clean_post_cache', array( __CLASS__, 'sync_order' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'sync_order' ) );
		add_action( 'woocommerce_refund_deleted', array( __CLASS__, 'sync_on_refund_delete' ), 10, 2 );

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Admin_Order_Stats_Background_Process();
		}
	}

	/**
	 * Updates the totals and intervals database queries with parameters used for Orders report: categories, coupons and order status.
	 *
	 * @param array $query_args      Query arguments supplied by the user.
	 * @param array $totals_query    Array of options for totals db query.
	 * @param array $intervals_query Array of options for intervals db query.
	 */
	protected function orders_stats_sql_filter( $query_args, &$totals_query, &$intervals_query ) {
		// TODO: performance of all of this?
		global $wpdb;

		$from_clause        = '';
		$orders_stats_table = $wpdb->prefix . self::TABLE_NAME;
		$operator           = $this->get_match_operator( $query_args );

		$where_filters = array();

		// TODO: maybe move the sql inside the get_included/excluded functions?
		// Products filters.
		$included_products = $this->get_included_products( $query_args );
		$excluded_products = $this->get_excluded_products( $query_args );
		if ( $included_products ) {
			$where_filters[] = " {$orders_stats_table}.order_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}wc_order_product_lookup.order_id
			FROM
				{$wpdb->prefix}wc_order_product_lookup
			WHERE
				{$wpdb->prefix}wc_order_product_lookup.product_id IN ({$included_products})
			)";
		}

		if ( $excluded_products ) {
			$where_filters[] = " {$orders_stats_table}.order_id NOT IN (
			SELECT
				DISTINCT {$wpdb->prefix}wc_order_product_lookup.order_id
			FROM
				{$wpdb->prefix}wc_order_product_lookup
			WHERE
				{$wpdb->prefix}wc_order_product_lookup.product_id IN ({$excluded_products})
			)";
		}

		// Coupons filters.
		$included_coupons = $this->get_included_coupons( $query_args );
		$excluded_coupons = $this->get_excluded_coupons( $query_args );
		if ( $included_coupons ) {
			$where_filters[] = " {$orders_stats_table}.order_id IN (
				SELECT
					DISTINCT {$wpdb->prefix}wc_order_coupon_lookup.order_id
				FROM
					{$wpdb->prefix}wc_order_coupon_lookup
				WHERE
					{$wpdb->prefix}wc_order_coupon_lookup.coupon_id IN ({$included_coupons})
				)";
		}

		if ( $excluded_coupons ) {
			$where_filters[] = " {$orders_stats_table}.order_id NOT IN (
				SELECT
					DISTINCT {$wpdb->prefix}wc_order_coupon_lookup.order_id
				FROM
					{$wpdb->prefix}wc_order_coupon_lookup
				WHERE
					{$wpdb->prefix}wc_order_coupon_lookup.coupon_id IN ({$excluded_coupons})
				)";
		}

		// TODO: move order status to wc_order_stats so that JOIN is not necessary.
		$order_status_filter = $this->get_status_subquery( $query_args, $operator );
		if ( $order_status_filter ) {
			$from_clause    .= " JOIN {$wpdb->prefix}posts ON {$orders_stats_table}.order_id = {$wpdb->prefix}posts.ID";
			$where_filters[] = $order_status_filter;
		}

		$customer_filter = $this->get_customer_subquery( $query_args );
		if ( $customer_filter ) {
			$where_filters[] = $customer_filter;
		}

		$where_subclause = implode( " $operator ", $where_filters );

		// To avoid requesting the subqueries twice, the result is applied to all queries passed to the method.
		if ( $where_subclause ) {
			$totals_query['where_clause']    .= " AND ( $where_subclause )";
			$totals_query['from_clause']     .= $from_clause;
			$intervals_query['where_clause'] .= " AND ( $where_subclause )";
			$intervals_query['from_clause']  .= $from_clause;
		}
	}

	/**
	 * Returns SELECT clause statements to be used for segmenting query.
	 *
	 * @param array  $query_args Query arguments supplied by the user.
	 * @param string $segmenting_table SQL table containing the segmenting info.
	 *
	 * @return string SELECT clause statements.
	 */
	protected function segment_selections( $query_args, $segmenting_table ) {
		global $wpdb;
		$order_stats_table = $wpdb->prefix . 'wc_order_stats';
		// TODO: confirm with TeamX where to use numbers for full orders and where to use partial numbers for products within orders.
		$columns_mapping = array(
			'orders_count'            => "COUNT(DISTINCT($segmenting_table.order_id)) as orders_count",
			'num_items_sold'          => "SUM($segmenting_table.product_qty) as num_items_sold",
			'gross_revenue'           => "SUM($segmenting_table.product_gross_revenue) AS gross_revenue",
			'coupons'                 => "SUM($segmenting_table.coupon_amount) AS coupons",
			'refunds'                 => "SUM($segmenting_table.refund_amount) AS refunds",
			'taxes'                   => "SUM($segmenting_table.tax_amount) AS taxes",
			'shipping'                => "SUM($segmenting_table.shipping_amount) AS shipping",
			'net_revenue'             => "SUM($order_stats_table.net_total) AS net_revenue", // $segmenting_table.product_net_revenue.
			'avg_items_per_order'     => "SUM($order_stats_table.num_items_sold) / COUNT(DISTINCT($order_stats_table.order_id)) AS avg_items_per_order",
			'avg_order_value'         => "SUM($order_stats_table.net_total) / COUNT(DISTINCT($order_stats_table.order_id)) AS avg_order_value",
			'num_returning_customers' => "SUM($order_stats_table.returning_customer = 1) AS num_returning_customers",
			'num_new_customers'       => "SUM($order_stats_table.returning_customer = 0) AS num_new_customers",
		);

		if ( isset( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
			$keep = array();
			foreach ( $query_args['fields'] as $field ) {
				if ( isset( $columns_mapping[ $field ] ) ) {
					$keep[ $field ] = $columns_mapping[ $field ];
				}
			}
			$selections = implode( ', ', $keep );
		} else {
			$selections = implode( ', ', $columns_mapping );
		}
		return $selections;
	}

	/**
	 * Update row-level db result for segments in 'totals' section to the format used for output.
	 *
	 * @param array  $segments_db_result Results from the SQL db query for segmenting.
	 * @param string $segment_dimension Name of column used for grouping the result.
	 *
	 * @return array Reformatted array.
	 */
	protected function reformat_totals_segments( $segments_db_result, $segment_dimension ) {
		$segment_result = array();

		if ( strpos( $segment_dimension, '.' ) ) {
			$segment_dimension = substr( strstr( $segment_dimension, '.' ), 1 );
		}

		foreach ( $segments_db_result as $segment_data ) {
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$segment_datum    = array(
				'segment_id' => $segment_id,
				'subtotals'  => $this->cast_numbers( $segment_data ),
			);
			$segment_result[ $segment_id ] = $segment_datum;
		}

		return $segment_result;
	}

	/**
	 * Update row-level db result for segments in 'intervals' section to the format used for output.
	 *
	 * @param array  $segments_db_result Results from the SQL db query for segmenting.
	 * @param string $segment_dimension Name of column used for grouping the result.
	 *
	 * @return array Reformatted array.
	 */
	protected function reformat_intervals_segments( $segments_db_result, $segment_dimension ) {
		$aggregated_segment_result = array();

		if ( strpos( $segment_dimension, '.' ) ) {
			$segment_dimension = substr( strstr( $segment_dimension, '.' ), 1 );
		}

		foreach ( $segments_db_result as $segment_data ) {
			$time_interval = $segment_data['time_interval'];
			if ( ! isset( $aggregated_segment_result[ $time_interval ] ) ) {
				$aggregated_segment_result[ $time_interval ]             = array();
				$aggregated_segment_result[ $time_interval ]['segments'] = array();
			}
			unset( $segment_data['time_interval'] );
			unset( $segment_data['datetime_anchor'] );
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$segment_datum = array(
				'segment_id' => $segment_id,
				'subtotals'  => $this->cast_numbers( $segment_data ),
			);
			$aggregated_segment_result[ $time_interval ]['segments'][ $segment_id ] = $segment_datum;
		}

		return $aggregated_segment_result;
	}

	/**
	 * Return all segments for given segmentby parameter.
	 *
	 * @param array $query_args Query args provided by the user.
	 *
	 * @return array|void
	 */
	protected function get_all_segments( $query_args ) {
		global $wpdb;
		if ( ! isset( $query_args['segmentby'] ) ) {
			return;
		}

		if ( 'product' === $query_args['segmentby'] ) {
			$segments = wc_get_products(
				array(
					'return' => 'ids',
					'limit'  => -1,
				)
			);
		} elseif ( 'variation' === $query_args['segmentby'] ) {
			// TODO: assuming that this will only be used for one product, check assumption.
			if ( ! isset( $query_args['products'] ) || count( $query_args['products'] ) !== 1 ) {
				return;
			}

			$segments = wc_get_products(
				array(
					'return' => 'ids',
					'limit'  => - 1,
					'type'   => 'variation',
					'parent' => $query_args['products'][0],
				)
			);
		} elseif ( 'category' === $query_args['segmentby'] ) {
			$categories = get_categories(
				array(
					'taxonomy' => 'product_cat',
				)
			);
			$segments   = wp_list_pluck( $categories, 'cat_ID' );
		} elseif ( 'tax' === $query_args['segmentby'] ) {
			// There are two types of taxes: taxes that are currently in the db and taxes that were used in sold products before.
			// Taxes from the db.
			$current_taxes = $wpdb->get_results( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates", ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			$current_taxes = wp_list_pluck( $current_taxes, 'tax_rate_id' );
			// taxes from sold products.
			$sold_taxes = $wpdb->get_results( "SELECT DISTINCT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='rate_id'", ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			$sold_taxes = wp_list_pluck( $sold_taxes, 'meta_value' );
			$segments   = array_unique( array_merge( $current_taxes, $sold_taxes ) );
		} elseif ( 'coupon' === $query_args['segmentby'] ) {
			$coupon_ids = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='shop_coupon' AND post_status='publish'", ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			$segments   = wp_list_pluck( $coupon_ids, 'ID' );
		} elseif ( 'customer_type' === $query_args['segmentby'] ) {
			// 0 -- new customer
			// 1 -- returning customer
			$segments = array( 0, 1 );
		}

		return $segments;
	}

	/**
	 * Adds zeroes for segments not present in the data selection.
	 *
	 * @param array $query_args Query arguments provided by the user.
	 * @param array $segments Array of segments from the database for given data points.
	 * @param array $all_segment_ids Array of all possible segment ids.
	 *
	 * @return array
	 */
	protected function fill_in_missing_segments( $query_args, $segments, $all_segment_ids ) {

		$segment_subtotals = array();
		if ( isset( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
			foreach ( $query_args['fields'] as $field ) {
				if ( isset( $this->report_columns[ $field ] ) ) {
					$segment_subtotals[ $field ] = 0;
				}
			}
		} else {
			foreach ( $this->report_columns as $field ) {
				$segment_subtotals[ $field ] = 0;
			}
		}
		if ( ! is_array( $segments ) ) {
			$segments = array();
		}
		foreach ( $all_segment_ids as $segment_id ) {
			if ( ! isset( $segments[ $segment_id ] ) ) {
				$segments[ $segment_id ] = array(
					'segment_id' => $segment_id,
					'subtotals'  => $segment_subtotals,
				);
			}
		}

		// Using array_values to remove custom keys, so that it gets later converted to JSON as an array.
		return array_values( $segments );
	}

	/**
	 * Adds missing segments to intervals, modifies $data.
	 *
	 * @param array    $query_args Query arguments provided by the user.
	 * @param stdClass $data Response data.
	 * @param array    $all_segment_ids Array of all possible segment ids.
	 */
	protected function fill_in_missing_interval_segments( $query_args, &$data, $all_segment_ids ) {
		foreach ( $data->intervals as $order_id => $interval_data ) {
			$data->intervals[ $order_id ]['segments'] = $this->fill_in_missing_segments( $query_args, $data->intervals[ $order_id ]['segments'], $all_segment_ids );
		}
	}

	/**
	 * Retrieves and returns segments for 'totals' section of the result.
	 *
	 * @param array  $query_args Query arguments provided by the user.
	 * @param array  $totals_query Array containing the SQL clauses for 'totals' section of the result.
	 * @param string $table_name SQL table name used by the data store.
	 * @param array  $all_segments Array of all possible segment ids.
	 *
	 * @return array|void Array of segments for totals section of the result.
	 */
	protected function get_totals_segments( $query_args, $totals_query, $table_name, $all_segments ) {
		global $wpdb;
		if ( ! isset( $query_args['segmentby'] ) ) {
			return;
		}

		$segmenting_table   = $wpdb->prefix . 'wc_order_product_lookup';
		$segmenting_from    = '';
		$segmenting_where   = '';
		$segmenting_groupby = '';
		if ( 'product' === $query_args['segmentby'] ) {
			$segmenting_groupby = $segmenting_table . '.product_id';
		} elseif ( 'variation' === $query_args['segmentby'] ) {
			$segmenting_groupby = $segmenting_table . '.variation_id';
		} elseif ( 'category' === $query_args['segmentby'] ) {
			// If we wanted category name as well, then we need to add the following:
			// LEFT JOIN wp_terms ON wp_term_taxonomy.term_id = wp_terms.term_id, then wp_terms.name is category name
			// RIGHT JOIN so that all categories are added.
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}term_relationships ON {$segmenting_table}.product_id = {$wpdb->prefix}term_relationships.object_id
			RIGHT JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
			";
			$segmenting_where   = " AND taxonomy = 'product_cat'";
			$segmenting_groupby = 'wp_term_taxonomy.term_taxonomy_id';
		} elseif ( 'tax' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ($segmenting_table.order_id = order_items.order_id) 
			";
			$segmenting_where   = " AND (order_items.order_item_type = 'tax')";
			$segmenting_groupby = 'order_items.order_item_name';
		} elseif ( 'coupon' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			INNER JOIN {$wpdb->prefix}wc_order_coupon_lookup AS coupon_lookup ON ($segmenting_table.order_id = coupon_lookup.order_id)
            ";
			$segmenting_groupby = 'coupon_lookup.coupon_id';
		} elseif ( 'customer_type' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}wc_order_stats AS customer_type_tbl ON ($segmenting_table.order_id = customer_type_tbl.order_id) 
			";
			$segmenting_groupby = 'customer_type_tbl.returning_customer';
		}

		$segment_selections = $this->segment_selections( $query_args, $segmenting_table );
		$totals_segments    = $wpdb->get_results(
			"SELECT
						$segment_selections,
						$segmenting_groupby
					FROM
						$table_name
						INNER JOIN $segmenting_table ON ($table_name.order_id = $segmenting_table.order_id)
						$segmenting_from
						{$totals_query['from_clause']}
					WHERE
						1=1
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}
						$segmenting_where
					GROUP BY
						$segmenting_groupby",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.
		// Reformat result.
		$totals_segments = $this->reformat_totals_segments( $totals_segments, $segmenting_groupby );

		$totals_segments = $this->fill_in_missing_segments( $query_args, $totals_segments, $all_segments );

		return $totals_segments;
	}

	/**
	 * Assign segments to time intervals by updating original $intervals array.
	 *
	 * @param array $intervals Result array from intervals SQL query.
	 * @param array $intervals_segments Result array from interval segments SQL query.
	 */
	protected function assign_segments_to_intervals( &$intervals, $intervals_segments ) {
		$old_keys = array_keys( $intervals );
		foreach ( $intervals as $interval ) {
			$intervals[ $interval['time_interval'] ] = $interval;
		}
		foreach ( $old_keys as $key ) {
			unset( $intervals[ $key ] );
		}

		foreach ( $intervals_segments as $time_interval => $segment ) {
			if ( ! isset( $intervals[ $time_interval ] ) ) {
				$intervals[ $time_interval ]['segments'] = array();
			}
			$intervals[ $time_interval ]['segments'] = $segment['segments'];
		}
	}

	/**
	 * Retrieves and returns segments for 'intervals' section of the result.
	 *
	 * @param array  $query_args Query arguments provided by the user.
	 * @param array  $intervals_query Array containing the SQL clauses for 'intervals' section of the result.
	 * @param string $table_name SQL table name used by the data store.
	 *
	 * @return array|void Array of segments for intervals section of the result.
	 */
	protected function get_intervals_segments( $query_args, $intervals_query, $table_name ) {
		global $wpdb;
		if ( ! isset( $query_args['segmentby'] ) ) {
			return;
		}

		$segmenting_table   = $wpdb->prefix . 'wc_order_product_lookup';
		$segmenting_from    = '';
		$segmenting_where   = '';
		$segmenting_groupby = '';
		if ( 'product' === $query_args['segmentby'] ) {
			$segmenting_groupby = $segmenting_table . '.product_id';
		} elseif ( 'variation' === $query_args['segmentby'] ) {
			$segmenting_groupby = $segmenting_table . '.variation_id';
		} elseif ( 'category' === $query_args['segmentby'] ) {
			// If we wanted category name as well, then we need to add the following:
			// LEFT JOIN wp_terms ON wp_term_taxonomy.term_id = wp_terms.term_id, then wp_terms.name is category name
			// RIGHT JOIN so that all categories are added.
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}term_relationships ON {$segmenting_table}.product_id = {$wpdb->prefix}term_relationships.object_id
			RIGHT JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
			";
			$segmenting_where   = " AND taxonomy = 'product_cat'";
			$segmenting_groupby = 'wp_term_taxonomy.term_taxonomy_id';
		} elseif ( 'tax' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ($segmenting_table.order_id = order_items.order_id) 
			";
			$segmenting_where   = " AND (order_items.order_item_type = 'tax')";
			$segmenting_groupby = 'order_items.order_item_name';
		} elseif ( 'coupon' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			INNER JOIN {$wpdb->prefix}wc_order_coupon_lookup AS coupon_lookup ON ($segmenting_table.order_id = coupon_lookup.order_id)
            ";
			$segmenting_groupby = 'coupon_lookup.coupon_id';
		} elseif ( 'customer_type' === $query_args['segmentby'] ) {
			$segmenting_from    = "
			LEFT JOIN {$wpdb->prefix}wc_order_stats AS customer_type_tbl ON ($segmenting_table.order_id = customer_type_tbl.order_id) 
			";
			$segmenting_groupby = 'customer_type_tbl.returning_customer';
		}

		// TODO: finish limit update.
		$segmenting_limit = '';

		$segment_selections = $this->segment_selections( $query_args, $segmenting_table );
		$intervals_segments = $wpdb->get_results(
			"SELECT
						MAX($table_name.date_created) AS datetime_anchor,
						{$intervals_query['select_clause']} AS time_interval,
						$segment_selections,
						$segmenting_groupby
					FROM
						$table_name
						INNER JOIN $segmenting_table ON ($table_name.order_id = $segmenting_table.order_id)
						$segmenting_from
						{$intervals_query['from_clause']}
					WHERE
						1=1
						{$intervals_query['where_time_clause']}
						{$intervals_query['where_clause']}
						$segmenting_where
					GROUP BY
						time_interval, $segmenting_groupby
					$segmenting_limit",
			ARRAY_A
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		// Reformat result.
		$intervals_segments = $this->reformat_intervals_segments( $intervals_segments, $segmenting_groupby );

		// Missing segments will be filled once all the intervals are added.
		return $intervals_segments;
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

		// These defaults are only applied when not using REST API, as the API has its own defaults that overwrite these for most values (except before, after, etc).
		$defaults   = array(
			'per_page'         => get_option( 'posts_per_page' ),
			'page'             => 1,
			'order'            => 'DESC',
			'orderby'          => 'date',
			'before'           => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'            => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'interval'         => 'week',
			'fields'           => '*',
			'segmentby'        => '',

			'match'            => 'all',
			'status_is'        => array(),
			'status_is_not'    => array(),
			'product_includes' => array(),
			'product_excludes' => array(),
			'coupon_includes'  => array(),
			'coupon_excludes'  => array(),
			'customer'         => '',
			'categories'       => array(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'totals'    => (object) array(),
				'intervals' => (object) array(),
				'total'     => 0,
				'pages'     => 0,
				'page_no'   => 0,
			);

			$selections      = $this->selected_columns( $query_args );
			$totals_query    = $this->get_time_period_sql_params( $query_args, $table_name );
			$intervals_query = $this->get_intervals_sql_params( $query_args, $table_name );

			// Additional filtering for Orders report.
			$this->orders_stats_sql_filter( $query_args, $totals_query, $intervals_query );

			$totals = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$totals_query['from_clause']}
					WHERE
						1=1
						{$totals_query['where_time_clause']}
						{$totals_query['where_clause']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			if ( null === $totals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
			}

			$unique_products       = $this->get_unique_product_count( $totals_query['from_clause'], $totals_query['where_time_clause'], $totals_query['where_clause'] );
			$totals[0]['products'] = $unique_products;

			$all_segments = $this->get_all_segments( $query_args );
			$totals[0]['segments'] = $this->get_totals_segments( $query_args, $totals_query, $table_name, $all_segments );

			$totals = (object) $this->cast_numbers( $totals[0] );

			$db_intervals = $wpdb->get_col(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						WHERE
							1=1
							{$intervals_query['where_time_clause']}
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval"
			); // WPCS: cache ok, DB call ok, , unprepared SQL ok.

			$db_interval_count       = count( $db_intervals );
			$expected_interval_count = WC_Admin_Reports_Interval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $intervals_query['per_page'] );

			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_interval_count, $expected_interval_count, $table_name );

			if ( '' !== $selections ) {
				$selections = ', ' . $selections;
			}

			$intervals = $wpdb->get_results(
				"SELECT
							MAX(date_created) AS datetime_anchor,
							{$intervals_query['select_clause']} AS time_interval
							{$selections}
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						WHERE
							1=1
							{$intervals_query['where_time_clause']}
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval
						ORDER BY
							{$intervals_query['order_by_clause']}
						{$intervals_query['limit']}",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $intervals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'wc-admin' ) );
			}

			$intervals_segments = $this->get_intervals_segments( $query_args, $intervals_query, $table_name );

			// Pigeon hole segments.
			$this->assign_segments_to_intervals( $intervals, $intervals_segments );

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			if ( WC_Admin_Reports_Interval::intervals_missing( $expected_interval_count, $db_interval_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'] );
				$this->fill_in_missing_interval_segments( $query_args, $data, $all_segments );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$this->create_interval_subtotals( $data->intervals );

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Get unique products based on user time query
	 *
	 * @param string $from_clause       From clause with date query.
	 * @param string $where_time_clause Where clause with date query.
	 * @param string $where_clause      Where clause with date query.
	 * @return integer Unique product count.
	 */
	public function get_unique_product_count( $from_clause, $where_time_clause, $where_clause ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		return $wpdb->get_var(
			"SELECT
					COUNT( DISTINCT {$wpdb->prefix}wc_order_product_lookup.product_id )
				FROM
				    {$wpdb->prefix}wc_order_product_lookup JOIN {$table_name} ON {$wpdb->prefix}wc_order_product_lookup.order_id = {$table_name}.order_id
					{$from_clause}
				WHERE
					1=1
					{$where_time_clause}
					{$where_clause}"
		); // WPCS: cache ok, DB call ok, unprepared SQL ok.
	}

	/**
	 * Queue a background process that will repopulate the entire orders stats database.
	 *
	 * @todo Make this work on large DBs.
	 */
	public static function queue_order_stats_repopulate_database() {

		// This needs to be updated to work in batches instead of getting all orders, as
		// that will not work well on DBs with more than a few hundred orders.
		$order_ids = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => parent::get_report_order_statuses(),
				'type'   => 'shop_order',
				'return' => 'ids',
			)
		);

		foreach ( $order_ids as $id ) {
			self::$background_process->push_to_queue( $id );
		}

		self::$background_process->save();
		self::$background_process->dispatch();
	}

	/**
	 * Add order information to the lookup table when orders are created or modified.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function sync_order( $post_id ) {
		if ( 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		self::update( $order );
	}

	/**
	 * Syncs order information when a refund is deleted.
	 *
	 * @param int $refund_id Refund ID.
	 * @param int $order_id Order ID.
	 */
	public static function sync_on_refund_delete( $refund_id, $order_id ) {
		self::sync_order( $order_id );
	}

	/**
	 * Update the database with stats data.
	 *
	 * @param WC_Order $order Order to update row for.
	 * @return int|bool|null Number or rows modified or false on failure.
	 */
	public static function update( $order ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		if ( ! $order->get_id() || ! $order->get_date_created() ) {
			return false;
		}

		if ( ! in_array( $order->get_status(), parent::get_report_order_statuses(), true ) ) {
			$wpdb->delete(
				$table_name,
				array(
					'order_id' => $order->get_id(),
				)
			);
			return;
		}

		$data = array(
			'order_id'           => $order->get_id(),
			'date_created'       => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'num_items_sold'     => self::get_num_items_sold( $order ),
			'gross_total'        => $order->get_total(),
			'coupon_total'       => $order->get_total_discount(),
			'refund_total'       => $order->get_total_refunded(),
			'tax_total'          => $order->get_total_tax(),
			'shipping_total'     => $order->get_shipping_total(),
			'net_total'          => (float) $order->get_total() - (float) $order->get_total_tax() - (float) $order->get_shipping_total(),
			'returning_customer' => self::is_returning_customer( $order ),
		);

		// Update or add the information to the DB.
		return $wpdb->replace(
			$table_name,
			$data,
			array(
				'%d',
				'%s',
				'%d',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
			)
		);
	}

	/**
	 * Calculation methods.
	 */

	/**
	 * Get number of items sold among all orders.
	 *
	 * @param array $order WC_Order object.
	 * @return int
	 */
	protected static function get_num_items_sold( $order ) {
		$num_items = 0;

		$line_items = $order->get_items( 'line_item' );
		foreach ( $line_items as $line_item ) {
			$num_items += $line_item->get_quantity();
		}

		return $num_items;
	}

	/**
	 * Check to see if an order's customer has made previous orders or not
	 *
	 * @param array $order WC_Order object.
	 * @return bool
	 */
	protected static function is_returning_customer( $order ) {
		$customer_id = $order->get_user_id();

		if ( 0 === $customer_id ) {
			return false;
		}

		$customer_orders = get_posts(
			array(
				'meta_key'    => '_customer_user', // WPCS: slow query ok.
				'meta_value'  => $customer_id, // WPCS: slow query ok.
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-on-hold', 'wc-processing', 'wc-completed' ),
				'numberposts' => 2,
			)
		);

		return count( $customer_orders ) > 1;
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
