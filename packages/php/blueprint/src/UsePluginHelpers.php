<?php

namespace Automattic\WooCommerce\Blueprint;

trait UsePluginHelpers {
	use UseWPFunctions;

	/**
	 * Activate a plugin by its slug.
	 *
	 * Searches for the plugin with the specified slug in the installed plugins
	 * and activates it.
	 *
	 * @param string $slug The slug of the plugin to activate.
	 *
	 * @return false|null|WP_Error Null on success, WP_Error on invalid file, false if not found.
	 */
	public function activate_plugin_by_slug( $slug ) {
		// Get all installed plugins.
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug.
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug.
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin.
				return $this->wp_activate_plugin( $plugin_path );
			}
		}
		return false;
	}

	/**
	 * Check if a plugin with the specified slug is installed.
	 *
	 * @param string $slug The slug of the plugin to check.
	 *
	 * @return bool
	 */
	public function is_plugin_dir( $slug ) {
		$all_plugins = $this->wp_get_plugins();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			// Extract the directory name from the plugin file path.
			$plugin_dir = explode( '/', $plugin_file )[0];

			// Check for an exact match with the slug.
			if ( $plugin_dir === $slug ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deactivate and delete a plugin by its slug.
	 *
	 * Searches for the plugin with the specified slug in the installed plugins,
	 * deactivates it if active, and then deletes it.
	 *
	 * @param string $slug The slug of the plugin to delete.
	 *
	 * @return bool|WP_Error True if the plugin was deleted, false otherwise.
	 */
	public function delete_plugin_by_slug( $slug ) {
		// Get all installed plugins.
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug.
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug.
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin.
				if ( $this->deactivate_plugin_by_slug( $slug ) ) {
					// Delete the plugin.
					return $this->wp_delete_plugins( array( $plugin_path ) );
				}
			}
		}
		return false;
	}

	/**
	 * Deactivate a plugin by its slug.
	 *
	 * Searches for the plugin with the specified slug in the installed plugins
	 * and deactivates it.
	 *
	 * @param string $slug The slug of the plugin to deactivate.
	 *
	 * @return bool True if the plugin was deactivated, false otherwise.
	 */
	public function deactivate_plugin_by_slug( $slug ) {
		// Get all installed plugins.
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug.
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug.
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin.
				deactivate_plugins( $plugin_path );

				// Check if the plugin has been deactivated.
				if ( ! is_plugin_active( $plugin_path ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
