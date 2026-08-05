<?php
/**
 * WooCommerce Product Variations Classic Redesign
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features;

/**
 * Loads assets for the product variations classic redesign feature.
 */
class ProductVariationsClassicRedesign {
	const FEATURE_ID         = 'product-variations-classic-redesign';
	const SCRIPT_HANDLE      = 'wc-experimental-products-app';
	const SCRIPT_PATH        = 'experimental-products-app';
	const ROOT_ID            = 'woocommerce-variations-classic-root';
	const ATTRIBUTES_ROOT_ID = 'woocommerce-product-attributes-classic-root';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'handle_woocommerce_product_data_tabs' ), 5 );
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
	 * Handle the woocommerce_product_data_tabs filter.
	 *
	 * @internal
	 *
	 * @param array $tabs Product data tabs.
	 * @return array Product data tabs.
	 */
	public function handle_woocommerce_product_data_tabs( array $tabs ): array {
		if ( isset( $tabs['variations'] ) && is_array( $tabs['variations'] ) ) {
			$tabs['variations']['priority'] = 40;
		}

		if ( isset( $tabs['linked_product'] ) && is_array( $tabs['linked_product'] ) ) {
			$tabs['linked_product']['priority'] = 60;
		}

		return $tabs;
	}

	/**
	 * Enqueue scripts and styles for the variations table.
	 */
	public function enqueue_scripts(): void {
		if ( ! self::is_product_edit_page() || self::is_legacy_variation_edit() ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_enqueue_style( self::SCRIPT_HANDLE );

		global $post;
		$product_id = $post ? $post->ID : 0;
		$script     = sprintf(
			'window.wc.experimentalProductsApp.initializeVariationView( %s, %d );',
			wp_json_encode( self::ROOT_ID ),
			$product_id
		);
		$script    .= sprintf(
			' window.wc.experimentalProductsApp.initializeProductAttributesView( %s, %d );',
			wp_json_encode( self::ATTRIBUTES_ROOT_ID ),
			$product_id
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			$script,
			'after'
		);
	}
}
