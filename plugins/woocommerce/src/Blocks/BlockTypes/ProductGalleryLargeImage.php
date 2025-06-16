<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use WP_Block;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryLargeImage extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

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
		return [ 'postId', 'hoverZoom', 'fullScreenOnClick' ];
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		if ( ! empty( $block->context['hoverZoom'] ) || ! empty( $block->context['fullScreenOnClick'] ) ) {
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

		$images_html       = '';
		$inner_blocks_html = '';

		/**
		 * ============================================================
		 * START TEMPORARY BACKWARDS COMPATIBILITY CODE - TO BE REMOVED
		 * ============================================================
		 * In case Product Gallery Large Image is still used in a
		 * "standalone" way, with no Product Image block inside,
		 * we need to render the images manually the "old way".
		 *
		 * Includes legacy_get_main_images_html method.
		 */

		$has_product_image_block = ! empty(
			array_filter(
				iterator_to_array( $block->inner_blocks ),
				function ( $inner_block ) {
					return 'woocommerce/product-image' === $inner_block->name;
				}
			)
		);

		if ( ! $has_product_image_block ) {
			$images_html = $this->legacy_get_main_images_html( $block->context, $product );
		}

		/**
		 * ==========================================================
		 * END TEMPORARY BACKWARDS COMPATIBILITY CODE - TO BE REMOVED
		 * ==========================================================
		 */

		foreach ( $block->inner_blocks as $inner_block ) {
			if ( 'woocommerce/product-image' === $inner_block->name ) {
				// Product Image requires special handling because we need to render it once for each image.
				$images_html .= $this->get_main_images_html( $block->context, $product, $inner_block );
			} else {
				// Render all the inner blocks once each.
				$inner_block_html = (
					new WP_Block(
						$inner_block->parsed_block,
						$block->context
					)
				)->render( array( 'dynamic' => true ) );

				$inner_blocks_html .= $inner_block_html;
			}
		}

		ob_start();
		?>
			<div class="wc-block-product-gallery-large-image wp-block-woocommerce-product-gallery-large-image">
				<?php // No need to use wp_kses here because the image HTML is built internally. ?>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $images_html; ?>
				<div class="wc-block-product-gallery-large-image__inner-blocks">
					<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $inner_blocks_html; ?>
				</div>
			</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Update the single image html.
	 *
	 * @param string $image_html The image html.
	 * @param array  $context The block context.
	 * @param int    $index The index of the image.
	 * @return string
	 */
	private function update_single_image( $image_html, $context, $index ) {
		$p = new \WP_HTML_Tag_Processor( $image_html );

		if ( $p->next_tag( 'a' ) ) {
			$p->remove_attribute( 'onclick' );
			$p->remove_attribute( 'style' );
			$p->set_attribute( 'tabindex', '-1' );
		} else {
			/**
			 * If we can't find and <a> tag, we're at then end of the document.
			 * We need to reinitialize the processor instance to search for <img> tag.
			 */
			$p = new \WP_HTML_Tag_Processor( $image_html );
		}

		// Bail out early if we don't find any image.
		if ( ! $p->next_tag( 'img' ) ) {
			return $image_html;
		}

		$p->set_attribute( 'tabindex', '-1' );
		$p->set_attribute( 'draggable', 'false' );
		$p->set_attribute( 'data-wp-on--click', 'actions.onSelectedLargeImageClick' );
		$p->set_attribute( 'data-wp-on--touchstart', 'actions.onTouchStart' );
		$p->set_attribute( 'data-wp-on--touchmove', 'actions.onTouchMove' );
		$p->set_attribute( 'data-wp-on--touchend', 'actions.onTouchEnd' );

		if ( 0 === $index ) {
			$p->set_attribute( 'fetchpriority', 'high' );
		} else {
			$p->set_attribute( 'fetchpriority', 'low' );
			$p->set_attribute( 'loading', 'lazy' );
		}

		$img_classes = 'wc-block-woocommerce-product-gallery-large-image__image';

		if ( ! empty( $context['fullScreenOnClick'] ) ) {
			$img_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--full-screen-on-click';

			$p->set_attribute( 'data-wp-on--click', 'actions.openDialog' );
		}
		if ( ! empty( $context['hoverZoom'] ) ) {
			$img_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--hoverZoom';

			$p->set_attribute( 'data-wp-on--mousemove', 'actions.startZoom' );
			$p->set_attribute( 'data-wp-on--mouseleave', 'actions.resetZoom' );
		}

		$p->add_class( $img_classes );

		return $p->get_updated_html();
	}

	/**
	 * Get the main images html code. The first element of the array contains the HTML of the first image that is visible, the second element contains the HTML of the other images that are hidden.
	 *
	 * @param array       $context The block context.
	 * @param \WC_Product $product The product object.
	 * @param WP_Block    $inner_block The inner block object.
	 * @return array
	 */
	private function get_main_images_html( $context, $product, $inner_block ) {
		$image_data = ProductGalleryUtils::get_product_gallery_image_data( $product, 'woocommerce_single' );

		ob_start();
		?>
			<ul
				class="wc-block-product-gallery-large-image__container"
				data-wp-interactive="woocommerce/product-gallery"
				data-wp-on--keydown="actions.onSelectedLargeImageKeyDown"
				aria-label="<?php esc_attr_e( 'Product gallery', 'woocommerce' ); ?>"
				tabindex="0"
				aria-roledescription="carousel"
			>
				<?php foreach ( $image_data as $index => $image ) : ?>
					<li
						class="wc-block-product-gallery-large-image__wrapper"
					>
						<?php
							$image_html = (
								new WP_Block(
									$inner_block->parsed_block,
									array_merge( $context, array( 'imageId' => $image['id'] ) )
								)
							)->render( array( 'dynamic' => true ) );

							echo $this->update_single_image( $image_html, $context, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php
		$template = ob_get_clean();

		return wp_interactivity_process_directives( $template );
	}

	/**
	 * Get the main images html code. The first element of the array contains the HTML of the first image that is visible, the second element contains the HTML of the other images that are hidden.
	 *
	 * @param array       $context The block context.
	 * @param \WC_Product $product The product object.
	 *
	 * @return array
	 */
	private function legacy_get_main_images_html( $context, $product ) {
		$image_data   = ProductGalleryUtils::get_product_gallery_image_data( $product, 'woocommerce_single' );
		$base_classes = 'wc-block-woocommerce-product-gallery-large-image__image wc-block-woocommerce-product-gallery-large-image__image--legacy';

		if ( ! empty( $context['fullScreenOnClick'] ) ) {
			$base_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--full-screen-on-click';
		}
		if ( ! empty( $context['hoverZoom'] ) ) {
			$base_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--hoverZoom';
		}

		ob_start();
		?>
			<ul
				class="wc-block-product-gallery-large-image__container"
				aria-roledescription="carousel"
			>
				<?php foreach ( $image_data as $index => $image ) : ?>
					<li class="wc-block-product-gallery-large-image__wrapper">
						<img
							class="<?php echo esc_attr( $base_classes ); ?>"
							src="<?php echo esc_attr( $image['src'] ); ?>"
							srcset="<?php echo esc_attr( $image['srcset'] ); ?>"
							sizes="<?php echo esc_attr( $image['sizes'] ); ?>"
							data-image-id="<?php echo esc_attr( $image['id'] ); ?>"
							alt="<?php echo esc_attr( $image['alt'] ); ?>"
							data-wp-on--touchstart="actions.onTouchStart"
							data-wp-on--touchmove="actions.onTouchMove"
							data-wp-on--touchend="actions.onTouchEnd"
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
							tabindex="-1"
							draggable="false"
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

	/**
	 * Large Image renders inner blocks manually so we need to skip default
	 * rendering routine for its inner blocks
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 * @return array
	 */
	public function add_block_type_metadata_settings( $settings, $metadata ) {
		if ( ! empty( $metadata['name'] ) && 'woocommerce/product-gallery-large-image' === $metadata['name'] ) {
			$settings['skip_inner_blocks'] = true;
		}
		return $settings;
	}
}
