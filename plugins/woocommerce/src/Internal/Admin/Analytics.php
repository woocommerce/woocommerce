<?php
/**
 * WooCommerce Analytics.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Admin\Features\Features;
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

		// Always register the batch hook so in-flight jobs survive after the legacy
		// flag is cleared (clearing happens before the first batch is queued).
		add_action( 'woocommerce_analytics_refund_fix_batch', array( $this, 'process_refund_fix_batch' ) );

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

		$already_running = ! empty(
			as_get_scheduled_actions(
				array(
					'hook'     => 'woocommerce_analytics_refund_fix_batch',
					'status'   => array( \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ),
					'per_page' => 1,
					'orderby'  => 'none',
				),
				'ids'
			)
		);

		if ( $already_running ) {
			return __( 'A fix is already in progress, please check back later.', 'woocommerce' );
		}

		// Clear the legacy flag before queuing so that every batch job runs with
		// the corrected full-refund import logic (uses_new_full_refund_data() → true).
		// Set the show-tool option so the tool stays visible until the merchant dismisses it.
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		update_option( 'woocommerce_analytics_show_old_refund_data_tool', 'yes' );

		WC()->queue()->schedule_single(
			time(),
			'woocommerce_analytics_refund_fix_batch',
			array( 0 ),
			'wc-admin-data'
		);

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
			WC()->queue()->schedule_single(
				time() + 5,
				'woocommerce_analytics_refund_fix_batch',
				array( $last_order_id ),
				'wc-admin-data'
			);
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

		$fix_in_progress = ! empty(
			as_get_scheduled_actions(
				array(
					'hook'     => 'woocommerce_analytics_refund_fix_batch',
					'status'   => array( \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ),
					'per_page' => 1,
					'orderby'  => 'none',
				),
				'ids'
			)
		);

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
