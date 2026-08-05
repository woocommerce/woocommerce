<?php
/**
 * WooCommerce Autoloader.
 *
 * @package WooCommerce\Classes
 * @version 2.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Features;

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

		if ( 0 !== strpos( $class, 'wc_' ) ) {
			return;
		}

		// The Legacy REST API was removed in WooCommerce 9.0, but some servers still have
		// the includes/class-wc-api.php file after they upgrade, which causes a fatal error when executing
		// "class_exists('WC_API')". This will prevent this error, while still making the class visible
		// when it's provided by the WooCommerce Legacy REST API plugin.
		if ( 'wc_api' === $class ) {
			return;
		}

		// If the class is already loaded from a merged package, prevent autoloader from loading it as well.
		if ( \Automattic\WooCommerce\Packages::should_load_class( $class ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( 0 === strpos( $class, 'wc_addons_gateway_' ) ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 18 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_gateway_' ) ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 11 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_shipping_' ) ) {
			$path = $this->include_path . 'shipping/' . substr( str_replace( '_', '-', $class ), 12 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_shortcode_' ) ) {
			$path = $this->include_path . 'shortcodes/';
		} elseif ( 0 === strpos( $class, 'wc_meta_box' ) ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( 0 === strpos( $class, 'wc_admin' ) ) {
			$path = $this->include_path . 'admin/';
		} elseif ( 0 === strpos( $class, 'wc_payment_token_' ) ) {
			$path = $this->include_path . 'payment-tokens/';
		} elseif ( 0 === strpos( $class, 'wc_log_handler_' ) ) {
			$path = $this->include_path . 'log-handlers/';
		} elseif ( 0 === strpos( $class, 'wc_integration' ) ) {
			$path = $this->include_path . 'integrations/' . substr( str_replace( '_', '-', $class ), 15 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_notes_' ) ) {
			$path = $this->include_path . 'admin/notes/';
		} elseif ( 0 === strpos( $class, 'wc_rest_' ) ) {
			// Handle REST API controllers in subdirectories.
			// For V4 controllers, check if the feature is enabled first.
			if ( false !== strpos( $class, '_v4_' ) ) {
				// Only load V4 controllers if the feature is enabled.
				if ( Features::is_enabled( 'rest-api-v4' ) ) {
					$rest_controller_paths = array(
						'rest-api/Controllers/Version4/',
					);

					foreach ( $rest_controller_paths as $rest_path ) {
						if ( $this->load_file( $this->include_path . $rest_path . $file ) ) {
							return;
						}
					}

					// Also check subdirectories recursively for V4.
					$this->load_rest_v4_controller_recursively( $file );
				}
			} else {
				// For non-V4 controllers, load normally.
				$rest_controller_paths = array(
					'rest-api/Controllers/Version1/',
					'rest-api/Controllers/Version2/',
					'rest-api/Controllers/Version3/',
					'rest-api/Controllers/Telemetry/',
				);

				foreach ( $rest_controller_paths as $rest_path ) {
					if ( $this->load_file( $this->include_path . $rest_path . $file ) ) {
						return;
					}
				}
			}
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}

	/**
	 * Recursively load REST API V4 controllers from subdirectories.
	 *
	 * @param string $file File name to search for.
	 */
	private function load_rest_v4_controller_recursively( $file ): bool {
		$v4_base_path = $this->include_path . 'rest-api/Controllers/Version4/';

		// Use RecursiveDirectoryIterator to search subdirectories.
		if ( is_dir( $v4_base_path ) ) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $v4_base_path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $dir_info ) {
				if ( $dir_info->isDir() ) {
					$subdir_path = $dir_info->getPathname() . '/';
					if ( $this->load_file( $subdir_path . $file ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}

new WC_Autoloader();
