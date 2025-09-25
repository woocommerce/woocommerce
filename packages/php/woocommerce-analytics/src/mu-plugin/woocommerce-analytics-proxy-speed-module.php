<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: WooCommerce Analytics - Proxy Speed Module
 * Description: Speeds up WooCommerce Analytics' proxy for avoiding ad blockers.
 * Plugin URI: https://woocommerce.com
 * Author: WooCommerce
 * Version: 1.0.0
 * Author URI: https://woocommerce.com
 *
 * Text Domain: woocommerce-analytics
 *
 * Inspired by: https://github.com/plausible/wordpress/blob/092b97b247f45bf347ae32f9614f20a81d9e10c0/mu-plugin/plausible-proxy-speed-module.php
 */
class WooCommerceAnalyticsProxySpeed {
	/**
	 * Path of the proxy request.
	 *
	 * @var string
	 */
	const PROXY_REQUEST_PATH = 'woocommerce-analytics/v1/track';

	/**
	 * Allowed plugin files for proxy request.
	 *
	 * @var array
	 */
	private $allowed_plugin_files = array( 'woocommerce.php', 'woocommerce-analytics.php', 'jetpack.php' );

	/**
	 * Add filters and actions.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
	}

	/**
	 * Filter the list of active plugins for custom endpoint requests.
	 *
	 * @param array $active_plugins The list of active plugins.
	 *
	 * @return array The filtered list of active plugins.
	 */
	public function filter_active_plugins( $active_plugins ) {
		if ( ! $this->is_proxy_request() || ! is_array( $active_plugins ) ) {
			return $active_plugins;
		}

		$filtered_plugins = array();

		foreach ( $active_plugins as $plugin ) {
			foreach ( $this->allowed_plugin_files as $allowed_plugin_file ) {
				if ( strpos( $plugin, $allowed_plugin_file ) !== false ) {
					$filtered_plugins[] = $plugin;
					break;
				}
			}
		}

		return $filtered_plugins;
	}

	/**
	 * Helper method to retrieve Request URI. Checks several globals.
	 *
	 * @return mixed
	 */
	private function get_request_uri() {
		return isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Check if current request is a proxy request.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		return strpos( $this->get_request_uri(), self::PROXY_REQUEST_PATH ) !== false;
	}
}

( new WooCommerceAnalyticsProxySpeed() )->init();
