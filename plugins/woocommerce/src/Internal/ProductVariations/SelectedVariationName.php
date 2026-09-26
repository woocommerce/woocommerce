<?php
/**
 * Contextual variation product names.
 *
 * @package WooCommerce\Internal\ProductVariations
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductVariations;

use Automattic\WooCommerce\Enums\ProductType;
use WC_Data_Store;
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

		if ( ! in_array( '', $product_attributes, true ) ) {
			return $product_name;
		}

		$data_store = $product->get_data_store();

		// Stores without the shared title policy keep the stored variation name.
		if ( ! $data_store instanceof WC_Data_Store || ! $data_store->has_callable( 'should_include_attributes_in_title' ) ) {
			return $product_name;
		}

		// @phpstan-ignore method.notFound (the call is proxied by WC_Data_Store::__call() and guarded by has_callable() above)
		if ( ! $data_store->should_include_attributes_in_title( $product ) ) {
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
}
