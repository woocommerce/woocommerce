<?php
/**
 * Beta Tester plugin settings class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class WC_Slot_Fill_Viewer {
	/**
	 * Constructor.
	 */
	public function __construct() {
        if ( ! $this->is_viewer_enabled() ) {
            return;
        }

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

	}

    /**
     * Check if the viewer is enabled.  (It always is).
     */
    private function is_viewer_enabled() {
        return get_option( 'woocommerce_slot_fill_viewer_enabled', 'no' ) === 'yes';
    }

	/**
	 * Register live branches scripts.
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_page' ) ||
			! \Automattic\WooCommerce\Admin\PageController::is_admin_page()
		) {
			return;
		}

		$script_path       = '/build/slot-fill-viewer.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/slot-fill-viewer.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = WC_Beta_Tester::instance()->plugin_url() . $script_path;

		wp_register_script(
			'woocommerce-slot-fill-viewer',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woocommerce-slot-fill-viewer' );

        $css_file_version = filemtime( WC_Beta_Tester::instance()->plugin_dir() . '/build/style-slot-fill-viewer.css' );

        wp_register_style(
            'woocommerce-slot-fill-viewer',
            WC_Beta_Tester::instance()->plugin_url() .  '/build/style-slot-fill-viewer.css',
            array(),
            $css_file_version
        );

        wp_enqueue_style( 'woocommerce-slot-fill-viewer' );
	}
}

new WC_Slot_Fill_Viewer();
