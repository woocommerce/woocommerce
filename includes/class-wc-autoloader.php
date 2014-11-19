<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Autoloader
 *
 * @class 		WC_Autoloader
 * @version		2.3.0
 * @package		WooCommerce/Classes/
 * @category	Class
 * @author 		WooThemes
 */
class WC_Autoloader {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( WC_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name
	 * @param  string $class
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	/**
	 * Include a class file
	 * @param  string $path
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'wc_addons_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 18 ) . '/';
		} elseif ( strpos( $class, 'wc_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 11 ) . '/';
		} elseif ( strpos( $class, 'wc_shipping_' ) === 0 ) {
			$path = $this->include_path . 'shipping/' . substr( str_replace( '_', '-', $class ), 12 ) . '/';
		} elseif ( strpos( $class, 'wc_shortcode_' ) === 0 ) {
			$path = $this->include_path . 'shortcodes/';
		} elseif ( strpos( $class, 'wc_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( strpos( $class, 'wc_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		if ( ! $this->load_file( $path . $file ) && strpos( $class, 'wc_' ) === 0 ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_Autoloader();
