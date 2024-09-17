<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'woocommerce/product-filters/overlay' ];
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
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wc-block-product-filters-overlay-navigation',
			)
		);
		$overlay_mode       = isset( $block->context['woocommerce/product-filters/overlay'] ) ? $block->context['woocommerce/product-filters/overlay'] : 'never';

		if ( 'open-overlay' === $attributes['triggerType'] && ( 'never' === $overlay_mode || ( ! wp_is_mobile() && 'mobile' === $overlay_mode ) ) ) {
			return null;
		}

		$html = strtr(
			'<div {{wrapper_attributes}}>
				{{primary_content}}
				{{secondary_content}}
			</div>',
			array(
				'{{wrapper_attributes}}' => $wrapper_attributes,
				'{{primary_content}}'    => 'open-overlay' === $attributes['triggerType'] ? $this->render_icon( $attributes ) : $this->render_label( $attributes ),
				'{{secondary_content}}'  => 'open-overlay' === $attributes['triggerType'] ? $this->render_label( $attributes ) : $this->render_icon( $attributes ),
			)
		);

		$p = new \WP_HTML_Tag_Processor( $html );

		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-filters' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$p->set_attribute(
				'data-wc-on--click',
				'open-overlay' === $attributes['triggerType'] ? 'actions.openDialog' : 'actions.closeDialog'
			);
			$p->set_attribute( 'data-wc-class--hidden', 'open-overlay' === $attributes['triggerType'] ? 'state.isDialogOpen' : '!state.isDialogOpen' );
			$html = $p->get_updated_html();
		}

		return $html;
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
