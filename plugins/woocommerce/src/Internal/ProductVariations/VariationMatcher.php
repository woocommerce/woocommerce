<?php
/**
 * Checks whether an earlier variation already matches an add to cart selection.
 *
 * @package WooCommerce\Internal\ProductVariations
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductVariations;

use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Detects when an earlier variation already matches the attributes being added.
 *
 * @internal
 *
 * @since 11.4.0
 */
class VariationMatcher {

	/**
	 * Whether an earlier available variation matches this selection.
	 *
	 * Fully specified variations return false, so they can still be added by their own ID. Disabled and unpriced variations are ignored, as are variations removed by woocommerce_available_variation. Out-of-stock variations are ignored when out-of-stock products are hidden from the catalog.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Product_Variation $variation           Variation being added to the cart.
	 * @param array<string, mixed> $selected_attributes Selected add-to-cart attribute values.
	 * @return bool
	 */
	public function has_earlier_match( WC_Product_Variation $variation, array $selected_attributes ): bool {
		$current_any_count = count( array_keys( $variation->get_attributes( 'edit' ), '', true ) );
		if ( 0 === $current_any_count ) {
			return false;
		}

		$parent = wc_get_product( $variation->get_parent_id() );
		if ( ! $parent instanceof WC_Product_Variable ) {
			return false;
		}

		$child_ids      = array_values( array_map( 'absint', $parent->get_children() ) );
		$selected_index = array_search( $variation->get_id(), $child_ids, true );
		if ( ! is_int( $selected_index ) || 0 === $selected_index ) {
			return false;
		}

		$earlier_ids = array_slice( $child_ids, 0, $selected_index );
		if ( ! empty( $earlier_ids ) ) {
			_prime_post_caches( $earlier_ids );
		}

		foreach ( $earlier_ids as $child_id ) {
			if ( ! $this->attributes_match( $child_id, $selected_attributes ) ) {
				continue;
			}

			$earlier = wc_get_product( $child_id );
			if ( $earlier instanceof WC_Product_Variation && $this->is_available( $earlier, $parent ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the variation's stored attributes match the selection.
	 *
	 * Empty attribute values are "any" and match every selection.
	 *
	 * @param int                  $variation_id Variation ID.
	 * @param array<string, mixed> $selected     Selected attribute values, keyed by attribute meta key.
	 * @return bool
	 */
	private function attributes_match( int $variation_id, array $selected ): bool {
		foreach ( wc_get_product_variation_attributes( $variation_id ) as $name => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$selected_value = $selected[ $name ] ?? null;
			if ( ! is_scalar( $selected_value ) || (string) $selected_value !== (string) $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the variation can be selected from the catalog.
	 *
	 * @param WC_Product_Variation $variation      Variation to check.
	 * @param WC_Product_Variable  $parent_product Parent variable product.
	 * @return bool
	 */
	private function is_available( WC_Product_Variation $variation, WC_Product_Variable $parent_product ): bool {
		if ( ! $variation->exists() ) {
			return false;
		}

		$hide_out_of_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
		if ( $hide_out_of_stock && ! $variation->is_in_stock() ) {
			return false;
		}

		/**
		 * This filter is documented in includes/class-wc-product-variable.php.
		 *
		 * @since 2.6.8
		 */
		$hide_invisible = apply_filters( 'woocommerce_hide_invisible_variations', true, $parent_product->get_id(), $variation );

		if ( $hide_invisible && ! $variation->variation_is_visible() ) {
			return false;
		}

		return (bool) $parent_product->get_available_variation( $variation );
	}
}
