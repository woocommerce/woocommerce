<?php
/**
 * Report table sync related functions and actions.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Sync Class.
 */
class WC_Admin_Reports_Sync {
	/**
	 * Action hook for reducing a range of batches down to single actions.
	 */
	const QUEUE_BATCH_ACTION = 'wc-admin_queue_batches';

	/**
	 * Action hook for queuing an action after another is complete.
	 */
	const QUEUE_DEPEDENT_ACTION = 'wc-admin_queue_dependent_action';

	/**
	 * Action hook for processing a batch of customers.
	 */
	const CUSTOMERS_BATCH_ACTION = 'wc-admin_process_customers_batch';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const ORDERS_BATCH_ACTION = 'wc-admin_process_orders_batch';

	/**
	 * Action hook for initializing the orders lookup batch creation.
	 */
	const ORDERS_LOOKUP_BATCH_INIT = 'wc-admin_orders_lookup_batch_init';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const SINGLE_ORDER_ACTION = 'wc-admin_process_order';

	/**
	 * Action scheduler group.
	 */
	const QUEUE_GROUP = 'wc-admin-data';

	/**
	 * Queue instance.
	 *
	 * @var WC_Queue_Interface
	 */
	protected static $queue = null;

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public static function queue() {
		if ( is_null( self::$queue ) ) {
			self::$queue = WC()->queue();
		}

		return self::$queue;
	}

	/**
	 * Set queue instance.
	 *
	 * @param WC_Queue_Interface $queue Queue instance.
	 */
	public static function set_queue( $queue ) {
		self::$queue = $queue;
	}

	/**
	 * Hook in sync methods.
	 */
	public static function init() {
		// Add report regeneration to tools REST API.
		add_filter( 'woocommerce_debug_tools', array( __CLASS__, 'add_regenerate_tool' ) );

		// Initialize syncing hooks.
		add_action( 'wp_loaded', array( __CLASS__, 'orders_lookup_update_init' ) );

		// Initialize scheduled action handlers.
		add_action( self::QUEUE_BATCH_ACTION, array( __CLASS__, 'queue_batches' ), 10, 3 );
		add_action( self::QUEUE_DEPEDENT_ACTION, array( __CLASS__, 'queue_dependent_action' ), 10, 3 );
		add_action( self::CUSTOMERS_BATCH_ACTION, array( __CLASS__, 'customer_lookup_process_batch' ) );
		add_action( self::ORDERS_BATCH_ACTION, array( __CLASS__, 'orders_lookup_process_batch' ) );
		add_action( self::ORDERS_LOOKUP_BATCH_INIT, array( __CLASS__, 'orders_lookup_batch_init' ) );
		add_action( self::SINGLE_ORDER_ACTION, array( __CLASS__, 'orders_lookup_process_order' ) );
	}

	/**
	 * Regenerate data for reports.
	 */
	public static function regenerate_report_data() {
		// Add registered customers to the lookup table before updating order stats
		// so that the orders can be associated with the `customer_id` column.
		self::customer_lookup_batch_init();
		// Queue orders lookup to occur after customers lookup generation is done.
		self::queue_dependent_action( self::ORDERS_LOOKUP_BATCH_INIT, array(), self::CUSTOMERS_BATCH_ACTION );

		return __( 'Report table data is being rebuilt.  Please allow some time for data to fully populate.', 'woocommerce-admin' );
	}

	/**
	 * Clears all queued actions.
	 */
	public static function clear_queued_actions() {
		$hooks = array(
			self::QUEUE_BATCH_ACTION,
			self::QUEUE_DEPEDENT_ACTION,
			self::CUSTOMERS_BATCH_ACTION,
			self::ORDERS_BATCH_ACTION,
			self::ORDERS_LOOKUP_BATCH_INIT,
			self::SINGLE_ORDER_ACTION,
		);
		foreach ( $hooks as $hook ) {
			self::queue()->cancel_all( $hook, null, self::QUEUE_GROUP );
		}
	}

	/**
	 * Adds regenerate tool to WC system status tools API.
	 *
	 * @param array $tools List of tools.
	 * @return array
	 */
	public static function add_regenerate_tool( $tools ) {
		if ( isset( $_GET['page'] ) && 'wc-status' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $tools;
		}

		return array_merge(
			$tools,
			array(
				'rebuild_stats' => array(
					'name'     => __( 'Rebuild reports data', 'woocommerce-admin' ),
					'button'   => __( 'Rebuild reports', 'woocommerce-admin' ),
					'desc'     => __( 'This tool will rebuild all of the information used by the reports.', 'woocommerce-admin' ),
					'callback' => array( __CLASS__, 'regenerate_report_data' ),
				),
			)
		);
	}

