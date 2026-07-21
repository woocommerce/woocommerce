<?php
/**
 * WooCommerce Analytics.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrderStatsDataStore;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

/**
 * Contains backend logic for the Analytics feature.
 */
class Analytics {
	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_analytics_enabled';
	/**
	 * Clear cache tool identifier.
	 */
	const CACHE_TOOL_ID = 'clear_woocommerce_analytics_cache';
	/**
	 * Full refund fix data tool identifier.
	 *
	 * @since 10.8.0
	 */
	const FULL_REFUND_FIX_DATA_TOOL_ID = 'fix_woocommerce_analytics_full_refund_data';

	/**
	 * Option holding the refund double-count scan state: running_count, complete,
	 * scan_attempts, last_scan_attempt.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_OPTION = 'woocommerce_analytics_refund_double_count';

	/**
	 * Action Scheduler hook for a refund double-count detection batch.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_SCAN_HOOK = 'woocommerce_analytics_refund_double_count_scan_batch';

	/**
	 * Action Scheduler hook for a refund double-count fix (re-import) batch.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_FIX_HOOK = 'woocommerce_analytics_refund_double_count_fix_batch';

	/**
	 * Maximum number of affected parent orders scanned/fixed per batch.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_BATCH_SIZE = 10000;

	/**
	 * Size of the parent_id range aggregated per refund double-count batch.
	 *
	 * Bounds the GROUP BY temp table: wc_order_stats has no index starting with
	 * parent_id, so an unbounded query would re-aggregate every row past the
	 * cursor on each batch.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_WINDOW = 500000;

	/**
	 * Maximum number of times the refund double-count scan is scheduled,
	 * counting the initial DB-upgrade schedule and every self-heal reschedule.
	 *
	 * @since 11.1.0
	 */
	const REFUND_DOUBLE_COUNT_MAX_SCAN_ATTEMPTS = 5;

	/**
	 * Class instance.
	 *
	 * @var Analytics instance
	 */
	protected static $instance = null;

