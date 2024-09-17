<?php
/**
 * WooCommerce Autoloader.
 *
 * @package WooCommerce\Classes
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
	 * Known class names and their file paths for autoloading.
	 *
	 * @var array
	 */
	private static array $known_classes_paths = array(
		'WC_Product_Usage'             => WC_ABSPATH . 'includes/product-usage/class-wc-product-usage.php',
		'WC_Product_Usage_Rule_Set'    => WC_ABSPATH . 'includes/product-usage/class-wc-product-usage-rule-set.php',
		'WC_WCCOM_Site'                => WC_ABSPATH . 'includes/wccom-site/class-wc-wccom-site.php',
		'WC_Helper'                    => WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php',
		'WC_Helper_Options'            => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php',
		'WC_Helper_API'                => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php',
		'WC_Woo_Update_Manager_Plugin' => WC_ABSPATH . 'includes/admin/helper/class-wc-woo-update-manager-plugin.php',
		'WC_Helper_Updater'            => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-updater.php',
		'WC_Plugin_Api_Updater'        => WC_ABSPATH . 'includes/admin/helper/class-wc-plugin-api-updater.php',
		'WC_Helper_Compat'             => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-compat.php',
		'WC_WCCOM_Site_Installer'      => WC_ABSPATH . 'includes/wccom-site/class-wc-wccom-site-installer.php',
		'WC_Helper_Subscriptions_API'  => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-subscriptions-api.php',
		'WC_Helper_Orders_API'         => WC_ABSPATH . 'includes/admin/helper/class-wc-helper-orders-api.php',
		'WC_Product_Usage_Notice'      => WC_ABSPATH . 'includes/admin/helper/class-wc-product-usage-notice.php',
	);

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
			require $path;
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
		if ( isset( self::$known_classes_paths[ $class ] ) ) {
			$this->load_file( self::$known_classes_paths[ $class ] );
			return;
		}

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
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_Autoloader();
