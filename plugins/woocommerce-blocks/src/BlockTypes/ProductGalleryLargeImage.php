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
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId' ];
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

		return strtr(
			'<div class="wp-block-woocommerce-product-gallery-large-image">
				{image}
				<div class="wp-block-woocommerce-product-gallery-large-image__content">
					{content}
				</div>
			</div>',
			array(
				'{image}'   => wp_get_attachment_image(
					$product->get_image_id(),
					'full',
					false,
					array(
						'class' => 'wp-block-woocommerce-product-gallery-large-image__image',
					)
				),
				'{content}' => $content,
			)
		);

	}
}
