<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Breadcrumbs class.
 */
class Breadcrumbs extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'breadcrumbs';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		ob_start();
		woocommerce_breadcrumb();
		$breadcrumb = ob_get_clean();

		if ( ! $breadcrumb ) {
			return;
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'font_size' ) );

		$font_size_classes_and_styles  = $this->get_font_size_classes_and_styles( $attributes );
		$classes_and_styles['classes'] = $classes_and_styles['classes'] . ' ' . $font_size_classes_and_styles['class'] . ' ';
		$classes_and_styles['styles']  = $classes_and_styles['styles'] . ' ' . $font_size_classes_and_styles['style'] . ' ';

		return sprintf(
			'<div class="woocommerce wp-block-breadcrumbs wc-block-breadcrumbs %1$s" style="%2$s">%3$s</div>',
			esc_attr( $classes_and_styles['classes'] ),
			esc_attr( $classes_and_styles['styles'] ),
			$breadcrumb
		);
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Gets font size classes and styles for the breadcrumbs block.
	 *
	 * Note: This implementation intentionally avoids using StyleAttributesUtils::get_font_size_class_and_style()
	 * and get_block_wrapper_attributes() to ensure style attributes take precedence over the class attribute fontSize.
	 * This is needed because the block.json defines a default fontSize, which is considered an anti-pattern
	 * since styles should be defined by themes and plugins instead.
	 *
	 * @param array $attributes The block attributes.
	 * @return array The font size classes and styles.
	 */
	private function get_font_size_classes_and_styles( $attributes ) {
		$font_size = $attributes['fontSize'] ?? '';

		$custom_font_size = $attributes['style']['typography']['fontSize'] ?? '';

		if ( ! $font_size && '' === $custom_font_size ) {
			return array(
				'class' => null,
				'style' => null,
			);
		}

		if ( '' !== $custom_font_size ) {
			return array(
				'class' => null,
				'style' => sprintf( 'font-size: %s;', $custom_font_size ),
			);
		}

		return array(
			'class' => sprintf( 'has-font-size has-%s-font-size', $font_size ),
			'style' => null,
		);
	}
}
