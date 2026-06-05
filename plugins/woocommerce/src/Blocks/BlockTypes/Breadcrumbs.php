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

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'woocommerce wc-block-breadcrumbs ' . trim( $classes_and_styles['classes'] ),
				'style' => trim( $classes_and_styles['styles'] ),
			)
		);

		$theme_font_size                = wp_get_global_styles(
			array( 'blocks', 'woocommerce/breadcrumbs', 'typography', 'fontSize' )
		);
		$has_non_small_theme_font_size  = is_string( $theme_font_size ) && 'var(--wp--preset--font-size--small)' !== $theme_font_size;
		$has_custom_font_size           = $attributes['style']['typography']['fontSize'] ?? '';
		$has_non_small_custom_font_size = $has_custom_font_size && strpos( $font_size_classes_and_styles['class'] ?? '', 'has-small-font-size' ) === false;

		// Remove the default 'has-small-font-size' class added by default when the block has a custom font size.
		// This is needed because the block.json defines a default fontSize, which is considered an anti-pattern
		// since styles should be defined by themes and plugins instead.
		if ( $has_custom_font_size ) {
			if ( $has_non_small_custom_font_size ) {
				$wrapper_attributes = str_replace( 'has-small-font-size', '', $wrapper_attributes );
			}
		} elseif ( $has_non_small_theme_font_size ) {
			$wrapper_attributes = str_replace( 'has-small-font-size', '', $wrapper_attributes );
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
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
