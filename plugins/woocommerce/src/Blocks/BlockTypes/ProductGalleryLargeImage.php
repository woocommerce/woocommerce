<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

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
		return [ 'postId', 'hoverZoom', 'fullScreenOnClick' ];
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		if ( $block->context['hoverZoom'] || $block->context['fullScreenOnClick'] ) {
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

		$images_html = $this->get_main_images_html( $block->context, $product );

		$processor = new \WP_HTML_Tag_Processor( $content );
		$processor->next_tag();
		$processor->remove_class( 'wp-block-woocommerce-product-gallery-large-image' );
		$content = $processor->get_updated_html();

		ob_start();
		?>
			<div class="wc-block-product-gallery-large-image wp-block-woocommerce-product-gallery-large-image">
				<?php // No need to use wp_kses here because the image HTML is built internally. ?>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $images_html; ?>
				<?php // No need to use wp_kses here because $content is inner blocks which are already escaped. ?>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $content; ?>
			</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Get the main images html code. The first element of the array contains the HTML of the first image that is visible, the second element contains the HTML of the other images that are hidden.
	 *
	 * @param array       $context The block context.
	 * @param \WC_Product $product The product object.
	 *
	 * @return array
	 */
	private function get_main_images_html( $context, $product ) {
		$image_data   = ProductGalleryUtils::get_product_gallery_image_data( $product, 'woocommerce_single' );
		$base_classes = 'wc-block-woocommerce-product-gallery-large-image__image';

		if ( $context['fullScreenOnClick'] ) {
			$base_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--full-screen-on-click';
		}
		if ( $context['hoverZoom'] ) {
			$base_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--hoverZoom';
		}

		ob_start();
		?>
			<ul
				class="wc-block-product-gallery-large-image__container"
				tabindex="-1"
				data-wp-interactive="woocommerce/product-gallery"
			>
				<?php foreach ( $image_data as $index => $image ) : ?>
					<li class="wc-block-product-gallery-large-image__wrapper">
						<img
							class="<?php echo esc_attr( $base_classes ); ?>"
							src="<?php echo esc_attr( $image['src'] ); ?>"
							srcset="<?php echo esc_attr( $image['srcset'] ); ?>"
							sizes="<?php echo esc_attr( $image['sizes'] ); ?>"
							data-image-id="<?php echo esc_attr( $image['id'] ); ?>"
							data-wp-on--keydown="actions.onSelectedLargeImageKeyDown"
							data-wp-on--touchstart="actions.onTouchStart"
							data-wp-on--touchmove="actions.onTouchMove"
							data-wp-on--touchend="actions.onTouchEnd"
							data-wp-watch="callbacks.toggleActiveImageAttributes"
							<?php if ( $context['hoverZoom'] ) : ?>
								data-wp-on--mousemove="actions.startZoom"
								data-wp-on--mouseleave="actions.resetZoom"
							<?php endif; ?>
							<?php if ( $context['fullScreenOnClick'] ) : ?>
								data-wp-on--click="actions.openDialog"
							<?php endif; ?>
							<?php if ( 0 === $index ) : ?>
								fetchpriority="high"
							<?php else : ?>
								fetchpriority="low"
								loading="lazy"
							<?php endif; ?>
							tabindex="0"
							alt=""
						/>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php
		$template = ob_get_clean();

		return wp_interactivity_process_directives( $template );
	}

	/**
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
