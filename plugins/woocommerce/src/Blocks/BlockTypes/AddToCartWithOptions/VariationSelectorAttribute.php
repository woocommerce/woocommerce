<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\VariationDataUtils;
use WP_Block;

/**
 * Block type for variation selector item in add to cart with options.
 * It's responsible to render each child attribute in a form of a list item.
 */
class VariationSelectorAttribute extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute';

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId' ];
	}

	/**
	 * Check if lazy loading of variation data is enabled.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool
	 */
	protected function is_lazy_load_enabled( $product ): bool {
		return VariationDataUtils::is_enabled( $product );
	}

	/**
	 * Get variation attributes efficiently without loading full variation objects.
	 *
	 * @param \WC_Product_Variable $product The variable product.
	 * @return array Array of variation data with attributes.
	 */
	protected function get_variation_attributes_efficiently( $product ): array {
		global $wpdb;

		$variation_ids = $product->get_children();
		if ( empty( $variation_ids ) ) {
			return array();
		}

		// Get the attribute names we need to look for.
		$attributes      = $product->get_attributes();
		$attribute_names = array();
		foreach ( $attributes as $attribute ) {
			if ( ! empty( $attribute['is_variation'] ) ) {
				$attribute_names[] = wc_variation_attribute_name( $attribute['name'] );
			}
		}

		if ( empty( $attribute_names ) ) {
			return array();
		}

		// Build placeholders for the query.
		$id_placeholders   = implode( ',', array_fill( 0, count( $variation_ids ), '%d' ) );
		$name_placeholders = implode( ',', array_fill( 0, count( $attribute_names ), '%s' ) );

		// Query all attribute meta for all variations in one go.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_key, meta_value
				FROM {$wpdb->postmeta}
				WHERE post_id IN ({$id_placeholders})
				AND meta_key IN ({$name_placeholders})",
				array_merge( $variation_ids, $attribute_names )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Build the variations data array in the format expected by the filtering code.
		$variations_data = array();
		foreach ( $variation_ids as $variation_id ) {
			$variations_data[ $variation_id ] = array(
				'variation_id' => $variation_id,
				'attributes'   => array(),
			);
		}

		foreach ( $results as $row ) {
			$variation_id = (int) $row['post_id'];
			$meta_key     = $row['meta_key'];
			$meta_value   = $row['meta_value'];

			if ( isset( $variations_data[ $variation_id ] ) ) {
				$variations_data[ $variation_id ]['attributes'][ $meta_key ] = $meta_value;
			}
		}

		return array_values( $variations_data );
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		global $product;

		$content = '';

		$product_attributes = $product->get_variation_attributes();

		foreach ( $product_attributes as $product_attribute_name => $product_attribute_terms ) {
			$content .= $this->get_product_row( $product_attribute_name, $product_attribute_terms, $block );
		}

		return $content;
	}

	/**
	 * Get product row HTML.
	 *
	 * @param string   $attribute_name Product Attribute Name.
	 * @param array    $product_attribute_terms Product Attribute Terms.
	 * @param WP_Block $block The Block.
	 * @return string Row HTML
	 */
	private function get_product_row( $attribute_name, $product_attribute_terms, $block ): string {
		global $product;

		$attribute_terms = $this->get_terms( $attribute_name, $product_attribute_terms );

		if ( $this->is_lazy_load_enabled( $product ) ) {
			$product_variations = $this->get_variation_attributes_efficiently( $product );
		} else {
			$product_variations = $product->get_available_variations();
		}

		// Filter out terms which are not available in any product variation.
		$attribute_terms = array_filter(
			$attribute_terms,
			function ( $term ) use ( $product_variations, $attribute_name, $attribute_terms ) {
				foreach ( $product_variations as $product_variation ) {
					if (
						$term['value'] === $product_variation['attributes'][ wc_variation_attribute_name( $attribute_name ) ] ||
						'' === $product_variation['attributes'][ wc_variation_attribute_name( $attribute_name ) ]
					) {
						return true;
					}
				}
			}
		);

		if ( empty( $attribute_terms ) ) {
			return '';
		}

		$block_content = AddToCartWithOptionsUtils::render_block_with_context(
			$block,
			array(
				'woocommerce/attributeId'    => 'wc_product_attribute_' . uniqid(),
				'woocommerce/attributeName'  => $attribute_name,
				'woocommerce/attributeTerms' => $attribute_terms,
			),
		);

		// Render the inner blocks of the Variation Selector Item Template block with `dynamic` set to `false`
		// to prevent calling `render_callback` and ensure that no wrapper markup is included.
		return $block_content;
	}

	/**
	 * Get product attributes terms.
	 *
	 * @param string $attribute_name Product Attribute Name.
	 * @param array  $attribute_terms Product Attribute Terms.
	 * @return array[] Array of term data with structure:
	 *                 [
	 *                     'label'      => (string) Display label for the term.
	 *                     'value'      => (string) Internal value/slug for the term.
	 *                     'isSelected' => (bool)   Whether this term is the default selection.
	 *                 ]
	 */
	protected function get_terms( $attribute_name, $attribute_terms ) {
		global $product;

		$is_taxonomy = taxonomy_exists( $attribute_name );

		$selected_attribute = $product->get_variation_default_attribute( $attribute_name );

		if ( $is_taxonomy ) {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product, $selected_attribute ) {
					return array(
						'value'      => $term->slug,
						/**
						 * Filter the variation option name.
						 *
						 * @since 9.7.0
						 *
						 * @param string     $option_label    The option label.
						 * @param WP_Term|string|null $item   Term object for taxonomies, option string for custom attributes.
						 * @param string     $attribute_name  Name of the attribute.
						 * @param WC_Product $product         Product object.
						 */
						'label'      => apply_filters(
							'woocommerce_variation_option_name',
							$term->name,
							$term,
							$attribute_name,
							$product
						),
						'isSelected' => $selected_attribute === $term->slug,
					);
				},
				wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ),
			);
		} else {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product, $selected_attribute ) {
					return array(
						'value'      => $term,
						/**
						 * Filter the variation option name.
						 *
						 * @since 9.7.0
						 *
						 * @param string     $option_label    The option label.
						 * @param WP_Term|string|null $item   Term object for taxonomies, option string for custom attributes.
						 * @param string     $attribute_name  Name of the attribute.
						 * @param WC_Product $product         Product object.
						 */
						'label'      => apply_filters(
							'woocommerce_variation_option_name',
							$term,
							null,
							$attribute_name,
							$product
						),
						'isSelected' => $selected_attribute === $term,
					);
				},
				$attribute_terms,
			);
		}

		return $items;
	}
}
