<?php
/**
 * Contextual variation product names.
 *
 * @package WooCommerce\Internal\ProductVariations
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductVariations;

use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Builds variation names from selected cart and checkout attributes.
 *
 * @internal
 *
 * @since 11.2.0
 */
class SelectedVariationName {

	/**
	 * Gets a variation product name using selected variation attributes from cart or checkout context.
	 *
	 * @param WC_Product           $product              Variation product.
	 * @param array<string, mixed> $variation_attributes Selected variation attributes.
	 * @param bool                 $filter_custom_attributes Whether to filter custom attribute values for cart display.
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_product_name( WC_Product $product, array $variation_attributes, bool $filter_custom_attributes = false ): string {
		if ( ! $product->is_type( ProductType::VARIATION ) || empty( $variation_attributes ) ) {
			return $product->get_name();
		}

		$product_name       = $product->get_name();
		$product_attributes = (array) $product->get_attributes();

		if ( ! in_array( '', $product_attributes, true ) || ! $this->should_include_selected_variation_attributes_in_name( $product, $product_attributes ) ) {
			return $product_name;
		}

		$selected_attributes = array();

		foreach ( $variation_attributes as $name => $value ) {
			$selected_attributes[ str_replace( 'attribute_', '', rawurldecode( (string) $name ) ) ] = $value;
		}

		$missing_attributes     = array();
		$missing_display        = array();
		$has_attributes_in_name = false;

		foreach ( $product_attributes as $name => $value ) {
			if ( ! array_key_exists( $name, $selected_attributes ) || ! is_scalar( $selected_attributes[ $name ] ) ) {
				continue;
			}

			$selected_value = (string) $selected_attributes[ $name ];
			$display_value  = wc_get_formatted_variation( array( $name => $selected_value ), true, false );

			if ( '' === $display_value ) {
				continue;
			}

			if ( wc_is_attribute_in_product_name( $display_value, $product_name ) ) {
				$has_attributes_in_name = true;
				continue;
			}

			if ( '' !== (string) $value ) {
				continue;
			}

			$missing_attributes[ $name ] = $selected_value;

			if ( $filter_custom_attributes && ! taxonomy_exists( $name ) ) {
				/**
				 * Filters the display name for a selected variation option.
				 *
				 * @since 3.4.0
				 * @param string          $value Selected variation option value.
				 * @param WP_Term|null    $term Term object when available.
				 * @param string          $name Attribute taxonomy name.
				 * @param WC_Product|null $product Product object.
				 */
				$display_value = apply_filters( 'woocommerce_variation_option_name', $selected_value, null, wc_attribute_taxonomy_name( 'attribute_' . $name ), $product );
				$display_value = is_scalar( $display_value ) ? rawurldecode( (string) $display_value ) : '';
			}

			if ( '' !== $display_value ) {
				$missing_display[] = $display_value;
			}
		}

		if ( empty( $missing_attributes ) ) {
			return $product_name;
		}

		if ( $has_attributes_in_name ) {
			$separator = ', ';
		} else {
			/**
			 * Filters the separator used between a variation product title and its attributes.
			 *
			 * @since 3.0.2
			 * @param string     $separator Separator between the product title and attributes.
			 * @param WC_Product $product Variation product object.
			 */
			$separator = apply_filters( 'woocommerce_product_variation_title_attributes_separator', ' - ', $product );
			$separator = is_scalar( $separator ) ? (string) $separator : ' - ';
		}

		$missing_values = implode( ', ', $missing_display );

		if ( '' === $missing_values ) {
			return $product_name;
		}

		return $product_name . $separator . $missing_values;
	}

	/**
	 * Checks whether selected variation attributes should be included in the product name.
	 *
	 * Mirrors the title policy in WC_Product_Variation_Data_Store_CPT::generate_product_title()
	 * (including its two filters) so contextual names follow the same rules as stored
	 * variation titles. Keep the two implementations in sync.
	 *
	 * @param WC_Product           $product    Product object.
	 * @param array<string, mixed> $attributes Product attributes.
	 * @return bool
	 */
	private function should_include_selected_variation_attributes_in_name( WC_Product $product, array $attributes ): bool {
		$should_include_attributes = count( $attributes ) < 3;

		if ( $should_include_attributes && 1 < count( $attributes ) ) {
			foreach ( array_keys( $attributes ) as $name ) {
				if ( false !== strpos( (string) $name, '-' ) ) {
					$should_include_attributes = false;
					break;
				}
			}
		}

		/**
		 * Filters whether variation product titles should include attributes.
		 *
		 * @since 3.0.0
		 * @param bool       $should_include_attributes Whether attributes should be included.
		 * @param WC_Product $product Variation product object.
		 */
		return (bool) apply_filters( 'woocommerce_product_variation_title_include_attributes', $should_include_attributes, $product );
	}
}
