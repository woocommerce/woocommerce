<?php

namespace Automattic\WooCommerce\Blueprint;

trait UsePluginHelpers {
	use UseWPFunctions;

	public function activate_plugin_by_slug( $slug ) {
		// Get all installed plugins
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin
				return $this->wp_activate_plugin( $plugin_path );
			}
		}
		return false;
	}

	// Function to deactivate and delete a plugin by its slug
	public function delete_plugin_by_slug( $slug ) {
		// Get all installed plugins
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin
				if ( $this->deactivate_plugin_by_slug( $slug ) ) {
					// Delete the plugin
					return $this->wp_delete_plugins( array( $plugin_path ) );
				}
			}
		}
		return false;
	}

	public function deactivate_plugin_by_slug( $slug ) {
		// Get all installed plugins
		$all_plugins = $this->wp_get_plugins();

		// Loop through all plugins to find the one with the specified slug
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin
				deactivate_plugins( $plugin_path );

				// Check if the plugin has been deactivated
				if ( !is_plugin_active( $plugin_path ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
