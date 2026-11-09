<?php
/**
 * Variation specificity checks for add to cart.
 *
 * @package WooCommerce\Internal\ProductVariations
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductVariations;

use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Detects when an earlier variation matches a selection more specifically.
 *
 * @internal
 *
 * @since 11.4.0
 */
class VariationSpecificity {

	/**
	 * Whether an earlier available variation matches this selection with fewer "any" attributes.
	 *
	 * Disabled and unpriced variations are ignored. Out-of-stock variations are ignored when out-of-stock products are hidden from the catalog.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Product_Variation $variation           Variation being added to the cart.
	 * @param array<string, mixed> $selected_attributes Selected add-to-cart attribute values.
	 * @return bool
	 */
	public function has_more_specific_match( WC_Product_Variation $variation, array $selected_attributes ): bool {
		$current_any_count = count( array_keys( $variation->get_attributes( 'edit' ), '', true ) );
		if ( 0 === $current_any_count ) {
			return false;
		}

		$parent = wc_get_product( $variation->get_parent_id() );
		if ( ! $parent instanceof WC_Product_Variable ) {
			return false;
		}

		$child_ids = array_map( 'absint', $parent->get_children() );
		$offset    = array_search( $variation->get_id(), $child_ids, true );
		if ( ! is_int( $offset ) || 0 === $offset ) {
			return false;
		}

		$selected = array();
		foreach ( $selected_attributes as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$name = (string) $key;
			if ( 0 === strpos( $name, 'attribute_' ) ) {
				$name = substr( $name, 10 );
			}
			$selected[ $name ] = (string) $value;
		}

		$earlier_ids       = array_slice( $child_ids, 0, $offset );
		$hide_out_of_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
		if ( ! empty( $earlier_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $earlier_ids );
		}

		foreach ( $earlier_ids as $earlier_id ) {
			$earlier = wc_get_product( $earlier_id );
			if ( ! $earlier instanceof WC_Product_Variation || ! $this->is_available( $earlier, $parent, $hide_out_of_stock ) ) {
				continue;
			}

			$attributes = $earlier->get_attributes( 'edit' );
			if ( count( array_keys( $attributes, '', true ) ) >= $current_any_count ) {
				continue;
			}

			$matches = true;
			foreach ( $attributes as $name => $value ) {
				$value = (string) $value;
				if ( '' !== $value && ( $selected[ $name ] ?? null ) !== $value ) {
					$matches = false;
					break;
				}
			}
			if ( $matches ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the variation can be selected from the catalog.
	 *
	 * @param WC_Product_Variation $variation         Variation to check.
	 * @param WC_Product_Variable  $parent_product    Parent variable product.
	 * @param bool                 $hide_out_of_stock Whether out-of-stock products are hidden.
	 * @return bool
	 */
	private function is_available( WC_Product_Variation $variation, WC_Product_Variable $parent_product, bool $hide_out_of_stock ): bool {
		if ( ! $variation->exists() || ( $hide_out_of_stock && ! $variation->is_in_stock() ) ) {
			return false;
		}

		/**
		 * This filter is documented in includes/class-wc-product-variable.php.
		 *
		 * @since 2.6.8
		 */
		$hide_invisible = apply_filters( 'woocommerce_hide_invisible_variations', true, $parent_product->get_id(), $variation );

		return ! $hide_invisible || $variation->variation_is_visible();
	}
}
