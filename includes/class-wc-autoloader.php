<?php
/**
 * WooCommerce Autoloader.
 *
 * @package WooCommerce/Classes
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 */
class WC_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( WC_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 'wc' !== substr( $class, 0, 2 ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = $this->include_path;

		switch ( true ) {
			case ( 'wc_addons_gateway_' === substr( $class, 0, 18 ) ):
				$path .= 'gateways/' . substr( str_replace( '_', '-', $class ), 18 ) . '/';
				break;
			case ( 'wc_gateway_' === substr( $class, 0, 11 ) ):
				$path .= 'gateways/' . substr( str_replace( '_', '-', $class ), 11 ) . '/';
				break;
			case ( 'wc_shipping_' === substr( $class, 0, 12 ) ):
				$path .= 'shipping/' . substr( str_replace( '_', '-', $class ), 12 ) . '/';
				break;
			case ( 'wc_shortcode_' === substr( $class, 0, 13 ) ):
				$path .= 'shortcodes/';
				break;
			case ( 'wc_meta_box' === substr( $class, 0, 11 ) ):
				$path .= 'admin/meta-boxes/';
				break;
			case ( 'wc_admin' === substr( $class, 0, 8 ) ):
				$path .= 'admin/';
				break;
			case ( 'wc_payment_token_' === substr( $class, 0, 17 ) ):
				$path .= 'payment-tokens/';
				break;
			case ( 'wc_log_handler_' === substr( $class, 0, 15 ) ):
				$path .= 'log-handlers/';
				break;
			default:
				$path = '';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_Autoloader();
