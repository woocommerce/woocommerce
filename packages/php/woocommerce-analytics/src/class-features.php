<?php
/**
 * Features class for WooCommerce Analytics.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

/**
 * Features class for WooCommerce Analytics.
 */
class Features {

	/**
	 * Check if proxy tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_proxy_tracking_enabled() {
		/**
		 * Filter to enable/disable experimental proxy tracking for WooCommerce Analytics
		 *
		 * @since 0.9.0
		 *
		 * @param bool $enabled Whether proxy tracking is enabled. Default false.
		 */
		return apply_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled', false );
	}

	/**
	 * Check if ClickHouse is enabled.
	 *
	 * @return bool
	 */
	public static function is_clickhouse_enabled() {
		/**
		 * Filter to enable/disable ClickHouse event tracking.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 0.5.0
		 *
		 * @param bool $enabled Whether ClickHouse event tracking is enabled.
		 */
		return apply_filters( 'woocommerce_analytics_clickhouse_enabled', false );
	}

	/**
	 * Check if auto-installation of the proxy speed module MU-plugin is enabled.
	 *
	 * @deprecated 0.19.0 The package no longer installs the MU-plugin. Use is_proxy_speed_module_host_provided().
	 *
	 * @return bool
	 */
	public static function is_proxy_speed_module_enabled() {
		/**
		 * Filter that controlled auto-installation of the proxy speed module MU-plugin.
		 *
		 * @since 0.15.0
		 * @deprecated 0.19.0 The package no longer installs the MU-plugin, so this has no effect.
		 *
		 * @param bool $auto_install Whether to auto-install the mu-plugin. Default false.
		 */
		return (bool) apply_filters_deprecated(
			'woocommerce_analytics_auto_install_proxy_speed_module',
			array( false ),
			'0.19.0',
			'woocommerce_analytics_proxy_speed_module_host_provided',
			'The package no longer installs the proxy speed module MU-plugin.'
		);
	}

	/**
	 * Check if the host runs Proxy_Speed_Module from its own platform code.
	 *
	 * @since 0.19.0
	 *
	 * @return bool
	 */
	public static function is_proxy_speed_module_host_provided() {
		/**
		 * Filter to authorize the proxy speed module without auto-installing the MU-plugin file.
		 *
		 * Return true from host platform code that calls Proxy_Speed_Module::maybe_serve() at MU-plugin stage.
		 * Register it on every request before `init` priority 20, or the stored authorization flips.
		 *
		 * @since 0.19.0
		 *
		 * @param bool $host_provided Whether the host provides the speed module. Default false.
		 */
		return true === apply_filters( 'woocommerce_analytics_proxy_speed_module_host_provided', false );
	}
}
