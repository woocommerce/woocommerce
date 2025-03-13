<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryThumbnails class.
 */
class ProductGalleryThumbnails extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-thumbnails';

	/**
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
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
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId', 'mode', 'cropImages' ];
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
		if ( ! isset( $block->context ) ) {
			return '';
		}

		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$post_id            = $block->context['postId'];

		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$crop_images            = $block->context['cropImages'] ?? false;
		$product_gallery_images = ProductGalleryUtils::get_product_gallery_images( $post_id, 'full', array(), 'wc-block-product-gallery-thumbnails__thumbnail', $crop_images );

		if ( ! $product_gallery_images || count( $product_gallery_images ) <= 1 ) {
			return '';
		}

		$html             = '';
		$thumbnail_size   = str_replace('%', '', $attributes['thumbnailSize'] ?? '20%');
		$thumbnails_class = 'wc-block-product-gallery-thumbnails--thumbnails-size-' . $thumbnail_size;

		foreach ( $product_gallery_images as $product_gallery_image_html ) {
			$processor = new \WP_HTML_Tag_Processor( $product_gallery_image_html );

			if ( $processor->next_tag( 'img' ) ) {
				$processor->add_class( 'wc-block-product-gallery-thumbnails__image' );
				$processor->set_attribute( 'data-wp-on--keydown', 'actions.onThumbnailKeyDown' );
				$processor->set_attribute( 'tabindex', '0' );
				$processor->set_attribute(
					'data-wp-on--click',
					'actions.selectCurrentImage'
				);

				$html .= $processor->get_updated_html();
			} else {
				$html .= $product_gallery_image_html;
			}
		}

		$allowed_html                    = wp_kses_allowed_html( 'post' );
		$allowed_html['img']['tabindex'] = true;

		return sprintf(
			'<div
				data-wp-interactive="woocommerce/product-gallery"
				class="wc-block-product-gallery-thumbnails wp-block-woocommerce-product-gallery-thumbnails %1$s"
				data-wp-class--wc-block-product-gallery-thumbnails--overflow-top="context.overflowTop"
				data-wp-class--wc-block-product-gallery-thumbnails--overflow-bottom="context.overflowBottom"
				data-wp-class--wc-block-product-gallery-thumbnails--overflow-left="context.overflowLeft"
				data-wp-class--wc-block-product-gallery-thumbnails--overflow-right="context.overflowRight"
				style="%2$s"
			>
				<div
					class="wc-block-product-gallery-thumbnails__scrollable"
					data-wp-init="actions.onScroll"
					data-wp-on--scroll="actions.onScroll"
				>
					%3$s
				</div>
			</div>',
			esc_attr( $classes_and_styles['classes'] . ' ' . $thumbnails_class ),
			esc_attr( $classes_and_styles['styles'] ),
			wp_kses(
				$html,
				$allowed_html
			),
		);
	}
}
