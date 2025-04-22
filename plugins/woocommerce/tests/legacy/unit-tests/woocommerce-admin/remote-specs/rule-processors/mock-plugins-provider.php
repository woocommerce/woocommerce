<?php
/**
 * Mock plugins Provider.
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\PluginsProvider\PluginsProviderInterface;

/**
 * Mock plugins Provider.
 *
 * Returns the provided plugins instead of the current plugins. Needed for
 * unit tests.
 */
class MockPluginsProvider implements PluginsProviderInterface {
	/**
	 * Construct the mock plugins provider using the specified value for the
	 * active plugins slugs.
	 *
	 * @param array $active_plugin_slugs The value to use for the active plugin slugs.
	 * @param array $get_plugins_data    The data to be used instead of get_plugins() calls.
	 */
	public function __construct(
		$active_plugin_slugs,
		$get_plugins_data = array()
	) {
		$this->active_plugin_slugs = $active_plugin_slugs;
		$this->get_plugins_data    = $get_plugins_data;
	}

	/**
	 * Get an array of provided plugin slugs.
	 *
	 * @return array
	 */
	public function get_active_plugin_slugs() {
		return $this->active_plugin_slugs;
	}

	/**
	 * Get plugin data.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return array|false
	 */
	public function get_plugin_data( $plugin ) {
		$plugin_path = $this->get_plugin_path_from_slug( $plugin );
		$plugins     = $this->get_plugins_data;

		return isset( $plugins[ $plugin_path ] ) ? $plugins[ $plugin_path ] : false;
	}

	/**
	 * Get the path to the plugin file relative to the plugins directory from the plugin slug.
	 *
	 * E.g. 'woocommerce' returns 'woocommerce/woocommerce.php'
	 *
	 * @param string $slug Plugin slug to get path for.
	 *
	 * @return string|false
	 */
	public function get_plugin_path_from_slug( $slug ) {
		$plugins = $this->get_plugins_data;

		if ( strstr( $slug, '/' ) ) {
			return $slug;
		}

		foreach ( $plugins as $plugin_path => $data ) {
			$path_parts = explode( '/', $plugin_path );
			if ( $path_parts[0] === $slug ) {
				return $plugin_path;
			}
		}

		return false;
	}
}
