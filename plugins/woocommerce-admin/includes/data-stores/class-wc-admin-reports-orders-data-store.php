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
		'orders_count'        => 'intval',
		'num_items_sold'      => 'intval',
		'gross_revenue'       => 'floatval',
		'coupons'             => 'floatval',
		'refunds'             => 'floatval',
		'taxes'               => 'floatval',
		'shipping'            => 'floatval',
		'net_revenue'         => 'floatval',
		'avg_items_per_order' => 'floatval',
		'avg_order_value'     => 'floatval',
	);

	/**
	 * SQL definition for each column.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'orders_count'        => 'COUNT(*) as orders_count',
		'num_items_sold'      => 'SUM(num_items_sold) as num_items_sold',
		'gross_revenue'       => 'SUM(gross_total) AS gross_revenue',
		'coupons'             => 'SUM(coupon_total) AS coupons',
		'refunds'             => 'SUM(refund_total) AS refunds',
		'taxes'               => 'SUM(tax_total) AS taxes',
		'shipping'            => 'SUM(shipping_total) AS shipping',
		'net_revenue'         => 'SUM(net_total) AS net_revenue',
		'avg_items_per_order' => 'AVG(num_items_sold) AS avg_items_per_order',
		'avg_order_value'     => 'AVG(gross_total) AS avg_order_value',
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

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Admin_Order_Stats_Background_Process();
		}
	}

	/**
	 * Returns expected number of items on the page in case of date ordering.
	 *
	 * @param int $expected_interval_count Expected number of intervals in total.
	 * @param int $items_per_page          Number of items per page.
	 * @param int $page_no                 Page number.
	 *
	 * @return float|int
	 */
	protected function expected_intervals_on_page( $expected_interval_count, $items_per_page, $page_no ) {
		$total_pages = (int) ceil( $expected_interval_count / $items_per_page );
		if ( $page_no < $total_pages ) {
			return $items_per_page;
		} elseif ( $page_no === $total_pages ) {
			return $expected_interval_count - ( $page_no - 1 ) * $items_per_page;
		} else {
			return 0;
		}
	}

	/**
	 * Returns true if there are any intervals that need to be filled in the response.
	 *
	 * @param int    $expected_interval_count Expected number of intervals in total.
	 * @param int    $db_records              Total number of records for given period in the database.
	 * @param int    $items_per_page          Number of items per page.
	 * @param int    $page_no                 Page number.
	 * @param string $order                   asc or desc.
	 * @param string $order_by                Column by which the result will be sorted.
	 * @param int    $intervals_count         Number of records for given (possibly shortened) time interval.
	 *
	 * @return bool
	 */
	protected function intervals_missing( $expected_interval_count, $db_records, $items_per_page, $page_no, $order, $order_by, $intervals_count ) {
		if ( $expected_interval_count > $db_records ) {
			if ( 'date' === $order_by ) {
				$expected_intervals_on_page = $this->expected_intervals_on_page( $expected_interval_count, $items_per_page, $page_no );
				if ( $intervals_count < $expected_intervals_on_page ) {
					return true;
				} else {
					return false;
				}
			} else {
				if ( 'desc' === $order ) {
					if ( $page_no > floor( $db_records / $items_per_page ) ) {
						return true;
					} else {
						return false;
					}
				} elseif ( 'asc' === $order ) {
					if ( $page_no <= ceil( ( $expected_interval_count - $db_records ) / $items_per_page ) ) {
						return true;
					} else {
						return false;
					}
				} else {
					// Invalid ordering.
					return false;
				}
			}
		} else {
			return false;
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

		$where_clause = '';
		$from_clause  = '';

		$orders_stats_table = $wpdb->prefix . self::TABLE_NAME;
		$allowed_products   = $this->get_allowed_products( $query_args );

		if ( count( $allowed_products ) > 0 ) {
			$allowed_products_str = implode( ',', $allowed_products );

			$where_clause .= " AND {$orders_stats_table}.order_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}wc_order_product_lookup.order_id
			FROM
				{$wpdb->prefix}wc_order_product_lookup
			WHERE
				{$wpdb->prefix}wc_order_product_lookup.product_id IN ({$allowed_products_str})
			)";
		}

		if ( is_array( $query_args['coupons'] ) && count( $query_args['coupons'] ) > 0 ) {
			$allowed_coupons_str = implode( ', ', $query_args['coupons'] );

			$where_clause .= " AND {$orders_stats_table}.order_id IN (
			SELECT
				DISTINCT {$wpdb->prefix}wc_order_coupon_lookup.order_id
			FROM
				{$wpdb->prefix}wc_order_coupon_lookup
			WHERE
				{$wpdb->prefix}wc_order_coupon_lookup.coupon_id IN ({$allowed_coupons_str})
			)";
		}

		if ( is_array( $query_args['order_status'] ) && count( $query_args['order_status'] ) > 0 ) {
			$statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['order_status'] );

			$from_clause  .= " JOIN {$wpdb->prefix}posts ON {$orders_stats_table}.order_id = {$wpdb->prefix}posts.ID";
			$where_clause .= " AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $statuses ) . "' ) ";
		}

		// To avoid requesting the subqueries twice, the result is applied to all queries passed to the method.
		$totals_query['where_clause']    .= $where_clause;
		$totals_query['from_clause']     .= $from_clause;
		$intervals_query['where_clause'] .= $where_clause;
		$intervals_query['from_clause']  .= $from_clause;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @since 3.5.0
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
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'interval'     => 'week',
			'fields'       => '*',
			'categories'   => array(),
			'coupons'      => array(),
			'order_status' => parent::get_report_order_statuses(),
			'products'     => array(),
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
			$totals_query    = $this->get_time_period_sql_params( $query_args );
			$intervals_query = $this->get_intervals_sql_params( $query_args );

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
						{$totals_query['where_clause']}", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			if ( null === $totals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			// Specification says these are not included in totals.
			unset( $totals[0]['date_start'] );
			unset( $totals[0]['date_end'] );

			$totals = (object) $this->cast_numbers( $totals[0] );

			$db_intervals = $wpdb->get_col(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
						FROM
							{$table_name}
							{$intervals_query['from_clause']}
						WHERE
							1=1
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

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_interval_count, $expected_interval_count );

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
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval
						ORDER BY
							{$intervals_query['order_by_clause']}
						{$intervals_query['limit']}", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $intervals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			if ( $this->intervals_missing( $expected_interval_count, $db_interval_count, $intervals_query['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'], $intervals_query['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'] );
			} else {
				$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
			}
			$this->create_interval_subtotals( $data->intervals );

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Queue a background process that will repopulate the entire orders stats database.
	 *
	 * @todo Make this work on large DBs.
	 */
	public static function queue_order_stats_repopulate_database() {

		// This needs to be updated to work in batches instead of getting all orders, as
		// that will not work well on DBs with more than a few hundred orders.
		$order_ids = wc_get_orders( array(
			'limit'  => -1,
			'status' => parent::get_report_order_statuses(),
			'type'   => 'shop_order',
			'return' => 'ids',
		) );

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
			$wpdb->delete( $table_name, array(
				'order_id' => $order->get_id(),
			) );
			return;
		}

		$data = array(
			'order_id'       => $order->get_id(),
			'date_created'   => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'num_items_sold' => self::get_num_items_sold( $order ),
			'gross_total'    => $order->get_total(),
			'coupon_total'   => $order->get_total_discount(),
			'refund_total'   => $order->get_total_refunded(),
			'tax_total'      => $order->get_total_tax(),
			'shipping_total' => $order->get_shipping_total(),
			'net_total'      => $order->get_total() - $order->get_total_tax() - $order->get_shipping_total(),
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
}
