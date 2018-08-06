<?php
/**
 * WC_Reports_Orders_Data_Store class file.
 *
 * @package WooCommerce/Classes
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Order_Stats_Background_Process', false ) ) {
	include_once WC_ABSPATH . 'includes/class-wc-order-stats-background-process.php';
}

/**
 * WC_Reports_Orders_Data_Store.
 *
 * @version  3.5.0
 */
class WC_Reports_Orders_Data_Store extends WC_Reports_Data_Store implements WC_Reports_Data_Store_Interface {

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
	 * Background process to populate order stats.
	 *
	 * @var WC_Order_Stats_Background_Process
	 */
	protected static $background_process;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! self::$background_process ) {
			self::$background_process = new WC_Order_Stats_Background_Process();
		}
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'sync_order' ) );

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Order_Stats_Background_Process();
		}
	}

	/**
	 * Casts strings returned from the database to appropriate data types for output.
	 *
	 * @param array $array Associative array of values extracted from the database.
	 * @return array|WP_Error
	 */
	protected function cast_numbers( $array ) {
		$type_for      = array(
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
		$retyped_array = array();
		foreach ( $array as $key => $value ) {
			if ( isset( $type_for[ $key ] ) ) {
				$retyped_array[ $key ] = $type_for[ $key ]( $value );
			} else {
				$retyped_array[ $key ] = $value;
			}
		}
		return $retyped_array;
	}

	/**
	 * Returns an array of products belonging to given categories.
	 *
	 * @param $categories
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
		if ( count( $query_args['categories'] ) > 0 ) {
			$allowed_products     = $this->get_products_by_cat_ids( $query_args['categories'] );
			$allowed_product_ids  = wp_list_pluck( $allowed_products, 'id' );
			$allowed_products_str = implode( ',', $allowed_product_ids );

			$where_clause .= " AND {$orders_stats_table}.order_id IN ( 
			SELECT 
				DISTINCT {$wpdb->prefix}wc_order_product_lookup.order_id
			FROM 
				{$wpdb->prefix}wc_order_product_lookup
			WHERE 
				{$wpdb->prefix}wc_order_product_lookup.product_id IN ({$allowed_products_str})
			)";
		}

		if ( count( $query_args['coupons'] ) > 0 ) {
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

		if ( '' !== $query_args['order_status'] ) {
			$from_clause  .= " JOIN {$wpdb->prefix}posts ON {$orders_stats_table}.order_id = {$wpdb->prefix}posts.ID";
			$where_clause .= " AND {$wpdb->prefix}posts.post_status = 'wc-{$query_args['order_status']}'";
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
	 * @param array $query_args Query parameters.
	 * @return array            Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only applied when not using REST API, as the API has its own defaults that overwrite these.
		$defaults   = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Reports_Interval::$iso_datetime_format, $week_back ),
			'interval'     => 'week',
			'fields'       => '*',
			'categories'   => array(),
			'coupons'      => array(),
			'order_status' => 'completed',
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {

			$selections = array(
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

			if ( isset( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
				$keep = array();
				foreach ( $query_args['fields'] as $field ) {
					if ( isset( $selections[ $field ] ) ) {
						$keep[ $field ] = $selections[ $field ];
					}
				}
				$selections = implode( ', ', $keep );
			} else {
				$selections = implode( ', ', $selections );
			}

			$totals_query    = $this->get_totals_sql_params( $query_args );
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
			); // WPCS: cache ok, DB call ok.

			if ( null === $totals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			// Specification says these are not included in totals.
			unset( $totals[0]['date_start'] );
			unset( $totals[0]['date_end'] );

			$totals = (object) $this->cast_numbers( $totals[0] );

			$db_interval_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								{$intervals_query['select_clause']} AS time_interval
							FROM
								{$table_name}
								{$intervals_query['from_clause']}
							WHERE
								1=1
								{$intervals_query['where_clause']}
							GROUP BY
								time_interval
					  		) AS tt"
			); // WPCS: cache ok, DB call ok.

			$total_pages = (int) ceil( $db_interval_count / $intervals_query['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			if ( '' !== $selections ) {
				$selections = ',' . $selections;
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
			); // WPCS: cache ok, DB call ok.

			if ( null === $intervals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $intervals );
			$this->create_interval_subtotals( $intervals );

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $db_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

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
			'status' => self::get_report_order_statuses(),
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
	 * @param array $data Stats data.
	 * @return int/bool Number or rows modified or false on failure.
	 */
	public static function update( $order ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		if ( ! $order->get_id() || ! $order->get_date_created() ) {
			return false;
		}

		if ( ! in_array( $order->get_status(), self::get_report_order_statuses() ) ) {
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
	 * Get the order statuses used when calculating reports.
	 *
	 * @return array
	 */
	protected static function get_report_order_statuses() {
		return apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) );
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
