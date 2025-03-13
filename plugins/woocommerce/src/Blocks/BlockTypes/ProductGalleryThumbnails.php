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
	 * Generate the View All markup.
	 *
	 * @param int $remaining_thumbnails_count The number of thumbnails that are not displayed.
	 *
	 * @return string
	 */
	protected function generate_view_all_html( $remaining_thumbnails_count ) {
		$view_all_html = '<div class="wc-block-product-gallery-thumbnails__thumbnail__overlay" data-wp-on--click="actions.openDialog" data-wp-on--keydown="actions.onViewAllImagesKeyDown" tabindex="0">
			<span class="wc-block-product-gallery-thumbnails__thumbnail__remaining-thumbnails-count">+%1$s</span>
			<span class="wc-block-product-gallery-thumbnails__thumbnail__view-all">%2$s</span>
			</div>';

		return sprintf(
			$view_all_html,
			esc_html( $remaining_thumbnails_count ),
			esc_html__( 'View all', 'woocommerce' )
		);
	}

	/**
	 * Inject View All markup into the product thumbnail HTML.
	 *
	 * @param string $thumbnail_html The thumbnail HTML.
	 * @param string $view_all_html  The view all HTML.
	 *
	 * @return string
	 */
	protected function inject_view_all( $thumbnail_html, $view_all_html ) {

		// Find the position of the last </div>.
		$pos = strrpos( $thumbnail_html, '</div>' );

		if ( false !== $pos ) {
			// Inject the view_all_html at the correct position.
			$html = substr_replace( $thumbnail_html, $view_all_html, $pos, 0 );

			return $html;
		}

		return $thumbnail_html;
	}

	/**
	 * Check if the thumbnails should be limited.
	 *
	 * @param int $thumbnails_count     Current count of processed thumbnails.
	 * @param int $number_of_thumbnails Number of thumbnails configured to display.
	 *
	 * @return bool
	 */
	protected function limit_thumbnails( $thumbnails_count, $number_of_thumbnails ) {
		return $thumbnails_count > $number_of_thumbnails;
	}

	/**
	 * Check if View All markup should be displayed.
	 *
	 * @param int   $thumbnails_count       Current count of processed thumbnails.
	 * @param array $product_gallery_images Array of product gallery image HTML strings.
	 * @param int   $number_of_thumbnails   Number of thumbnails configured to display.
	 *
	 * @return bool
	 */
	protected function should_display_view_all( $thumbnails_count, $product_gallery_images, $number_of_thumbnails ) {
		return $thumbnails_count === $number_of_thumbnails &&
		count( $product_gallery_images ) > $number_of_thumbnails;
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

		$html                         = '';
		$default_number_of_thumbnails = 3;
		$number_of_thumbnails         = isset( $attributes['numberOfThumbnails'] ) && is_numeric( $attributes['numberOfThumbnails'] ) ? $attributes['numberOfThumbnails'] : $default_number_of_thumbnails;
		$number_of_images             = count( $product_gallery_images );
		// If the number of thumbnails is greater than the number of images, set the number of thumbnails to the number of images.
		// But not less than than 3 (default number of thumbnails).
		$thumbnails_layout          = max( min( $number_of_images, $number_of_thumbnails ), $default_number_of_thumbnails );
		$number_of_thumbnails_class = 'wc-block-product-gallery-thumbnails--number-of-thumbnails-' . $thumbnails_layout;
		$thumbnails_count           = 1;

		foreach ( $product_gallery_images as $product_gallery_image_html ) {
			// Limit the number of thumbnails only in the standard mode (and not in dialog).
			if ( $this->limit_thumbnails( $thumbnails_count, $number_of_thumbnails ) ) {
				break;
			}

			$remaining_thumbnails_count = $number_of_images - $number_of_thumbnails;

			// Display view all if this is the last visible thumbnail and there are more images.
			if ( $this->should_display_view_all( $thumbnails_count, $product_gallery_images, $number_of_thumbnails ) ) {
				$product_gallery_image_html = $this->inject_view_all( $product_gallery_image_html, $this->generate_view_all_html( $remaining_thumbnails_count ) );
			}

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

			++$thumbnails_count;
		}

		$allowed_html                    = wp_kses_allowed_html( 'post' );
		$allowed_html['img']['tabindex'] = true;

		return sprintf(
			'<div class="wc-block-product-gallery-thumbnails wp-block-woocommerce-product-gallery-thumbnails %1$s" style="%2$s" data-wp-interactive="woocommerce/product-gallery">
				%3$s
			</div>',
			esc_attr( $classes_and_styles['classes'] . ' ' . $number_of_thumbnails_class ),
			esc_attr( $classes_and_styles['styles'] ),
			wp_kses(
				$html,
				$allowed_html
			),
		);
	}
}
