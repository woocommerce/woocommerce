<?php
/**
 * Beta Tester Plugin Live Branches feature class.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Live Branches Feature Class.
 */
class WC_Beta_Tester_Live_Branches {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

		// By the time this code runs it appears too late to hook into `admin_menu`.

		// NOTE - We don't have feature flags, so add the following code to enable it
		// in development: `$this->register_page()`.
	}

	/**
	 * Register live branches scripts.
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ||
			! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
		) {
			return;
		}

		$script_path       = '/build/live-branches.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/live-branches.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = WC_Beta_Tester::instance()->plugin_url() . $script_path;

		wp_register_script(
			'woocommerce-beta-tester-live-branches',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woocommerce-beta-tester-live-branches' );
	}

	/**
	 * Register live branches page.
	 */
	public function register_page() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		wc_admin_register_page(
			array(
				'id'         => 'woocommerce-beta-tester-live-branches',
				// phpcs:disable
				'title'      => __( 'Live Branches', 'woocommerce-beta-tester' ),
				'path'       => '/live-branches',
				'parent'     => 'woocommerce',
				'capability' => 'read',
			)
		);
	}
}

return new WC_Beta_Tester_Live_Branches();