	/**
	 * Determines if the feature has been toggled on or off.
	 *
	 * @var boolean
	 */
	protected static $is_updated = false;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::TOGGLE_OPTION_NAME, array( $this, 'reload_page_on_toggle' ), 10, 2 );
		add_action( 'woocommerce_settings_saved', array( $this, 'maybe_reload_page' ) );

		if ( ! FeaturesUtil::feature_is_enabled( 'analytics' ) ) {
			return;
		}

		add_filter( 'woocommerce_component_settings_preload_endpoints', array( $this, 'add_preload_endpoints' ) );
		add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'add_user_data_fields' ) );
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_cache_clear_tool' ) );
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_regenerate_order_fulfillment_status_tool' ), 12 );

		// Always register the batch hook so in-flight jobs survive after the legacy
		// flag is cleared (clearing happens before the first batch is queued).
		add_action( 'woocommerce_analytics_refund_fix_batch', array( $this, 'process_refund_fix_batch' ) );

		// Refund double-count (#66320) historical cleanup: one-time scan scheduled by the
		// 11.1.0 DB upgrade, plus an on-demand fix. Batch hooks are always registered so
		// in-flight self-scheduling jobs survive across requests.
		add_action( self::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( $this, 'process_refund_double_count_scan_batch' ) );
		add_action( self::REFUND_DOUBLE_COUNT_FIX_HOOK, array( $this, 'process_refund_double_count_fix_batch' ) );

		// A re-import that reprocesses existing rows (skip-existing unchecked) repairs the
		// double-counted rows, so clear the stale scan count when that import starts.
		add_action( 'woocommerce_analytics_regenerate_init', array( $this, 'maybe_clear_refund_double_count_on_regenerate' ), 10, 2 );

		if ( $this->should_show_refund_fix_tool() ) {
			add_filter( 'woocommerce_debug_tools', array( $this, 'register_full_refund_fix_data_tool' ) );
			add_action( 'admin_footer', array( $this, 'output_refund_fix_tool_js' ) );
			add_action( 'wp_ajax_woocommerce_check_refund_fix_needed', array( $this, 'ajax_check_refund_fix_needed' ) );
		}
	}

	/**
	 * Add the feature toggle to the features settings.
	 *
	 * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
	 *
	 * @param array $features Feature sections.
	 * @return array
	 */
	public static function add_feature_toggle( $features ) {
		return $features;
	}

	/**
	 * Reloads the page when the option is toggled to make sure all Analytics features are loaded.
	 *
	 * @param string $old_value Old value.
	 * @param string $value     New value.
	 */
	public static function reload_page_on_toggle( $old_value, $value ) {
		if ( $old_value === $value ) {
			return;
		}

		self::$is_updated = true;
	}

	/**
	 * Reload the page if the setting has been updated.
	 */
	public static function maybe_reload_page() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! self::$is_updated ) {
			return;
		}

		wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		exit();
	}

	/**
	 * Preload data from the countries endpoint.
	 *
	 * @param array $endpoints Array of preloaded endpoints.
	 * @return array
	 */
	public function add_preload_endpoints( $endpoints ) {
		$screen_id = ( function_exists( 'get_current_screen' ) && get_current_screen() ) ? get_current_screen()->id : '';

		// Only preload endpoints on wc-admin pages.
		if ( 'woocommerce_page_wc-admin' === $screen_id ) {
			$endpoints['performanceIndicators'] = '/wc-analytics/reports/performance-indicators/allowed';
			$endpoints['leaderboards']          = '/wc-analytics/leaderboards/allowed';
		}

		return $endpoints;
	}

	/**
	 * Adds fields so that we can store user preferences for the columns to display on a report.
	 *
	 * @param array $user_data_fields User data fields.
	 * @return array
	 */
	public function add_user_data_fields( $user_data_fields ) {
		return array_merge(
			$user_data_fields,
			array(
				'categories_report_columns',
				'coupons_report_columns',
				'customers_report_columns',
				'orders_report_columns',
				'products_report_columns',
				'revenue_report_columns',
				'taxes_report_columns',
				'variations_report_columns',
				'dashboard_sections',
				'dashboard_chart_type',
				'dashboard_chart_interval',
				'dashboard_leaderboard_rows',
				'order_attribution_install_banner_dismissed',
				'scheduled_updates_promotion_notice_dismissed',
			)
		);
	}

	/**
	 * Register the cache clearing tool on the WooCommerce > Status > Tools page.
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public function register_cache_clear_tool( $debug_tools ) {
		$settings_url = add_query_arg(
			array(
				'page' => 'wc-admin',
				'path' => '/analytics/settings',
			),
			get_admin_url( null, 'admin.php' )
		);

		$debug_tools[ self::CACHE_TOOL_ID ] = array(
			'name'     => __( 'Clear analytics cache', 'woocommerce' ),
			'button'   => __( 'Clear', 'woocommerce' ),
			'desc'     => sprintf(
				/* translators: 1: opening link tag, 2: closing tag */
				__( 'This tool will reset the cached values used in WooCommerce Analytics. If numbers still look off, try %1$sReimporting Historical Data%2$s.', 'woocommerce' ),
				'<a href="' . esc_url( $settings_url ) . '">',
				'</a>'
			),
			'callback' => array( $this, 'run_clear_cache_tool' ),
		);

		return $debug_tools;
	}

	/**
	 * Whether the full refund fix tool should be shown to the merchant.
	 *
	 * Returns true when the store still has legacy refund data OR when the fix was
	 * recently queued and the merchant has not yet dismissed the tool. New stores
	 * (where the option was never set) never see the tool.
	 *
	 * @since 10.8.0
	 *
	 * @return bool
	 */
	private function should_show_refund_fix_tool(): bool {
		return ! OrderUtil::uses_new_full_refund_data()
			|| 'yes' === get_option( 'woocommerce_analytics_show_old_refund_data_tool' );
	}

	/**
	 * Register the full refund fix data tool on the WooCommerce > Status > Tools page.
	 *
	 * The Fix button is disabled by default (via the PHP 'disabled' field). JS enables it
	 * only after a Check confirms there are affected orders to fix.
	 *
	 * @since 10.8.0
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public function register_full_refund_fix_data_tool( $debug_tools ) {
		$desc = __( 'This tool will fix the full refund data used in WooCommerce Analytics and re-import all the refunded historical data.', 'woocommerce' );

		$disabled = true;

		$debug_tools[ self::FULL_REFUND_FIX_DATA_TOOL_ID ] = array(
			'name'     => __( 'Fix analytics full refund data', 'woocommerce' ),
			'button'   => __( 'Fix', 'woocommerce' ),
			'desc'     => $desc,
			'callback' => array( $this, 'run_full_refund_fix_data_tool' ),
			'disabled' => $disabled,
		);

		return $debug_tools;
	}

	/**
	 * Handles the Fix button submission for the full refund fix tool.
	 *
	 * When the "Disable tool" action is requested (i.e. the Check confirmed no affected
	 * orders), deletes the old-data flag so the tool no longer appears. Otherwise
	 * schedules the first batch job to re-import all affected refund orders.
	 *
	 * @since 10.8.0
	 *
	 * @return string Success message.
	 */
	public function run_full_refund_fix_data_tool() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WooCommerce tools framework.
		if ( isset( $_GET['wc_refund_fix_action'] ) && 'disable' === sanitize_key( $_GET['wc_refund_fix_action'] ) ) {
			delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
			delete_option( 'woocommerce_analytics_show_old_refund_data_tool' );
			return __( 'Tool dismissed.', 'woocommerce' );
		}

		if ( self::is_batch_pending_or_running( 'woocommerce_analytics_refund_fix_batch' ) ) {
			return __( 'A fix is already in progress, please check back later.', 'woocommerce' );
		}

		// Clear the legacy flag before queuing so that every batch job runs with
		// the corrected full-refund import logic (uses_new_full_refund_data() → true).
		// Set the show-tool option so the tool stays visible until the merchant dismisses it.
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		update_option( 'woocommerce_analytics_show_old_refund_data_tool', 'yes' );

		self::schedule_batch( 'woocommerce_analytics_refund_fix_batch', 0 );

		return __( 'Re-importing refunded orders in batches. Full refund data will be updated shortly.', 'woocommerce' );
	}

	/**
	 * Process one batch of refund orders for the analytics fix.
	 *
	 * Fetches up to 100 orders with incorrect refund stats (cursor-based so
	 * concurrent imports cannot shift the result window) and re-imports each
	 * directly. Schedules itself for the next cursor position when the batch is
	 * full, stopping automatically once no more rows are found.
	 *
	 * @since 10.8.0
	 *
	 * @param int $min_order_id Exclusive lower bound on order_id; 0 for the first batch.
	 * @return void
	 * @throws \Exception On database error so Action Scheduler marks the job as failed.
	 */
	public function process_refund_fix_batch( $min_order_id = 0 ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$refunded_orders = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT order_stats.order_id
				FROM {$wpdb->prefix}wc_order_stats AS order_stats
				INNER JOIN {$wpdb->prefix}wc_order_stats AS parent_stats ON order_stats.parent_id = parent_stats.order_id
				WHERE order_stats.total_sales < 0
					AND order_stats.total_sales = order_stats.net_total
					AND order_stats.total_sales != order_stats.shipping_total
					AND order_stats.total_sales != order_stats.tax_total
					AND (parent_stats.shipping_total > 0 OR parent_stats.tax_total > 0)
					AND order_stats.order_id > %d
				ORDER BY order_stats.order_id ASC
				LIMIT 100",
				$min_order_id
			)
		);

		if ( ! $refunded_orders ) {
			if ( $wpdb->last_error ) {
				throw new \Exception( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			return;
		}

		foreach ( $refunded_orders as $refunded_order ) {
			OrdersScheduler::import( intval( $refunded_order->order_id ) );
		}

		if ( count( $refunded_orders ) >= 100 ) {
			$last_order_id = intval( end( $refunded_orders )->order_id );
			self::schedule_batch( 'woocommerce_analytics_refund_fix_batch', $last_order_id, 5 );
		}
	}

	/**
	 * Return the parsed refund double-count scan state with sane defaults.
	 *
	 * @since 11.1.0
	 *
	 * @return array{count:int, complete:bool, attempts:int, last_attempt:int}
	 */
	public static function get_refund_double_count_state(): array {
		$state = get_option( self::REFUND_DOUBLE_COUNT_OPTION );
		$state = is_array( $state ) ? $state : array();

		return array(
			'count'        => isset( $state['running_count'] ) ? (int) $state['running_count'] : 0,
			'complete'     => ! empty( $state['complete'] ),
			'attempts'     => isset( $state['scan_attempts'] ) ? (int) $state['scan_attempts'] : 0,
			'last_attempt' => isset( $state['last_scan_attempt'] ) ? (int) $state['last_scan_attempt'] : 0,
		);
	}

	/**
	 * Merge changes into the stored refund double-count state, preserving the
	 * scan attempt tracking fields across progress writes.
	 *
	 * @since 11.1.0
	 *
	 * @param array $changes State keys to overwrite.
	 * @return void
	 */
	private static function update_refund_double_count_state( array $changes ): void {
		$state = get_option( self::REFUND_DOUBLE_COUNT_OPTION );
		$state = is_array( $state ) ? $state : array();

		update_option( self::REFUND_DOUBLE_COUNT_OPTION, array_merge( $state, $changes ), false );
	}

	/**
	 * Reschedule the refund double-count scan when it never ran or died before
	 * completing, so a failed schedule or batch cannot silently suppress the
	 * merchant notice forever.
	 *
	 * Called from the imports/status REST endpoint (polled whenever the
	 * analytics settings page loads), so healing is lazy — no cron. Gated to at
	 * most REFUND_DOUBLE_COUNT_MAX_SCAN_ATTEMPTS total scheduling attempts, at
	 * least an hour apart, and skipped while a scan is already queued or a
	 * historical import is rewriting the very rows the scan reads. Only stores
	 * that owe a scan have the state option at all (the 11.1.0 upgrade creates
	 * it), so fresh installs never schedule one.
	 *
	 * @since 11.1.0
	 *
	 * @return void
	 */
	public static function maybe_reschedule_refund_double_count_scan(): void {
		if ( false === get_option( self::REFUND_DOUBLE_COUNT_OPTION ) ) {
			return;
		}

		$state = self::get_refund_double_count_state();

		if ( $state['complete'] ) {
			return;
		}

		if ( $state['attempts'] >= self::REFUND_DOUBLE_COUNT_MAX_SCAN_ATTEMPTS ) {
			return;
		}

		if ( time() - $state['last_attempt'] < HOUR_IN_SECONDS ) {
			return;
		}

		if ( self::is_batch_pending_or_running( self::REFUND_DOUBLE_COUNT_SCAN_HOOK ) ) {
			return;
		}

		if ( ReportsSync::is_importing() ) {
			return;
		}

		// Record the attempt before scheduling so a crash between the two
		// writes cannot grant unaccounted retries.
		self::update_refund_double_count_state(
			array(
				'scan_attempts'     => $state['attempts'] + 1,
				'last_scan_attempt' => time(),
			)
		);

		// The settings page polls imports/status from more than one component,
		// so two concurrent requests can both pass the pending check; the
		// unique flag makes Action Scheduler drop the duplicate.
		as_schedule_single_action( time(), self::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data', true );
	}

	/**
	 * Whether an Action Scheduler job for the given batch hook is currently
	 * pending or running.
	 *
	 * Detected live from Action Scheduler so a batch that dies never leaves a
	 * stuck "in progress" flag behind.
	 *
	 * @since 11.1.0
	 *
	 * @param string $hook Action Scheduler hook name.
	 * @return bool
	 */
	private static function is_batch_pending_or_running( string $hook ): bool {
		return ! empty(
			as_get_scheduled_actions(
				array(
					'hook'     => $hook,
					'status'   => array( \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ),
					'per_page' => 1,
					'orderby'  => 'none',
				),
				'ids'
			)
		);
	}

	/**
	 * Schedule a single batch job in the wc-admin-data group.
	 *
	 * @since 11.1.0
	 *
	 * @param string $hook   Action Scheduler hook name.
	 * @param int    $cursor Cursor argument passed to the batch (exclusive lower bound on the IDs it processes).
	 * @param int    $delay  Seconds to wait before the batch may run.
	 * @return void
	 */
	private static function schedule_batch( string $hook, int $cursor, int $delay = 0 ): void {
		WC()->queue()->schedule_single(
			time() + $delay,
			$hook,
			array( $cursor ),
			'wc-admin-data'
		);
	}

	/**
	 * Effective batch size for the refund double-count scan and fix batches.
	 *
	 * @since 11.1.0
	 *
	 * @return int
	 */
	private static function get_refund_double_count_batch_size(): int {
		/**
		 * Filters the maximum number of affected parent orders processed per
		 * refund double-count scan/fix batch.
		 *
		 * @since 11.1.0
		 *
		 * @param int $batch_size Maximum affected parent orders per batch.
		 */
		return max( 1, (int) apply_filters( 'woocommerce_analytics_refund_double_count_batch_size', self::REFUND_DOUBLE_COUNT_BATCH_SIZE ) );
	}

	/**
	 * Whether a refund double-count fix batch is currently pending or running.
	 *
	 * @since 11.1.0
	 *
	 * @return bool
	 */
	public static function is_refund_double_count_fix_in_progress(): bool {
		return self::is_batch_pending_or_running( self::REFUND_DOUBLE_COUNT_FIX_HOOK );
	}

	/**
	 * Schedule the first refund double-count fix batch.
	 *
	 * @since 11.1.0
	 *
	 * @return void
	 */
	public static function schedule_refund_double_count_fix(): void {
		self::schedule_batch( self::REFUND_DOUBLE_COUNT_FIX_HOOK, 0 );
	}

	/**
	 * Build the SQL that selects the next batch of parent order IDs whose refund
	 * rows over-sum their parent's own totals — the signature of the #66320
	 * partial-then-full double-count.
	 *
	 * The +0.01 tolerance guards against floating-point noise; COUNT(*) > 1 keeps
	 * single full refunds out (they cannot double-count). The window upper bound
	 * caps how many rows each batch aggregates (wc_order_stats has no index
	 * starting with parent_id, so LIMIT alone cannot bound the GROUP BY work),
	 * while the LIMIT caps how many affected parents a single batch processes.
	 * All of a parent's refund rows share one parent_id, so no group ever
	 * straddles a window boundary.
	 *
	 * @since 11.1.0
	 *
	 * @param int $after_parent_id Exclusive lower bound on parent_id; 0 for the first batch.
	 * @param int $window_end      Inclusive upper bound on parent_id for this batch.
	 * @return string Prepared SQL selecting the next batch of affected parent_id values in ascending order.
	 */
	private function get_refund_double_count_parents_sql( int $after_parent_id, int $window_end ): string {
		global $wpdb;

		return $wpdb->prepare(
			"SELECT r.parent_id
			FROM {$wpdb->prefix}wc_order_stats AS r
			INNER JOIN {$wpdb->prefix}wc_order_stats AS o ON o.order_id = r.parent_id
			WHERE r.parent_id > %d AND r.parent_id <= %d
			GROUP BY r.parent_id, o.net_total, o.tax_total, o.shipping_total
			HAVING COUNT(*) > 1
				AND ABS( SUM( r.net_total + r.tax_total + r.shipping_total ) ) > ( o.net_total + o.tax_total + o.shipping_total ) + 0.01
			ORDER BY r.parent_id ASC
			LIMIT %d",
			$after_parent_id,
			$window_end,
			self::get_refund_double_count_batch_size()
		);
	}

	/**
	 * End of the parent_id window containing the given cursor: the next multiple
	 * of the window size strictly above it. Works both for boundary cursors left
	 * by an exhausted window and mid-window cursors left by a full-LIMIT batch.
	 *
	 * @since 11.1.0
	 *
	 * @param int $cursor Exclusive lower bound on parent_id for the current batch.
	 * @return int Inclusive upper bound on parent_id for the current batch.
	 */
	private static function get_refund_double_count_window_end( int $cursor ): int {
		return ( intdiv( $cursor, self::REFUND_DOUBLE_COUNT_WINDOW ) + 1 ) * self::REFUND_DOUBLE_COUNT_WINDOW;
	}

	/**
	 * Highest order_id present in the order stats table (0 when empty).
	 *
	 * Used to detect when the last parent_id window has been swept; a MAX() on
	 * the primary key is O(1).
	 *
	 * @since 11.1.0
	 *
	 * @return int
	 */
	private static function get_max_order_stats_id(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return intval( $wpdb->get_var( "SELECT MAX(order_id) FROM {$wpdb->prefix}wc_order_stats" ) );
	}

	/**
	 * Process one batch of the refund double-count detection scan.
	 *
	 * Counts affected parents in the current parent_id window (capped at the
	 * batch size), accumulates the running total in a single option, and
	 * self-schedules — from the last processed parent_id while a window still
	 * has full batches, otherwise from the next window boundary — until the
	 * last window past MAX(order_id) is swept. Only stores a count — never an
	 * ID list — so the notice read stays O(1).
	 *
	 * @since 11.1.0
	 *
	 * @param int $cursor Exclusive lower bound on parent_id for this batch; 0 for the first batch.
	 * @return void
	 * @throws \Exception On database error so Action Scheduler marks the job as failed.
	 */
	public function process_refund_double_count_scan_batch( $cursor = 0 ): void {
		$cursor = intval( $cursor );

		// Only new-data stores are affected by #66320. Old-data stores are repaired by
		// their own migration re-import, so mark the scan complete with a zero count.
		if ( ! OrderUtil::uses_new_full_refund_data() ) {
			$this->finish_refund_double_count_scan( 0 );
			return;
		}

		global $wpdb;

		$state         = self::get_refund_double_count_state();
		$running_count = ( $cursor > 0 ) ? $state['count'] : 0;

		$window_end = self::get_refund_double_count_window_end( $cursor );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$parent_ids = $wpdb->get_col( $this->get_refund_double_count_parents_sql( $cursor, $window_end ) );

		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'Refund double-count scan batch failed at cursor %d: %s', $cursor, $wpdb->last_error ),
				array( 'source' => 'wc-analytics-order-import' )
			);
			throw new \Exception( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$running_count += count( $parent_ids );

		if ( count( $parent_ids ) >= self::get_refund_double_count_batch_size() ) {
			// Full batch: more affected parents may remain in this window.
			$next_cursor = intval( end( $parent_ids ) );
		} elseif ( $window_end >= self::get_max_order_stats_id() ) {
			// Window exhausted and no rows can exist past it: scan complete.
			$this->finish_refund_double_count_scan( $running_count );
			return;
		} else {
			$next_cursor = $window_end;
		}

		self::update_refund_double_count_state(
			array(
				'running_count' => $running_count,
				'complete'      => false,
			)
		);

		self::schedule_batch( self::REFUND_DOUBLE_COUNT_SCAN_HOOK, $next_cursor, 5 );
	}

	/**
	 * Persist the terminal scan state.
	 *
	 * @since 11.1.0
	 *
	 * @param int $count Final affected-order count.
	 * @return void
	 */
	private function finish_refund_double_count_scan( int $count ): void {
		self::update_refund_double_count_state(
			array(
				'running_count' => $count,
				'complete'      => true,
			)
		);
	}

	/**
	 * Process one batch of the refund double-count fix.
	 *
	 * Re-imports each affected parent order (which re-syncs the parent and every
	 * refund row with the corrected #66320 logic) and self-schedules — from the
	 * last selected parent_id while a window still has full batches, otherwise
	 * from the next window boundary — until the last window past MAX(order_id)
	 * is swept. The cursor always advances past every selected ID — never
	 * re-queried from the start — so a row that somehow fails to repair cannot
	 * loop the sweep forever. On the final batch the stored count is reset to
	 * zero so the notice self-heals.
	 *
	 * @since 11.1.0
	 *
	 * @param int $cursor Exclusive lower bound on parent_id for this batch; 0 for the first batch.
	 * @return void
	 * @throws \Exception On database error so Action Scheduler marks the job as failed.
	 */
	public function process_refund_double_count_fix_batch( $cursor = 0 ): void {
		$cursor = intval( $cursor );

		global $wpdb;

		$window_end = self::get_refund_double_count_window_end( $cursor );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$parent_ids = $wpdb->get_col( $this->get_refund_double_count_parents_sql( $cursor, $window_end ) );

		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'Refund double-count fix batch failed at cursor %d: %s', $cursor, $wpdb->last_error ),
				array( 'source' => 'wc-analytics-order-import' )
			);
			throw new \Exception( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		foreach ( $parent_ids as $parent_id ) {
			OrdersScheduler::import( intval( $parent_id ) );
		}

		if ( count( $parent_ids ) >= self::get_refund_double_count_batch_size() ) {
			// Full batch: more affected parents may remain in this window.
			$next_cursor = intval( end( $parent_ids ) );
		} elseif ( $window_end >= self::get_max_order_stats_id() ) {
			// The whole table has been swept; the affected rows are now correct.
			$this->finish_refund_double_count_scan( 0 );
			return;
		} else {
			$next_cursor = $window_end;
		}

		self::schedule_batch( self::REFUND_DOUBLE_COUNT_FIX_HOOK, $next_cursor, 5 );
	}

	/**
	 * Clear the refund double-count scan state when a full-history re-import
	 * reprocesses existing rows (skip-existing unchecked), since that repairs
	 * every affected row.
	 *
	 * A skip-existing-checked import leaves the affected orders untouched, and a
	 * windowed import ($days !== false) never reprocesses affected orders older
	 * than the window, so in both cases the state is kept to avoid permanently
	 * hiding a real issue.
	 *
	 * @since 11.1.0
	 *
	 * @param int|bool $days          Number of days to import, or false for the full history.
	 * @param bool     $skip_existing Whether the import skips already-imported records.
	 * @return void
	 */
	public function maybe_clear_refund_double_count_on_regenerate( $days, $skip_existing ): void {
		if ( ! $skip_existing && false === $days ) {
			delete_option( self::REFUND_DOUBLE_COUNT_OPTION );
		}
	}

	/**
	 * AJAX handler: checks whether the store has analytics order stats rows that
	 * look like unprocessed full refunds.
	 *
	 * @since 10.8.0
	 * @return void
	 */
	public function ajax_check_refund_fix_needed(): void {
		check_ajax_referer( 'woocommerce_refund_fix_check', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'woocommerce' ) ), 403 );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_affected = $wpdb->get_var(
			"SELECT order_stats.order_id
			FROM {$wpdb->prefix}wc_order_stats AS order_stats
			INNER JOIN {$wpdb->prefix}wc_order_stats AS parent_stats ON order_stats.parent_id = parent_stats.order_id
			WHERE order_stats.total_sales < 0
				AND order_stats.total_sales = order_stats.net_total
				AND order_stats.total_sales != order_stats.shipping_total
				AND order_stats.total_sales != order_stats.tax_total
				AND (parent_stats.shipping_total > 0 OR parent_stats.tax_total > 0)
			LIMIT 1"
		);

		if ( $wpdb->last_error ) {
			wp_send_json_error(
				array(
					'code'    => 'db_error',
					'message' => $wpdb->last_error,
				),
				500
			);
		}

		$fix_in_progress = self::is_batch_pending_or_running( 'woocommerce_analytics_refund_fix_batch' );

		wp_send_json_success(
			array(
				'needs_fix'       => ! empty( $has_affected ),
				'fix_in_progress' => $fix_in_progress,
			)
		);
	}

	/**
	 * Output the inline script that injects a "Check" button into the full refund
	 * fix tool row on the WooCommerce > Status > Tools page.
	 *
	 * @since 10.8.0
	 * @return void
	 */
	public function output_refund_fix_tool_js(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WooCommerce tools framework.
		if ( ! isset( $_GET['page'], $_GET['tab'] ) || 'wc-status' !== $_GET['page'] || 'tools' !== $_GET['tab'] ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WooCommerce tools framework.
		if ( isset( $_GET['wc_refund_fix_action'] ) && 'disable' === sanitize_key( $_GET['wc_refund_fix_action'] ) ) {
			return;
		}

		$tool_class         = self::FULL_REFUND_FIX_DATA_TOOL_ID;
		$nonce              = wp_create_nonce( 'woocommerce_refund_fix_check' );
		$ajax_url           = admin_url( 'admin-ajax.php' );
		$label_check        = __( 'Check', 'woocommerce' );
		$label_working      = __( 'Checking…', 'woocommerce' );
		$msg_needs_fix      = __( 'Your store has orders that need fixing.', 'woocommerce' );
		$msg_no_fix         = __( 'No affected orders found.', 'woocommerce' );
		$label_disable_tool = __( 'Disable tool', 'woocommerce' );
		$msg_in_progress    = __( 'A fix is already in progress, please check back later.', 'woocommerce' );
		$msg_error          = __( 'Check failed, please try again.', 'woocommerce' );
		?>
		<script type="text/javascript">
		( function() {
			const toolRow = document.querySelector( 'tr.<?php echo esc_js( $tool_class ); ?>' );
			if ( ! toolRow ) {
				return;
			}
			const actionCell = toolRow.querySelector( 'td.run-tool' );
			if ( ! actionCell ) {
				return;
			}

			const statusSpan = document.createElement( 'span' );
			statusSpan.style.cssText = 'display:block;margin-top:6px;';
			statusSpan.setAttribute( 'aria-live', 'polite' );
			statusSpan.setAttribute( 'role', 'status' );

			const checkBtn = document.createElement( 'button' );
			checkBtn.type = 'button';
			checkBtn.className = 'button button-secondary';
			checkBtn.style.marginRight = '8px';
			checkBtn.textContent = <?php echo wp_json_encode( $label_check ); ?>;

			const fixBtn = actionCell.querySelector( 'input[type=submit]' );
			const originalFixLabel = fixBtn ? fixBtn.value : '';
			const toolForm = document.getElementById( 'form_<?php echo esc_js( $tool_class ); ?>' );

			checkBtn.addEventListener( 'click', function() {
				checkBtn.disabled = true;
				checkBtn.textContent = <?php echo wp_json_encode( $label_working ); ?>;
				statusSpan.textContent = '';
				statusSpan.style.color = '';

				const data = new FormData();
				data.append( 'action', 'woocommerce_check_refund_fix_needed' );
				data.append( 'nonce', <?php echo wp_json_encode( $nonce ); ?> );

				fetch( <?php echo wp_json_encode( $ajax_url ); ?>, { method: 'POST', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( json ) {
						checkBtn.disabled = false;
						checkBtn.textContent = <?php echo wp_json_encode( $label_check ); ?>;
						if ( json.success ) {
							if ( json.data.fix_in_progress ) {
								statusSpan.textContent = <?php echo wp_json_encode( $msg_in_progress ); ?>;
								statusSpan.style.color = '#1d2327';
							} else if ( json.data.needs_fix ) {
								statusSpan.textContent = <?php echo wp_json_encode( $msg_needs_fix ); ?>;
								statusSpan.style.color = '#d63638';
								if ( fixBtn ) {
									fixBtn.value = originalFixLabel;
									fixBtn.disabled = false;
								}
								const existingFlag = toolForm ? toolForm.querySelector( 'input[name="wc_refund_fix_action"]' ) : null;
								if ( existingFlag ) {
									existingFlag.parentNode.removeChild( existingFlag );
								}
							} else {
								statusSpan.textContent = <?php echo wp_json_encode( $msg_no_fix ); ?>;
								statusSpan.style.color = '#1d2327';
								if ( fixBtn ) {
									fixBtn.value = <?php echo wp_json_encode( $label_disable_tool ); ?>;
									fixBtn.disabled = false;
								}
								if ( toolForm && ! toolForm.querySelector( 'input[name="wc_refund_fix_action"]' ) ) {
									const flagInput = document.createElement( 'input' );
									flagInput.type = 'hidden';
									flagInput.name = 'wc_refund_fix_action';
									flagInput.value = 'disable';
									toolForm.appendChild( flagInput );
								}
							}
						} else {
							statusSpan.textContent = ( json.data && json.data.message ) ? json.data.message : <?php echo wp_json_encode( $msg_error ); ?>;
							statusSpan.style.color = '#d63638';
						}
					} )
					.catch( function() {
						checkBtn.disabled = false;
						checkBtn.textContent = <?php echo wp_json_encode( $label_check ); ?>;
						statusSpan.textContent = <?php echo wp_json_encode( $msg_error ); ?>;
						statusSpan.style.color = '#d63638';
					} );
			} );

			if ( fixBtn ) {
				actionCell.insertBefore( checkBtn, fixBtn );
			} else {
				actionCell.appendChild( checkBtn );
			}
			actionCell.appendChild( statusSpan );
		} )();
		</script>
		<?php
	}

	/**
	 * Register the regenerate order fulfillment status tool on the WooCommerce > Status > Tools page.
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public function register_regenerate_order_fulfillment_status_tool( $debug_tools ) {
		// Check if the fulfillments feature is enabled.
		$container           = wc_get_container();
		$features_controller = $container->get( FeaturesController::class );

		if ( ! $features_controller->feature_is_enabled( 'fulfillments' ) ) {
			return $debug_tools;
		}

		// If the order fulfillment status has already been regenerated, don't register the tool again.
		if ( true === (bool) get_option( 'woocommerce_analytics_order_fulfillment_status_regenerated' ) ) {
			return $debug_tools;
		}

		$debug_tools['regenerate_order_fulfillment_status'] = array(
			'name'     => __( 'Regenerate order fulfillment status for Analytics', 'woocommerce' ),
			'button'   => __( 'Regenerate', 'woocommerce' ),
			'desc'     => __( 'This tool will regenerate the order fulfillment status for all orders and update the Analytics data using a direct SQL query.', 'woocommerce' ),
			'callback' => array( $this, 'run_regenerate_order_fulfillment_status_tool' ),
		);

		return $debug_tools;
	}

	/**
	 * Regenerate order fulfillment status directly using SQL.
	 *
	 * @return string Success message or error message.
	 */
	public function run_regenerate_order_fulfillment_status_tool() {
		global $wpdb;

		// Check if the column exists, create it if not.
		if ( ! OrderStatsDataStore::has_fulfillment_status_column() ) {
			$create_column_result = OrderStatsDataStore::add_fulfillment_status_column();

			if ( true !== $create_column_result ) {
				return sprintf(
					/* translators: %s: error message */
					__( 'Failed to create fulfillment status column: %s', 'woocommerce' ),
					$create_column_result
				);
			}
		}

		$order_stats_table = $wpdb->prefix . 'wc_order_stats';

		// If HPOS is enabled, use the wc_orders_meta table, else use wp_postmeta.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_meta_table  = OrdersTableDataStore::get_meta_table_name();
			$order_meta_column = 'order_id';
		} else {
			$order_meta_table  = $wpdb->postmeta;
			$order_meta_column = 'post_id';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and column names cannot be prepared.
				"UPDATE {$order_stats_table} os INNER JOIN {$order_meta_table} om ON os.order_id = om.{$order_meta_column}
				SET os.fulfillment_status = CASE
					WHEN om.meta_value = %s THEN NULL
					ELSE om.meta_value
				END
				WHERE om.meta_key = %s",
				'no_fulfillments',
				'_fulfillment_status'
			)
		);

		if ( false === $updated ) {
			return __( 'Failed to update order fulfillment status. Please check the database logs for errors.', 'woocommerce' );
		}

		// Mark as completed.
		update_option( 'woocommerce_analytics_order_fulfillment_status_regenerated', true, false );

		return sprintf(
			/* translators: %d: number of orders updated */
			__( 'Successfully updated fulfillment status for %d orders.', 'woocommerce' ),
			$updated
		);
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		$report_pages = self::get_report_pages();
		foreach ( $report_pages as $report_page ) {
			if ( ! is_null( $report_page ) ) {
				wc_admin_register_page( $report_page );
			}
		}
	}

	/**
	 * Get report pages.
	 */
	public static function get_report_pages() {
		$overview_page = array(
			'id'       => 'woocommerce-analytics',
			'title'    => __( 'Analytics', 'woocommerce' ),
			'path'     => '/analytics/overview',
			'icon'     => 'dashicons-chart-bar',
			'position' => 57,
		// After WooCommerce & Product menu items.
		);

		$report_pages = array(
			$overview_page,
			array(
				'id'     => 'woocommerce-analytics-overview',
				'title'  => __( 'Overview', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/overview',
			),
			array(
				'id'     => 'woocommerce-analytics-products',
				'title'  => __( 'Products', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/products',
			),
			array(
				'id'     => 'woocommerce-analytics-revenue',
				'title'  => __( 'Revenue', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/revenue',
			),
			array(
				'id'     => 'woocommerce-analytics-orders',
				'title'  => __( 'Orders', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/orders',
			),
			array(
				'id'     => 'woocommerce-analytics-variations',
				'title'  => __( 'Variations', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/variations',
			),
			array(
				'id'     => 'woocommerce-analytics-categories',
				'title'  => __( 'Categories', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/categories',
			),
			array(
				'id'     => 'woocommerce-analytics-coupons',
				'title'  => __( 'Coupons', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/coupons',
			),
			array(
				'id'     => 'woocommerce-analytics-taxes',
				'title'  => __( 'Taxes', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/taxes',
			),
			array(
				'id'     => 'woocommerce-analytics-downloads',
				'title'  => __( 'Downloads', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/downloads',
			),
			'yes' === get_option( 'woocommerce_manage_stock' ) ? array(
				'id'     => 'woocommerce-analytics-stock',
				'title'  => __( 'Stock', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/stock',
			) : null,
			array(
				'id'     => 'woocommerce-analytics-customers',
				'title'  => __( 'Customers', 'woocommerce' ),
				'parent' => 'woocommerce',
				'path'   => '/customers',
			),
			array(
				'id'     => 'woocommerce-analytics-settings',
				'title'  => __( 'Settings', 'woocommerce' ),
				'parent' => 'woocommerce-analytics',
				'path'   => '/analytics/settings',
			),
		);

		/**
		 * The analytics report items used in the menu.
		 *
		 * @since 6.4.0
		 */
		return apply_filters( 'woocommerce_analytics_report_menu_items', $report_pages );
	}

	/**
	 * "Clear" analytics cache by invalidating it.
	 */
	public function run_clear_cache_tool() {
		Cache::invalidate();

		return __( 'Analytics cache cleared.', 'woocommerce' );
	}
}
