<?php
/**
 * A class of utilities for dealing with plugins.
 */

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * A class of utilities for dealing with plugins.
 */
class PluginUtil {

	/**
	 * The LegacyProxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private $proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $proxy The instance of LegacyProxy to use.
	 */
	final public function init( LegacyProxy $proxy ) {
		$this->proxy = $proxy;
	}

	/**
	 * Get a list with the names of the WordPress plugins that are WooCommerce aware
	 * (they have a "WC tested up to" header).
	 *
	 * @param bool $active_only True to return only active plugins, false to return all the active plugins.
	 * @return string[] A list of plugin names (path/file.php).
	 */
	public function get_woocommerce_aware_plugins( bool $active_only = false ) {
		$all_plugins = $this->proxy->call_function( 'get_plugins' );

		return array_keys(
			array_filter(
				$all_plugins,
				function( $plugin_data, $plugin_name ) use ( $active_only ) {
					return '' !== ArrayUtil::get_value_or_default( $plugin_data, 'WC tested up to', '' )
					&& ( ! $active_only || $this->proxy->call_function( 'is_plugin_active', $plugin_name ) );
				},
				ARRAY_FILTER_USE_BOTH
			)
		);
	}
}
