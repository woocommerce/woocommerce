<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryLargeImage extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-large-image';

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId', 'hoverZoom' ];
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		if ( $block->context['hoverZoom'] ) {
			parent::enqueue_assets( $attributes, $content, $block );
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
		$post_id = $block->context['postId'];

		if ( ! isset( $post_id ) ) {
			return '';
		}

		global $product;

		$previous_product = $product;
		$product          = wc_get_product( $post_id );
		if ( ! $product instanceof \WC_Product ) {
			$product = $previous_product;

			return '';
		}

		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			$frontend_scripts = new \WC_Frontend_Scripts();
			$frontend_scripts::load_scripts();
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		$processor->next_tag();
		$processor->remove_class( 'wp-block-woocommerce-product-gallery-large-image' );
		$content = $processor->get_updated_html();

		$image_html = wp_get_attachment_image(
			$product->get_image_id(),
			'full',
			false,
			array(
				'class' => 'wc-block-woocommerce-product-gallery-large-image__image',
			)
		);

		[$directives, $image_html] = $block->context['hoverZoom'] ? $this->get_html_with_interactivity( $image_html ) : array( array(), $image_html );

		return strtr(
			'<div class="wp-block-woocommerce-product-gallery-large-image" {directives}>
				{image}
				<div class="wc-block-woocommerce-product-gallery-large-image__content">
					{content}
				</div>
			</div>',
			array(
				'{image}'      => $image_html,
				'{content}'    => $content,
				'{directives}' => array_reduce(
					array_keys( $directives ),
					function( $carry, $key ) use ( $directives ) {
						return $carry . ' ' . $key . '="' . esc_attr( $directives[ $key ] ) . '"';
					},
					''
				),
			)
		);
	}

	/**
	 * Get the HTML that adds interactivity to the image. This is used for the hover zoom effect.
	 *
	 * @param string $image_html The image HTML.
	 * @return array
	 */
	private function get_html_with_interactivity( $image_html ) {
		$context = array(
			'woocommerce' => array(
				'styles' => array(
					'transform'        => 'scale(1.0)',
					'transform-origin' => '',
				),
			),
		);

		$directives = array(
			'data-wc-on--mousemove'  => 'actions.woocommerce.handleMouseMove',
			'data-wc-on--mouseleave' => 'actions.woocommerce.handleMouseLeave',
			'data-wc-context'        => wp_json_encode( $context, JSON_NUMERIC_CHECK ),
		);

		$image_html_processor = new \WP_HTML_Tag_Processor( $image_html );
		$image_html_processor->next_tag( 'img' );
		$image_html_processor->add_class( 'wc-block-woocommerce-product-gallery-large-image__image--hoverZoom' );
		$image_html_processor->set_attribute( 'data-wc-bind--style', 'selectors.woocommerce.styles' );
		$image_html = $image_html_processor->get_updated_html();

		return array(
			$directives,
			$image_html,
		);
	}
}
