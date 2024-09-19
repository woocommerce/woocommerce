<?php
declare(strict_types=1);

namespace Automattic\WooCommerce;

/**
 * This class list and register core hooks for WooCommerce, without loading the file that contains the hooks. Classes that provides these hooks should be loaded by an Autoloader or already loaded.
 */
class HooksRegistry {

	/**
	 * List of all actions that should be registered for all requests.
	 *
	 * @var array[] $all_request_actions
	 */
	private static array $all_request_actions = array(
		array( 'pre_set_site_transient_update_plugins', array( \WC_Helper_Updater::class, 'transient_update_plugins' ), 21, 1 ),
		array( 'pre_set_site_transient_update_themes', array( \WC_Helper_Updater::class, 'transient_update_themes' ), 21, 1 ),
		array( 'upgrader_process_complete', array( \WC_Helper_Updater::class, 'upgrader_process_complete' ) ),
		array( 'upgrader_pre_download', array( \WC_Helper_Updater::class, 'block_expired_updates' ), 10, 2 ),

		array( 'plugins_api', array( \WC_Plugin_Api_Updater::class, 'plugins_api' ), 20, 3 ),
		array( 'themes_api', array( \WC_Plugin_Api_Updater::class, 'themes_api' ), 20, 3 ),

		array( 'woocommerce_helper_loaded', array( \WC_Helper_Compat::class, 'helper_loaded' ) ),
		array( 'woocommerce_wccom_install_products', array( \WC_WCCOM_Site_Installer::class, 'install' ) ),
		array( 'woocommerce_rest_api_get_rest_namespaces', array( \WC_WCCOM_Site::class, 'register_rest_namespace' ) ),
	);

	/**
	 * List of all filters that should be registered for all requests.
	 *
	 * @var array[] $all_request_filters
	 */
	private static array $all_request_filters = array(
		array( 'rest_api_init', array( \WC_Helper_Subscriptions_API::class, 'register_rest_routes' ) ),
		array( 'rest_api_init', array( \WC_Helper_Orders_API::class, 'register_rest_routes' ) ),
		array( 'determine_current_user', array( \WC_WCCOM_Site::class, 'authenticate_wccom' ), 14 ),
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
		array( 'admin_notices', array( \WC_Woo_Update_Manager_Plugin::class, 'show_woo_update_manager_install_notice' ) ),
		array( 'admin_init', array( \WC_Helper_Updater::class, 'add_hook_for_modifying_update_notices' ) ),
		array( 'current_screen', array( \WC_Product_Usage_Notice::class, 'maybe_show_product_usage_notice' ) ),
		array( 'wp_ajax_woocommerce_dismiss_product_usage_notice', array( \WC_Product_Usage_Notice::class, 'ajax_dismiss' ) ),
		array( 'wp_ajax_woocommerce_remind_later_product_usage_notice', array( \WC_Product_Usage_Notice::class, 'ajax_remind_later' ) ),
		array( 'woocommerce_helper_loaded', array( \WC_Helper_Compat::class, 'helper_loaded' ) ),
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
		foreach ( self::$all_request_actions as $action ) {
			call_user_func_array( 'add_action', $action );
		}

		foreach ( self::$all_request_filters as $filter ) {
			call_user_func_array( 'add_filter', $filter );
		}

		foreach ( self::$admin_actions as $action ) {
			call_user_func_array( 'add_action', $action );
		}

		foreach ( self::$admin_filters as $filter ) {
			call_user_func_array( 'add_filter', $filter );
		}

		foreach ( self::$frontend_actions as $action ) {
			call_user_func_array( 'add_action', $action );
		}

		foreach ( self::$frontend_filters as $filter ) {
			call_user_func_array( 'add_filter', $filter );
		}
	}

	/**
	 * DANGEROUS: This method is used for testing and benchmarking. Do not call, unless you really know what you are doing.
	 */
	public static function unload_hooks() {
		foreach ( self::$admin_actions as $action ) {
			call_user_func_array( 'remove_action', $action );
		}

		foreach ( self::$admin_filters as $filter ) {
			call_user_func_array( 'remove_filter', $filter );
		}
		foreach ( self::$frontend_actions as $action ) {
			call_user_func_array( 'remove_action', $action );
		}

		foreach ( self::$frontend_filters as $filter ) {
			call_user_func_array( 'remove_filter', $filter );
		}

		foreach ( self::$all_request_actions as $action ) {
			call_user_func_array( 'remove_action', $action );
		}

		foreach ( self::$all_request_filters as $filter ) {
			call_user_func_array( 'remove_filter', $filter );
		}
	}
}
