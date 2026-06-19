<?php
/**
 * Server-side rendering of the `woocommerce/catalog-sorting` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the `woocommerce/catalog-sorting` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_catalog_sorting( array $attributes, string $content, $block ): string {
	if ( ! isset( $attributes['fontSize'] ) ) {
		$attributes['fontSize'] = 'small';
	}

	ob_start();
	woocommerce_catalog_ordering( $attributes );
	$catalog_sorting = ob_get_clean();

	if ( ! $catalog_sorting ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( $catalog_sorting );

	if ( $processor->next_tag( array( 'tag_name' => 'form' ) ) ) {
		$processor->set_attribute( 'data-wp-interactive', 'woocommerce/catalog-sorting' );
		$processor->set_attribute( 'data-wp-on--submit', 'actions.preventSubmit' );
	}

	if ( $processor->next_tag( array( 'tag_name' => 'select' ) ) ) {
		$processor->set_attribute( 'data-wp-on--change', 'actions.handleSortChange' );
	}

	$catalog_sorting    = $processor->get_updated_html();
	$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(
				' ',
				array_filter(
					array(
						'woocommerce wc-block-catalog-sorting',
						esc_attr( $classes_and_styles['classes'] ),
					)
				)
			),
			'style' => esc_attr( $classes_and_styles['styles'] ?? '' ),
		)
	);

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$catalog_sorting
	);
}

/**
 * Registers the `woocommerce/catalog-sorting` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_catalog_sorting(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/catalog-sorting' ) ) {
		return;
	}

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_catalog_sorting',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_catalog_sorting' );
