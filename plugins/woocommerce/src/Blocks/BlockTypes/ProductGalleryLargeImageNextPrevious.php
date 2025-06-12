<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
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

		$product_gallery_image_count = ProductGalleryUtils::get_product_gallery_image_count( $product );

		// Don't show the arrows block if there is only one image.
		if ( $product_gallery_image_count <= 1 ) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'align' ) );
		$vertical_alignment = StyleAttributesUtils::get_align_class_and_style( $attributes );

		ob_start();
		?>
		<div
			class="wc-block-product-gallery-large-image-next-previous <?php echo esc_attr( $vertical_alignment['class'] ); ?>"
			data-wp-interactive="woocommerce/product-gallery"
		>
			<button
				class="wc-block-product-gallery-large-image-next-previous__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.selectPreviousImage"
				data-wp-on--keydown="actions.onArrowsKeyDown"
				data-wp-bind--aria-disabled="context.disableLeft"
				aria-label="Previous image"
			>
				<svg
					class="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="M6.445 12.005.986 6 6.445-.005l1.11 1.01L3.014 6l4.54 4.995-1.109 1.01Z"
						clipRule="evenodd"
					/>
				</svg>
			</button>
			<button
				class="wc-block-product-gallery-large-image-next-previous__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.selectNextImage"
				data-wp-on--keydown="actions.onArrowsKeyDown"
				data-wp-bind--aria-disabled="context.disableRight"
				aria-label="Next image"
			>
				<svg
					class="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="M1.555-.004 7.014 6l-5.459 6.005-1.11-1.01L4.986 6 .446 1.005l1.109-1.01Z"
						clipRule="evenodd"
					/>
				</svg>
			</button>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}
