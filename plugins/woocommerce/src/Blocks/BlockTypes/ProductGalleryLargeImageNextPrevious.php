<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryLargeImageNextPrevious extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-large-image-next-previous';

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

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$product_gallery_data   = ProductGalleryUtils::get_product_gallery_image_data( $product );
		$product_gallery_images = $product_gallery_data['images'];

		// Don't show the arrows block if there is only one image.
		if ( count( $product_gallery_images ) <= 1 ) {
			return '';
		}

		return sprintf(
			'<div
				class="wc-block-product-gallery-large-image-next-previous wp-block-woocommerce-product-gallery-large-image-next-previous"
				data-wp-interactive="woocommerce/product-gallery"
			>
				<div class="wc-block-product-gallery-large-image-next-previous-container">
					<button
						className="wc-block-product-gallery-large-image-next-previous-left"
						data-wp-on--click="actions.selectPreviousImage"
						data-wp-bind--disabled="context.disableLeft"
						aria-label="Previous image"
					>
						&lt;
					</button>
					<button
						className="wc-block-product-gallery-large-image-next-previous-right"
						data-wp-on--click="actions.selectNextImage"
						data-wp-bind--disabled="context.disableRight"
						aria-label="Next image"
					>
						&gt;
					</button>
				</div>
			</div>'
		);
	}
}
