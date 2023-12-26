<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * ProductItemTrait
 *
 * Shared functionality for formating product item data.
 */
trait ProductItemTrait {
	/**
	 * Get an array of pricing data.
	 *
	 * @param \WC_Product $product Product instance.
	 * @param string      $tax_display_mode If returned prices are incl or excl of tax.
	 * @return array
	 */
	protected function prepare_product_price_response( \WC_Product $product, $tax_display_mode = '' ) {
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );
		$price_function   = $this->get_price_function_from_tax_display_mode( $tax_display_mode );
		$prices           = parent::prepare_product_price_response( $product, $tax_display_mode );

		// Add raw prices (prices with greater precision).
		$prices['raw_prices'] = [
			'precision'     => wc_get_rounding_precision(),
			'price'         => $this->prepare_money_response( $price_function( $product ), wc_get_rounding_precision() ),
			'regular_price' => $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_regular_price() ] ), wc_get_rounding_precision() ),
			'sale_price'    => $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_sale_price() ] ), wc_get_rounding_precision() ),
		];

		return $prices;
	}

	/**
	 * Format variation data, for example convert slugs such as attribute_pa_size to Size.
	 *
	 * @param array       $variation_data Array of data from the cart.
	 * @param \WC_Product $product Product data.
	 * @return array
	 */
	protected function format_variation_data( $variation_data, $product ) {
		$return = [];

		if ( ! is_iterable( $variation_data ) ) {
			return $return;
		}

		foreach ( $variation_data as $key => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $key ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				/**
				 * Filters the variation option name.
				 *
				 * Filters the variation option name for custom option slugs.
				 *
				 * @since 2.5.0
				 *
				 * @internal Matches filter name in WooCommerce core.
				 *
				 * @param string $value The name to display.
				 * @param null $unused Unused because this is not a variation taxonomy.
				 * @param string $taxonomy Taxonomy or product attribute name.
				 * @param \WC_Product $product Product data.
				 * @return string
				 */
				$value = apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $product );
				$label = wc_attribute_label( str_replace( 'attribute_', '', $key ), $product );
			}

			$return[] = [
				'attribute' => $this->prepare_html_response( $label ),
				'value'     => $this->prepare_html_response( $value ),
			];
		}

		return $return;
	}
}
