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
			 * If we can't find and <a> tag, we're at the end of the document.
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
							echo $this->get_video_html( $media, $context, $inner_block->parsed_block['attrs'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	 * Get video HTML for the product gallery large image area.
	 *
	 * @param array $media                    Video media data.
	 * @param array $context                  The block context.
	 * @param array $product_image_attributes Product Image block attributes.
	 * @return string
	 */
	private function get_video_html( $media, $context, $product_image_attributes = array() ) {
		if ( empty( $media['video_src'] ) ) {
			return '';
		}

		$settings    = isset( $media['settings'] ) && is_array( $media['settings'] ) ? $media['settings'] : array();
		$preload     = isset( $settings['preload'] ) && in_array(
			$settings['preload'],
			array( 'auto', 'metadata', 'none' ),
			true
		)
			? $settings['preload']
			: 'metadata';
		$aspect_ratio = $this->get_product_image_aspect_ratio( $product_image_attributes );
		$video_class = 'wc-block-woocommerce-product-gallery-large-image__image ' .
			'wc-block-woocommerce-product-gallery-large-image__video';
		$attrs       = array(
			'aria-label'               => $media['alt'] ?? '',
			'autoplay'                 => 'autoplay',
			'class'                    => $video_class,
			'data-image-id'            => $media['id'],
			'data-wp-on--touchend'     => 'actions.onTouchEnd',
			'data-wp-on--touchmove'    => 'actions.onTouchMove',
			'data-wp-on--touchstart'   => 'actions.onTouchStart',
			'data-wp-watch'            => 'callbacks.syncVideoPlayback',
			'draggable'                => 'false',
			'loop'                     => 'loop',
			'muted'                    => 'muted',
			'playsinline'              => 'playsinline',
			'preload'                  => $preload,
			'src'                      => $media['video_src'],
			'style'                    => $this->get_video_style( $product_image_attributes, $aspect_ratio ),
			'tabindex'                 => '-1',
		);
		$wrapper_attrs = array(
			'class' => 'wc-block-components-product-image wc-block-grid__product-image ' .
				'wc-block-components-product-image--aspect-ratio-' . str_replace( '/', '-', $aspect_ratio ),
		);

		if ( ! empty( $media['poster_id'] ) && ! empty( $media['poster_src'] ) ) {
			$attrs['poster'] = $media['poster_src'];
		}

		if ( ! empty( $context['fullScreenOnClick'] ) ) {
			$attrs['data-wp-on--click'] = 'actions.openDialog';
		}

		$video_html = '<video ' . wc_implode_html_attributes(
			array_filter(
				$attrs,
				static function ( $value ) {
					return '' !== $value;
				}
			)
		) . '></video>';

		return '<div ' . wc_implode_html_attributes( $wrapper_attrs ) . '>' . $video_html . '</div>';
	}

	/**
	 * Get the Product Image aspect ratio applied to gallery videos.
	 *
	 * @param array $product_image_attributes Product Image block attributes.
	 * @return string
	 */
	private function get_product_image_aspect_ratio( $product_image_attributes ) {
		$aspect_ratio = $product_image_attributes['aspectRatio'] ?? $product_image_attributes['style']['dimensions']['aspectRatio'] ?? 'auto';

		return is_string( $aspect_ratio ) && '' !== $aspect_ratio ? $aspect_ratio : 'auto';
	}

	/**
	 * Get inline styles for gallery videos from Product Image attributes.
	 *
	 * @param array  $product_image_attributes Product Image block attributes.
	 * @param string $aspect_ratio             Product Image aspect ratio.
	 * @return string
	 */
	private function get_video_style( $product_image_attributes, $aspect_ratio ) {
		$styles = array();
		$scale  = isset( $product_image_attributes['scale'] ) ? $product_image_attributes['scale'] : 'cover';
		$scale  = in_array( $scale, array( 'cover', 'contain', 'fill' ), true ) ? $scale : 'cover';
		$scale  = 'auto' === $aspect_ratio ? 'contain' : $scale;

		if ( ! empty( $product_image_attributes['height'] ) ) {
			$styles[] = sprintf( 'height:%s', $product_image_attributes['height'] );
		}

		if ( ! empty( $product_image_attributes['width'] ) ) {
			$styles[] = sprintf( 'width:%s', $product_image_attributes['width'] );
		}

		$styles[] = sprintf( 'object-fit:%s', $scale );

		if ( 'auto' !== $aspect_ratio ) {
			$styles[] = sprintf( 'aspect-ratio:%s', $aspect_ratio );
		}

		if ( ! empty( $product_image_attributes['style']['dimensions']['minHeight'] ) ) {
			$styles[] = sprintf( 'min-height:%s', $product_image_attributes['style']['dimensions']['minHeight'] );
		}

		return implode( ';', $styles ) . ';';
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
