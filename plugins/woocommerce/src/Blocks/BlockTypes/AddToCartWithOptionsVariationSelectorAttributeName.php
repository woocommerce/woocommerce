<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Block type for variation selector attribute name in add to cart with options.
 * It's responsible to render the attribute name.
 */
class AddToCartWithOptionsVariationSelectorAttributeName extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-name';

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

		$content = '';

		if ( isset( $attribute_name ) ) {
			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
	
			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'class' => esc_attr( $classes_and_styles['classes'] ),
					'for'   => esc_attr( 'attribute_' . sanitize_title( $attribute_name ) ),
					'id'    => esc_attr( 'attribute_' . sanitize_title( $attribute_name ) . '_label' ),
					'style' => esc_attr( $classes_and_styles['styles'] ),
				)
			);

			$label_text = esc_html( wc_attribute_label( $attribute_name ) );

			$content .= sprintf(
				'<label %s>%s</label>',
				$wrapper_attributes,
				$label_text
			);
		}

		return $content;
	}
}
