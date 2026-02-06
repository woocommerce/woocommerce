<?php
/**
 * POS Product Visibility Sync class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles syncing pos_product_visibility taxonomy to products and variations.
 *
 * When a variable product is marked as hidden from POS, all its variations
 * should also be marked as hidden. This class ensures that:
 * - Products and their variations have the correct pos-hidden term
 * - New variations inherit the pos-hidden term from their parent
 *
 * @since 10.5.0
 */
class POSProductVisibilitySync {

	/**
	 * Register hooks for syncing POS visibility.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_new_product_variation', array( $this, 'inherit_parent_pos_visibility' ), 10, 2 );
	}

	/**
	 * Set POS visibility for a product and its variations.
	 *
	 * This method sets or removes the pos-hidden term on the product,
	 * and if it's a variable product, syncs the visibility to all variations.
	 *
	 * @since 10.5.0
	 *
	 * @param int  $product_id     The product ID.
	 * @param bool $visible_in_pos Whether the product should be visible in POS.
	 * @return void
	 */
	public function set_product_pos_visibility( int $product_id, bool $visible_in_pos ): void {
		$is_currently_visible = ! has_term( 'pos-hidden', 'pos_product_visibility', $product_id );

		if ( $is_currently_visible === $visible_in_pos ) {
			return; // No change detected.
		}

		if ( $visible_in_pos ) {
			wp_remove_object_terms( $product_id, 'pos-hidden', 'pos_product_visibility' );
		} else {
			wp_set_object_terms( $product_id, 'pos-hidden', 'pos_product_visibility' );
		}

		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variable' ) ) {
			$this->sync_pos_visibility_to_variations( $product, $visible_in_pos );
		}
	}

	/**
	 * Sync POS visibility to all variations of a variable product.
	 *
	 * @since 10.5.0
	 *
	 * @param \WC_Product $product        The variable product.
	 * @param bool        $visible_in_pos Whether the product should be visible in POS.
	 * @return void
	 */
	private function sync_pos_visibility_to_variations( \WC_Product $product, bool $visible_in_pos ): void {
		$variation_ids = $product->get_children();
		foreach ( $variation_ids as $variation_id ) {
			if ( $visible_in_pos ) {
				wp_remove_object_terms( $variation_id, 'pos-hidden', 'pos_product_visibility' );
			} else {
				wp_set_object_terms( $variation_id, 'pos-hidden', 'pos_product_visibility' );
			}

			// Save variation to update date_modified.
			$variation = wc_get_product( $variation_id );
			if ( $variation ) {
				$variation->save();
			}
		}
	}

	/**
	 * Inherit POS visibility from parent when a new variation is created.
	 *
	 * When a new variation is created, check if the parent product has the
	 * pos-hidden term and apply it to the variation if so.
	 *
	 * @since 10.5.0
	 *
	 * @param int                        $variation_id The variation ID.
	 * @param \WC_Product_Variation|null $variation    The variation object.
	 * @return void
	 */
	public function inherit_parent_pos_visibility( $variation_id, $variation ): void {
		if ( ! $variation instanceof \WC_Product_Variation ) {
			return;
		}

		$parent_id = $variation->get_parent_id();
		if ( has_term( 'pos-hidden', 'pos_product_visibility', $parent_id ) ) {
			wp_set_object_terms( $variation_id, 'pos-hidden', 'pos_product_visibility' );
		}
	}
}
