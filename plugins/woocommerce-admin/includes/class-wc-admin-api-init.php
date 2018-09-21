<?php
/**
 * REST API bootstrap.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Api_Init class.
 */
class WC_Admin_Api_Init {

	/**
	 * Boostrap REST API.
	 */
	public function __construct() {
		// Initialize classes.
		add_action( 'plugins_loaded', array( $this, 'init_classes' ), 19 );
		// Hook in data stores.
		add_filter( 'woocommerce_data_stores', array( 'WC_Admin_Api_Init', 'add_data_stores' ) );
		// Add wc-admin report tables to list of WooCommerce tables.
		add_filter( 'woocommerce_install_get_tables', array( 'WC_Admin_Api_Init', 'add_report_tables' ) );
		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'rest_endpoints', array( 'WC_Admin_Api_Init', 'filter_rest_endpoints' ), 10, 1 );
		add_filter( 'woocommerce_debug_tools', array( 'WC_Admin_Api_Init', 'add_regenerate_tool' ) );

		// Initialize Orders data store class's static vars.
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Admin_Api_Init', 'orders_data_store_init' ), 20 );
	}

	/**
	 * Init classes.
	 */
	public function init_classes() {
		// Interfaces.
		require_once dirname( __FILE__ ) . '/interfaces/class-wc-admin-reports-data-store-interface.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-query.php';

		// Common date time code.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-interval.php';

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

	/**
	 * Init REST API.
	 */
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
			'WC_Admin_REST_Reports_Taxes_Controller',
			'WC_Admin_REST_Reports_Taxes_Stats_Controller',
			'WC_Admin_REST_Reports_Coupons_Controller',
			'WC_Admin_REST_Reports_Coupons_Stats_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Filter REST API endpoints.
	 *
	 * @param array $endpoints List of endpoints.
	 * @return array
	 */
	public static function filter_rest_endpoints( $endpoints ) {
		// Override GET /wc/v3/system_status/tools.
		if ( isset( $endpoints['/wc/v3/system_status/tools'] )
		     && isset( $endpoints['/wc/v3/system_status/tools'][1] )
		     && isset( $endpoints['/wc/v3/system_status/tools'][0] )
		     && $endpoints['/wc/v3/system_status/tools'][1]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
		) {
			$endpoints['/wc/v3/system_status/tools'][0] = $endpoints['/wc/v3/system_status/tools'][1];
		}
		// // Override GET & PUT for /wc/v3/system_status/tools.
		if ( isset( $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'] )
		     && isset( $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][3] )
		     && isset( $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][2] )
		     && $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][2]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
		     && $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][3]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
		) {
			$endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][0] = $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][2];
			$endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][1] = $endpoints['/wc/v3/system_status/tools/(?P<id>[\w-]+)'][3];
		}

		// Override GET /wc/v3/reports.
		if ( isset( $endpoints['/wc/v3/reports'] )
		     && isset( $endpoints['/wc/v3/reports'][1] )
		     && isset( $endpoints['/wc/v3/reports'][0] )
		     && $endpoints['/wc/v3/reports'][1]['callback'][0] instanceof WC_Admin_REST_Reports_Controller
		) {
			$endpoints['/wc/v3/reports'][0] = $endpoints['/wc/v3/reports'][1];
		}

		return $endpoints;
	}

	/**
	 * Regenerate data for reports.
	 */
	public static function regenrate_report_data() {
		WC_Admin_Reports_Orders_Data_Store::queue_order_stats_repopulate_database();
		self::order_product_lookup_store_init();
	}

	/**
	 * Adds regenerate tool.
	 *
	 * @param array $tools List of tools.
	 * @return array
	 */
	public static function add_regenerate_tool( $tools ) {
		return array_merge(
			$tools,
			array(
				'rebuild_stats' => array(
					'name'     => __( 'Rebuild reports data', 'woocommerce' ),
					'button'   => __( 'Rebuild reports', 'woocommerce' ),
					'desc'     => __( 'This tool will rebuild all of the information used by the reports.', 'woocommerce' ),
					'callback' => array( 'WC_Admin_Api_Init', 'regenrate_report_data' ),
				),
			)
		);
	}

	/**
	 * Init orders data store.
	 */
	public static function orders_data_store_init() {
		WC_Admin_Reports_Orders_Data_Store::init();
	}

	/**
	 * Init orders product lookup store.
	 *
	 * @param WC_Background_Updater|null $updater Updater instance.
	 * @return bool
	 */
	// TODO: this needs to be updated a bit, as it no longer runs as a part of WC_Install, there is no bg updater.
	public static function order_product_lookup_store_init( $updater = null ) {
		global $wpdb;

		$orders = get_transient( 'wc_update_350_all_orders' );
		if ( false === $orders ) {
			$orders = wc_get_orders( array(
				'limit'  => -1,
				'return' => 'ids',
			) );
			set_transient( 'wc_update_350_all_orders', $orders, DAY_IN_SECONDS );
		}

		// Process orders until close to running out of memory timeouts on large sites then requeue.
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

		return true;
	}

	/**
	 * Adds data stores.
	 *
	 * @param array $data_stores List of data stores.
	 * @return array
	 */
	public static function add_data_stores( $data_stores ) {
		return array_merge(
			$data_stores,
			array(
				'report-revenue-stats'  => 'WC_Admin_Reports_Orders_Data_Store',
				'report-orders-stats'   => 'WC_Admin_Reports_Orders_Data_Store',
				'report-products'       => 'WC_Admin_Reports_Products_Data_Store',
				'report-products-stats' => 'WC_Admin_Reports_Products_Stats_Data_Store',
				'report-categories'     => 'WC_Admin_Reports_Categories_Data_Store',
			)
		);
	}

	/**
	 * Adds report tables.
	 *
	 * @param array $wc_tables List of WooCommerce tables.
	 * @return array
	 */
	public static function add_report_tables( $wc_tables ) {
		global $wpdb;

		return array_merge(
			$wc_tables,
			array(
				// TODO: will this work on multisite?
				"{$wpdb->prefix}wc_order_stats",
				"{$wpdb->prefix}wc_order_product_lookup",
				"{$wpdb->prefix}wc_order_tax_lookup",
				"{$wpdb->prefix}wc_order_coupon_lookup",
			)
		);
	}

	/**
	 * Get database schema.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}wc_order_stats (
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
		  CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
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
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_tax_lookup (
		  	order_id BIGINT UNSIGNED NOT NULL,
		  	tax_rate_id BIGINT UNSIGNED NOT NULL,
		  	date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  	shipping_tax double DEFAULT 0 NOT NULL,
		  	order_tax double DEFAULT 0 NOT NULL,
		  	total_tax double DEFAULT 0 NOT NULL,
		  	KEY order_id (order_id),
		  	KEY tax_rate_id (tax_rate_id),
		  	KEY date_created (date_created)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_coupon_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			coupon_id BIGINT UNSIGNED NOT NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			coupon_gross_discount double DEFAULT 0 NOT NULL,
			KEY order_id (order_id),
			KEY coupon_id (coupon_id),
			KEY date_created (date_created)
		  ) $collate;";

		return $tables;
	}

	/**
	 * Create database tables.
	 */
	public static function create_db_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	/**
	 * Install plugin.
	 */
	public static function install() {
		// Create tables.
		self::create_db_tables();

		// Initialize report tables.
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Admin_Api_Init', 'order_product_lookup_store_init' ), 20 );
	}

}

new WC_Admin_Api_Init();
