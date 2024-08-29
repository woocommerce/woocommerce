<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGallery class.
 */
class ProductGallery extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery';

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'postId' ];
	}

	/**
	 * Inject dialog into the product gallery HTML.
	 *
	 * @param string $gallery_html The gallery HTML.
	 * @param string $dialog_html  The dialog HTML.
	 *
	 * @return string
	 */
	protected function inject_dialog( $gallery_html, $dialog_html ) {

		// Find the position of the last </div>.
		$pos = strrpos( $gallery_html, '</div>' );

		if ( false !== $pos ) {
			// Inject the dialog_html at the correct position.
			$html = substr_replace( $gallery_html, $dialog_html, $pos, 0 );

			return $html;
		}
	}

	/**
	 * Return the dialog content.
	 *
	 * @return string
	 */
	protected function render_dialog() {
		$template_part = BlockTemplateUtils::get_template_part( 'product-gallery' );

		$parsed_template = parse_blocks(
			$template_part
		);

		$html = array_reduce(
			$parsed_template,
			function ( $carry, $item ) {
				return $carry . render_block( $item );
			},
			''
		);

		$html_processor = new \WP_HTML_Tag_Processor( $html );

		$html_processor->next_tag(
			array(
				'class_name' => 'wp-block-woocommerce-product-gallery',
			)
		);

		$html_processor->remove_attribute( 'data-wc-context' );

		$gallery_dialog = strtr(
			'
			<dialog data-wc-bind--open="context.isDialogOpen" role="dialog" aria-modal="true" aria-label="{{dialog_aria_label}}" hidden data-wc-bind--hidden="!context.isDialogOpen" data-wc-watch="callbacks.keyboardAccess" data-wc-watch--dialog-focus-trap="callbacks.dialogFocusTrap" data-wc-class--wc-block-product-gallery--dialog-open="context.isDialogOpen">
				<div class="wc-block-product-gallery-dialog__header">
				<div class="wc-block-product-galler-dialog__header-right">
					<button class="wc-block-product-gallery-dialog__close" data-wc-on--click="actions.closeDialog" aria-label="{{close_dialog_aria_label}}">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect width="24" height="24" rx="2"/>
							<path d="M13 11.8L19.1 5.5L18.1 4.5L12 10.7L5.9 4.5L4.9 5.5L11 11.8L4.5 18.5L5.5 19.5L12 12.9L18.5 19.5L19.5 18.5L13 11.8Z" fill="black"/>
						</svg>
					</button>
				</div>
				</div>
				<div class="wc-block-product-gallery-dialog__body">
					{{html}}
				</div>
			</dialog>',
			array(
				'{{html}}'                    => $html_processor->get_updated_html(),
				'{{dialog_aria_label}}'       => __( 'Product gallery', 'woocommerce' ),
				'{{close_dialog_aria_label}}' => __( 'Close Product Gallery dialog', 'woocommerce' ),
			)
		);
		return $gallery_dialog;
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
		$post_id                = $block->context['postId'] ?? '';
		$product_gallery_images = ProductGalleryUtils::get_product_gallery_images( $post_id, 'thumbnail', array() );
		$classname_single_image = '';
		// This is a temporary solution. We have to refactor this code when the block will have to be addable on every page/post https://github.com/woocommerce/woocommerce-blocks/issues/10882.
		global $product;

		if ( count( $product_gallery_images ) < 2 ) {
			// The gallery consists of a single image.
			$classname_single_image = 'is-single-product-gallery-image';
		}

		$number_of_thumbnails           = $block->attributes['thumbnailsNumberOfThumbnails'] ?? 0;
		$classname                      = $attributes['className'] ?? '';
		$dialog                         = isset( $attributes['mode'] ) && 'full' !== $attributes['mode'] ? $this->render_dialog() : '';
		$post_id                        = $block->context['postId'] ?? '';
		$product                        = wc_get_product( $post_id );
		$product_gallery_first_image    = ProductGalleryUtils::get_product_gallery_image_ids( $product, 1 );
		$product_gallery_first_image_id = reset( $product_gallery_first_image );
		$product_id                     = strval( $product->get_id() );

		$html = $this->inject_dialog( $content, $dialog );
		$p    = new \WP_HTML_Tag_Processor( $html );

		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-gallery' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$p->set_attribute(
				'data-wc-context',
				wp_json_encode(
					array(
						'selectedImage'                   => $product_gallery_first_image_id,
						'firstMainImageId'                => $product_gallery_first_image_id,
						'isDialogOpen'                    => false,
						'visibleImagesIds'                => ProductGalleryUtils::get_product_gallery_image_ids( $product, $number_of_thumbnails, true ),
						'dialogVisibleImagesIds'          => ProductGalleryUtils::get_product_gallery_image_ids( $product, null, false ),
						'mouseIsOverPreviousOrNextButton' => false,
						'productId'                       => $product_id,
						'elementThatTriggeredDialogOpening' => null,
					),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			);

			if ( $product->is_type( 'variable' ) ) {
				$p->set_attribute( 'data-wc-init--watch-changes-on-add-to-cart-form', 'callbacks.watchForChangesOnAddToCartForm' );
			}

			$p->add_class( $classname );
			$p->add_class( $classname_single_image );
			$html = $p->get_updated_html();
		}

		return $html;
	}
}
