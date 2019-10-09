<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '2.3.0';

	/**
	 * Stores if init has ran yet.
	 *
	 * @var boolean
	 */
	protected static $did_init = false;

	/**
	 * Init the package - load the blocks library and define constants.
	 */
	public static function init() {
		if ( true === self::$did_init || ! self::has_dependencies() ) {
			return;
		}

		self::$did_init = true;
		self::remove_core_blocks();

		if ( ! self::is_built() ) {
			return self::add_build_notice();
		}

		Library::init();
		Assets::init();
		RestApi::init();
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Check dependencies exist.
	 *
	 * @return boolean
	 */
	protected static function has_dependencies() {
		return class_exists( 'WooCommerce' ) && function_exists( 'register_block_type' );
	}

	/**
	 * See if files have been built or not.
	 *
	 * @return bool
	 */
	protected static function is_built() {
		return file_exists( dirname( __DIR__ ) . '/build/featured-product.js' );
	}

	/**
	 * Add a notice stating that the build has not been done yet.
	 */
	protected static function add_build_notice() {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				printf(
					/* Translators: %1$s is the install command, %2$s is the build command, %3$s is the watch command. */
					esc_html__( 'WooCommerce Blocks development mode requires files to be built. From the plugin directory, run %1$s to install dependencies, %2$s to build the files or %3$s to build the files and watch for changes.', 'woocommerce' ),
					'<code>npm install</code>',
					'<code>npm run build</code>',
					'<code>npm start</code>'
				);
				echo '</p></div>';
			}
		);
	}

	/**
	 * Remove core blocks (for 3.6 and below).
	 */
	protected static function remove_core_blocks() {
		remove_action( 'init', array( 'WC_Block_Library', 'init' ) );
		remove_action( 'init', array( 'WC_Block_Library', 'register_blocks' ) );
		remove_action( 'init', array( 'WC_Block_Library', 'register_assets' ) );
		remove_filter( 'block_categories', array( 'WC_Block_Library', 'add_block_category' ) );
		remove_action( 'admin_print_footer_scripts', array( 'WC_Block_Library', 'print_script_settings' ), 1 );
		remove_action( 'init', array( 'WGPB_Block_Library', 'init' ) );
	}

}
