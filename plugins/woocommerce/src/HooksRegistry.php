<?php
declare(strict_types=1);

namespace Automattic\WooCommerce;

/**
 * This class list and register core hooks for WooCommerce, without loading the file that contains the hooks. Classes that provides these hooks should be loaded by an Autoloader or already loaded.
 */
class HooksRegistry {

	/**
	 * Whether the hooks are already loaded or not.
	 *
	 * @var bool $hooks_loaded
	 */
	public static bool $hooks_loaded = false;

	/**
	 * List of all actions that should be registered for all requests.
	 *
	 * @var array[] $all_request_actions
	 */
	private static array $all_request_actions = array(
		array( 'pre_set_site_transient_update_plugins', array( 'WC_Helper_Updater', 'transient_update_plugins' ), 21, 1 ),
		array( 'pre_set_site_transient_update_themes', array( 'WC_Helper_Updater', 'transient_update_themes' ), 21, 1 ),
		array( 'upgrader_process_complete', array( 'WC_Helper_Updater', 'upgrader_process_complete' ) ),
		array( 'upgrader_pre_download', array( 'WC_Helper_Updater', 'block_expired_updates' ), 10, 2 ),

		array( 'plugins_api', array( 'WC_Plugin_Api_Updater', 'plugins_api' ), 20, 3 ),
		array( 'themes_api', array( 'WC_Plugin_Api_Updater', 'themes_api' ), 20, 3 ),

		array( 'woocommerce_helper_loaded', array( 'WC_Helper_Compat', 'helper_loaded' ) ),

	);

	/**
	 * List of all filters that should be registered for all requests.
	 *
	 * @var array[] $all_request_filters
	 */
	private static array $all_request_filters = array(
		array( 'rest_api_init', array( 'WC_Helper_Subscriptions_API', 'register_rest_routes' ) ),
		array( 'rest_api_init', array( 'WC_Helper_Orders_API', 'register_rest_routes' ) ),

	);

	/**
	 * List of actions that should be registered for frontend requests.
	 *
	 * @var array $frontend_actions
	 */
	private static array $frontend_actions = array();

	/**
	 * List of filters that should be registered for frontend requests.
	 *
	 * @var array $frontend_filters
	 */
	private static array $frontend_filters = array();

	/**
	 * List of actions that should be registered for admin requests.
	 *
	 * @var array[] $admin_actions
	 */
	private static array $admin_actions = array(
		array( 'admin_notices', array( 'WC_Woo_Update_Manager_Plugin', 'show_woo_update_manager_install_notice' ) ),
		array( 'admin_init', array( 'WC_Helper_Updater', 'add_hook_for_modifying_update_notices' ) ),
		array( 'current_screen', array( 'WC_Product_Usage_Notice', 'maybe_show_product_usage_notice' ) ),
		array( 'wp_ajax_woocommerce_dismiss_product_usage_notice', array( 'WC_Product_Usage_Notice', 'ajax_dismiss' ) ),
		array( 'wp_ajax_woocommerce_remind_later_product_usage_notice', array( 'WC_Product_Usage_Notice', 'ajax_remind_later' ) ),
		array( 'woocommerce_helper_loaded', array( 'WC_Helper_Compat', 'helper_loaded' ) ),
	);

	/**
	 * List of filters that should be registered for admin requests.
	 *
	 * @var array $admin_filters
	 */
	private static array $admin_filters = array();

	/**
	 * Load all registered hooks.
	 */
	public static function load_hooks() {
		if ( self::$hooks_loaded ) {
			wc_doing_it_wrong( 'HooksRegistry::load_hooks', 'HooksRegistry is already loaded.', '9.5.0' );
			return;
		}

		if ( is_admin() ) {
			foreach ( self::$admin_actions as $action ) {
				add_action( ...$action );
			}

			foreach ( self::$admin_filters as $filter ) {
				add_filter( ...$filter );
			}
		}

		if ( ! is_admin() && ! defined( 'DOING_CRON' ) ) {
			foreach ( self::$frontend_actions as $action ) {
				add_action( ...$action );
			}

			foreach ( self::$frontend_filters as $filter ) {
				add_filter( ...$filter );
			}
		}

		foreach ( self::$all_request_actions as $action ) {
			add_action( ...$action );
		}

		foreach ( self::$all_request_filters as $filter ) {
			add_filter( ...$filter );
		}

		self::$hooks_loaded = true;
	}

	/**
	 * DANGEROUS: This method is used for testing and benchmarking. Do not call, unless you really know what you are doing.
	 */
	public static function unload_hooks() {
		if ( ! self::$hooks_loaded ) {
			return;
		}

		if ( is_admin() ) {
			foreach ( self::$admin_actions as $action ) {
				remove_action( ...$action );
			}

			foreach ( self::$admin_filters as $filter ) {
				remove_filter( ...$filter );
			}
		}

		if ( ! is_admin() && ! defined( 'DOING_CRON' ) ) {
			foreach ( self::$frontend_actions as $action ) {
				remove_action( ...$action );
			}

			foreach ( self::$frontend_filters as $filter ) {
				remove_filter( ...$filter );
			}
		}

		foreach ( self::$all_request_actions as $action ) {
			remove_action( ...$action );
		}

		foreach ( self::$all_request_filters as $filter ) {
			remove_filter( ...$filter );
		}

		self::$hooks_loaded = false;
	}
}
