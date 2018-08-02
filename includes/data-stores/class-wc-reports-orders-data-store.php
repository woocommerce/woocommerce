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
		add_action( self::CRON_EVENT, array( __CLASS__, 'queue_update_recent_orders' ) );
		add_action( 'woocommerce_before_order_object_save', array( __CLASS__, 'queue_update_modified_orders' ) );
		add_action( 'shutdown', array( __CLASS__, 'dispatch_recalculator' ) );

		// Each hour update the DB with info for the previous hour.
		if ( ! wp_next_scheduled( self::CRON_EVENT ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d H:30:00' ) ), 'hourly', self::CRON_EVENT );
		}

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Order_Stats_Background_Process();
		}
	}

	protected function cast_numbers( $array ) {
		$type_for = array(
			'date_start'          => 'strval',
			'date_end'            => 'strval',
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
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @since 3.5.0
	 * @param array $query_args Query parameters.
	 * @return array            Data.
	 */
	public function get_data( $query_args ) {
		// TODO: split this humongous method.
		global $wpdb;

		$now       = new DateTime();
		$week      = DateInterval::createFromDateString( '7 days' );
		$week_back = $now->sub( $week );

		$defaults = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date',
			'before'   => $now->format( WC_Reports_Interval::$iso_datetime_format ),
			'after'    => $week_back->format( WC_Reports_Interval::$iso_datetime_format ),
			'interval' => 'week',
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$totals_query    = $this->get_totals_sql_params( $query_args );
			$intervals_query = $this->get_intervals_sql_params( $query_args );

			$selections = array(
				'date_start'          => 'MIN(start_time) AS date_start',
				'date_end'            => 'MAX(start_time) AS date_end',
				'orders_count'        => 'SUM(num_orders) as orders_count',
				'num_items_sold'      => 'SUM(num_items_sold) as num_items_sold',
				'gross_revenue'       => 'SUM(orders_gross_total) AS gross_revenue',
				'coupons'             => 'SUM(orders_coupon_total) AS coupons',
				'refunds'             => 'SUM(orders_refund_total) AS refunds',
				'taxes'               => 'SUM(orders_tax_total) AS taxes',
				'shipping'            => 'SUM(orders_shipping_total) AS shipping',
				'net_revenue'         => 'SUM(orders_net_total) AS net_revenue',
				'avg_items_per_order' => 'num_items_sold / num_orders AS avg_items_per_order',
				'avg_order_value'     => 'orders_gross_total / num_orders AS avg_order_value',
			);

			if ( isset( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
				$keep = array();
				foreach ( $query_args['fields'] as $field ) {
					if ( isset( $selections[ $field ] ) ) {
						$keep[ $field ] = $selections[ $field ];
					}
				}
				$selections = implode( ',', $keep );
			} else {
				$selections = implode( ',', $selections );
			}

			$table_name = $wpdb->prefix . self::TABLE_NAME;
			$totals     = $wpdb->get_results(
				"SELECT
							{$selections}
						FROM
							{$table_name}
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


			$db_intervals = $wpdb->get_col(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
						FROM
							{$table_name}
						WHERE
							1=1
							{$intervals_query['where_clause']}
						GROUP BY
							time_interval"
			); // WPCS: cache ok, DB call ok.
			$db_interval_count       = count( $db_intervals );
			$expected_interval_count = WC_Reports_Interval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );
			$total_pages             = (int) ceil( $expected_interval_count / $intervals_query['per_page'] );

			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return array();
			}

			$this->update_intervals_sql_params( $intervals_query, $query_args, $db_interval_count, $expected_interval_count );

			if ( '' !== $selections ) {
				$selections = ',' . $selections;
			}
			$intervals = $wpdb->get_results(
				"SELECT
							{$intervals_query['select_clause']} AS time_interval
							{$selections}
						FROM
							{$table_name}
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

			foreach ( $intervals as $key => $interval ) {
				$intervals[ $key ] = (object) $this->cast_numbers( $interval );
			}


			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			$this->update_dates( $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Queue a background process that will repopulate the entire orders stats database.
	 */
	public static function queue_order_stats_repopulate_database() {
		// To get the first start time, get the oldest order and round the completion time down to the nearest hour.
		$oldest = wc_get_orders( array(
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'ASC',
			'status'  => self::get_report_order_statuses(),
			'type'    => 'shop_order',
		) );

		$oldest = $oldest ? reset( $oldest ) : false;
		if ( ! $oldest ) {
			return;
		}

		$start_time = strtotime( $oldest->get_date_created()->format( 'Y-m-d\TH:00:00O' ) );
		$end_time   = $start_time + HOUR_IN_SECONDS;

		while ( $end_time < time() ) {
			self::$background_process->push_to_queue( $start_time );
			$start_time = $end_time;
			$end_time   = $start_time + HOUR_IN_SECONDS;
		}

		self::$background_process->save();
	}

	/**
	 * Queue a background process that will update the database with stats info from the last hour.
	 */
	public static function queue_update_recent_orders() {
		// Populate the stats information for the previous hour.
		$last_hour = strtotime( date( 'Y-m-d H:00:00' ) ) - HOUR_IN_SECONDS;
		self::$background_process->push_to_queue( $last_hour );
		self::$background_process->save();
	}

	/**
	 * Schedule a recalculation for an order's time when the order gets updated.
	 * This schedules for the order's current time and for the time the order used to be in if necessary.
	 *
	 * @param WC_Order $order Order that is in the process of getting modified.
	 */
	public static function queue_update_modified_orders( $order ) {
		$date_created = $order->get_date_created();
		if ( ! $date_created ) {
			return;
		}
		$new_time   = strtotime( $date_created->format( 'Y-m-d\TH:00:00O' ) );
		$old_time   = false;
		$new_status = $order->get_status();
		$old_status = false;

		if ( $order->get_id() ) {
			$old_order  = wc_get_order( $order->get_id() );
			$old_time   = strtotime( $old_order->get_date_created()->format( 'Y-m-d\TH:00:00O' ) );
			$old_status = $old_order->get_status();
		}

		$order_statuses = self::get_report_order_statuses();
		if ( in_array( $new_status, $order_statuses, true ) || in_array( $old_status, $order_statuses, true ) ) {
			self::$background_process->push_to_queue( $new_time );

			if ( $old_time && $new_time !== $old_time ) {
				self::$background_process->push_to_queue( $old_time );
			}

			self::$background_process->save();
		}
	}

	/**
	 * Kick off any scheduled data recalculations.
	 */
	public static function dispatch_recalculator() {
		self::$background_process->dispatch();
	}

	/**
	 * Get stats summary information for orders between two time frames.
	 *
	 * @param int $start_time Timestamp.
	 * @param int $end_time Timestamp.
	 * @return Array of stats.
	 */
	public static function summarize_orders( $start_time, $end_time ) {
		$summary = array(
			'num_orders'            => 0,
			'num_items_sold'        => 0,
			'orders_gross_total'    => 0.0,
			'orders_coupon_total'   => 0.0,
			'orders_refund_total'   => 0.0,
			'orders_tax_total'      => 0.0,
			'orders_shipping_total' => 0.0,
			'orders_net_total'      => 0.0,
		);

		$orders = wc_get_orders( array(
			'limit'        => -1,
			'type'         => 'shop_order',
			'orderby'      => 'none',
			'status'       => self::get_report_order_statuses(),
			'date_created' => $start_time . '...' . $end_time,
		) );

		$summary['num_orders']            = count( $orders );
		$summary['num_items_sold']        = self::get_num_items_sold( $orders );
		$summary['orders_gross_total']    = self::get_orders_gross_total( $orders );
		$summary['orders_coupon_total']   = self::get_orders_coupon_total( $orders );
		$summary['orders_refund_total']   = self::get_orders_refund_total( $orders );
		$summary['orders_tax_total']      = self::get_orders_tax_total( $orders );
		$summary['orders_shipping_total'] = self::get_orders_shipping_total( $orders );
		$summary['orders_net_total']      = $summary['orders_gross_total'] - $summary['orders_tax_total'] - $summary['orders_shipping_total'];

		return $summary;
	}

	/**
	 * Update the database with stats data.
	 *
	 * @param int   $start_time Timestamp.
	 * @param array $data Stats data.
	 * @return int/bool Number or rows modified or false on failure.
	 */
	public static function update( $start_time, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$start_time = date( 'Y-m-d H:00:00', $start_time );

		$defaults = array(
			'start_time'            => $start_time,
			'num_orders'            => 0,
			'num_items_sold'        => 0,
			'orders_gross_total'    => 0.0,
			'orders_coupon_total'   => 0.0,
			'orders_refund_total'   => 0.0,
			'orders_tax_total'      => 0.0,
			'orders_shipping_total' => 0.0,
			'orders_net_total'      => 0.0,
		);
		$data = wp_parse_args( $data, $defaults );

		// Update or add the information to the DB.
		return $wpdb->replace(
			$table_name,
			$data,
			array(
				'%s',
				'%d',
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
	 * @param array $orders Array of WC_Order objects.
	 * @return int
	 */
	protected static function get_num_items_sold( $orders ) {
		$num_items = 0;

		foreach ( $orders as $order ) {
			$line_items = $order->get_items( 'line_item' );
			foreach ( $line_items as $line_item ) {
				$num_items += $line_item->get_quantity();
			}
		}

		return $num_items;
	}

	/**
	 * Get the gross total for all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return float
	 */
	protected static function get_orders_gross_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_total();
		}

		return $total;
	}

	/**
	 * Get the coupon total for all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return float
	 */
	protected static function get_orders_coupon_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_discount_total();
		}

		return $total;
	}

	/**
	 * Get the refund total for all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return float
	 */
	protected static function get_orders_refund_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_total_refunded();
		}

		return $total;
	}

	/**
	 * Get the tax total for all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return float
	 */
	protected static function get_orders_tax_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_total_tax();
		}

		return $total;
	}

	/**
	 * Get the shipping total for all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return float
	 */
	protected static function get_orders_shipping_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_shipping_total();
		}

		return $total;
	}
}
