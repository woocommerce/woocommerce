<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductFilters class.
 */
class ProductFilters extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters';

	/**
	 * Register the context.
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId' ];
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Return the dialog content.
	 *
	 * @return string
	 */
	protected function render_dialog() {
		$template_part = BlockTemplateUtils::get_template_part( 'product-filters' );

		$parsed_blocks         = parse_blocks(
			$template_part
		);
		$product_filters_block = $parsed_blocks[0];
		$html                  = $product_filters_block['innerHTML'];
		$target_div            = '</div>';

		$product_filters_content_html = array_reduce(
			$product_filters_block['innerBlocks'],
			function ( $carry, $item ) {
				return $carry . render_block( $item );
			},
			''
		);

		$html = str_replace( $target_div, $product_filters_content_html . $target_div, $html );

		$html = strtr(
			'<dialog data-wc-bind--open="context.isDialogOpen" data-wc-bind--hidden="!context.isDialogOpen" role="dialog" aria-modal="true" hidden data-wc-class--wc-block-product-filters--dialog-open="context.isDialogOpen">
				{{html}}
			</dialog>',
			array(
				'{{html}}' => $html,
			)
		);

		return $html;
	}

	/**
	 * Inject dialog into the product filters HTML.
	 *
	 * @param string $product_filters_html The Product Filters HTML.
	 * @param string $dialog_html  The dialog HTML.
	 *
	 * @return string
	 */
	protected function inject_dialog( $product_filters_html, $dialog_html ) {

		// Find the position of the last </div>.
		$pos = strrpos( $product_filters_html, '</div>' );

		if ( false !== $pos ) {
			// Inject the dialog_html at the correct position.
			$html = substr_replace( $product_filters_html, $dialog_html, $pos, 0 );

			return $html;
		}
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

		$html = $content;
		$p    = new \WP_HTML_Tag_Processor( $html );

		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-filters' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$p->set_attribute(
				'data-wc-context',
				wp_json_encode(
					array(
						'isDialogOpen'                    => false,
					),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			);
			$html = $p->get_updated_html();
		}

		do_action( 'qm/debug', $html );

		$html = $this->inject_dialog( $html, $this->render_dialog() );

		return $html;
	}
}
