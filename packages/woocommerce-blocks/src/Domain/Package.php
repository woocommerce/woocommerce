<?php
namespace Automattic\WooCommerce\Blocks\Domain;

/**
 * Main package class.
 *
 * Returns information about the package and handles init.
 *
 * @since 2.5.0
 */
class Package {

	/**
	 * Holds the current version of the blocks plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Holds the main path to the blocks plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Constructor
	 *
	 * @param string $version      Version of the plugin.
	 * @param string $plugin_path  Path to the main plugin file.
	 */
	public function __construct( $version, $plugin_path ) {
		$this->version = $version;
		$this->path    = $plugin_path;
	}

	/**
	 * Returns the version of the plugin.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Returns the path to the plugin directory.
	 *
	 * @param string $relative_path  If provided, the relative path will be
	 *                               appended to the plugin path.
	 *
	 * @return string
	 */
	public function get_path( $relative_path = '' ) {
		return trailingslashit( $this->path ) . $relative_path;
	}

	/**
	 * Returns the url to the blocks plugin directory.
	 *
	 * @param string $relative_url If provided, the relative url will be
	 *                             appended to the plugin url.
	 *
	 * @return string
	 */
	public function get_url( $relative_url = '' ) {
		// Append index.php so WP does not return the parent directory.
		return plugin_dir_url( $this->path . '/index.php' ) . $relative_url;
	}

	/**
	 * Checks if we're executing the code in an experimental build mode.
	 *
	 * @return boolean
	 */
	public static function is_experimental_build() {
		return WOOCOMMERCE_BLOCKS_PHASE > 2;
	}

	/**
	 * Checks if we're executing the code in an feature plugin or experimental build mode.
	 *
	 * @return boolean
	 */
	public static function is_feature_plugin_build() {
		return WOOCOMMERCE_BLOCKS_PHASE > 1;
	}
}
