<?php
/* HEADER */ // phpcs:ignore

/**
 * This class provides information about the current plugin and the site's active plugins.
 */
class Plugins_Handler {

	/**
	 * Returns an array containing the paths of all active plugins and all known activating plugins.
	 *
	 * @return array An array of plugin paths as strings or an empty array.
	 */
	public function get_all_active_plugins_paths() {
		global $jetpack_autoloader_activating_plugins_paths;

		$active_plugins_paths    = $this->get_active_plugins_paths();
		$multisite_plugins_paths = $this->get_multisite_plugins_paths();
		$active_plugins_paths    = array_merge( $multisite_plugins_paths, $active_plugins_paths );

		$activating_plugins_paths = $this->get_plugins_activating_via_request();
		$activating_plugins_paths = array_unique( array_merge( $activating_plugins_paths, $jetpack_autoloader_activating_plugins_paths ) );

		$plugins_paths = array_unique( array_merge( $active_plugins_paths, $activating_plugins_paths ) );

		return $plugins_paths;
	}

	/**
	 * Returns an array containing the paths of the active sitewide plugins in a multisite environment.
	 *
	 * @return array The paths of the active sitewide plugins or an empty array.
	 */
	protected function get_multisite_plugins_paths() {
		$plugin_slugs = is_multisite()
			? array_keys( get_site_option( 'active_sitewide_plugins', array() ) )
			: array();

		$plugin_slugs = array_filter( $plugin_slugs, array( $this, 'is_directory_plugin' ) );
		return array_map( array( $this, 'create_plugin_path' ), $plugin_slugs );
	}

	/**
	 * Returns an array containing the paths of the currently active plugins.
	 *
	 * @return array The active plugins' paths or an empty array.
	 */
	protected function get_active_plugins_paths() {
		$plugin_slugs = (array) get_option( 'active_plugins', array() );
		$plugin_slugs = array_filter( $plugin_slugs, array( $this, 'is_directory_plugin' ) );
		return array_map( array( $this, 'create_plugin_path' ), $plugin_slugs );
	}

	/**
	 * Adds the plugin directory from the WP_PLUGIN_DIR constant to the plugin slug.
	 *
	 * @param string $plugin_slug The plugin slug.
	 */
	private function create_plugin_path( $plugin_slug ) {
		return trailingslashit( WP_PLUGIN_DIR ) . substr( $plugin_slug, 0, strrpos( $plugin_slug, '/' ) );
	}

	/**
	 * Ensure the plugin has its own directory and not a single-file plugin.
	 *
	 * @param string $plugin Plugin name, may be prefixed with "/".
	 *
	 * @return bool
	 */
	public function is_directory_plugin( $plugin ) {
		return false !== strpos( $plugin, '/', 1 );
	}

	/**
	 * Checks whether the autoloader should be reset. The autoloader should be reset
	 * when a plugin is activating via a method other than a request, for example
	 * using WP-CLI. When this occurs, the activating plugin was not known when
	 * the autoloader selected the package versions for the classmap and filemap
	 * globals, so the autoloader must reselect the versions.
	 *
	 * If the current plugin is not already known, this method will add it to the
	 * $jetpack_autoloader_activating_plugins_paths global.
	 *
	 * @return boolean True if the autoloder must be reset, else false.
	 */
	public function should_autoloader_reset() {
		global $jetpack_autoloader_activating_plugins_paths;

		$plugins_paths       = $this->get_all_active_plugins_paths();
		$current_plugin_path = $this->get_current_plugin_path();
		$plugin_unknown      = ! in_array( $current_plugin_path, $plugins_paths, true );

		if ( $plugin_unknown ) {
			// If the current plugin isn't known, add it to the activating plugins list.
			$jetpack_autoloader_activating_plugins_paths[] = $current_plugin_path;
		}

		return $plugin_unknown;
	}

	/**
	 * Returns an array containing the names of plugins that are activating via a request.
	 *
	 * @return array An array of names of the activating plugins or an empty array.
	 */
	private function get_plugins_activating_via_request() {

		 // phpcs:disable WordPress.Security.NonceVerification.Recommended

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : false;
		$nonce  = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : false;

		/**
		 * Note: we're not actually checking the nonce here becase it's too early
		 * in the execution. The pluggable functions are not yet loaded to give
		 * plugins a chance to plug their versions. Therefore we're doing the bare
		 * minimum: checking whether the nonce exists and it's in the right place.
		 * The request will fail later if the nonce doesn't pass the check.
		 */

		// In case of a single plugin activation there will be a plugin slug.
		if ( 'activate' === $action && ! empty( $nonce ) ) {
			return array( $this->create_plugin_path( wp_unslash( $plugin ) ) );
		}

		$plugins = isset( $_REQUEST['checked'] ) ? $_REQUEST['checked'] : array();

		// In case of bulk activation there will be an array of plugins.
		if ( 'activate-selected' === $action && ! empty( $nonce ) ) {
			$plugin_slugs = array_map( 'wp_unslash', $plugins );
			return array_map( array( $this, 'create_plugin_path' ), $plugin_slugs );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return array();
	}

	/**
	 * Returns the path of the current plugin.
	 *
	 * @return string The path of the current plugin.
	 */
	public function get_current_plugin_path() {
		$vendor_path = dirname( __FILE__ );
		// Path to the plugin's folder (the parent of the vendor folder).
		return substr( $vendor_path, 0, strrpos( $vendor_path, '/' ) );
	}
}
