<?php

namespace Automattic\WooCommerce\Blueprint;

trait UseWPFunctions {

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
}
