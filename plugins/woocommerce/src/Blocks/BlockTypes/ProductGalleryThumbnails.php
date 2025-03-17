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

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$post_id            = $block->context['postId'];

		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$product_gallery_thumbnails_data = ProductGalleryUtils::get_product_gallery_image_data( $product );
		$product_gallery_images          = $product_gallery_thumbnails_data['images'];
		// Don't show the thumbnails block if there is only one image.
		if ( count( $product_gallery_images ) <= 1 ) {
			return '';
		}

		// Will eventually be replaced by a slider. Temporary solution.
		$default_number_of_thumbnails = 3;
		$number_of_thumbnails         = isset( $attributes['numberOfThumbnails'] ) && is_numeric( $attributes['numberOfThumbnails'] ) ? $attributes['numberOfThumbnails'] : $default_number_of_thumbnails;
		$number_of_images             = count( $product_gallery_images );
		// If the number of thumbnails is greater than the number of images, set the number of thumbnails to the number of images.
		// But not less than than 3 (default number of thumbnails).
		$thumbnails_layout          = max( min( $number_of_images, $number_of_thumbnails ), $default_number_of_thumbnails );
		$number_of_thumbnails_class = 'wc-block-product-gallery-thumbnails--number-of-thumbnails-' . $thumbnails_layout;
		$remaining_thumbnails_count = $number_of_images - $number_of_thumbnails;
		wp_interactivity_config( 'woocommerce/product-gallery', array( 'numberOfThumbnails' => $number_of_thumbnails ) );
		// End of temporary solution.

		ob_start();
		?>
		<div
			class="wc-block-product-gallery-thumbnails
						<?php echo esc_attr( $classes_and_styles['classes'] . ' ' . $number_of_thumbnails_class ); ?>" 
			style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
			data-wp-interactive="woocommerce/product-gallery">
			<template
				data-wp-each--image="state.thumbnails"
				data-wp-each-key="context.image.id">
				<div class="wc-block-product-gallery-thumbnails__thumbnail">
					<img
						class="wc-block-product-gallery-thumbnails__thumbnail__image"
						data-wp-bind--data-image-id="context.image.id"
						data-wp-bind--src="context.image.src"
						data-wp-bind--srcset="context.image.srcset" 
						data-wp-bind--sizes="context.image.sizes"
						data-wp-on--click="actions.selectCurrentImage"
						data-wp-on--keydown="actions.onThumbnailKeyDown"
						decoding="async"
						tabindex="0"
						loading="lazy" />
					<div class="wc-block-product-gallery-thumbnails__thumbnail__overlay" 
						data-wp-bind--visible="actions.displayViewAll"
						data-wp-on--click="actions.openDialog"
						data-wp-on--keydown="actions.onViewAllImagesKeyDown"
						tabindex="0">
						<span class="wc-block-product-gallery-thumbnails__thumbnail__remaining-thumbnails-count">+<?php echo esc_html( $remaining_thumbnails_count ); ?></span>
						<span class="wc-block-product-gallery-thumbnails__thumbnail__view-all"><?php echo esc_html__( 'View all', 'woocommerce' ); ?></span>
					</div>
				</div>
			</template>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}