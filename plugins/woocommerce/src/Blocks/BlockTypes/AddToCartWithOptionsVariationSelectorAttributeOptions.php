<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Block type for variation selector attribute options in add to cart with options.
 * It's responsible to render the attribute options.
 */
class AddToCartWithOptionsVariationSelectorAttributeOptions extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-options';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
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
		global $attribute_name;
		global $attribute_terms;

		if ( isset( $attribute_name ) ) {
			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
	
			$field_style = $attributes['style'];

			$classes = implode(
				' ',
				array_filter(
					array(
						'dropdown' === $field_style ?
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__dropdown' :
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills',
						esc_attr( $classes_and_styles['classes'] ),
					)
				)
			);

			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'data-block-name' => $this->get_full_block_name(),
					'class' => $classes,
					'style' => esc_attr( $classes_and_styles['styles'] ),
					'id' => esc_attr( 'attribute_' . sanitize_title( $attribute_name ) ),
				)
			);

			if ( 'dropdown' === $field_style ) {
				return $this->render_dropdown( $wrapper_attributes );
			}

			return $this->render_pills( $wrapper_attributes );
		}

		return '';
	}

	/**
	 * Render the attribute options as a dropdown.
	 *
	 * @param array $wrapper_attributes The wrapper attributes.
	 * @return string The dropdown.
	 */
	protected function render_dropdown( $wrapper_attributes ) {
		global $product;
		global $attribute_name;
		global $attribute_terms;

		$options = sprintf(
			'<option value="">%s</option>',
			esc_html__( 'Choose an option', 'woocommerce' )
		);

		$selected = $product->get_variation_default_attribute( $attribute_name );

		$options .= $this->render_attribute_options( $product, $attribute_name, $attribute_terms, $selected, taxonomy_exists( $attribute_name ) );
		
		return sprintf(
			'<select %s data-attribute_name="attribute_%s">%s</select>',
			$wrapper_attributes,
			esc_attr( sanitize_title( $attribute_name ) ),
			$options
		);
	}

	/**
	 * Render the attribute options as pills.
	 *
	 * @param array $wrapper_attributes The wrapper attributes.
	 * @return string The pills.
	 */
	protected function render_pills( $wrapper_attributes ) {
		global $product;
		global $attribute_name;
		global $attribute_terms;
		
		$selected = $product->get_variation_default_attribute( $attribute_name );
		
		$options = '';

		$options .= $this->render_attribute_pills( $product, $attribute_name, $attribute_terms, $selected, taxonomy_exists( $attribute_name ) );
		
		return sprintf(
			'<div %s role="radiogroup">%s</div>',
			$wrapper_attributes,
			$options
		);
	}

	/**
	 * Get HTML for attribute options.
	 *
	 * @param WC_Product $product The product object.
	 * @param string     $attribute_name Name of the attribute.
	 * @param array      $options Available options.
	 * @param string     $selected Selected value.
	 * @param bool       $is_taxonomy Whether this is a taxonomy-based attribute.
	 * @return string Options HTML
	 */
	private function render_attribute_options( $product, $attribute_name, $options, $selected, $is_taxonomy ): string {
		if ( empty( $options ) ) {
			return '';
		}

		$html  = '';
		$items = $is_taxonomy
			? wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) )
			: $options;

		foreach ( $items as $item ) {
			$option_value = $is_taxonomy ? $item->slug : $item;
			$option_label = $is_taxonomy ? $item->name : $item;

			if ( ! $is_taxonomy || in_array( $option_value, $options, true ) ) {
				$selected_attr = $is_taxonomy
					? selected( sanitize_title( $selected ), $option_value, false )
					: selected( $selected, $option_value, false );

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
				$filtered_label = apply_filters(
					'woocommerce_variation_option_name',
					$option_label,
					$is_taxonomy ? $item : null,
					$attribute_name,
					$product
				);

				$html .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $option_value ),
					$selected_attr,
					esc_html( $filtered_label )
				);
			}
		}

		return $html;
	}

	/**
	 * Get HTML for attribute options.
	 *
	 * @param WC_Product $product The product object.
	 * @param string     $attribute_name Name of the attribute.
	 * @param array      $options Available options.
	 * @param string     $selected Selected value.
	 * @param bool       $is_taxonomy Whether this is a taxonomy-based attribute.
	 * @return string Options HTML
	 */
	private function render_attribute_pills( $product, $attribute_name, $options, $selected, $is_taxonomy ): string {
		if ( empty( $options ) ) {
			return '';
		}

		$html  = '';
		$items = $is_taxonomy
			? wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) )
			: $options;

		foreach ( $items as $item ) {
			$option_value = $is_taxonomy ? $item->slug : $item;
			$option_label = $is_taxonomy ? $item->name : $item;

			if ( ! $is_taxonomy || in_array( $option_value, $options, true ) ) {
				$is_selected = $is_taxonomy
					? sanitize_title( $selected ) === $option_value
					: $selected === $option_value;

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
				$filtered_label = apply_filters(
					'woocommerce_variation_option_name',
					$option_label,
					$is_taxonomy ? $item : null,
					$attribute_name,
					$product
				);

				$classes = implode(
					' ',
					array_filter(
						array(
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
							'dropdown' === $selected ?
								'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected' :
								'',
						)
					)
				);

				$html .= sprintf(
					'<div role="radio" class="%s" value="%s">%s</div>',
					esc_attr( $classes ),
					esc_attr( $option_value ),
					esc_html( $filtered_label )
				);
			}
		}

		return $html;
	}
}
