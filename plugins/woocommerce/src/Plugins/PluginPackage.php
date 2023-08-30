<?php
/**
 * Plugin package class for retrieving basic plugin information.
 */

namespace Automattic\WooCommerce\Plugins;

/**
 * PluginPackage class.
 */
class PluginPackage {

    /**
	 * Plugin ID.
	 *
	 * @var string
	 */
	private $id;

    /**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	private $plugin_path;

    /**
	 * Build path.
	 *
	 * @var string
	 */
	private $build_path;

    /**
	 * Includes path.
	 *
	 * @var string
	 */
	private $includes_path;

    /**
     * Constructor.
     */
    public function __construct( $id, $version, $plugin_path, $build_path, $includes_path ) {
        $this->id            = $id;
        $this->version       = $version;
        $this->plugin_path   = $plugin_path;
        $this->build_path    = $build_path;
        $this->includes_path = $includes_path;
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
	 * Returns the path to the build directory.
	 *
	 * @param string $relative_path  If provided, the relative path will be
	 *                               appended to the build path.
	 *
	 * @return string
	 */
	public function get_build_path( $relative_path = '' ) {
		return trailingslashit( $this->build_path ) . $relative_path;
	}

    /**
	 * Returns the path to the build directory.
	 *
	 * @param string $relative_path  If provided, the relative path will be
	 *                               appended to the build path.
	 *
	 * @return string
	 */
	public function get_includes_path( $relative_path = '' ) {
		return trailingslashit( $this->includes_path ) . $relative_path;
	}

}