<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryPager class.
 */
class ProductGalleryPager extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-pager';

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
		return [ 'productGalleryClientId', 'pagerDisplayMode', 'thumbnailsNumberOfThumbnails', 'postId' ];
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
		$pager_display_mode = $block->context['pagerDisplayMode'] ?? '';

		if ( 'off' === $pager_display_mode ) {
			return null;
		}

		$number_of_thumbnails = $block->context['thumbnailsNumberOfThumbnails'] ?? 0;
		$classname            = $attributes['className'] ?? '';
		$wrapper_attributes   = get_block_wrapper_attributes( array( 'class' => trim( $classname ) ) );
		$post_id              = $block->context['postId'] ?? '';
		$product              = wc_get_product( $post_id );

		if ( $product ) {
			$product_gallery_images_ids = ProductGalleryUtils::get_product_gallery_image_ids( $product );
			$number_of_available_images = count( $product_gallery_images_ids );
			$number_of_thumbnails       = $number_of_thumbnails < $number_of_available_images ? $number_of_thumbnails : $number_of_available_images;

			if ( $number_of_thumbnails > 1 ) {
				$html = $this->render_pager( $product_gallery_images_ids, $pager_display_mode, $number_of_thumbnails );

				return sprintf(
					'<div %1$s data-wc-interactive=\'%3$s\'>
						%2$s
					</div>',
					$wrapper_attributes,
					$html,
					wp_json_encode( array( 'namespace' => 'woocommerce/product-gallery' ) )
				);
			}
			return '';
		}
	}

	/**
	 * Renders the pager for the product gallery.
	 *
	 * @param  array  $product_gallery_images_ids An array of image IDs for the product gallery.
	 * @param  string $pager_display_mode         The display mode for the pager.
	 * @param  int    $number_of_thumbnails       The number of thumbnails to display in the pager.
	 * @return string|null The rendered pager HTML, or null if the pager should not be displayed.
	 */
	private function render_pager( $product_gallery_images_ids, $pager_display_mode, $number_of_thumbnails ) {
		if ( $number_of_thumbnails < 2 || 'off' === $pager_display_mode ) {
			return null;
		}

		return $this->render_pager_pages( $product_gallery_images_ids, $number_of_thumbnails, $pager_display_mode );
	}

	/**
	 * Renders the pager pages for the product gallery.
	 *
	 * @param  array  $product_gallery_images_ids An array of image IDs for the product gallery.
	 * @param  int    $number_of_thumbnails The number of thumbnails to display in the pager.
	 * @param  string $pager_display_mode The display mode for the pager. Defaults to 'dots'.
	 * @return string The rendered pager pages HTML.
	 */
	private function render_pager_pages( $product_gallery_images_ids, $number_of_thumbnails, $pager_display_mode = 'dots' ) {
		$html = '';

		foreach ( $product_gallery_images_ids as $key => $product_gallery_image_id ) {
			if ( $key >= $number_of_thumbnails ) {
				break;
			}

			$is_first_pager_item = 0 === $key;
			$pager_item          = sprintf(
				'<li class="wc-block-product-gallery-pager__pager-item %2$s"><button aria-pressed="%3$s" data-wc-bind--aria-pressed="state.pagerButtonPressed">%1$s</button></li>',
				'dots' === $pager_display_mode ? $this->get_dot_icon( $is_first_pager_item ) : $key + 1,
				$is_first_pager_item ? 'wc-block-product-gallery-pager__pager-item--is-active' : '',
				$is_first_pager_item ? 'true' : 'false'
			);

			$p = new \WP_HTML_Tag_Processor( $pager_item );

			if ( $p->next_tag() ) {
				$p->set_attribute(
					'data-wc-context',
					wp_json_encode(
						array( 'imageId' => strval( $product_gallery_image_id ) ),
					)
				);
				$p->set_attribute(
					'data-wc-on--click',
					'actions.selectImage'
				);
				$p->set_attribute(
					'data-wc-class--wc-block-product-gallery-pager__pager-item--is-active',
					'state.isSelected'
				);
				$html .= $p->get_updated_html();
			}
		}

		return sprintf(
			'<ul class="wc-block-product-gallery-pager__pager">
				%1$s
			</ul>',
			$html
		);
	}

	/**
	 * Generates an SVG dot icon with the specified opacity.
	 *
	 * @param bool $is_active Whether the dot icon should be in active state. Defaults to false.
	 * @return string The SVG dot icon HTML.
	 */
	private function get_dot_icon( $is_active = false ) {
		$initial_opacity = $is_active ? '1' : '0.2';
		return sprintf(
			'<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<circle cx="6" cy="6" r="6" fill="black" fill-opacity="%1$s" data-wc-bind--fill-opacity="state.pagerDotFillOpacity" />
			</svg>',
			$initial_opacity
		);
	}
}
