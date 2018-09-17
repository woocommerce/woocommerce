<?php


class WC_Admin_Api_Init {

	public function __construct() {
		// Initialize classes.
		add_action( 'plugins_loaded', array( $this, 'init_classes' ), 19 );
		// Hook in data stores.
		add_filter( 'woocommerce_data_stores', array( 'WC_Admin_Api_Init', 'add_data_stores' ) );
		// Add wc-admin report tables to list of WooCommerce tables.
		add_filter( 'woocommerce_install_get_tables', array( 'WC_Admin_Api_Init', 'add_report_tables' ) );
		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		// Initialize report classes.
		add_action( 'plugins_loaded', array( 'WC_Admin_Api_Init', 'orders_data_store_init' ), 20 );
		add_action( 'plugins_loaded', array( 'WC_Admin_Api_Init', 'order_product_lookup_store_init' ), 20 );

		// Create tables.
		$this->create_db_tables();
	}

	public function init_classes() {
		// Interfaces.
		require_once dirname( __FILE__ ) . '/interfaces/class-wc-admin-reports-data-store-interface.php';
		require_once dirname( __FILE__ ) . '/interfaces/class-wc-reports-data-store-interface.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-query.php';


		// Query classes for reports.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-revenue-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-categories-query.php';

		// Reports data stores.
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-orders-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-categories-data-store.php';
	}

	public function rest_api_init() {
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-system-status-tools-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-categories-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-customers-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-files-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-orders-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-revenue-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-stats-controller.php';

		$controllers = array(
			'WC_Admin_REST_Reports_Controller',
			'WC_Admin_REST_System_Status_Tools_Controller',
			'WC_Admin_REST_Reports_Products_Controller',
			'WC_Admin_REST_Reports_Products_Stats_Controller',
			'WC_Admin_REST_Reports_Revenue_Stats_Controller',
			'WC_Admin_REST_Reports_Orders_Stats_Controller',
			'WC_Admin_REST_Reports_Categories_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	public static function orders_data_store_init() {
		WC_Admin_Reports_Orders_Data_Store::init();
	}

	public static function order_product_lookup_store_init( $updater = false ) {
		global $wpdb;

		$orders = get_transient( 'wc_update_350_all_orders' );
		if ( false === $orders ) {
			$orders = wc_get_orders( array(
				'limit'  => -1,
				'return' => 'ids',
			) );
			set_transient( 'wc_update_350_all_orders', $orders, DAY_IN_SECONDS );
		}

		// Process orders untill close to running out of memory timeouts on large sites then requeue.
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			foreach ( $order->get_items() as $order_item ) {
				$wpdb->replace(
					$wpdb->prefix . 'wc_order_product_lookup',
					array(
						'order_item_id'         => $order_item->get_id(),
						'order_id'              => $order->get_id(),
						'product_id'            => $order_item->get_product_id( 'edit' ),
						'customer_id'           => ( 0 < $order->get_customer_id( 'edit' ) ) ? $order->get_customer_id( 'edit' ) : null,
						'product_qty'           => $order_item->get_quantity( 'edit' ),
						'product_gross_revenue' => $order_item->get_subtotal( 'edit' ),
						'date_created'          => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
					),
					array(
						'%d',
						'%d',
						'%d',
						'%d',
						'%d',
						'%f',
						'%s',
					)
				);
			}
			// Pop the order ID from the array for updating the transient later should we near memory exhaustion.
			unset( $orders[ $order_id ] );
			if ( $updater instanceof WC_Background_Updater && $updater->is_memory_exceeded() ) {
				// Update the transient for the next run to avoid processing the same orders again.
				set_transient( 'wc_update_350_all_orders', $orders, DAY_IN_SECONDS );
				return true;
			}
		}
	}

	public static function add_data_stores( $data_stores ) {
		return array_merge(
			$data_stores,
			array(
				'report-revenue-stats'  => 'WC_Reports_Orders_Data_Store',
				'report-orders-stats'   => 'WC_Reports_Orders_Data_Store',
				'report-products'       => 'WC_Reports_Products_Data_Store',
				'report-products-stats' => 'WC_Reports_Products_Stats_Data_Store',
				'report-categories'     => 'WC_Reports_Categories_Data_Store',
			)
		);
	}

	public static function add_report_tables( $wc_tables ) {
		global $wpdb;

		return array_merge(
			$wc_tables,
			array(
				// TODO: will this work on multisite?
				"{$wpdb->prefix}wc_admin_order_stats",
				"{$wpdb->prefix}wc_admin_order_product_lookup",
			)
		);
	}

	private static function get_schema() {
		global $wpdb;

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}wc_admin_order_stats (
			order_id bigint(20) unsigned NOT NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_items_sold int(11) UNSIGNED DEFAULT 0 NOT NULL,
			gross_total double DEFAULT 0 NOT NULL,
			coupon_total double DEFAULT 0 NOT NULL,
			refund_total double DEFAULT 0 NOT NULL,
			tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			net_total double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_admin_order_product_lookup (
			order_item_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			product_qty INT UNSIGNED NOT NULL,
			product_gross_revenue double DEFAULT 0 NOT NULL,
			PRIMARY KEY  (order_item_id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY customer_id (customer_id),
			KEY date_created (date_created)
		  ) $collate;";

		return $tables;
	}

	protected function create_db_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

}

new WC_Admin_Api_Init();
