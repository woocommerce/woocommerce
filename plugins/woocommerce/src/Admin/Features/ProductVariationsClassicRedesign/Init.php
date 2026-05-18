<?php
/**
 * WooCommerce Product Variations Classic Redesign
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\ProductVariationsClassicRedesign;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use WC_Meta_Box_Product_Data;
use WC_Product;

/**
 * Loads assets for the product variations classic redesign feature.
 */
class Init {
	const FEATURE_ID    = 'product-variations-classic-redesign';
	const SCRIPT_HANDLE = 'wc-experimental-products-app';
	const SCRIPT_PATH   = 'experimental-products-app';
	const ROOT_ID       = 'woocommerce-variations-classic-root';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'handle_woocommerce_product_data_tabs' ), 5 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'preserve_variation_attributes' ), 10, 1 );
		add_action( 'woocommerce_before_product_object_save', array( $this, 'preserve_variation_attributes_on_legacy_ajax_save' ), 10, 1 );
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
	 * Reorders Variations and Linked Products to bracket the Product attributes tab, and
	 * renames "Attributes" to "Product attributes" so the legacy meta-box reflects its new
	 * scope (non-variation attributes only).
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

		if ( isset( $tabs['attribute'] ) && is_array( $tabs['attribute'] ) ) {
			$tabs['attribute']['label'] = __( 'Product attributes', 'woocommerce' );
		}

		return $tabs;
	}

	/**
	 * Preserves variation attributes saved by the inline editor when the legacy form-POST save handler runs.
	 *
	 * The legacy save handler calls prepare_attributes() which rebuilds the product attributes array
	 * from $_POST form fields. Because the Product attributes tab filters out variation=true attributes,
	 * those rows have no form fields and would otherwise be wiped on every WordPress "Update" click.
	 * This handler re-merges the persisted variation attributes back into the in-flight product so
	 * the inline REST save and the legacy form save are properly decoupled.
	 *
	 * @since 10.9.0
	 * @param WC_Product $product The product being saved.
	 */
	public function preserve_variation_attributes( WC_Product $product ): void {
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		wc_get_container()
			->get( ProductCache::class )
			->remove( $product->get_id() );

		$existing = wc_get_product( $product->get_id() );

		if ( ! $existing ) {
			return;
		}

		$existing_variation = array_filter(
			array_filter( $existing->get_attributes() ),
			array( WC_Meta_Box_Product_Data::class, 'filter_variation_attributes' )
		);

		$non_variation = array_filter(
			array_filter( $product->get_attributes() ),
			array( WC_Meta_Box_Product_Data::class, 'filter_non_variation_attributes' )
		);

		$product->set_attributes( array_merge( $non_variation, $existing_variation ) );
	}

	/**
	 * Variation-attribute preservation companion for the legacy `woocommerce_save_attributes` AJAX flow.
	 *
	 * The legacy "Save attributes" button on the Product attributes tab triggers an AJAX handler
	 * (`WC_AJAX::save_attributes()`) that bypasses `woocommerce_admin_process_product_object`.
	 * That handler rebuilds the attributes array from POST data (which contains no variation rows,
	 * since the renamed tab filters them out) and overwrites the product, wiping any persisted
	 * variation attributes. This hook re-merges them in before `WC_Product::save()` persists.
	 *
	 * The guard restricts the merge to the legacy AJAX endpoint only — inline REST saves from the
	 * new editor, programmatic `WC_Product::save()` calls, and the WordPress "Update" form-POST flow
	 * (already covered by `preserve_variation_attributes` above) all proceed unmodified.
	 *
	 * @since 10.9.0
	 * @param WC_Product $product The product being saved.
	 */
	public function preserve_variation_attributes_on_legacy_ajax_save( WC_Product $product ): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WC_AJAX::save_attributes() before this hook fires.
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		if ( 'woocommerce_save_attributes' !== $action ) {
			return;
		}

		$this->preserve_variation_attributes( $product );
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

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			sprintf(
				'window.wc.experimentalProductsApp.initializeVariationView( %s, %d );',
				wp_json_encode( self::ROOT_ID ),
				$product_id
			),
			'after'
		);
	}
}
