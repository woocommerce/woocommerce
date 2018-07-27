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
	 * Setup class.
	 */
	public function __construct() {
		add_action( self::CRON_EVENT, array( $this, 'queue_update_recent_orders' ) );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'queue_update_modified_orders' ) );
		add_action( 'shutdown', array( $this, 'dispatch_recalculator' ) );

		// Each hour update the DB with info for the previous hour.
		if ( ! wp_next_scheduled( self::CRON_EVENT ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d H:30:00' ) ), 'hourly', self::CRON_EVENT );
		}

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Order_Stats_Background_Process();
		}
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

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$totals_query    = $this->get_totals_sql_params( $query_args );
			$intervals_query = $this->get_intervals_sql_params( $query_args );

			$selections = array(
				'date_start'            => 'MIN(start_time) AS date_start',
				'date_end'              => 'MAX(start_time) AS date_end',
				'num_orders'            => 'SUM(num_orders) as num_orders',
				'num_items_sold'        => 'SUM(num_items_sold) as num_items_sold',
				'orders_gross_total'    => 'SUM(orders_gross_total) AS orders_gross_total',
				'orders_coupon_total'   => 'SUM(orders_coupon_total) AS orders_coupon_total',
				'orders_refund_total'   => 'SUM(orders_refund_total) AS orders_refund_total',
				'orders_tax_total'      => 'SUM(orders_tax_total) AS orders_tax_total',
				'orders_shipping_total' => 'SUM(orders_shipping_total) AS orders_shipping_total',
				'orders_net_total'      => 'SUM(orders_net_total) AS orders_net_total',
				'avg_items_per_order'   => 'num_items_sold / num_orders AS avg_items_per_order',
				'avg_order_value'       => 'orders_gross_total / num_orders AS avg_order_value',
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
							{$totals_query['where_clause']}"); // WPCS: cache ok, DB call ok.

			$db_records_within_interval = $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								{$intervals_query['select_clause']} AS time_interval
							FROM 
								{$table_name}
							WHERE 
								1=1
								{$intervals_query['where_clause']}
							GROUP BY
								time_interval
							LIMIT 0, {$intervals_query['per_page']}
							) AS tt"
			); // WPCS: cache ok, DB call ok.

			$this->update_intervals_sql_params( $intervals_query, $db_records_within_interval );

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
						LIMIT {$intervals_query['offset']}, {$intervals_query['per_page']}"
			); // WPCS: cache ok, DB call ok.

			if ( ! $totals || ! $intervals ) {
				return new WP_Error( 'woocommerce_reports_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
			}

			$data = (object) array(
				'totals'    => $totals[0],
				'intervals' => $intervals,
			);

			if ( $db_records_within_interval < $intervals_query['per_page'] ) {
				$this->fill_in_missing_intervals( $query_args['after'], $query_args['before'], $query_args['interval'], $data );
				$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
				$this->remove_extra_records( $data, $query_args['page'] - 1, $intervals_query['per_page'] );
			} else {
				$this->update_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data );
			}

			wp_cache_set( $cache_key, $data, $this->cache_group, 3600 );
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
		$end_time = $start_time + HOUR_IN_SECONDS;

		while ( $end_time < time() ) {
			self::$background_process->push_to_queue( $start_time );
			$start_time = $end_time;
			$end_time = $start_time + HOUR_IN_SECONDS;
		}

		self::$background_process->save();
	}

	/**
	 * Queue a background process that will update the database with stats info from the last hour.
	 */
	public function queue_update_recent_orders() {
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
	public function queue_update_modified_orders( $order ) {
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
	public function dispatch_recalculator() {
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

		// Don't store rows that don't have useful information.
		// @todo maybe remove this when/if on-the-fly generation is implemented.
		if ( ! $data['num_orders'] ) {
			return $wpdb->delete(
				$table_name,
				array(
					'start_time' => $start_time,
				),
				array(
					'%s',
				)
			);
		}

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
