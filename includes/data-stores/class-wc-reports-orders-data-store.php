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
//		add_action( 'woocommerce_before_order_object_save', array( __CLASS__, 'queue_update_modified_orders' ) );

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

		if ( true || false === $data ) {
			$totals_query    = $this->get_totals_sql_params( $query_args );
			$intervals_query = $this->get_intervals_sql_params( $query_args );

			// todo
			$selections = array(
				'date_start'          => 'MIN(date_created) AS date_start',
				'date_end'            => 'MAX(date_created) AS date_end',
				//'orders_count'        => 'SUM(num_orders) as orders_count',
				'num_items_sold'      => 'SUM(num_items_sold) as num_items_sold',
				'gross_revenue'       => 'SUM(gross_total) AS gross_revenue',
				'coupons'             => 'SUM(coupon_total) AS coupons',
				'refunds'             => 'SUM(refund_total) AS refunds',
				'taxes'               => 'SUM(tax_total) AS taxes',
				'shipping'            => 'SUM(shipping_total) AS shipping',
				'net_revenue'         => 'SUM(net_total) AS net_revenue',
			//	'avg_items_per_order' => 'num_items_sold / num_orders AS avg_items_per_order',
			//	'avg_order_value'     => 'gross_total / num_orders AS avg_order_value',
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

			$db_interval_count = (int) $wpdb->get_var(
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
				'total'     => $db_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args['page'],
			);

			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data );
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

		$data = array(
			'order_id' => $order->get_id(),
			'date_created' => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'num_items_sold' => self::get_num_items_sold( $order ),
			'gross_total' => $order->get_total(),
			'coupon_total' => $order->get_total_discount(),
			'refund_total' => $order->get_total_refunded(),
			'tax_total' => $order->get_total_tax(),
			'shipping_total' => $order->get_shipping_total(),
			'net_total' => $order->get_total() - $order->get_total_tax() - $order->get_shipping_total(),
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
