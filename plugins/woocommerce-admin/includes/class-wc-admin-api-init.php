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
		add_filter( 'woocommerce_install_get_tables', array( 'WC_Admin_Api_Init', 'add_tables' ) );
		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'rest_endpoints', array( 'WC_Admin_Api_Init', 'filter_rest_endpoints' ), 10, 1 );
		add_filter( 'woocommerce_debug_tools', array( 'WC_Admin_Api_Init', 'add_regenerate_tool' ) );

		// Initialize Orders data store class's static vars.
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Admin_Api_Init', 'orders_data_store_init' ), 20 );
		// Initialize Customers Report data store sync hooks.
		// Note: we need to hook into 'wp' before `wc_current_user_is_active`.
		// See: https://github.com/woocommerce/woocommerce/blob/942615101ba00c939c107c3a4820c3d466864872/includes/wc-user-functions.php#L749.
		add_action( 'wp', array( 'WC_Admin_Api_Init', 'customers_report_data_store_init' ), 9 );

		// Handle batched queuing.
		add_action( 'wc-admin_queue_batches', array( __CLASS__, 'queue_batches' ), 10, 3 );
		add_action( 'wc-admin_process_customers_batch', array( __CLASS__, 'customer_lookup_process_batch' ) );
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
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-variations-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-categories-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-taxes-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-taxes-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-coupons-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-coupons-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-downloads-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-downloads-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-customers-query.php';

		// Data stores.
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-orders-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-orders-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-variations-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-categories-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-taxes-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-taxes-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-coupons-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-coupons-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-downloads-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-downloads-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-customers-data-store.php';

		// Data triggers.
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-notes-data-store.php';

		// CRUD classes.
		require_once dirname( __FILE__ ) . '/class-wc-admin-note.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-notes.php';
	}

	/**
	 * Init REST API.
	 */
	public function rest_api_init() {
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-admin-notes-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-customers-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-data-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-data-download-ips-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-orders-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-products-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-product-reviews-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-system-status-tools-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-categories-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-customers-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-files-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-orders-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-orders-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-variations-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-performance-indicators-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-revenue-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-stock-controller.php';

		$controllers = apply_filters(
			'woocommerce_admin_rest_controllers',
			array(
				'WC_Admin_REST_Admin_Notes_Controller',
				'WC_Admin_REST_Customers_Controller',
				'WC_Admin_REST_Data_Controller',
				'WC_Admin_REST_Data_Download_Ips_Controller',
				'WC_Admin_REST_Orders_Stats_Controller',
				'WC_Admin_REST_Products_Controller',
				'WC_Admin_REST_Product_Reviews_Controller',
				'WC_Admin_REST_Reports_Controller',
				'WC_Admin_REST_System_Status_Tools_Controller',
				'WC_Admin_REST_Reports_Products_Controller',
				'WC_Admin_REST_Reports_Variations_Controller',
				'WC_Admin_REST_Reports_Products_Stats_Controller',
				'WC_Admin_REST_Reports_Revenue_Stats_Controller',
				'WC_Admin_REST_Reports_Orders_Controller',
				'WC_Admin_REST_Reports_Orders_Stats_Controller',
				'WC_Admin_REST_Reports_Categories_Controller',
				'WC_Admin_REST_Reports_Taxes_Controller',
				'WC_Admin_REST_Reports_Taxes_Stats_Controller',
				'WC_Admin_REST_Reports_Coupons_Controller',
				'WC_Admin_REST_Reports_Coupons_Stats_Controller',
				'WC_Admin_REST_Reports_Stock_Controller',
				'WC_Admin_REST_Reports_Downloads_Controller',
				'WC_Admin_REST_Reports_Downloads_Stats_Controller',
				'WC_Admin_REST_Reports_Customers_Controller',
			)
		);

		// The performance indicators controller must be registered last, after other /stats endpoints have been registered.
		$controllers[] = 'WC_Admin_REST_Reports_Performance_Indicators_Controller';

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

		// Override /wc/v3/customers.
		if ( isset( $endpoints['/wc/v3/customers'] )
			&& isset( $endpoints['/wc/v3/customers'][3] )
			&& isset( $endpoints['/wc/v3/customers'][2] )
			&& $endpoints['/wc/v3/customers'][2]['callback'][0] instanceof WC_Admin_REST_Customers_Controller
			&& $endpoints['/wc/v3/customers'][3]['callback'][0] instanceof WC_Admin_REST_Customers_Controller
		) {
			$endpoints['/wc/v3/customers'][0] = $endpoints['/wc/v3/customers'][2];
			$endpoints['/wc/v3/customers'][1] = $endpoints['/wc/v3/customers'][3];
		}

		// Override /wc/v3/orders/$id.
		if ( isset( $endpoints['/wc/v3/orders/(?P<id>[\d]+)'] )
			&& isset( $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][5] )
			&& isset( $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][4] )
			&& isset( $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][3] )
			&& $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Stats_Controller
			&& $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][4]['callback'][0] instanceof WC_Admin_REST_Orders_Stats_Controller
			&& $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][5]['callback'][0] instanceof WC_Admin_REST_Orders_Stats_Controller
		) {
			$endpoints['/wc/v3/orders/(?P<id>[\d]+)'][0] = $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][3];
			$endpoints['/wc/v3/orders/(?P<id>[\d]+)'][1] = $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][4];
			$endpoints['/wc/v3/orders/(?P<id>[\d]+)'][2] = $endpoints['/wc/v3/orders/(?P<id>[\d]+)'][5];
		}

		// Override /wc/v3orders.
		if ( isset( $endpoints['/wc/v3/orders'] )
			&& isset( $endpoints['/wc/v3/orders'][3] )
			&& isset( $endpoints['/wc/v3/orders'][2] )
			&& $endpoints['/wc/v3/orders'][2]['callback'][0] instanceof WC_Admin_REST_Orders_Stats_Controller
			&& $endpoints['/wc/v3/orders'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Stats_Controller
		) {
			$endpoints['/wc/v3/orders'][0] = $endpoints['/wc/v3/orders'][2];
			$endpoints['/wc/v3/orders'][1] = $endpoints['/wc/v3/orders'][3];
		}

		// Override /wc/v3/data.
		if ( isset( $endpoints['/wc/v3/data'] )
			&& isset( $endpoints['/wc/v3/data'][1] )
			&& $endpoints['/wc/v3/data'][1]['callback'][0] instanceof WC_Admin_REST_Data_Controller
		) {
			$endpoints['/wc/v3/data'][0] = $endpoints['/wc/v3/data'][1];
		}

		// Override /wc/v3/products.
		if ( isset( $endpoints['/wc/v3/products'] )
			&& isset( $endpoints['/wc/v3/products'][3] )
			&& isset( $endpoints['/wc/v3/products'][2] )
			&& $endpoints['/wc/v3/products'][2]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v3/products'][3]['callback'][0] instanceof WC_Admin_REST_Products_Controller
		) {
			$endpoints['/wc/v3/products'][0] = $endpoints['/wc/v3/products'][2];
			$endpoints['/wc/v3/products'][1] = $endpoints['/wc/v3/products'][3];
		}

		// Override /wc/v3/products/$id.
		if ( isset( $endpoints['/wc/v3/products/(?P<id>[\d]+)'] )
			&& isset( $endpoints['/wc/v3/products/(?P<id>[\d]+)'][5] )
			&& isset( $endpoints['/wc/v3/products/(?P<id>[\d]+)'][4] )
			&& isset( $endpoints['/wc/v3/products/(?P<id>[\d]+)'][3] )
			&& $endpoints['/wc/v3/products/(?P<id>[\d]+)'][3]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v3/products/(?P<id>[\d]+)'][4]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v3/products/(?P<id>[\d]+)'][5]['callback'][0] instanceof WC_Admin_REST_Products_Controller
		) {
			$endpoints['/wc/v3/products/(?P<id>[\d]+)'][0] = $endpoints['/wc/v3/products/(?P<id>[\d]+)'][3];
			$endpoints['/wc/v3/products/(?P<id>[\d]+)'][1] = $endpoints['/wc/v3/products/(?P<id>[\d]+)'][4];
			$endpoints['/wc/v3/products/(?P<id>[\d]+)'][2] = $endpoints['/wc/v3/products/(?P<id>[\d]+)'][5];
		}

		// Override /wc/v3/products/reviews.
		if ( isset( $endpoints['/wc/v3/products/reviews'] )
			&& isset( $endpoints['/wc/v3/products/reviews'][3] )
			&& isset( $endpoints['/wc/v3/products/reviews'][2] )
			&& $endpoints['/wc/v3/products/reviews'][2]['callback'][0] instanceof WC_Admin_REST_Product_Reviews_Controller
			&& $endpoints['/wc/v3/products/reviews'][3]['callback'][0] instanceof WC_Admin_REST_Product_Reviews_Controller
		) {
			$endpoints['/wc/v3/products/reviews'][0] = $endpoints['/wc/v3/products/reviews'][2];
			$endpoints['/wc/v3/products/reviews'][1] = $endpoints['/wc/v3/products/reviews'][3];
		}

		return $endpoints;
	}

	/**
	 * Regenerate data for reports.
	 */
	public static function regenerate_report_data() {
		// Add registered customers to the lookup table before updating order stats
		// so that the orders can be associated with the `customer_id` column.
		self::customer_lookup_batch_init();
		WC_Admin_Reports_Orders_Stats_Data_Store::queue_order_stats_repopulate_database();
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
					'name'     => __( 'Rebuild reports data', 'wc-admin' ),
					'button'   => __( 'Rebuild reports', 'wc-admin' ),
					'desc'     => __( 'This tool will rebuild all of the information used by the reports.', 'wc-admin' ),
					'callback' => array( 'WC_Admin_Api_Init', 'regenerate_report_data' ),
				),
			)
		);
	}

	/**
	 * Init orders data store.
	 */
	public static function orders_data_store_init() {
		WC_Admin_Reports_Orders_Stats_Data_Store::init();
		WC_Admin_Reports_Products_Data_Store::init();
		WC_Admin_Reports_Taxes_Data_Store::init();
		WC_Admin_Reports_Coupons_Data_Store::init();
	}

	/**
	 * Init orders product lookup store.
	 *
	 * @param WC_Background_Updater|null $updater Updater instance.
	 * @return bool
	 */
	public static function order_product_lookup_store_init( $updater = null ) {
		// TODO: this needs to be updated a bit, as it no longer runs as a part of WC_Install, there is no bg updater.
		global $wpdb;

		$orders = get_transient( 'wc_update_350_all_orders' );
		if ( false === $orders ) {
			$orders = wc_get_orders(
				array(
					'limit'  => -1,
					'return' => 'ids',
				)
			);
			set_transient( 'wc_update_350_all_orders', $orders, DAY_IN_SECONDS );
		}

		// Process orders until close to running out of memory timeouts on large sites then requeue.
		foreach ( $orders as $order_id ) {
			WC_Admin_Reports_Products_Data_Store::sync_order_products( $order_id );
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
	 * Init customers report data store.
	 */
	public static function customers_report_data_store_init() {
		WC_Admin_Reports_Customers_Data_Store::init();
	}

	/**
	 * Returns the batch size for regenerating reports.
	 *
	 * @return int Batch size.
	 */
	public static function get_batch_size() {
		return apply_filters( 'wc_admin_report_regenerate_batch_size', 25 );
	}

	/**
	 * Queue a large number of batch jobs, respecting the batch size limit.
	 * Reduces a range of batches down to "single batch" jobs.
	 *
	 * @param int    $range_start Starting batch number.
	 * @param int    $range_end Ending batch number.
	 * @param string $single_batch_action Action to schedule for a single batch.
	 * @return void
	 */
	public static function queue_batches( $range_start, $range_end, $single_batch_action ) {
		$batch_size = self::get_batch_size();
		$range_size = 1 + ( $range_end - $range_start );
		$queue      = WC()->queue();
		$schedule   = time() + 5;

		if ( $range_size > $batch_size ) {
			// If the current batch range is larger than a single batch,
			// split the range into $batch_size chunks.
			$chunk_size = ceil( $range_size / $batch_size );

			for ( $i = 0; $i < $batch_size; $i++ ) {
				$batch_start = $range_start + ( $i * $chunk_size );
				$batch_end   = min( $range_end, $range_start + ( $chunk_size * ( $i + 1 ) ) - 1 );

				$queue->schedule_single(
					$schedule,
					'wc-admin_queue_batches',
					array( $batch_start, $batch_end, $single_batch_action )
				);
			}
		} else {
			// Otherwise, queue the single batches.
			for ( $i = $range_start; $i <= $range_end; $i++ ) {
				$queue->schedule_single( $schedule, $single_batch_action, array( $i ) );
			}
		}
	}

	/**
	 * Init customer lookup table update (in batches).
	 */
	public static function customer_lookup_batch_init() {
		$batch_size     = self::get_batch_size();
		$customer_query = new WP_User_Query(
			array(
				'fields'  => 'ID',
				'role'    => 'customer',
				'number'  => 1,
			)
		);
		$queue           = WC()->queue();
		$schedule        = time() + 5;
		$total_customers = $customer_query->get_total();
		$num_batches     = ceil( $total_customers / $batch_size );

		self::queue_batches( 1, $num_batches, 'wc-admin_process_customers_batch' );
	}

	/**
	 * Process a batch of customers to update.
	 *
	 * @param int $batch_number Batch number to process (essentially a query page number).
	 * @return void
	 */
	public static function customer_lookup_process_batch( $batch_number ) {
		$batch_size     = self::get_batch_size();
		$customer_query = new WP_User_Query(
			array(
				'fields'  => 'ID',
				'role'    => 'customer',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => $batch_size,
				'paged'   => $batch_number,
			)
		);

		$customer_ids = $customer_query->get_results();

		foreach ( $customer_ids as $customer_id ) {
			// TODO: schedule single customer update if this fails?
			WC_Admin_Reports_Customers_Data_Store::update_registered_customer( $customer_id );
		}
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
				'report-revenue-stats'  => 'WC_Admin_Reports_Orders_Stats_Data_Store',
				'report-orders'         => 'WC_Admin_Reports_Orders_Data_Store',
				'report-orders-stats'   => 'WC_Admin_Reports_Orders_Stats_Data_Store',
				'report-products'       => 'WC_Admin_Reports_Products_Data_Store',
				'report-variations'     => 'WC_Admin_Reports_Variations_Data_Store',
				'report-products-stats' => 'WC_Admin_Reports_Products_Stats_Data_Store',
				'report-categories'     => 'WC_Admin_Reports_Categories_Data_Store',
				'report-taxes'          => 'WC_Admin_Reports_Taxes_Data_Store',
				'report-taxes-stats'    => 'WC_Admin_Reports_Taxes_Stats_Data_Store',
				'report-coupons'        => 'WC_Admin_Reports_Coupons_Data_Store',
				'report-coupons-stats'  => 'WC_Admin_Reports_Coupons_Stats_Data_Store',
				'report-downloads'      => 'WC_Admin_Reports_Downloads_Data_Store',
				'report-downloads-stats' => 'WC_Admin_Reports_Downloads_Stats_Data_Store',
				'admin-note'             => 'WC_Admin_Notes_Data_Store',
				'report-customers'       => 'WC_Admin_Reports_Customers_Data_Store',
			)
		);
	}

	/**
	 * Adds new tables.
	 *
	 * @param array $wc_tables List of WooCommerce tables.
	 * @return array
	 */
	public static function add_tables( $wc_tables ) {
		global $wpdb;

		return array_merge(
			$wc_tables,
			array(
				// TODO: will this work on multisite?
				"{$wpdb->prefix}wc_order_stats",
				"{$wpdb->prefix}wc_order_product_lookup",
				"{$wpdb->prefix}wc_order_tax_lookup",
				"{$wpdb->prefix}wc_order_coupon_lookup",
				"{$wpdb->prefix}woocommerce_admin_notes",
				"{$wpdb->prefix}woocommerce_admin_note_actions",
				"{$wpdb->prefix}wc_customer_lookup",
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
			returning_customer boolean DEFAULT 0 NOT NULL,
			status varchar(200) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
			order_item_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			variation_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			product_qty INT UNSIGNED NOT NULL,
			product_net_revenue double DEFAULT 0 NOT NULL,
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
			discount_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, coupon_id),
			KEY coupon_id (coupon_id),
			KEY date_created (date_created)
		  ) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_admin_notes (
				note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				type varchar(20) NOT NULL,
				locale varchar(20) NOT NULL,
				title longtext NOT NULL,
				content longtext NOT NULL,
				icon varchar(200) NOT NULL,
				content_data longtext NULL default null,
				status varchar(200) NOT NULL,
				source varchar(200) NOT NULL,
				date_created datetime NOT NULL default '0000-00-00 00:00:00',
				date_reminder datetime NULL default null,
				PRIMARY KEY (note_id)
				) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_admin_note_actions (
				action_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				note_id BIGINT UNSIGNED NOT NULL,
				name varchar(255) NOT NULL,
				label varchar(255) NOT NULL,
				query longtext NOT NULL,
				PRIMARY KEY (action_id),
				KEY note_id (note_id)
				) $collate;
			CREATE TABLE {$wpdb->prefix}wc_customer_lookup (
				customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED DEFAULT NULL,
				username varchar(60) DEFAULT '' NOT NULL,
				first_name varchar(255) NOT NULL,
				last_name varchar(255) NOT NULL,
				email varchar(100) NOT NULL,
				date_last_active timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_registered timestamp NULL default null,
				country char(2) DEFAULT '' NOT NULL,
				postcode varchar(20) DEFAULT '' NOT NULL,
				city varchar(100) DEFAULT '' NOT NULL,
				PRIMARY KEY (customer_id),
				UNIQUE KEY user_id (user_id),
				KEY email (email)
				) $collate;
			";

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
		// TODO: just call self::regenerate_report_data() here?
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Admin_Api_Init', 'order_product_lookup_store_init' ), 20 );
		add_action( 'woocommerce_after_register_post_type', array( 'WC_Admin_Api_Init', 'customer_lookup_batch_init' ), 20 );
	}

}

new WC_Admin_Api_Init();
