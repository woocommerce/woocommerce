<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductFilters class.
 */
class ProductFiltersOverlayNavigation extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters-overlay-navigation';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		$html_content = strtr(
			'<div class="wp-block-woocommerce-product-filters-overlay-navigation wc-block-product-filters-overlay-navigation alignright">
				{{primary_content}}
				{{secondary_content}}
			</div>',
			array(
				'{{classes_and_styles}}' => $classes_and_styles,
				'{{primary_content}}'    => 'open-overlay' === $attributes['triggerType'] ? $this->render_icon( $attributes ) : $this->render_label( $attributes ),
				'{{secondary_content}}'  => 'open-overlay' === $attributes['triggerType'] ? $this->render_label( $attributes ) : $this->render_icon( $attributes ),
			)
		);
		return $html_content;
	}

	/**
	 * Gets the icon to render depending on the triggerType attribute.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Label to render on the block
	 */
	private function render_icon( $attributes ) {
		if ( 'open-overlay' === $attributes['triggerType'] ) {
			return '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;"><path d="M10 17.5H14V16H10V17.5ZM6 6V7.5H18V6H6ZM8 12.5H16V11H8V12.5Z" fill="currentColor"></path></svg>';
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false" style="width: 16px; height: 16px;"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>';
	}

	/**
	 * Gets the label to render depending on the triggerType.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Label to render on the block
	 */
	private function render_label( $attributes ) {
		return sprintf(
			'<span>%s</span>',
			'open-overlay' === $attributes['triggerType'] ? __( 'Filters', 'woocommerce' ) : __( 'Close', 'woocommerce' )
		);
	}
}
