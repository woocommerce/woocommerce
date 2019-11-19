<?php
/**
 * Returns information about the package and handles init.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Domain;

/**
 * Main package class.
 *
 * @since 2.5.0
 */
class Package {

	/**
	 * Holds the current version for the plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Holds the main path to the plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Holds the path to the main plugin file (entry)
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Constructor
	 *
	 * @param string $version      Version of the plugin.
	 * @param string $plugin_file  Path to the main plugin file.
	 */
	public function __construct( $version, $plugin_file ) {
		$this->version     = $version;
		$this->plugin_file = $plugin_file;
		$this->path        = dirname( $plugin_file );
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
	 * Returns the path to the main plugin file.
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
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
		return '' === $relative_path
			? trailingslashit( $this->path )
			: trailingslashit( $this->path ) . $relative_path;
	}

	/**
	 * Returns the url to the plugin directory.
	 *
	 * @param string $relative_url If provided, the relative url will be
	 *                             appended to the plugin url.
	 *
	 * @return string
	 */
	public function get_url( $relative_url = '' ) {
		return '' === $relative_url
			? plugin_dir_url( $this->get_plugin_file() )
			: plugin_dir_url( $this->get_plugin_file() ) . $relative_url;
	}
}
