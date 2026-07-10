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
	 * Block name. Block has been initially created as Large Image but has been renamed
	 * to more generic name.
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

		foreach ( $block->inner_blocks as $inner_block ) {
			if ( 'woocommerce/product-image' === $inner_block->name ) {
				// Product Image requires special handling because we need to render it once for each media item.
				$images_html .= $this->get_main_media_html( $block->context, $product, $inner_block );
			} else {
				// For Next/Previous Buttons block, check if we have more than one media item, otherwise don't render it.
				if ( 'woocommerce/product-gallery-large-image-next-previous' === $inner_block->name ) {
					$product_gallery_media_count = ProductGalleryUtils::get_product_gallery_media_count( $product );
					if ( $product_gallery_media_count <= 1 ) {
						continue;
					}
				}

				// Render all the inner blocks once each.
				$inner_block_html = (
					new WP_Block(
						$inner_block->parsed_block,
						array_merge(
							(array) $block->context,
							array( 'iapi/provider' => 'woocommerce/product-gallery' )
						),
					)
				)->render( array( 'dynamic' => true ) );

				$inner_blocks_html .= $inner_block_html;
			}
		}

		ob_start();
		?>
			<div class="wc-block-product-gallery-large-image wp-block-woocommerce-product-gallery-large-image">
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
		$p = $this->get_product_image_processor( $image_html );

		// Bail out early if we don't find any image.
		if ( ! $p->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $image_html;
		}

		$p->set_attribute( 'tabindex', '-1' );
		$p->set_attribute( 'draggable', 'false' );
		$p->set_attribute( 'data-wp-watch', 'callbacks.toggleImageVisibility' );
		$p->set_attribute( 'data-wp-on--click', 'actions.onViewerClick' );
		$p->set_attribute( 'data-wp-on--touchstart', 'actions.onTouchStart' );
		$p->set_attribute( 'data-wp-on--touchmove', 'actions.onTouchMove' );
		$p->set_attribute( 'data-wp-on--touchend', 'actions.onTouchEnd' );

		if ( 0 === $index ) {
			$p->set_attribute( 'fetchpriority', 'high' );
			$p->set_attribute( 'loading', 'eager' );
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
	 * Get the main media HTML.
	 *
	 * @param array       $context The block context.
	 * @param \WC_Product $product The product object.
	 * @param WP_Block    $inner_block The inner block object.
	 * @return string
	 */
	private function get_main_media_html( $context, $product, $inner_block ) {
		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_single' );

		ob_start();
		?>
			<ul
				class="wc-block-product-gallery-large-image__container"
				data-wp-interactive="woocommerce/product-gallery"
				data-wp-on--keydown="actions.onViewerImageKeyDown"
				aria-label="<?php esc_attr_e( 'Product gallery', 'woocommerce' ); ?>"
				tabindex="0"
				aria-roledescription="carousel"
			>
				<?php foreach ( $media_data as $index => $media ) : ?>
					<li
						class="wc-block-product-gallery-large-image__wrapper"
					>
						<?php
						if ( 'video' === ( $media['media_type'] ?? '' ) ) {
							echo $this->get_video_html( $media, $context, $inner_block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							continue;
						}

						$image_html = (
							new WP_Block(
								$inner_block->parsed_block,
								array_merge( $context, array( 'imageId' => $media['id'] ) )
							)
						)->render( array( 'dynamic' => true ) );

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $this->update_single_image( $image_html, $context, $index );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php
		$template = ob_get_clean();

		return wp_interactivity_process_directives( $template );
	}

	/**
	 * Get a Product Image processor prepared for gallery updates.
	 *
	 * @param string $image_html The image html.
	 * @return \WP_HTML_Tag_Processor
	 */
	private function get_product_image_processor( $image_html ) {
		$p = new \WP_HTML_Tag_Processor( $image_html );

		if ( $p->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$p->remove_attribute( 'onclick' );
			$p->remove_attribute( 'style' );
			$p->set_attribute( 'tabindex', '-1' );
		} else {
			$p = new \WP_HTML_Tag_Processor( $image_html );
		}

		return $p;
	}

	/**
	 * Get video HTML for the product gallery large image area.
	 *
	 * @param array    $media       Video media data.
	 * @param array    $context     The block context.
	 * @param WP_Block $inner_block The inner block object.
	 * @return string
	 */
	private function get_video_html( $media, $context, $inner_block ) {
		$image_html = (
			new WP_Block(
				$inner_block->parsed_block,
				$context
			)
		)->render( array( 'dynamic' => true ) );

		return $this->replace_product_image_with_video( $image_html, $media, $context );
	}

	/**
	 * Replace Product Image markup with product gallery video markup.
	 *
	 * @param string $image_html Product Image HTML.
	 * @param array  $media      Video media data.
	 * @param array  $context    The block context.
	 * @return string
	 */
	private function replace_product_image_with_video( $image_html, $media, $context ) {
		$p = $this->get_product_image_processor( $image_html );

		if ( ! $p->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $image_html;
		}

		$attrs = ProductGalleryUtils::get_video_attributes( $media, 'gallery' );

		if ( empty( $attrs ) ) {
			return $image_html;
		}

		$image_style   = $p->get_attribute( 'style' );
		$video_classes = 'wc-block-woocommerce-product-gallery-large-image__image ' .
			'wc-block-woocommerce-product-gallery-large-image__video';

		if ( ! empty( $context['fullScreenOnClick'] ) ) {
			$video_classes             .= ' wc-block-woocommerce-product-gallery-large-image__image--full-screen-on-click';
			$attrs['data-wp-on--click'] = 'actions.openDialog';
		}

		$attrs = array_merge(
			$attrs,
			array(
				'class'                  => $video_classes,
				'data-wp-on--touchend'   => 'actions.onTouchEnd',
				'data-wp-on--touchmove'  => 'actions.onTouchMove',
				'data-wp-on--touchstart' => 'actions.onTouchStart',
				'draggable'              => 'false',
				'tabindex'               => '-1',
			)
		);

		if ( is_string( $image_style ) && '' !== $image_style ) {
			$attrs['style'] = $image_style;
		}

		$placeholder_attribute = 'data-wc-product-gallery-video-placeholder';

		if ( ! $p->set_attribute( $placeholder_attribute, '1' ) ) {
			return $image_html;
		}

		$updated_html      = $p->get_updated_html();
		$video_html        = ProductGalleryUtils::get_video_html( $attrs );
		$pattern           = sprintf(
			'/<img\b(?=[^>]*\s%s(?:\s*=\s*(?:"1"|\'1\'|1))?)[^>]*>/i',
			preg_quote( $placeholder_attribute, '/' )
		);
		$replacement_count = 0;
		$video_markup      = preg_replace_callback(
			$pattern,
			static function () use ( $video_html ) {
				return $video_html;
			},
			$updated_html,
			1,
			$replacement_count
		);

		return is_string( $video_markup ) && $replacement_count
			? $video_markup
			: $image_html;
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
	 * Viewer renders inner blocks manually so we need to skip default
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
