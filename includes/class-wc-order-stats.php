<?php
/**
 * WooCommerce Orders Stats
 *
 * Handles the orders stats database and API.
 *
 * @package WooCommerce/Classes
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Order_Stats_Background_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/class-wc-order-stats-background-process.php';
}

/**
 * Order stats class.
 */
class WC_Order_Stats {

	const TABLE_NAME = 'wc_order_stats';
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
		add_action( 'init', array( $this, 'install' ) ); // @todo Don't do this on every page load.
		add_action( self::CRON_EVENT, array( $this, 'queue_update_recent_orders' ) );
		// @todo Need to also handle orders whose 'Date created' has changed by hooking into the status changed action and scheduling a recalc.

		if ( ! empty( $_GET['repopstatsdb'] ) ) {
			add_action( 'init', array( $this, 'queue_order_stats_repopulate_database' ) );
		}

		// Each hour update the DB with info for the previous hour.
		if ( ! wp_next_scheduled( self::CRON_EVENT ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d H:30:00' ) ), 'hourly', self::CRON_EVENT );
		}

		if ( ! self::$background_process ) {
			self::$background_process = new WC_Order_Stats_Background_Process();
		}
	}

	/**
	 * Get order stats information.
	 *
	 * @param string $start_time UTC Timestamp.
	 * @param string $end_time UTC Timestamp.
	 * @param array  $args Optional arguments.
	 * @return array
	 */
	public static function query( $start_time, $end_time, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'interval' => 'hour', // hour, week, day, month, or year.
			'fields' => '*',
		);
		$args = wp_parse_args( $args, $defaults );

		$selections = array(
			'start_time' => 'MIN(start_time) as start_time',
			'num_orders' => 'SUM(num_orders) as num_orders',
			'num_items_sold' => 'SUM(num_items_sold) as num_items_sold',
			'num_products_sold' => 'SUM(num_products_sold) as num_products_sold',
			'orders_gross_total' => 'SUM(orders_gross_total) as orders_gross_total',
			'orders_coupon_total' => 'SUM(orders_coupon_total) as orders_coupon_total',
			'orders_refund_total' => 'SUM(orders_refund_total) as orders_refund_total',
			'orders_tax_total' => 'SUM(orders_tax_total) as orders_tax_total',
			'orders_shipping_total' => 'SUM(orders_shipping_total) as orders_shipping_total',
			'orders_net_total' => 'SUM(orders_net_total) as orders_net_total',
		);

