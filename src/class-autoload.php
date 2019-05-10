<?php
/**
 * Autoload REST API classes.
 *
 * @package WooCommerce/Rest_Api
 */

namespace WooCommerce\Rest_Api;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Rest_Api\Singleton;

/**
 * Autoload class.
 */
class Autoload {
	use Singleton;

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	protected function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Class Autoloader.
	 *
	 * @param string $class Classname.
	 */
	public function autoload( $class ) {
		$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '\\' . __NAMESPACE__ . '\\', '', $class ) );
		$file  = $this->get_file_name_from_class( $class );
		$dir   = trailingslashit( dirname( __FILE__ ) );

		// Non-namespaced files.
		if ( stristr( $class, 'WC_REST_' ) ) {
			if ( stristr( $class, 'WC_REST_Blocks' ) ) {
				$subdir = 'wc-blocks/';
			} elseif ( stristr( $class, '_V1_' ) ) {
				$subdir = 'v1/';
			} elseif ( stristr( $class, '_V2_' ) ) {
				$subdir = 'v2/';
			} else {
				$subdir = 'v3/';
			}

			if ( file_exists( $dir . $subdir . $file ) ) {
				include $dir . $subdir . $file;
				return;
			}
		}

		if ( file_exists( $dir . $class . '.php' ) ) {
			include $dir . $class . '.php';
			return;
		}

		if ( file_exists( $dir . $file ) ) {
			include $dir . $file;
			return;
		}
	}
}
