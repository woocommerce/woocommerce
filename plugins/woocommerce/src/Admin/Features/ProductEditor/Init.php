<?php
/**
 * WooCommerce Product Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductEditor;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Loads assets related to the product editor.
 */
class Init {

	/**
	 * Constructor
	 */
	public function __construct() {
        $this->register_page();
        $this->register_scripts_and_styles();
    }

    /**
     * Register the editor page.
     */
    public function register_page() {
		add_menu_page(
            __( 'Product Editor', 'woocommerce' ),
            __( 'Product Editor', 'woocommerce' ),
            'edit_products',
            'product-editor',
            array( View::class, 'render' ),
            '',
            56
        );
    }

    /**
     * Register the interactivity scripts.
     */
    public function register_scripts_and_styles() {
        $suffix       = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
        $version      = Constants::get_constant( 'WC_VERSION' );

		WCAdminAssets::register_script( 'wp-admin-scripts', 'interactivity-components', false );

        wp_register_script_module(
            'product-editor-interactivity',
            WC()->plugin_url() . '/assets/js/admin/product-editor-interactivity' . $suffix . '.js',
            array( '@wordpress/interactivity' ),
            $version,
        );

        wp_enqueue_style(
            'product-editor-interactivity',
            WC()->plugin_url() . '/assets/css/product-editor-interactivity' . $suffix . '.css',
            array(),
            $version,
        );

        add_action( 'admin_enqueue_scripts', array( wp_interactivity(), 'register_script_modules' ) );
        add_action( 'admin_print_footer_scripts', array( wp_interactivity(), 'print_client_interactivity_data' ) );
        add_action(
            'admin_enqueue_scripts',
            function () {
                wp_enqueue_script('wp-core-data');
                wp_enqueue_script('wp-data');
                wp_enqueue_script_module(
                    'product-editor-interactivity',
                );
            }
        );
    }

}