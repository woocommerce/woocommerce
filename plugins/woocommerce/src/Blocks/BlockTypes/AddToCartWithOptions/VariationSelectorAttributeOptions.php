<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Block type for variation selector attribute options in add to cart with options.
 * It's responsible to render the attribute options.
 */
class VariationSelectorAttributeOptions extends AbstractAddToCartWithOptionsBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-options';

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

		$attribute_name = $block->context['woocommerce/attributeName'];

		if ( isset( $attribute_name ) ) {

			$attributes = $this->parse_attributes( $attributes );

			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

			$field_style = $attributes['style'];

			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
					'class'               => esc_attr( $classes_and_styles['classes'] ),
					'style'               => esc_attr( $classes_and_styles['styles'] ),
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
}
