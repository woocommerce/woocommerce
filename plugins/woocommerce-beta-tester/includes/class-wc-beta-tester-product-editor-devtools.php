<?php
/**
 * Beta Tester Plugin Product Editor Devtools feature class.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Product Editor Devtools Feature Class.
 */
class WC_Beta_Tester_Product_Editor_Devtools {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Register product editor devtools scripts.
	 */
	public function register_scripts() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}
		if ( ! Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) || ! \Automattic\WooCommerce\Admin\PageController::is_admin_page() ) {
			return;
		}

		$script_path       = '/build/product-editor-devtools.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/product-editor-devtools.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = WC_Beta_Tester::instance()->plugin_url() . $script_path;

		wp_register_script(
			'woocommerce-beta-tester-product-editor-devtools',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woocommerce-beta-tester-product-editor-devtools' );

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/product-editor-devtools.css' );
		wp_register_style(
			'woocommerce-beta-tester-product-editor-devtools',
			plugins_url( '/../build/product-editor-devtools.css', __FILE__ ),
			array(),
			$css_file_version
		);

		wp_enqueue_style( 'woocommerce-beta-tester-product-editor-devtools' );
	}
}

return new WC_Beta_Tester_Product_Editor_Devtools();
