<?php
/**
 * WooCommerce Analytics.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrderStatsDataStore;
use Automattic\WooCommerce\Internal\Admin\Notes\RefundDoubleCountToolNotice;
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
	 * Double-counted refunds fix tool identifier.
	 *
	 * @since 11.2.0
	 */
	const REFUND_DOUBLE_COUNT_TOOL_ID = 'fix_woocommerce_analytics_refund_double_count';

	/**
	 * Option holding the state of the double-counted refunds fix tool.
	 *
	 * @since 11.2.0
	 */
	const REFUND_DOUBLE_COUNT_OPTION = 'woocommerce_analytics_refund_double_count';

	/**
	 * Action Scheduler hook for a double-counted refunds fix batch.
	 *
	 * @since 11.2.0
	 */
	const REFUND_DOUBLE_COUNT_FIX_HOOK = 'woocommerce_analytics_refund_double_count_fix_batch';

	/**
	 * Maximum number of affected parent orders re-imported per batch.
	 *
	 * @since 11.2.0
	 */
	const REFUND_DOUBLE_COUNT_BATCH_SIZE = 100;

	/**
	 * Number of order IDs checked per batch.
	 *
	 * @since 11.2.0
	 */
	const REFUND_DOUBLE_COUNT_RANGE_SIZE = 50000;

	/**
	 * Fix run statuses stored in the tool state.
	 */
	private const REFUND_DOUBLE_COUNT_STATUS_RUNNING   = 'running';
	private const REFUND_DOUBLE_COUNT_STATUS_COMPLETE  = 'complete';
	private const REFUND_DOUBLE_COUNT_STATUS_CANCELLED = 'cancelled';

	/**
	 * Receives the fix tool's Tracks events instead of Tracks when set. Tests use it because
	 * WC_Tracks::record_event() skips PHPUnit users.
	 *
	 * @var callable|null
	 */
	private static $refund_double_count_event_recorder = null;

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

		// Merchant-triggered repair of refunds double-counted before #66320.
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_refund_double_count_tool' ) );
		add_action( self::REFUND_DOUBLE_COUNT_FIX_HOOK, array( $this, 'process_refund_double_count_fix_batch' ), 10, 2 );
		add_action( 'woocommerce_analytics_regenerate_init', array( $this, 'maybe_cancel_refund_double_count_fix_on_regenerate' ), 10, 2 );

		if ( self::should_show_refund_fix_tool() ) {
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
	private static function should_show_refund_fix_tool(): bool {
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

		self::schedule_batch( 'woocommerce_analytics_refund_fix_batch', array( 0 ) );

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
			self::schedule_batch( 'woocommerce_analytics_refund_fix_batch', array( $last_order_id ), 5 );
		}
	}

	/**
	 * Whether a store may have refunds double-counted by the bug fixed in #66320.
	 *
	 * Only stores that use the new full refund data and ran WooCommerce before 11.1.0,
	 * where the write path was fixed, can have affected rows.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public static function is_refund_double_count_tool_applicable(): bool {
		if ( ! OrderUtil::uses_new_full_refund_data() || OrderUtil::unknown_orders_data_store_in_use() ) {
			return false;
		}

		if ( self::get_refund_double_count_state()['dismissed'] ) {
			return false;
		}

		// Installs older than 9.2.0 never recorded their initial version.
		$initial_version = get_option( \WC_Install::INITIAL_INSTALLED_VERSION );

		return ! is_string( $initial_version ) || '' === $initial_version || version_compare( $initial_version, '11.1.0', '<' );
	}

	/**
	 * Get the state of the double-counted refunds fix tool, with defaults applied.
	 *
	 * @internal
	 *
	 * @return array{run_id: string, status: string, max_order_id: int, fixed: int, unresolved: int, batches: int, started_at: int, completed_at: int, dismissed: bool}
	 */
	public static function get_refund_double_count_state(): array {
		$state = get_option( self::REFUND_DOUBLE_COUNT_OPTION );
		$state = is_array( $state ) ? $state : array();

		return array(
			'run_id'       => is_string( $state['run_id'] ?? null ) ? $state['run_id'] : '',
			'status'       => is_string( $state['status'] ?? null ) ? $state['status'] : '',
			'max_order_id' => absint( $state['max_order_id'] ?? 0 ),
			'fixed'        => absint( $state['fixed'] ?? 0 ),
			'unresolved'   => absint( $state['unresolved'] ?? 0 ),
			'batches'      => absint( $state['batches'] ?? 0 ),
			'started_at'   => absint( $state['started_at'] ?? 0 ),
			'completed_at' => absint( $state['completed_at'] ?? 0 ),
			'dismissed'    => ! empty( $state['dismissed'] ),
		);
	}

	/**
	 * Get the tool state from the database rather than the request cache, so that
	 * a new run or a cancellation saved by another request is seen.
	 *
	 * @return array Tool state, as get_refund_double_count_state() returns it.
	 */
	private static function get_fresh_refund_double_count_state(): array {
		// Clear 'notoptions' too, or a request that cached the option as missing never sees it once created.
		wp_cache_delete( self::REFUND_DOUBLE_COUNT_OPTION, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		return self::get_refund_double_count_state();
	}

	/**
	 * Merge changes into the stored double-counted refunds fix tool state.
	 *
	 * @param array $changes State keys to overwrite.
	 * @return void
	 */
	private static function update_refund_double_count_state( array $changes ): void {
		update_option( self::REFUND_DOUBLE_COUNT_OPTION, array_merge( self::get_fresh_refund_double_count_state(), $changes ), false );
	}

	/**
	 * Whether a fix run is in progress. A run whose actions all died counts as not running.
	 *
	 * @param array $state Tool state from get_refund_double_count_state().
	 * @return bool
	 */
	private static function is_refund_double_count_fix_running( array $state ): bool {
		return self::REFUND_DOUBLE_COUNT_STATUS_RUNNING === $state['status']
			&& self::is_batch_pending_or_running( self::REFUND_DOUBLE_COUNT_FIX_HOOK );
	}

	/**
	 * Whether the given run is the current, still running fix run.
	 *
	 * @phpstan-impure
	 *
	 * @param string $run_id Run ID passed to a batch.
	 * @return bool
	 */
	private static function is_current_refund_double_count_run( string $run_id ): bool {
		return self::is_running_state_of( self::get_fresh_refund_double_count_state(), $run_id );
	}

	/**
	 * Whether the tool state belongs to the given run and that run is still running.
	 *
	 * @param array  $state  Tool state from get_refund_double_count_state().
	 * @param string $run_id Run ID passed to a batch.
	 * @return bool
	 */
	private static function is_running_state_of( array $state, string $run_id ): bool {
		return '' !== $run_id && $run_id === $state['run_id'] && self::REFUND_DOUBLE_COUNT_STATUS_RUNNING === $state['status'];
	}

	/**
	 * Register the double-counted refunds fix tool on the WooCommerce > Status > Tools page.
	 *
	 * The button and status text follow the state of the current or last run.
	 *
	 * @internal
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public function register_refund_double_count_tool( $debug_tools ) {
		if ( ! is_array( $debug_tools ) || ! self::is_refund_double_count_tool_applicable() ) {
			return $debug_tools;
		}

		$state       = self::get_refund_double_count_state();
		$button      = __( 'Check and fix', 'woocommerce' );
		$disabled    = false;
		$status_text = '';

		if ( self::is_refund_double_count_fix_running( $state ) ) {
			$button      = __( 'Checking and fixing…', 'woocommerce' );
			$disabled    = true;
			$status_text = sprintf(
				/* translators: %d: number of orders fixed so far. */
				_n( '%d order fixed so far.', '%d orders fixed so far.', $state['fixed'], 'woocommerce' ),
				$state['fixed']
			);
		} elseif ( self::REFUND_DOUBLE_COUNT_STATUS_COMPLETE === $state['status'] && $state['unresolved'] > 0 ) {
			$status_text = sprintf(
				/* translators: %d: number of orders that could not be fixed. */
				_n( '%d order could not be fixed. See the wc-analytics-order-import log for details.', '%d orders could not be fixed. See the wc-analytics-order-import log for details.', $state['unresolved'], 'woocommerce' ),
				$state['unresolved']
			);
		} elseif ( self::REFUND_DOUBLE_COUNT_STATUS_COMPLETE === $state['status'] ) {
			$button      = __( 'Dismiss', 'woocommerce' );
			$status_text = 0 === $state['fixed']
				? __( 'No affected orders were found.', 'woocommerce' )
				: sprintf(
					/* translators: 1: number of orders fixed, 2: date the fix finished. */
					_n( 'Fixed %1$d order on %2$s.', 'Fixed %1$d orders on %2$s.', $state['fixed'], 'woocommerce' ),
					$state['fixed'],
					wp_date( wc_date_format(), $state['completed_at'] )
				);
		} elseif ( '' !== $state['status'] ) {
			$status_text = __( 'The previous run did not finish.', 'woocommerce' );
		}

		$debug_tools[ self::REFUND_DOUBLE_COUNT_TOOL_ID ] = array(
			'name'             => __( 'Fix double-counted refunds in Analytics', 'woocommerce' ),
			'button'           => $button,
			'desc'             => __( 'This tool finds orders where a partial refund followed by a full refund was counted twice in the Analytics returns, and re-imports them. Only orders refunded before WooCommerce 11.1 can be affected.', 'woocommerce' ),
			'status_text'      => esc_html( $status_text ),
			'callback'         => array( $this, 'run_refund_double_count_tool' ),
			'disabled'         => $disabled,
			'requires_refresh' => true,
		);

		return $debug_tools;
	}

	/**
	 * Handle the double-counted refunds fix tool button.
	 *
	 * Starts a fix run, or dismisses the tool once a run finished with nothing left to fix.
	 * Also reachable through the system status tools REST API, so it re-checks the state.
	 *
	 * @internal
	 *
	 * @return string Result message.
	 */
	public function run_refund_double_count_tool() {
		$state           = self::get_refund_double_count_state();
		$previous_status = '' === $state['status'] ? 'none' : $state['status'];

		if ( self::is_refund_double_count_fix_running( $state ) ) {
			self::record_refund_double_count_tool_run( 'refused_running', $previous_status );
			return __( 'A fix is already in progress, please check back later.', 'woocommerce' );
		}

		if ( self::REFUND_DOUBLE_COUNT_STATUS_COMPLETE === $state['status'] && 0 === $state['unresolved'] ) {
			self::update_refund_double_count_state( array( 'dismissed' => true ) );
			self::record_refund_double_count_tool_run( 'dismissed', $previous_status );
			return __( 'Tool dismissed.', 'woocommerce' );
		}

		if ( self::is_batch_pending_or_running( 'woocommerce_analytics_refund_fix_batch' ) ) {
			self::record_refund_double_count_tool_run( 'refused_full_refund_fix', $previous_status );
			return __( 'The full refund data fix is still running. Please try again once it has finished.', 'woocommerce' );
		}

		$run_id = wp_generate_uuid4();

		self::update_refund_double_count_state(
			array(
				'run_id'       => $run_id,
				'status'       => self::REFUND_DOUBLE_COUNT_STATUS_RUNNING,
				'max_order_id' => self::get_max_order_stats_id(),
				'fixed'        => 0,
				'unresolved'   => 0,
				'batches'      => 0,
				'started_at'   => time(),
				'completed_at' => 0,
			)
		);
		self::schedule_batch( self::REFUND_DOUBLE_COUNT_FIX_HOOK, array( 0, $run_id ) );
		RefundDoubleCountToolNotice::delete_if_not_applicable();
		self::record_refund_double_count_tool_run( 'started', $previous_status );

		return __( 'Checking for affected orders and fixing them in the background. Reload this page to see the progress.', 'woocommerce' );
	}

	/**
	 * Whether an Action Scheduler job for the given batch hook is currently
	 * pending or running.
	 *
	 * Detected live from Action Scheduler so a batch that dies never leaves a
	 * stuck "in progress" flag behind.
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
	 * @param string $hook  Action Scheduler hook name.
	 * @param array  $args  Arguments passed to the batch.
	 * @param int    $delay Seconds to wait before the batch may run.
	 * @return void
	 */
	private static function schedule_batch( string $hook, array $args, int $delay = 0 ): void {
		WC()->queue()->schedule_single(
			time() + $delay,
			$hook,
			$args,
			'wc-admin-data'
		);
	}

	/**
	 * Maximum number of affected parent orders re-imported per fix batch.
	 *
	 * @return int
	 */
	private static function get_refund_double_count_batch_size(): int {
		/**
		 * Filters the maximum number of parent orders re-imported per double-counted refunds fix batch.
		 *
		 * @since 11.2.0
		 *
		 * @param int $batch_size Maximum parent orders per batch.
		 */
		return max( 1, (int) apply_filters( 'woocommerce_analytics_refund_double_count_batch_size', self::REFUND_DOUBLE_COUNT_BATCH_SIZE ) );
	}

	/**
	 * Number of order IDs checked per fix batch.
	 *
	 * @return int
	 */
	private static function get_refund_double_count_range_size(): int {
		/**
		 * Filters the number of order IDs checked per double-counted refunds fix batch.
		 *
		 * @since 11.2.0
		 *
		 * @param int $range_size Order IDs per batch.
		 */
		return max( 1, (int) apply_filters( 'woocommerce_analytics_refund_double_count_range_size', self::REFUND_DOUBLE_COUNT_RANGE_SIZE ) );
	}

	/**
	 * Highest order_id present in the order stats table (0 when empty).
	 *
	 * @return int
	 */
	private static function get_max_order_stats_id(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return intval( $wpdb->get_var( "SELECT MAX(order_id) FROM {$wpdb->prefix}wc_order_stats" ) );
	}

	/**
	 * Get the parent orders whose refund rows add up to more than the order itself,
	 * the sign of a partial refund followed by a double-counted full refund (#66320).
	 *
	 * Refunds are found through the parent index of the active order table, so the
	 * query only reads the refunds of the parent orders that match the condition.
	 *
	 * @param string $parent_condition Prepared SQL condition on the parent stats row, aliased `o`.
	 * @param int    $limit            Maximum number of IDs to return; 0 for no limit.
	 * @return int[] Parent order IDs in ascending order.
	 * @throws \Exception On database error.
	 */
	private static function get_refund_double_counted_parent_ids( string $parent_condition, int $limit = 0 ): array {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$orders_table  = OrdersTableDataStore::get_orders_table_name();
			$id_column     = 'id';
			$parent_column = 'parent_order_id';
			$type_column   = 'type';
		} else {
			$orders_table  = $wpdb->posts;
			$id_column     = 'ID';
			$parent_column = 'post_parent';
			$type_column   = 'post_type';
		}

		$stats_table = $wpdb->prefix . 'wc_order_stats';
		$limit_sql   = $limit > 0 ? $wpdb->prepare( 'LIMIT %d', $limit ) : '';
		// Half the smallest currency unit absorbs floating-point noise but still catches a double-counted
		// smallest-unit refund. Capped at 8 decimals, beyond which DOUBLE sums get too noisy to compare.
		$tolerance_sql = sprintf( '%.10F', 0.5 / ( 10 ** min( wc_get_price_decimals(), 8 ) ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table and column names are hardcoded; the condition is prepared by the caller.
		$parent_ids = $wpdb->get_col(
			"SELECT o.order_id
			FROM {$stats_table} AS o
			INNER JOIN {$orders_table} AS refund ON refund.{$parent_column} = o.order_id AND refund.{$type_column} = 'shop_order_refund'
			INNER JOIN {$stats_table} AS r ON r.order_id = refund.{$id_column}
			WHERE {$parent_condition} AND o.parent_id = 0
			GROUP BY o.order_id
			HAVING COUNT(*) > 1
				AND ABS( SUM( r.net_total + r.tax_total + r.shipping_total ) ) > MAX( o.net_total + o.tax_total + o.shipping_total ) + {$tolerance_sql}
			ORDER BY o.order_id ASC
			{$limit_sql}"
		);
		// phpcs:enable

		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'Double-counted refunds query failed: %s', $wpdb->last_error ),
				array( 'source' => 'wc-analytics-order-import' )
			);
			throw new \Exception( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return array_map( 'intval', $parent_ids );
	}

	/**
	 * Process one batch of the double-counted refunds fix.
	 *
	 * Re-imports the affected parent orders in the next range of order IDs, checks they
	 * were repaired, and schedules the next batch until the highest order ID recorded when
	 * the run started is covered. Batches of an older or cancelled run do nothing.
	 *
	 * @internal
	 *
	 * @param int    $cursor Exclusive lower bound on the parent order ID.
	 * @param string $run_id ID of the run that scheduled the batch.
	 * @return void
	 * @throws \Exception On database error so Action Scheduler marks the action as failed.
	 */
	public function process_refund_double_count_fix_batch( $cursor = 0, $run_id = '' ): void {
		global $wpdb;

		$cursor = absint( $cursor );
		$run_id = is_string( $run_id ) ? $run_id : '';

		if ( ! self::is_current_refund_double_count_run( $run_id ) ) {
			return;
		}

		$max_order_id = self::get_refund_double_count_state()['max_order_id'];
		$batch_size   = self::get_refund_double_count_batch_size();
		$range_end    = min( $cursor + self::get_refund_double_count_range_size(), $max_order_id );

		$parent_ids = self::get_refund_double_counted_parent_ids(
			$wpdb->prepare( 'o.order_id > %d AND o.order_id <= %d', $cursor, $range_end ),
			$batch_size
		);

		foreach ( $parent_ids as $parent_id ) {
			OrdersScheduler::import( $parent_id );
		}

		// OrdersScheduler::import() skips some orders silently, so check the result.
		$unfixed_ids = array();
		if ( $parent_ids ) {
			$placeholders = implode( ', ', array_fill( 0, count( $parent_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Only %d placeholders are interpolated.
			$unfixed_ids = self::get_refund_double_counted_parent_ids( $wpdb->prepare( "o.order_id IN ( {$placeholders} )", $parent_ids ) );
		}

		if ( $unfixed_ids ) {
			wc_get_logger()->warning(
				sprintf( 'Could not fix the double-counted refunds of orders: %s', implode( ', ', $unfixed_ids ) ),
				array( 'source' => 'wc-analytics-order-import' )
			);
		}

		// A newer run or a cancellation may have happened while the orders were re-imported. Check and
		// write the same fresh copy of the state, so this batch never writes into another run's state.
		$state = self::get_fresh_refund_double_count_state();
		if ( ! self::is_running_state_of( $state, $run_id ) ) {
			return;
		}

		$next_cursor = count( $parent_ids ) >= $batch_size ? (int) end( $parent_ids ) : $range_end;
		$is_done     = $next_cursor >= $max_order_id;
		$changes     = array(
			'fixed'      => $state['fixed'] + count( $parent_ids ) - count( $unfixed_ids ),
			'unresolved' => $state['unresolved'] + count( $unfixed_ids ),
			'batches'    => $state['batches'] + 1,
		);

		if ( $is_done ) {
			$changes['status']       = self::REFUND_DOUBLE_COUNT_STATUS_COMPLETE;
			$changes['completed_at'] = time();
		}

		update_option( self::REFUND_DOUBLE_COUNT_OPTION, array_merge( $state, $changes ), false );

		if ( $is_done ) {
			self::record_refund_double_count_fix_finished( array_merge( $state, $changes ), self::REFUND_DOUBLE_COUNT_STATUS_COMPLETE );
		} else {
			self::schedule_batch( self::REFUND_DOUBLE_COUNT_FIX_HOOK, array( $next_cursor, $run_id ), 5 );
		}
	}

	/**
	 * Cancel a running double-counted refunds fix when a full historical import starts,
	 * since that import re-imports every affected order anyway.
	 *
	 * @internal
	 *
	 * @param int|bool $days          Number of days to import, or false for the full history.
	 * @param bool     $skip_existing Whether the import skips already imported orders.
	 * @return void
	 */
	public function maybe_cancel_refund_double_count_fix_on_regenerate( $days, $skip_existing ): void {
		if ( false !== $days || $skip_existing ) {
			return;
		}

		$state = self::get_fresh_refund_double_count_state();
		if ( self::REFUND_DOUBLE_COUNT_STATUS_RUNNING !== $state['status'] ) {
			return;
		}

		self::update_refund_double_count_state( array( 'status' => self::REFUND_DOUBLE_COUNT_STATUS_CANCELLED ) );
		as_unschedule_all_actions( self::REFUND_DOUBLE_COUNT_FIX_HOOK );
		self::record_refund_double_count_fix_finished( $state, self::REFUND_DOUBLE_COUNT_STATUS_CANCELLED );
	}

	/**
	 * Override where the fix tool's Tracks events go. Intended for tests only.
	 *
	 * @internal
	 *
	 * @param callable|null $recorder Receives `(string $event_name, array $properties)`. Pass null to send to Tracks again.
	 * @return void
	 */
	public static function set_refund_double_count_event_recorder( ?callable $recorder ): void {
		self::$refund_double_count_event_recorder = $recorder;
	}

	/**
	 * Record a click on the fix tool's button and what it did.
	 *
	 * @param string $outcome         One of started, dismissed, refused_running or refused_full_refund_fix.
	 * @param string $previous_status Run status before the click, or 'none'.
	 * @return void
	 */
	private static function record_refund_double_count_tool_run( string $outcome, string $previous_status ): void {
		self::record_refund_double_count_event(
			'tool_run',
			array(
				'outcome'         => $outcome,
				'previous_status' => $previous_status,
			)
		);
	}

	/**
	 * Record the end of a fix run.
	 *
	 * @param array  $state  Tool state at the end of the run.
	 * @param string $result complete or cancelled.
	 * @return void
	 */
	private static function record_refund_double_count_fix_finished( array $state, string $result ): void {
		self::record_refund_double_count_event(
			'fix_finished',
			array(
				'result'           => $result,
				'fixed_count'      => $state['fixed'],
				'unresolved_count' => $state['unresolved'],
				'batches'          => $state['batches'],
				'duration_seconds' => $state['started_at'] > 0 ? max( 0, time() - $state['started_at'] ) : 0,
				'max_order_id'     => $state['max_order_id'],
				'db_engine'        => self::get_db_engine(),
			)
		);
	}

	/**
	 * Send one of the fix tool's Tracks events. Telemetry failures never stop the fix.
	 *
	 * @param string $name       Event name after the analytics_refund_double_count_ prefix.
	 * @param array  $properties Event properties.
	 * @return void
	 */
	private static function record_refund_double_count_event( string $name, array $properties ): void {
		$properties['order_storage'] = OrderUtil::custom_orders_table_usage_is_enabled() ? 'hpos' : 'cpt';
		$event_name                  = 'analytics_refund_double_count_' . $name;

		try {
			if ( null !== self::$refund_double_count_event_recorder ) {
				( self::$refund_double_count_event_recorder )( $event_name, $properties );
				return;
			}

			if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
				wc_admin_record_tracks_event( $event_name, $properties );
			}
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	/**
	 * Database engine and major.minor version, e.g. mariadb-10.11 or mysql-8.0.
	 *
	 * @return string
	 */
	private static function get_db_engine(): string {
		global $wpdb;

		$server_info = (string) $wpdb->db_server_info();

		if ( false !== stripos( $server_info, 'mariadb' ) ) {
			// Older MariaDB servers report a "5.5.5-" prefix before the real version.
			return preg_match( '/(\d+\.\d+)\.\d+-MariaDB/i', $server_info, $matches ) ? 'mariadb-' . $matches[1] : 'mariadb';
		}

		return preg_match( '/^(\d+\.\d+)/', $server_info, $matches ) ? 'mysql-' . $matches[1] : 'unknown';
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
