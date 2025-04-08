<?php
/**
 * WooCommerce Product Data Views
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\ProductDataViews;

use Automattic\Jetpack\Constants;
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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			if ( $this->is_product_data_view_page() ) {
				add_filter(
					'admin_body_class',
					static function ( $classes ) {
						return "$classes";
					}
				);
			}
		}
	}

	/**
	 * Returns true if we are on a JS powered admin page.
	 */
	public static function is_product_data_view_page() {
		// phpcs:disable WordPress.Security.NonceVerification
		return isset( $_GET['page'] ) && 'woocommerce-products-dashboard' === $_GET['page'];
		// phpcs:enable WordPress.Security.NonceVerification
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
	 * Enqueue styles needed for the rich text editor.
	 */
	public function enqueue_styles() {
		if ( ! $this->is_product_data_view_page() ) {
			return;
		}
		wp_enqueue_style( 'wc-product-editor' );
	}

	/**
	 * Enqueue scripts needed for the product form block editor.
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_product_data_view_page() ) {
			return;
		}

		$script_handle = 'wc-admin-edit-product';
		wp_register_script( $script_handle, '', array( 'wp-blocks' ), '0.1.0', true );
		wp_enqueue_script( $script_handle );
		wp_enqueue_media();
		wp_register_style( 'wc-global-presets', false ); // phpcs:ignore
		wp_add_inline_style( 'wc-global-presets', wp_get_global_stylesheet( array( 'presets' ) ) );
		wp_enqueue_style( 'wc-global-presets' );
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
			'edit.php?post_type=product',
			$ptype_obj->labels->name,
			esc_html__( 'All Products ( new )', 'woocommerce' ),
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
		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );
		if ( function_exists( 'gutenberg_url' ) ) {
			// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_style(
				'wp-gutenberg-posts-dashboard',
				gutenberg_url( 'build/edit-site/posts.css', __FILE__ ),
				array( 'wp-components' ),
			);
			// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( 'wp-gutenberg-posts-dashboard' );
		}
		WCAdminAssets::get_instance();
		wp_enqueue_script( 'wc-admin-product-editor', WC()->plugin_url() . '/assets/js/admin/product-editor' . $suffix . '.js', array( 'wc-product-editor' ), $version, false );
		wp_add_inline_script( 'wp-edit-site', 'window.wc.productEditor.initializeProductsDashboard( "woocommerce-products-dashboard" );', 'after' );
		wp_enqueue_script( 'wp-edit-site' );

		echo '<div id="woocommerce-products-dashboard"></div>';
	}
}
