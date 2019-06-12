<?php
/**
 * Custom Autoloader used when included inside another project as a package.
 *
 * Since this package can be included multiple times, but we want to ensure the latest version
 * is loaded (rather than the first package found), we need a custom autoloader.
 *
 * This autoloader is only loaded for the chosen (latest) package.
 *
 * For version 4 onwards, we use psr-4 class naming.
 * For version 3 and below, we use a classmap to maintain class naming/backwards compatibility.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Holds the classmap.
	 *
	 * @var array
	 */
	protected static $classmap = [];

	/**
	 * Register the autoloader.
	 *
	 * @param array $classmap Classmap of files to include.
	 */
	public static function register( $classmap ) {
		self::$classmap = (array) $classmap;
		spl_autoload_register( array( __CLASS__, 'autoload_class' ) );
	}

	/**
	 * Try to autoload a class.
	 *
	 * @param string $class Class being autoloaded.
	 * @return boolean
	 */
	public static function autoload_class( $class ) {
		$prefix   = 'WooCommerce\\RestApi\\';
		$base_dir = __DIR__ . '/';

		// does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return self::autoload_from_classmap( $class );
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			return include $file;
		}
	}

	/**
	 * Try to autoload a class from a classmap.
	 *
	 * @param string $class Class being autoloaded.
	 * @return boolean
	 */
	protected static function autoload_from_classmap( $class ) {
		if ( empty( self::$classmap ) ) {
			return false;
		}

		if ( array_key_exists( $class, self::$classmap ) ) {
			return include self::$classmap[ $class ];
		}
	}
}
