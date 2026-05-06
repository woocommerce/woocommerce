<?php
/**
 * WooCommerce Product Variations Classic Redesign
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\ProductVariationsClassicRedesign;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Loads assets for the product variations classic redesign feature.
 */
class Init {
	const FEATURE_ID    = 'product-variations-classic-redesign';
	const SCRIPT_HANDLE = 'wc-experimental-products-app-variation-view';
	const SCRIPT_PATH   = 'experimental-products-app-variation-view';
	const ROOT_ID       = 'woocommerce-variations-classic-root';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
	}

	/**
	 * Returns true if we are on a product edit screen.
	 */
	public static function is_product_edit_page(): bool {
		$screen = get_current_screen();
		return $screen && 'product' === $screen->post_type && 'post' === $screen->base;
	}

	/**
	 * Returns true if the user has requested legacy editing for a specific variation.
	 */
	public static function is_legacy_variation_edit(): bool {
		// phpcs:disable WordPress.Security.NonceVerification
		return isset( $_GET['edit_variation'] ) && is_numeric( $_GET['edit_variation'] );
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Enqueue scripts and styles for the variations table.
	 */
	public function enqueue_scripts(): void {
		if ( ! self::is_product_edit_page() || self::is_legacy_variation_edit() ) {
			return;
		}

		$this->register_variation_view_assets();

		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_enqueue_style( 'wc-experimental-products-app' );
		wp_enqueue_style( self::SCRIPT_HANDLE );

		global $post;
		$product_id = $post ? $post->ID : 0;

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			sprintf(
				'window.wc.experimentalProductsAppVariationView.initializeVariationView( %s, %d );',
				wp_json_encode( self::ROOT_ID ),
				$product_id
			),
			'after'
		);
	}

	/**
	 * Registers variation view assets without loading the full WooCommerce Admin app.
	 */
	private function register_variation_view_assets(): void {
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			$script_assets_filename = WCAdminAssets::get_script_asset_filename( self::SCRIPT_PATH, 'index' );
			$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . self::SCRIPT_PATH . '/' . $script_assets_filename;

			wp_register_script(
				self::SCRIPT_HANDLE,
				WCAdminAssets::get_url( self::SCRIPT_PATH . '/index', 'js' ),
				$script_assets['dependencies'],
				WCAdminAssets::get_file_version( 'js', $script_assets['version'] ),
				true
			);
			wp_set_script_translations( self::SCRIPT_HANDLE, 'woocommerce' );
		}

		if ( ! wp_style_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			$style_version = WC_VERSION;

			try {
				$style_assets_filename = WCAdminAssets::get_script_asset_filename( self::SCRIPT_PATH, 'style' );
				$style_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . self::SCRIPT_PATH . '/' . $style_assets_filename;
				$style_version         = $style_assets['version'];
			} catch ( \Throwable $e ) {
				$style_version = WC_VERSION;
			}

			wp_register_style(
				self::SCRIPT_HANDLE,
				WCAdminAssets::get_url( self::SCRIPT_PATH . '/style', 'css' ),
				array(),
				WCAdminAssets::get_file_version( 'css', $style_version )
			);
			wp_style_add_data( self::SCRIPT_HANDLE, 'rtl', 'replace' );
		}
	}
}
