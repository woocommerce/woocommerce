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
		if ( empty( $block->context ) ) {
			return '';
		}

		$attribute_name = $block->context['attributeName'];

		if ( isset( $attribute_name ) ) {
			wp_enqueue_script_module( $this->get_full_block_name() );

			$attributes = $this->parse_attributes( $attributes );

			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
	
			$field_style = $attributes['style'];

			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
					'class' => esc_attr( $classes_and_styles['classes'] ),
					'style' => esc_attr( $classes_and_styles['styles'] ),
				)
			);

			if ( 'dropdown' === $field_style ) {
				$content = $this->render_dropdown( $attributes, $content, $block );
			} else {
				$content = $this->render_pills( $attributes, $content, $block );
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
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode(
					$value,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				);
			}
			$normalized_attributes[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return implode( ' ', $normalized_attributes );
	}

	/**
	 * Get the default selected attribute.
	 *
	 * @param array $attribute_terms The attribute's.
	 * @return string|null The default selected attribute. 
	 */
	protected function get_default_selected_attribute( $attribute_terms ) {
		foreach ( $attribute_terms as $attribute_term ) {
			if ( $attribute_term['isSelected'] ) {
				return $attribute_term['value'];
			}
		};

		return null;
	}

	/**
	 * Render the attribute options as pills.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string The pills.
	 */
	protected function render_pills( $attributes, $content, $block ) {
		$attribute_id = $block->context['attributeId'];
		$attribute_name = $block->context['attributeName'];
		$attribute_terms = $block->context['attributeTerms'];

		return sprintf(
			'<div %s>
				<template data-wp-each--option="context.options">
        	<div %s></div>
    		</template>
			</div>',
			$this->get_normalized_attributes(
				array(
					'role'                => "radiogroup",
					'class'               => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills',
					'id'                  => $attribute_id,
					'aria-labeledby'      => $attribute_id . '_label',
					'data-wp-interactive' => $this->get_full_block_name() . '__pills',
					'data-wp-context'     => array(
						'options'  => $attribute_terms,
						'selected' => $this->get_default_selected_attribute( $attribute_terms ),
						'focused'  => '',
					),
				),
			),
			$this->get_normalized_attributes(
				array(
					'role'                       => "radio",
					'data-wp-bind--tabindex'     => "context.tabIndex",
					'data-wp-bind--aria-checked' => "context.option.isSelected",
					'class'                      => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
					'data-wp-text'               => "context.option.label",
					'data-wp-watch'              => "callbacks.watchSelected",
					'data-wp-on--click'          => "actions.handleClick",
					'data-wp-on--keydown'        => "actions.handleKeyDown",
					'data-wp-context'            => array(
						'tabIndex' => -1,
					),
				),
			),
		);
	}

	/**
	 * Render the attribute options as a dropdown.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string The dropdown.
	 */
	protected function render_dropdown( $attributes, $content, $block ) {
		$attribute_id = $block->context['attributeId'];
		$attribute_name = $block->context['attributeName'];
		$attribute_terms = $block->context['attributeTerms'];

		$options = array_merge(
			array( array( 'label' => esc_html__( 'Choose an option', 'woocommerce' ), 'value' => '' ) ),
			$attribute_terms
		);

		return sprintf(
			'<select %s>
				<template data-wp-each--option="context.options">
        	<option %s></option>
    		</template>
			</select>',
			$this->get_normalized_attributes(
				array(
					'class'               => 'wc-block-add-to-cart-with-options-variation-selector-attribute-options__dropdown',
					'id'                  => $attribute_id,
					'data-wp-interactive' => $this->get_full_block_name() . '__dropdown',
					'data-wp-context'     => array(
						'options'  => $options,
						'selected' => $this->get_default_selected_attribute( $attribute_terms ),
					),
				),
			),
			$this->get_normalized_attributes(
				array(
					'data-wp-text'           => "context.option.label",
					'data-wp-bind--value'    => "context.option.value",
					'data-wp-bind--selected' => "context.isSelected",
					'data-wp-init'           => "callbacks.init",
					'data-wp-on--change'     => "actions.handleChange",
					'data-wp-context'        => array(
						'isSelected' => null,
					),
				),
			),
		);
	}
}
