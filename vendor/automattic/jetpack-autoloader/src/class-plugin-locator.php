<?php
/* HEADER */ // phpcs:ignore

/**
 * This class scans the WordPress installation to find active plugins.
 */
class Plugin_Locator {

	/**
	 * The path processor for finding plugin paths.
	 *
	 * @var Path_Processor
	 */
	private $path_processor;

	/**
	 * The constructor.
	 *
	 * @param Path_Processor $path_processor The Path_Processor instance.
	 */
	public function __construct( $path_processor ) {
		$this->path_processor = $path_processor;
	}

	/**
	 * Finds the path to the current plugin.
	 *
	 * @return string $path The path to the current plugin.
	 * @throws \RuntimeException If the current plugin does not have an autoloader.
	 */
	public function find_current_plugin() {
		// Escape from `vendor/__DIR__` to root plugin directory.
		$plugin_directory = dirname( dirname( __DIR__ ) );

		// Use the path processor to ensure that this is an autoloader we're referencing.
		$path = $this->path_processor->find_directory_with_autoloader( $plugin_directory, array() );
		if ( false === $path ) {
			throw new \RuntimeException( 'Failed to locate plugin ' . $plugin_directory );
		}

		return $path;
	}

	/**
	 * Checks a given option for plugin paths.
	 *
	 * @param string $option_name  The option that we want to check for plugin information.
	 * @param bool   $site_option  Indicates whether or not we want to check the site option.
	 *
	 * @return array $plugin_paths The list of absolute paths we've found.
	 */
	public function find_using_option( $option_name, $site_option = false ) {
		$raw = $site_option ? get_site_option( $option_name, array() ) : get_option( $option_name, array() );
		if ( empty( $raw ) ) {
			return array();
		}

		return $this->convert_plugins_to_paths( $raw );
	}

	/**
	 * Checks for plugins that are being activated in this request and returns all that it finds.
	 *
	 * @return array $plugin_paths The list of absolute paths we've found.
	 */
	public function find_activating_this_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		/**
		 * Note: we're not actually checking the nonce here because it's too early
		 * in the execution. The pluggable functions are not yet loaded to give
		 * plugins a chance to plug their versions. Therefore we're doing the bare
		 * minimum: checking whether the nonce exists and it's in the right place.
		 * The request will fail later if the nonce doesn't pass the check.
		 */
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return array();
		}

		$plugin_slugs = array();

		$action = isset( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : false;
		switch ( $action ) {
			case 'activate':
				if ( empty( $_REQUEST['plugin'] ) ) {
					break;
				}

				$plugin_slugs[] = wp_unslash( $_REQUEST['plugin'] );
				break;

			case 'activate-selected':
				if ( empty( $_REQUEST['checked'] ) ) {
					break;
				}

				$plugin_slugs = wp_unslash( $_REQUEST['checked'] );
				break;
		}

		return $this->convert_plugins_to_paths( $plugin_slugs );
	}

	/**
	 * Given an array of plugin slugs or paths, this will convert them to absolute paths and filter
	 * out the plugins that are not directory plugins. Note that array keys will also be included
	 * if they are plugin paths!
	 *
	 * @param string[] $plugins Plugin paths or slugs to filter.
	 *
	 * @return string[]
	 */
	private function convert_plugins_to_paths( $plugins ) {
		// We're going to look for plugins in the standard directories.
		$path_constants = array( WP_PLUGIN_DIR, WPMU_PLUGIN_DIR );

		$plugin_paths = array();
		foreach ( $plugins as $key => $value ) {
			$path = $this->path_processor->find_directory_with_autoloader( $key, $path_constants );
			if ( $path ) {
				$plugin_paths[] = $path;
			}

			$path = $this->path_processor->find_directory_with_autoloader( $value, $path_constants );
			if ( $path ) {
				$plugin_paths[] = $path;
			}
		}

		return $plugin_paths;
	}
}
