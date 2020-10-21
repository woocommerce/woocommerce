<?php
namespace Automattic\WooCommerce\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Domain\Package;

/**
 * The Api class provides an interface to various asset registration helpers.
 *
 * Contains asset api methods
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
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $this->package->get_path() . $file ) ) {
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
	 * Registers a script according to `wp_register_script`, additionally
	 * loading the translations for the file.
	 *
	 * @since 2.5.0
	 *
	 * @param string $handle        Name of the script. Should be unique.
	 * @param string $relative_src  Relative url for the script to the path
	 *                              from plugin root.
	 * @param array  $dependencies  Optional. An array of registered script
	 *                              handles this script depends on. Default
	 *                              empty array.
	 * @param bool   $has_i18n      Optional. Whether to add a script
	 *                              translation call to this file. Default:
	 *                              true.
	 */
	public function register_script( $handle, $relative_src, $dependencies = [], $has_i18n = true ) {
		$src        = $this->get_asset_url( $relative_src );
		$asset_path = $this->package->get_path(
			str_replace( '.js', '.asset.php', $relative_src )
		);

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = isset( $asset['dependencies'] ) ? array_merge( $asset['dependencies'], $dependencies ) : $dependencies;
			$version      = ! empty( $asset['version'] ) ? $asset['version'] : $this->get_file_version( $relative_src );
		} else {
			$version = $this->get_file_version( $relative_src );
		}

		wp_register_script( $handle, $src, apply_filters( 'woocommerce_blocks_register_script_dependencies', $dependencies, $handle ), $version, true );

		if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'woocommerce', $this->package->get_path( 'languages' ) );
		}
	}

	/**
	 * Queues a block script in the frontend.
	 *
	 * @since 2.5.0
	 * @since 2.6.0 Changed $name to $script_name and added $handle argument.
	 * @since 2.9.0 Made it so scripts are not loaded in admin pages.
	 *
	 * @param string $script_name  Name of the script used to identify the file inside build folder.
	 * @param string $handle       Optional. Provided if the handle should be different than the script name. `wc-` prefix automatically added.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 */
	public function register_block_script( $script_name, $handle = '', $dependencies = [] ) {
		if ( is_admin() ) {
			return;
		}
		$relative_src = $this->get_block_asset_build_path( $script_name );
		$handle       = '' !== $handle ? 'wc-' . $handle : 'wc-' . $script_name;
		$this->register_script( $handle, $relative_src, $dependencies );
		wp_enqueue_script( $handle );
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @since 2.5.0
	 * @since 2.6.0 Change src to be relative source.
	 *
	 * @param string $handle       Name of the stylesheet. Should be unique.
	 * @param string $relative_src Relative source of the stylesheet to the plugin path.
	 * @param array  $deps         Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media        Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                             'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function register_style( $handle, $relative_src, $deps = [], $media = 'all' ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $relative_src );
		$src      = $this->get_asset_url( $relative_src );
		$ver      = $this->get_file_version( $filename );
		wp_register_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * Returns the appropriate asset path for loading either legacy builds or
	 * current builds.
	 *
	 * @param   string $filename  Filename for asset path (without extension).
	 * @param   string $type      File type (.css or .js).
	 *
	 * @return  string             The generated path.
	 */
	public function get_block_asset_build_path( $filename, $type = 'js' ) {
		global $wp_version;
		$suffix = version_compare( $wp_version, '5.3', '>=' )
			? ''
			: '-legacy';
		return "build/$filename$suffix.$type";
	}
}
