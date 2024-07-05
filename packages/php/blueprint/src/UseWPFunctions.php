<?php

namespace Automattic\WooCommerce\Blueprint;

trait UseWPFunctions {

	/**
	 * Adds a filter to a specified tag.
	 *
	 * @param string   $tag             The name of the filter to hook the $function_to_add to.
	 * @param callable $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	public function wp_add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		add_filter($tag, $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Adds an action to a specified tag.
	 *
	 * @param string   $tag             The name of the action to hook the $function_to_add to.
	 * @param callable $function_to_add The callback to be run when the action is triggered.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	public function wp_add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		add_action($tag, $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Calls the functions added to a filter hook.
	 *
	 * @param string $tag   The name of the filter hook.
	 * @param mixed  $value The value on which the filters hooked to $tag are applied on.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	public function wp_apply_filters($tag, $value) {
		$args = func_get_args();
		return call_user_func_array('apply_filters', $args);
	}

	/**
	 * Executes the functions hooked on a specific action hook.
	 *
	 * @param string $tag The name of the action to be executed.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the functions hooked to the action.
	 */
	public function wp_do_action($tag, ...$args) {
		do_action($tag, ...$args);
	}

	public function wp_is_plugin_active(string $plugin) {
		if (!function_exists('is_plugin_active') || !function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active($plugin);
	}

	public function wp_plugins_api($action, $args = array()) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		}
		return plugins_api($action, $args);
	}

	public function wp_get_plugins(string $plugin_folder = '') {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins($plugin_folder);
	}

	public function wp_get_themes($args = array()) {
		return wp_get_themes($args);
	}

	public function wp_get_theme($stylesheet = null) {
		return wp_get_theme($stylesheet);
	}

	public function wp_themes_api($action, $args = array()) {
		return themes_api($action, $args);
	}

	public function wp_activate_plugin($plugin, $redirect = '', $network_wide = false, $silent = false) {
		if (!function_exists('activate_plugin')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return activate_plugin($plugin, $redirect, $network_wide, $silent);
	}

	public function wp_delete_plugins($plugins) {
		if (!function_exists('delete_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return delete_plugins($plugins);
	}
}
