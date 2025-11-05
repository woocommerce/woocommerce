<?php
/**
 * WooCommerce Analytics.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrderStatsDataStore;
use WC_Order;

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
	 * Action Scheduler hook name for regenerating order fulfillment status.
	 */
	const REGENERATE_FULFILLMENT_STATUS_ACTION = 'woocommerce_analytics_regenerate_order_fulfillment_status';

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

		if ( ! Features::is_enabled( 'analytics' ) ) {
			return;
		}

		add_filter( 'woocommerce_component_settings_preload_endpoints', array( $this, 'add_preload_endpoints' ) );
		add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'add_user_data_fields' ) );
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_cache_clear_tool' ) );
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_regenerate_order_fulfillment_status_tool' ), 12 );
		add_action( self::REGENERATE_FULFILLMENT_STATUS_ACTION, array( $this, 'process_regenerate_order_fulfillment_status_batch' ) );
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

		// Check if regeneration is currently in progress.
		$progress    = get_transient( 'woocommerce_analytics_fulfillment_status_progress' );
		$is_running  = false;
		$button_text = __( 'Regenerate', 'woocommerce' );
		$description = __( 'This tool will regenerate the order fulfillment status for all orders and update the Analytics data.', 'woocommerce' );

		if ( false !== $progress && is_array( $progress ) && isset( $progress['status'], $progress['processed'], $progress['total'] ) && 'running' === $progress['status'] ) {
			$is_running  = true;
			$button_text = __( 'In progress...', 'woocommerce' );
			$description = sprintf(
				/* translators: 1: processed count, 2: total count */
				__( 'Regeneration is currently in progress. Processed %1$d of %2$d orders.', 'woocommerce' ),
				$progress['processed'],
				$progress['total']
			);
		}

		$debug_tools['regenerate_order_fulfillment_status'] = array(
			'name'     => __( 'Regenerate order fulfillment status for Analytics', 'woocommerce' ),
			'button'   => $button_text,
			'desc'     => $description,
			'callback' => array( $this, 'run_regenerate_order_fulfillment_status_tool' ),
			'disabled' => $is_running,
		);

		return $debug_tools;
	}

	/**
	 * Schedule the regeneration of order fulfillment status via Action Scheduler.
	 *
	 * @return string Success message or error message.
	 */
	public function run_regenerate_order_fulfillment_status_tool() {
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

		// Check if an action is already scheduled or in progress.
		$progress = get_transient( 'woocommerce_analytics_fulfillment_status_progress' );
		if ( ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::REGENERATE_FULFILLMENT_STATUS_ACTION ) )
			|| ( false !== $progress && 'running' === $progress['status'] ) ) {
			return __( 'Order fulfillment status regeneration is already in progress.', 'woocommerce' );
		}

		// Initialize progress tracking.
		delete_transient( 'woocommerce_analytics_fulfillment_status_progress' );
		set_transient(
			'woocommerce_analytics_fulfillment_status_progress',
			array(
				'processed' => 0,
				'total'     => $this->get_total_orders_with_fulfillments(),
				'page'      => 1,
				'status'    => 'running',
			),
			DAY_IN_SECONDS
		);

		// Schedule the first batch.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), self::REGENERATE_FULFILLMENT_STATUS_ACTION, array( 'page' => 1 ) );
			return __( 'Order fulfillment status regeneration has been scheduled and will run in the background.', 'woocommerce' );
		}

		return __( 'Action Scheduler is not available. Please ensure WooCommerce is properly installed.', 'woocommerce' );
	}

	/**
	 * Process a batch of orders for fulfillment status regeneration.
	 *
	 * @param int $page The current page/batch number to process.
	 * @return void
	 */
	public function process_regenerate_order_fulfillment_status_batch( $page = 1 ) {
		global $wpdb;
		$page               = max( 1, absint( $page ) );
		$per_page           = 100;
		$order_stats_table  = $wpdb->prefix . 'wc_order_stats';
		$fulfillments_table = $wpdb->prefix . 'wc_order_fulfillments';
		$updated_count      = 0;

		// Get progress.
		$progress = get_transient( 'woocommerce_analytics_fulfillment_status_progress' );
		if ( false === $progress ) {
			// Progress transient expired or was deleted, stop processing.
			return;
		}

		$offset = ( $page - 1 ) * $per_page;

		// Get distinct order IDs from the fulfillments table.
		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be prepared.
				"SELECT DISTINCT entity_id FROM {$fulfillments_table} WHERE entity_type = 'WC_Order' AND date_deleted IS NULL ORDER BY entity_id ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		if ( empty( $order_ids ) ) {
			// No more orders to process, mark as complete.
			update_option( 'woocommerce_analytics_order_fulfillment_status_regenerated', true, false );
			delete_transient( 'woocommerce_analytics_fulfillment_status_progress' );
			return;
		}

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || ! $order instanceof WC_Order ) {
				continue;
			}

			// Get the fulfillment status from order meta.
			$fulfillment_status = FulfillmentUtils::get_order_fulfillment_status( $order );

			// Update the wc_order_stats table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$order_stats_table,
				array( 'fulfillment_status' => ( 'no_fulfillments' !== $fulfillment_status ) ? $fulfillment_status : null ),
				array( 'order_id' => $order_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false !== $result ) {
				++$updated_count;
			}
		}

		// Update progress.
		$progress['processed'] += $updated_count;
		$progress['page']       = $page;
		set_transient( 'woocommerce_analytics_fulfillment_status_progress', $progress, DAY_IN_SECONDS );

		// Schedule next batch if there are more orders to process.
		if ( count( $order_ids ) === $per_page ) {
			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( time() + 1, self::REGENERATE_FULFILLMENT_STATUS_ACTION, array( 'page' => $page + 1 ) );
			}
		} else {
			// This was the last batch, mark as complete and cleanup.
			update_option( 'woocommerce_analytics_order_fulfillment_status_regenerated', true, false );
			delete_transient( 'woocommerce_analytics_fulfillment_status_progress' );
		}
	}

	/**
	 * Get the total number of orders with fulfillments.
	 *
	 * @return int Total number of orders with fulfillments.
	 */
	private function get_total_orders_with_fulfillments() {
		global $wpdb;
		$fulfillments_table = $wpdb->prefix . 'wc_order_fulfillments';

		$total = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be prepared.
			"SELECT COUNT(DISTINCT entity_id) FROM {$fulfillments_table} WHERE entity_type = 'WC_Order' AND date_deleted IS NULL"
		);

		return (int) $total;
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
			'position' => 57, // After WooCommerce & Product menu items.
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
