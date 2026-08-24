<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CatalogSorting class.
 */
class CatalogSorting extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'catalog-sorting';

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
		woocommerce_catalog_ordering( $attributes );
		$catalog_sorting = ob_get_clean();

		if ( ! $catalog_sorting ) {
			return;
		}

		// Use WP_HTML_Tag_Processor to inject Interactivity API directives.
		$processor = new \WP_HTML_Tag_Processor( $catalog_sorting );

		// Find and modify the form element.
		if ( $processor->next_tag( array( 'tag_name' => 'form' ) ) ) {
			$processor->set_attribute( 'data-wp-interactive', 'woocommerce/catalog-sorting' );
			$processor->set_attribute( 'data-wp-on--submit', 'actions.preventSubmit' );
		}

		// Find and modify the select element.
		if ( $processor->next_tag( array( 'tag_name' => 'select' ) ) ) {
			$processor->set_attribute( 'data-wp-on--change', 'actions.handleSortChange' );
		}

		$catalog_sorting = $processor->get_updated_html();

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode(
					' ',
					array_filter(
						[
							'woocommerce wc-block-catalog-sorting',
							esc_attr( $classes_and_styles['classes'] ),
						]
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
}
