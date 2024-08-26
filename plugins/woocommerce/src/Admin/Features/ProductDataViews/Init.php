<?php
/**
 * WooCommerce Product Data Views
 */

namespace Automattic\WooCommerce\Admin\Features\ProductDataViews;

use Automattic\WooCommerce\Blocks\Utils\Utils;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Loads assets related to the product block editor.
 */
class Init {
	/**
	 * Constructor
	 */
	public function __construct() {
		if ( $this->has_data_views_support() ) {
			add_action( 'admin_menu', array( $this, 'woocommerce_add_new_products_dashboard' ) );
		}
	}

	/**
	 * Checks for data views support.
	 */
	private function has_data_views_support() {
		if ( Utils::wp_version_compare( '6.6', '>=' ) ) {
			return true;
		}

		if ( is_plugin_active( 'gutenberg/gutenberg.php' ) ) {
			$gutenberg_version = '';

			if ( defined( 'GUTENBERG_VERSION' ) ) {
				$gutenberg_version = GUTENBERG_VERSION;
			}

			if ( ! $gutenberg_version ) {
				$gutenberg_data    = get_file_data(
					WP_PLUGIN_DIR . '/gutenberg/gutenberg.php',
					array( 'Version' => 'Version' )
				);
				$gutenberg_version = $gutenberg_data['Version'];
			}
			return version_compare( $gutenberg_version, '19.0', '>=' );
		}

		return false;
	}

	/**
	 * Replaces the default posts menu item with the new posts dashboard.
	 */
	public function woocommerce_add_new_products_dashboard() {
		$gutenberg_experiments = get_option( 'gutenberg-experiments' );
		if ( ! $gutenberg_experiments ) {
			return;
		}
		$ptype_obj = get_post_type_object( 'product' );
		add_submenu_page(
			'woocommerce',
			$ptype_obj->labels->name,
			esc_html__( 'All Products', 'woocommerce' ),
			'manage_woocommerce',
			'woocommerce-products-dashboard',
			array( $this, 'woocommerce_products_dashboard' ),
			1
		);
	}

	/**
	 * Renders the new posts dashboard page.
	 */
	public function woocommerce_products_dashboard() {
		wp_register_style(
			'wp-gutenberg-posts-dashboard',
			gutenberg_url( 'build/edit-site/posts.css', __FILE__ ),
			array()
		);
		WCAdminAssets::get_instance();
		wp_enqueue_style( 'wp-gutenberg-posts-dashboard' );
		wp_enqueue_script( 'wc-admin-product-editor', WC()->plugin_url() . '/assets/js/admin/product-editor' . $suffix . '.js', array( 'wc-product-editor' ), $version, false );
		wp_add_inline_script( 'wp-edit-site', 'window.wc.productEditor.initializeProductsDashboard( "woocommerce-products-dashboard" );', 'after' );
		wp_enqueue_script( 'wp-edit-site' );

		echo '<div id="woocommerce-products-dashboard"></div>';
	}
}