	/**
	 * Schedule an action to process a single Order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function schedule_single_order_process( $order_id ) {
		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}

		if ( apply_filters( 'woocommerce_disable_order_scheduling', false ) ) {
			self::orders_lookup_process_order( $order_id );
			return;
		}

		// This can get called multiple times for a single order, so we look
		// for existing pending jobs for the same order to avoid duplicating efforts.
		$existing_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => "[{$order_id}]",
				'group'    => self::QUEUE_GROUP,
			)
		);

		if ( $existing_jobs ) {
			$existing_job = current( $existing_jobs );

			// Bail out if there's a pending single order action, or a pending dependent action.
			if (
				( self::SINGLE_ORDER_ACTION === $existing_job->get_hook() ) ||
				(
					self::QUEUE_DEPEDENT_ACTION === $existing_job->get_hook() &&
					in_array( self::SINGLE_ORDER_ACTION, $existing_job->get_args() )
				)
			) {
				return;
			}
		}

		// We want to ensure that customer lookup updates are scheduled before order updates.
		self::queue_dependent_action( self::SINGLE_ORDER_ACTION, array( $order_id ), self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Attach order lookup update hooks.
	 */
	public static function orders_lookup_update_init() {
		// Activate WC_Order extension.
		WC_Admin_Order::add_filters();

		add_action( 'save_post', array( __CLASS__, 'schedule_single_order_process' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'schedule_single_order_process' ) );

		WC_Admin_Reports_Orders_Stats_Data_Store::init();
		WC_Admin_Reports_Customers_Data_Store::init();
		WC_Admin_Reports_Coupons_Data_Store::init();
		WC_Admin_Reports_Products_Data_Store::init();
		WC_Admin_Reports_Taxes_Data_Store::init();
	}

	/**
	 * Init order/product lookup tables update (in batches).
	 */
	public static function orders_lookup_batch_init() {
		$batch_size  = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$order_query = new WC_Order_Query(
			array(
				'return'   => 'ids',
				'limit'    => 1,
				'paginate' => true,
			)
		);
		$result      = $order_query->get_orders();

		if ( 0 === $result->total ) {
			return;
		}

		$num_batches = ceil( $result->total / $batch_size );

		self::queue_batches( 1, $num_batches, self::ORDERS_BATCH_ACTION );
	}

