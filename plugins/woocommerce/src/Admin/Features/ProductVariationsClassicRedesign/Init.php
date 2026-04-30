<?php
/**
 * WooCommerce Product Variations Classic Redesign
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\ProductVariationsClassicRedesign;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Loads assets for the product variations classic redesign feature.
 */
class Init {
	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		if ( FeaturesUtil::feature_is_enabled( 'product_variations_classic_redesign' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
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

		wp_enqueue_script( 'wc-variations-classic' );
		wp_enqueue_style( 'wc-variations-classic' );

		global $post;
		$product_id = $post ? $post->ID : 0;

		wp_add_inline_script(
			'wc-variations-classic',
			sprintf(
				'window.wcVariationsClassicSettings = %s;',
				wp_json_encode(
					array(
						'productId' => $product_id,
						'nonce'     => wp_create_nonce( 'wp_rest' ),
						'restUrl'   => rest_url( '/' ),
					)
				)
			),
			'before'
		);
	}
}
