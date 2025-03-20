<?php

namespace Automattic\WooCommerce\Blueprint;

/**
 * Trait UseWPFunctions
 */
trait UseWPFunctions {
	/**
	 * Whether the filesystem has been initialized.
	 *
	 * @var bool
	 */
	private $filesystem_initialized = false;
	/**
	 * Adds a filter to a specified tag.
	 *
	 * @param string   $tag             The name of the filter to hook the $function_to_add to.
	 * @param callable $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	public function wp_add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, $function_to_add, $priority, $accepted_args );
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
	public function wp_add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * Calls the functions added to a filter hook.
	 *
	 * @param string $tag   The name of the filter hook.
	 * @param mixed  $value The value on which the filters hooked to $tag are applied on.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	// phpcs:ignore
	public function wp_apply_filters( $tag, $value ) {
		$args = func_get_args();
		return call_user_func_array( 'apply_filters', $args );
	}

	/**
	 * Executes the functions hooked on a specific action hook.
	 *
	 * @param string $tag The name of the action to be executed.
	 * @param mixed  ...$args Optional. Additional arguments which are passed on to the functions hooked to the action.
	 */
	public function wp_do_action( $tag, ...$args ) {
		// phpcs:ignore
		do_action( $tag, ...$args );
	}
	/**
	 * Checks if a plugin is active.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function wp_is_plugin_active( string $plugin ) {
		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin );
	}

	/**
	 * Retrieves plugin information from the WordPress Plugin API.
	 *
	 * @param string $action The type of information to retrieve from the API.
	 * @param array  $args Optional. Arguments to pass to the API.
	 * @return object|WP_Error The API response object or WP_Error on failure.
	 */
	public function wp_plugins_api( $action, $args = array() ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		return plugins_api( $action, $args );
	}

	/**
	 * Retrieves all plugins.
	 *
	 * @param string $plugin_folder Optional. Path to the plugin folder to scan.
	 * @return array Array of plugins.
	 */
	public function wp_get_plugins( string $plugin_folder = '' ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins( $plugin_folder );
	}

	/**
	 * Retrieves all themes.
	 *
	 * @param array $args Optional. Arguments to pass to the API.
	 * @return array Array of themes.
	 */
	public function wp_get_themes( $args = array() ) {
		return wp_get_themes( $args );
	}

	/**
	 * Retrieves a theme.
	 *
	 * @param string|null $stylesheet Optional. The theme's stylesheet name.
	 * @return WP_Theme The theme object.
	 */
	public function wp_get_theme( $stylesheet = null ) {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		return wp_get_theme( $stylesheet );
	}

	/**
	 * Retrieves theme information from the WordPress Theme API.
	 *
	 * @param string $action The type of information to retrieve from the API.
	 * @param array  $args Optional. Arguments to pass to the API.
	 * @return object|WP_Error The API response object or WP_Error on failure.
	 */
	public function wp_themes_api( $action, $args = array() ) {
		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		return themes_api( $action, $args );
	}

	/**
	 * Activates a plugin.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 * @param string $redirect Optional. URL to redirect to after activation.
	 * @param bool   $network_wide Optional. Whether to enable the plugin for all sites in the network.
	 * @param bool   $silent Optional. Whether to prevent calling activation hooks.
	 * @return WP_Error|null WP_Error on failure, null on success.
	 */
	public function wp_activate_plugin( $plugin, $redirect = '', $network_wide = false, $silent = false ) {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return activate_plugin( $plugin, $redirect, $network_wide, $silent );
	}

	/**
	 * Deletes plugins.
	 *
	 * @param array $plugins List of plugins to delete.
	 * @return array|WP_Error Array of results or WP_Error on failure.
	 */
	public function wp_delete_plugins( $plugins ) {
		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return delete_plugins( $plugins );
	}

	/**
	 * Updates an option in the database.
	 *
	 * @param string      $option Name of the option to update.
	 * @param mixed       $value New value for the option.
	 * @param string|null $autoload Optional. Whether to load the option when WordPress starts up.
	 * @return bool True if option was updated, false otherwise.
	 */
	public function wp_update_option( $option, $value, $autoload = null ) {
		return update_option( $option, $value, $autoload );
	}

	/**
	 * Retrieves an option from the database.
	 *
	 * @param string $option Name of the option to retrieve.
	 * @param mixed  $default_value Optional. Default value to return if the option does not exist.
	 * @return mixed Value of the option or $default if the option does not exist.
	 */
	public function wp_get_option( $option, $default_value = false ) {
		return get_option( $option, $default_value );
	}

	/**
	 * Switches the current theme.
	 *
	 * @param string $name The name of the theme to switch to.
	 */
	public function wp_switch_theme( $name ) {
		if ( ! function_exists( 'switch_theme' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		return switch_theme( $name );
	}

	/**
	 * Initializes the WordPress filesystem.
	 *
	 * @return bool
	 */
	public function wp_init_filesystem() {
		if ( ! $this->filesystem_initialized ) {
			if ( ! class_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();
			$this->filesystem_initialized = true;
		}

		return true;
	}

	/**
	 * Unzips a file to a specified location.
	 *
	 * @param string $path Path to the ZIP file.
	 * @param string $to Destination directory.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function wp_unzip_file( $path, $to ) {
		$this->wp_init_filesystem();
		return unzip_file( $path, $to );
	}

	/**
	 * Retrieves the upload directory information.
	 *
	 * @return array Array of upload directory information.
	 */
	public function wp_upload_dir() {
		return \wp_upload_dir();
	}

	/**
	 * Retrieves the root directory of the current theme.
	 *
	 * @return string The root directory of the current theme.
	 */
	public function wp_get_theme_root() {
		return \get_theme_root();
	}

	/**
	 * Checks if a variable is a WP_Error.
	 *
	 * @param mixed $thing Variable to check.
	 * @return bool True if the variable is a WP_Error, false otherwise.
	 */
	public function is_wp_error( $thing ) {
		return is_wp_error( $thing );
	}

	/**
	 * Downloads a file from a URL.
	 *
	 * @param string $url The URL of the file to download.
	 * @return string|WP_Error The local file path on success, WP_Error on failure.
	 */
	public function wp_download_url( $url ) {
		if ( ! function_exists( 'download_url' ) ) {
			include ABSPATH . '/wp-admin/includes/file.php';
		}
		return download_url( $url );
	}

	/**
	 * Alias for WP_Filesystem::put_contents().
	 *
	 * @param string $file_path The path to the file to write.
	 * @param mixed  $content    The data to write to the file.
	 *
	 * @return mixed
	 */
	public function wp_filesystem_put_contents( $file_path, $content ) {
		global $wp_filesystem;
		$this->wp_init_filesystem();

		return $wp_filesystem->put_contents( $file_path, $content );
	}
}
