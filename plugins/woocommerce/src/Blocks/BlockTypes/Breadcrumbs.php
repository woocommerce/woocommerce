<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use WP_Block;

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

		$font_size_classes_and_styles  = $this->get_font_size_classes_and_styles( $attributes, $block );
		$classes_and_styles['classes'] = $classes_and_styles['classes'] . ' ' . ( $font_size_classes_and_styles['class'] ?? '' ) . ' ';
		$classes_and_styles['styles']  = $classes_and_styles['styles'] . ' ' . ( $font_size_classes_and_styles['style'] ?? '' ) . ' ';

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'woocommerce wc-block-breadcrumbs ' . trim( $classes_and_styles['classes'] ),
				'style' => trim( $classes_and_styles['styles'] ),
			)
		);

		$has_non_small_custom_font_size = strpos( $font_size_classes_and_styles['class'] ?? '', 'has-small-font-size' ) === false;

		// Remove the default 'has-small-font-size' class when the block has a custom font size different from small.
		// This is needed because the block.json defines a default font size, which is considered an anti-pattern
		// since styles should be defined by themes and plugins instead.
		if ( $has_non_small_custom_font_size ) {
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
	 * @param array    $attributes The block attributes.
	 * @param WP_Block $block      The block instance.
	 * @return array The font size classes and styles.
	 */
	private function get_font_size_classes_and_styles( array $attributes, $block ) {
		$custom_font_size = $attributes['style']['typography']['fontSize'] ?? '';

		if ( '' !== $custom_font_size ) {
			return array(
				'class' => null,
				'style' => sprintf( 'font-size: %s;', $custom_font_size ),
			);
		}

		$explicit_font_size = isset( $block->parsed_block['attrs']['fontSize'] ) ? $block->parsed_block['attrs']['fontSize'] : null;

		if ( is_string( $explicit_font_size ) && '' !== $explicit_font_size ) {
			return array(
				'class' => sprintf( 'has-font-size has-%s-font-size', $explicit_font_size ),
				'style' => null,
			);
		}

		$theme_font_size_classes_and_styles = $this->get_theme_font_size_classes_and_styles();

		if ( $theme_font_size_classes_and_styles['class'] || $theme_font_size_classes_and_styles['style'] ) {
			return $theme_font_size_classes_and_styles;
		}

		$font_size = $attributes['fontSize'] ?? '';

		if ( $font_size ) {
			return array(
				'class' => sprintf( 'has-font-size has-%s-font-size', $font_size ),
				'style' => null,
			);
		}

		return array(
			'class' => null,
			'style' => null,
		);
	}

	/**
	 * Gets font size classes and styles from theme.json block styles.
	 *
	 * @return array The font size classes and styles.
	 */
	private function get_theme_font_size_classes_and_styles() {
		$theme_font_size = wp_get_global_styles(
			array( 'blocks', 'woocommerce/breadcrumbs', 'typography', 'fontSize' )
		);

		if ( ! is_string( $theme_font_size ) || '' === $theme_font_size ) {
			return array(
				'class' => null,
				'style' => null,
			);
		}

		$preset_prefix = 'var(--wp--preset--font-size--';

		if ( str_starts_with( $theme_font_size, $preset_prefix ) ) {
			$slug = rtrim( substr( $theme_font_size, strlen( $preset_prefix ) ), ')' );

			return array(
				'class' => sprintf( 'has-font-size has-%s-font-size', $slug ),
				'style' => null,
			);
		}

		return array(
			'class' => null,
			'style' => sprintf( 'font-size: %s;', $theme_font_size ),
		);
	}
}
