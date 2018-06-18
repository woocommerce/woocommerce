<?php
/**
 * WooCommerce Orders Stats
 *
 * Handles the orders stats database and API.
 *s
 * @package WooCommerce/Classes
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order stats class.
 */
class WC_Order_Stats {

	const TABLE_NAME = 'wc_order_stats';

	/**
	 * Setup class.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'install' ) ); // @todo Don't do this on every page load.
		add_action( 'init', array( $this, 'populate_database_naive' ), 99 );

		// next steps:
		// background process for populating (LEFT OFF HERE)
		// tool for repopulating everything
		// scheduler for adding new lines each hour
		// handling for when old orders are updated
		// easy method(s) for querying the stored data
	}

	public function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$sql = "CREATE TABLE $table_name (
			start_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			end_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_orders smallint(5) UNSIGNED DEFAULT 0 NOT NULL,
			num_items_sold smallint(5) UNSIGNED DEFAULT 0 NOT NULL,
			num_products_sold smallint(5) UNSIGNED DEFAULT 0 NOT NULL,
			orders_gross_total double DEFAULT 0 NOT NULL,
			orders_coupon_total double DEFAULT 0 NOT NULL,
			orders_refund_total double DEFAULT 0 NOT NULL,
			orders_tax_total double DEFAULT 0 NOT NULL,
			orders_shipping_total double DEFAULT 0 NOT NULL,
			orders_net_total double DEFAULT 0 NOT NULL,
			average_order_total double DEFAULT 0 NOT NULL,
			PRIMARY KEY (start_date)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function populate_database() {
		// @todo do it this way instead of the naive way.
		// start a background process to update
		// each go-through of the process will process one hour's worth of orders
	}

	public function populate_database_naive() {
		// To get the first start time, get the oldest order and round the completion time down to the nearest hour.
		$oldest = wc_get_orders( array(
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'ASC',
			'status'  => 'completed',
		) );

		$oldest = $oldest ? reset( $oldest ) : false;
		if ( ! $oldest ) {
			return;
		}

		$start_time = strtotime( $oldest->get_date_created()->date( 'Y-m-d\TH:0:0O' ) );
		$end_time = $start_time + HOUR_IN_SECONDS;

		while ( $end_time < time() ) {
			$summary = $this->summarize_orders( $start_time, $end_time );
			$this->update( $start_time, $end_time, $summary );

			$start_time = $end_time;
			$end_time = $start_time + HOUR_IN_SECONDS;
		}
	}

	public function summarize_orders( $start_time, $end_time ) {
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
			'average_order_total'   => 0.0,
		);

		$orders = wc_get_orders( array(
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'ASC',
			'status' => 'completed',
			'date_created' => $start_time . '...' . $end_time,
		) );

		// @todo The gross/net total logic may need tweaking. Verify that logic is correct.
		$summary['num_orders']            = count( $orders );
		$summary['num_items_sold']        = $this->get_num_items_sold( $orders );
		$summary['num_products_sold']     = $this->get_num_products_sold( $orders );
		$summary['orders_gross_total']    = $this->get_orders_gross_total( $orders );
		$summary['orders_coupon_total']   = $this->get_orders_coupon_total( $orders );
		$summary['orders_refund_total']   = $this->get_orders_refund_total( $orders );
		$summary['orders_tax_total']      = $this->get_orders_tax_total( $orders );
		$summary['orders_shipping_total'] = $this->get_orders_shipping_total( $orders );
		$summary['orders_net_total']      = $summary['orders_gross_total'] - $summary['orders_coupon_total'] - $summary['orders_refund_total'] - $summary['orders_tax_total'] - $summary['orders_shipping_total'];

		if ( $summary['num_orders'] ) {
			$summary['average_order_total'] = wc_round_tax_total( $summary['orders_gross_total'] / $summary['num_orders'] );
		}

		return $summary;
	}

	public function update( $start, $end, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$data['start_date'] = date( 'Y-m-d H-0-0', $start );
		$data['end_date'] = date( 'Y-m-d H-0-0', $end );

		$result = $wpdb->replace(
			$table_name,
			$data,
			array(
				'%d',
				'%d',
				'%d',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Calculation methods.
	 */

	private function get_num_items_sold( $orders ) {
		$num_items = 0;

		foreach ( $orders as $order ) {
			$line_items = $order->get_items( 'line_item' );
			foreach ( $line_items as $line_item ) {
				$num_items += $line_item->get_quantity();
			}
		}

		return $num_items;
	}

	private function get_num_products_sold( $orders ) {
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

	private function get_orders_gross_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_subtotal();
		}

		return $total;
	}

	private function get_orders_coupon_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_discount_total();
		}

		return $total;
	}

	private function get_orders_refund_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_total_refunded();
		}

		return $total;
	}

	private function get_orders_tax_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_total_tax();
		}

		return $total;
	}

	private function get_orders_shipping_total( $orders ) {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += $order->get_shipping_total();
		}

		return $total;
	}
}
new WC_Order_Stats();
