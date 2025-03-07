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
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	private function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'style' => 'pills',
		);

		return wp_parse_args( $attributes, $defaults );
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
		global $attribute_name;
		global $attribute_terms;

		if ( isset( $attribute_name ) ) {
			wp_enqueue_script_module( $this->get_full_block_name() );

			$attributes = $this->parse_attributes( $attributes );

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
					'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
					'class' => $classes,
					'style' => esc_attr( $classes_and_styles['styles'] ),
				)
			);

			if ( 'dropdown' === $field_style ) {
				$content = $this->render_dropdown( $attributes );
			} else {
				$content = $this->render_pills( $attributes );
			}

			return sprintf(
				'<div %s>%s</div>',
				$wrapper_attributes,
				$content
			);
		}

		return '';
	}

	/**
	 * Get the normalized version of the attributes.
	 *
	 * @param array $attributes         The element's attributes.
	 * @param array $default_attributes The element's default attributes.
	 * @return string The HTML element's attributes.
	 */
	protected function get_normalized_attributes( $attributes, $default_attributes = array() ) {
		$normalized_attributes = array();

		$merged_attributes = array_merge( $default_attributes, $attributes );

		foreach ( $merged_attributes as $key => $value ) {
			$normalized_attributes[] = $key . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $normalized_attributes );
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @return string The default selected attribute. 
	 */
	protected function get_default_selected_attribute() {
		global $product;
		global $attribute_name;

		$is_taxonomy = taxonomy_exists( $attribute_name );
		
		$selected_attribute = $product->get_variation_default_attribute( $attribute_name );
		
		if ( $is_taxonomy ) {
			$selected_attribute = sanitize_title( $selected_attribute );
		}

		return $selected_attribute;
	}

	/**
	 * Render the attribute options as pills.
	 *
	 * @param array $attributes The block's attributes.
	 * @return string The pills.
	 */
	protected function render_pills( $attributes ) {
		global $attribute_name;
		global $attribute_terms;

		return sprintf(
			'<div %s>
				<template data-wp-each--option="context.options">
        	<div %s></div>
    		</template>
			</div>',
			$this->get_normalized_attributes(
				array(
					'role'            => "radiogroup",
					'class'           => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills',
					'id'              => esc_attr( 'attribute_' . sanitize_title( $attribute_name ) ),
					'aria-labeledby'  => esc_attr( 'attribute_' . sanitize_title( $attribute_name ) . '_label' ),
					'data-wp-context' => wp_json_encode(
						array(
							'options'  => $attribute_terms,
							'selected' => $this->get_default_selected_attribute(),
						),
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
					),
				),
			),
			$this->get_normalized_attributes(
				array(
					'role'                       => "radio",
					'data-wp-bind--tabindex'     => "context.tabIndex",
					'data-wp-bind--aria-checked' => "context.isSelected",
					'class'                      => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
					'data-wp-text'               => "context.option.label",
					'data-wp-watch'              => "callbacks.watchSelected",
					'data-wp-on--click'          => "actions.handleClick",
					'data-wp-on--keydown'        => "actions.handleKeyDown",
					'data-wp-context'            => wp_json_encode(
						array(
							'isSelected' => false,
							'tabIndex'   => -1,
						),
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
					),
				),
			),
		);
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
}
