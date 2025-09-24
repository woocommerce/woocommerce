<?php
/**
 * WooCommerce Analytics.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Utilities\OrderUtil;

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
	 */
	const FULL_REFUND_FIX_DATA_TOOL_ID = 'fix_woocommerce_analytics_full_refund_data';

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
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_full_refund_fix_data_tool' ) );
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
		$endpoints['performanceIndicators'] = '/wc-analytics/reports/performance-indicators/allowed';
		$endpoints['leaderboards']          = '/wc-analytics/leaderboards/allowed';
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
	 * Register the full refund fix data tool on the WooCommerce > Status > Tools page.
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public function register_full_refund_fix_data_tool( $debug_tools ) {
		if ( OrderUtil::uses_new_full_refund_data() ) {
			return $debug_tools;
		}

		$debug_tools[ self::FULL_REFUND_FIX_DATA_TOOL_ID ] = array(
			'name'     => __( 'Fix analytics full refund data', 'woocommerce' ),
			'button'   => __( 'Fix', 'woocommerce' ),
			'desc'     => __( 'This tool will fix the full refund data used in WooCommerce Analytics and re-import all the refunded historical data.', 'woocommerce' ),
			'callback' => array( $this, 'run_full_refund_fix_data_tool' ),
		);

		return $debug_tools;
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

	/**
	 * "Fix" full refund data by re-importing all the refunded historical data.
	 */
	public function run_full_refund_fix_data_tool() {
		global $wpdb;

		Cache::invalidate();

		// Get every order ID where:
		// 1. the total sales is less than 0, and
		// 2. is not refunded shipping fee only, and
		// 3. is not refunded tax fee only.
		$refunded_orders = $wpdb->get_results(
			"SELECT order_stats.order_id
			FROM {$wpdb->prefix}wc_order_stats AS order_stats
			WHERE order_stats.total_sales < 0 # Refunded orders
				AND order_stats.total_sales != order_stats.shipping_total # Exclude refunded orders that only include a shipping refund
				AND order_stats.total_sales != order_stats.tax_total # Exclude refunded orders that only include a tax refund"
		);

		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		if ( $refunded_orders ) {
			foreach ( $refunded_orders as $refunded_order ) {
				/**
				 * Trigger an action to schedule the data import for old refunded order items.
				 *
				 * @param int $order_id The ID of the order to be synced.
				 * @since 10.2.0
				 */
				do_action( 'woocommerce_schedule_import', intval( $refunded_order->order_id ) );
			}
		}

		return __( 'Re-importing refunded orders, full refund data will be updated shortly.', 'woocommerce' );
	}
}
