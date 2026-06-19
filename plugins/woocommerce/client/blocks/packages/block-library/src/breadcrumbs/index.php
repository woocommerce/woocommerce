<?php
/**
 * Server-side rendering of the `woocommerce/breadcrumbs` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the `woocommerce/breadcrumbs` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string|null Rendered breadcrumbs block.
 */
function render_block_woocommerce_breadcrumbs( $attributes, $content, $block ) {
	ob_start();
	woocommerce_breadcrumb();
	$breadcrumb = ob_get_clean();

	if ( ! $breadcrumb ) {
		return null;
	}

	$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'font_size' ) );

	$font_size_classes_and_styles  = get_block_woocommerce_breadcrumbs_font_size_classes_and_styles( $attributes, $block );
	$classes_and_styles['classes'] = $classes_and_styles['classes'] . ' ' . ( $font_size_classes_and_styles['class'] ?? '' ) . ' ';
	$classes_and_styles['styles']  = $classes_and_styles['styles'] . ' ' . ( $font_size_classes_and_styles['style'] ?? '' ) . ' ';

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'woocommerce wc-block-breadcrumbs ' . trim( $classes_and_styles['classes'] ),
			'style' => trim( $classes_and_styles['styles'] ),
		)
	);

	$has_non_small_custom_font_size = false === strpos( $font_size_classes_and_styles['class'] ?? '', 'has-small-font-size' );

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
 * Gets font size classes and styles for the breadcrumbs block.
 *
 * @since 11.0.0
 *
 * @param array    $attributes The block attributes.
 * @param WP_Block $block      The block instance.
 * @return array The font size classes and styles.
 */
function get_block_woocommerce_breadcrumbs_font_size_classes_and_styles( array $attributes, $block ): array {
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

	$theme_font_size_classes_and_styles = get_block_woocommerce_breadcrumbs_theme_font_size_classes_and_styles();

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
 * @since 11.0.0
 *
 * @return array The font size classes and styles.
 */
function get_block_woocommerce_breadcrumbs_theme_font_size_classes_and_styles(): array {
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

/**
 * Registers the `woocommerce/breadcrumbs` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_breadcrumbs(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_breadcrumbs',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_breadcrumbs' );
