<?php
/**
 * Contains asset api methods
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Domain\Package;

/**
 * The Api class provides an interface to various asset registration helpers.
 *
 * @since 2.5.0
 */
class Api {

	/**
	 * Reference to the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor for class
	 *
	 * @param Package $package An instance of Package.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file (relative to the plugin
	 *                     directory).
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return filemtime( $this->package->get_path( trim( $file, '/' ) ) );
		}
		return $this->package->get_version();
	}

	/**
	 * Retrieve the url to an asset for this plugin.
	 *
	 * @param string $relative_path An optional relative path appended to the
	 *                              returned url.
	 *
	 * @return string
	 */
	protected function get_asset_url( $relative_path = '' ) {
		return $this->package->get_url( $relative_path );
	}

	/**
	 * Returns the dependency array for the given asset relative path.
	 *
	 * @param string $asset_relative_path  Something like 'build/constants.js'
	 *                                     considered to be relative to the main
	 *                                     asset path.
	 * @param array  $extra_dependencies   Extra dependencies to be explicitly
	 *                                     added to the generated array.
	 *
	 * @return array  An array of dependencies
	 */
	protected function get_dependencies(
		$asset_relative_path,
		$extra_dependencies = []
	) {
		$dependency_path = $this->package->get_path(
			str_replace( '.js', '.deps.json', $asset_relative_path )
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions
		$dependencies = file_exists( $dependency_path )
			// phpcs:ignore WordPress.WP.AlternativeFunctions
			? json_decode( file_get_contents( $dependency_path ) )
			: [];
		return array_merge( $dependencies, $extra_dependencies );
	}

	/**
	 * Registers a script according to `wp_register_script`, additionally
	 * loading the translations for the file.
	 *
	 * @since 2.5.0
	 *
	 * @param string $handle        Name of the script. Should be unique.
	 * @param string $relative_src  Relative url for the script to the path
	 *                              from plugin root.
	 * @param array  $deps          Optional. An array of registered script
	 *                              handles this script depends on. Default
	 *                              empty array.
	 * @param bool   $has_i18n      Optional. Whether to add a script
	 *                              translation call to this file. Default:
	 *                              true.
	 */
	public function register_script( $handle, $relative_src, $deps = [], $has_i18n = true ) {
		wp_register_script(
			$handle,
			$this->get_asset_url( $relative_src ),
			$this->get_dependencies( $relative_src, $deps ),
			$this->get_file_version( $relative_src ),
			true
		);
		if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				$handle,
				'woocommerce',
				$this->package->get_path( 'languages' )
			);
		}
	}

	/**
	 * Queues a block script.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name Name of the script used to identify the file inside build folder.
	 */
	public function register_block_script( $name ) {
		$src    = 'build/' . $name . '.js';
		$handle = 'wc-' . $name;
		$this->register_script( $handle, $src );
		wp_enqueue_script( $handle );
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @since 2.5.0
	 *
	 * @param string $handle Name of the stylesheet. Should be unique.
	 * @param string $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param array  $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media  Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                       'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function register_style( $handle, $src, $deps = [], $media = 'all' ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $src );
		$ver      = $this->get_file_version( $filename );
		wp_register_style( $handle, $src, $deps, $ver, $media );
	}
}