		if ( is_array( $args['fields'] ) ) {
			$keep = array();
			foreach ( $args['fields'] as $field ) {
				if ( isset( $selections[ $field ] ) ) {
					$keep[ $field ] = $selections[ $field ];
				}
			}
			if ( empty( $keep ) ) {
				return array();
			}
			$selections = implode( ',', $keep );
		} else {
			$selections = implode( ',', $selections );
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$query = $wpdb->prepare(
			'SELECT ' . $selections . ' FROM ' . $table_name . ' WHERE start_time >= %s AND start_time < %s GROUP BY ' . strtoupper( esc_sql( $args['interval'] ) ) . '(start_time);',
			$start_time,
			$end_time
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Create the table that will hold the order stats information.
	 */
	public function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$sql = "CREATE TABLE $table_name (
			start_time int(11) DEFAULT 0 NOT NULL,
			num_orders int(11) UNSIGNED DEFAULT 0 NOT NULL,
			num_items_sold int(11) UNSIGNED DEFAULT 0 NOT NULL,
			num_products_sold int(11) UNSIGNED DEFAULT 0 NOT NULL,
			orders_gross_total double DEFAULT 0 NOT NULL,
			orders_coupon_total double DEFAULT 0 NOT NULL,
			orders_refund_total double DEFAULT 0 NOT NULL,
			orders_tax_total double DEFAULT 0 NOT NULL,
			orders_shipping_total double DEFAULT 0 NOT NULL,
			orders_net_total double DEFAULT 0 NOT NULL,
			PRIMARY KEY (start_time)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Queue a background process that will repopulate the entire orders stats database.
	 */
	public function queue_order_stats_repopulate_database() {
		// To get the first start time, get the oldest order and round the completion time down to the nearest hour.
		$oldest = wc_get_orders( array(
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'ASC',
			'status'  => 'completed',
			'type'    => 'shop_order',
		) );

		$oldest = $oldest ? reset( $oldest ) : false;
		if ( ! $oldest ) {
			return;
		}

		$start_time = strtotime( $oldest->get_date_created()->format( 'Y-m-d\TH:00:00O' ) );
		$end_time = $start_time + HOUR_IN_SECONDS;

		while ( $end_time < time() ) {
			self::$background_process->push_to_queue(
				array(
					'start_time' => $start_time,
					'end_time' => $end_time,
				)
			);

			$start_time = $end_time;
			$end_time = $start_time + HOUR_IN_SECONDS;
		}

		self::$background_process->save()->dispatch();
	}

	/**
	 * Queue a background process that will update the database with stats info from the last hour.
	 */
	public function queue_update_recent_orders() {
		// Populate the stats information for the previous hour.
		$last_hour = strtotime( date( 'Y-m-d H:00:00' ) ) - HOUR_IN_SECONDS;
		self::$background_process->push_to_queue(
			array(
				'start_time' => $last_hour,
				'end_time' => $last_hour + HOUR_IN_SECONDS,
			)
		);

		// Recalculate the stats information for orders modified in the previous hour.
		$modified_orders = wc_get_orders(
			array(
				'limit' => -1,
				'date_modified' => '>=' . $last_hour,
			)
		);

		$scheduled_dates = array( $last_hour );
		foreach( $modified_orders as $order ) {
			$modified_date = strtotime( $order->get_date_modified()->date( 'Y-m-d H:00:00' ) );
			if ( ! in_array( $modified_date, $scheduled_dates, true ) ) {
				self::$background_process->push_to_queue(
					array(
						'start_time' => $modified_date,
						'end_time' => $modified_date + HOUR_IN_SECONDS,
					)
				);
				$scheduled_dates[] = $modified_date;
			}
		}

		self::$background_process->save()->dispatch();
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
			'num_products_sold'     => 0,
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
			'orderby'      => 'date',
			'order'        => 'ASC',
			'status'       => 'completed',
			'date_created' => $start_time . '...' . $end_time,
		) );

		// @todo The gross/net total logic may need tweaking. Verify that logic is correct.
		$summary['num_orders']            = count( $orders );
		$summary['num_items_sold']        = self::get_num_items_sold( $orders );
		$summary['num_products_sold']     = self::get_num_products_sold( $orders );
		$summary['orders_gross_total']    = self::get_orders_gross_total( $orders );
		$summary['orders_coupon_total']   = self::get_orders_coupon_total( $orders );
		$summary['orders_refund_total']   = self::get_orders_refund_total( $orders );
		$summary['orders_tax_total']      = self::get_orders_tax_total( $orders );
		$summary['orders_shipping_total'] = self::get_orders_shipping_total( $orders );
		$summary['orders_net_total']      = $summary['orders_gross_total'] - $summary['orders_coupon_total'] - $summary['orders_refund_total'] - $summary['orders_tax_total'] - $summary['orders_shipping_total'];

		return $summary;
	}

	/**
	 * Update the database with stats data.
	 *
	 * @param int $start_time Timestamp.
	 * @param array $data Stats data.
	 * @return int/bool Number or rows modified or false on failure.
	 */
	public static function update( $start_time, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$defaults = array(
			'start_time'            => $start_time,
			'num_orders'            => 0,
			'num_items_sold'        => 0,
			'num_products_sold'     => 0,
			'orders_gross_total'    => 0.0,
			'orders_coupon_total'   => 0.0,
			'orders_refund_total'   => 0.0,
			'orders_tax_total'      => 0.0,
			'orders_shipping_total' => 0.0,
			'orders_net_total'      => 0.0,
		);
		$data = wp_parse_args( $data, $defaults );

		// Don't store rows that don't have useful information.
		if ( ! $data['num_orders'] ) {
			return $wpdb->delete(
				$table_name,
				array(
					'start_time' => $start_time,
				),
				array(
					'%d',
				)
			);
		}

		// Update or add the information to the DB.
		return $wpdb->replace(
			$table_name,
			$data,
			array(
				'%d',
				'%d',
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
	 * Get number of products sold among all orders.
	 *
	 * @param array $orders Array of WC_Order objects.
	 * @return int
	 */
	protected static function get_num_products_sold( $orders ) {
		$counted_products = array();
		$num_products = 0;

		foreach ( $orders as $order ) {
			$line_items = $order->get_items( 'line_item' );
			foreach ( $line_items as $line_item ) {
				if ( ! method_exists( $line_item, 'get_product_id' ) ) {
					continue;
				}

				$product_id = $line_item->get_product_id();
				if ( ! isset( $counted_products[ $product_id ] ) ) {
					$counted_products[ $product_id ] = 1;
					++$num_products;
				}
			}
		}

		return $num_products;
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
			$total += $order->get_subtotal();
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
new WC_Order_Stats();