	/**
	 * Process a batch of orders to update (stats and products).
	 *
	 * @param int $batch_number Batch number to process (essentially a query page number).
	 * @return void
	 */
	public static function orders_lookup_process_batch( $batch_number ) {
		$batch_size  = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$order_query = new WC_Order_Query(
			array(
				'return'  => 'ids',
				'limit'   => $batch_size,
				'page'    => $batch_number,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);
		$order_ids   = $order_query->get_orders();

		foreach ( $order_ids as $order_id ) {
			self::orders_lookup_process_order( $order_id );
		}
	}

	/**
	 * Process a single order to update lookup tables for.
	 * If an error is encountered in one of the updates, a retry action is scheduled.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function orders_lookup_process_order( $order_id ) {
		$result = array_sum(
			array(
				WC_Admin_Reports_Orders_Stats_Data_Store::sync_order( $order_id ),
				WC_Admin_Reports_Products_Data_Store::sync_order_products( $order_id ),
				WC_Admin_Reports_Coupons_Data_Store::sync_order_coupons( $order_id ),
				WC_Admin_Reports_Taxes_Data_Store::sync_order_taxes( $order_id ),
			)
		);

		// If all updates were either skipped or successful, we're done.
		// The update methods return -1 for skip, or a boolean success indicator.
		if ( 4 === absint( $result ) ) {
			return;
		}

		// Otherwise assume an error occurred and reschedule.
		self::schedule_single_order_process( $order_id );
	}

	/**
	 * Returns the batch size for regenerating reports.
	 * Note: can differ per batch action.
	 *
	 * @param string $action Single batch action name.
	 * @return int Batch size.
	 */
	public static function get_batch_size( $action ) {
		$batch_sizes = array(
			self::QUEUE_BATCH_ACTION     => 100,
			self::CUSTOMERS_BATCH_ACTION => 25,
			self::ORDERS_BATCH_ACTION    => 10,
		);
		$batch_size  = isset( $batch_sizes[ $action ] ) ? $batch_sizes[ $action ] : 25;

		/**
		 * Filter the batch size for regenerating a report table.
		 *
		 * @param int    $batch_size Batch size.
		 * @param string $action Batch action name.
		 */
		return apply_filters( 'wc_admin_report_regenerate_batch_size', $batch_size, $action );
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
		$batch_size       = self::get_batch_size( self::QUEUE_BATCH_ACTION );
		$range_size       = 1 + ( $range_end - $range_start );
		$action_timestamp = time() + 5;

		if ( $range_size > $batch_size ) {
			// If the current batch range is larger than a single batch,
			// split the range into $queue_batch_size chunks.
			$chunk_size = ceil( $range_size / $batch_size );

			for ( $i = 0; $i < $batch_size; $i++ ) {
				$batch_start = $range_start + ( $i * $chunk_size );
				$batch_end   = min( $range_end, $range_start + ( $chunk_size * ( $i + 1 ) ) - 1 );

				self::queue()->schedule_single(
					$action_timestamp,
					self::QUEUE_BATCH_ACTION,
					array( $batch_start, $batch_end, $single_batch_action ),
					self::QUEUE_GROUP
				);
			}
		} else {
			// Otherwise, queue the single batches.
			for ( $i = $range_start; $i <= $range_end; $i++ ) {
				self::queue()->schedule_single( $action_timestamp, $single_batch_action, array( $i ), self::QUEUE_GROUP );
			}
		}
	}

	/**
	 * Queue an action to run after another.
	 *
	 * @param string $action Action to run after prerequisite.
	 * @param array  $action_args Action arguments.
	 * @param string $prerequisite_action Prerequisite action.
	 */
	public static function queue_dependent_action( $action, $action_args, $prerequisite_action ) {
		$blocking_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => $prerequisite_action, // search is used instead of hook to find queued batch creation.
				'group'    => self::QUEUE_GROUP,
			)
		);

		$next_job_schedule = null;
		$blocking_job_hook = null;

		if ( $blocking_jobs ) {
			$blocking_job      = current( $blocking_jobs );
			$blocking_job_hook = $blocking_job->get_hook();
			$next_job_schedule = $blocking_job->get_schedule()->next();
		}

		// Eliminate the false positive scenario where the blocking job is
		// actually another queued dependent action awaiting the same prerequisite.
		// Also, ensure that the next schedule is a DateTime (it can be null).
		if (
			is_a( $next_job_schedule, 'DateTime' ) &&
			( self::QUEUE_DEPEDENT_ACTION !== $blocking_job_hook )
		) {
			self::queue()->schedule_single(
				$next_job_schedule->getTimestamp() + 5,
				self::QUEUE_DEPEDENT_ACTION,
				array( $action, $action_args, $prerequisite_action ),
				self::QUEUE_GROUP
			);
		} else {
			self::queue()->schedule_single( time() + 5, $action, $action_args, self::QUEUE_GROUP );
		}
	}

	/**
	 * Init customer lookup table update (in batches).
	 */
	public static function customer_lookup_batch_init() {
		$batch_size      = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query  = new WP_User_Query(
			array(
				'fields' => 'ID',
				'number' => 1,
			)
		);
		$total_customers = $customer_query->get_total();

		if ( 0 === $total_customers ) {
			return;
		}

		$num_batches = ceil( $total_customers / $batch_size );

		self::queue_batches( 1, $num_batches, self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Process a batch of customers to update.
	 *
	 * @param int $batch_number Batch number to process (essentially a query page number).
	 * @return void
	 */
	public static function customer_lookup_process_batch( $batch_number ) {
		$batch_size     = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query = new WP_User_Query(
			array(
				'fields'  => 'ID',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => $batch_size,
				'paged'   => $batch_number,
			)
		);

		$customer_ids = $customer_query->get_results();

		foreach ( $customer_ids as $customer_id ) {
			// @todo Schedule single customer update if this fails?
			WC_Admin_Reports_Customers_Data_Store::update_registered_customer( $customer_id );
		}
	}
}

WC_Admin_Reports_Sync::init();
