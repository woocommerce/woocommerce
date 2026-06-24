<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

/**
 * ProductGallery class.
 */
class ProductGallery extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

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
	 * Return the dialog content.
	 *
	 * @param array $media_items An array of all media items of the product.
	 * @return string
	 */
	protected function render_dialog( $media_items ) {
		ob_start();
		?>
			<dialog
				data-wp-bind--open="context.isDialogOpen"
				data-wp-bind--inert="!context.isDialogOpen"
				data-wp-on--close="actions.closeDialog"
				data-wp-on--keydown="actions.onDialogKeyDown"
				data-wp-watch="callbacks.dialogStateChange"
				class="wc-block-product-gallery-dialog"
				role="dialog"
				aria-modal="true"
				aria-label="Product Gallery">
				<div class="wc-block-product-gallery-dialog__header">
					<button class="wc-block-product-gallery-dialog__close-button" data-wp-on--click="actions.closeDialog" aria-label="<?php echo esc_attr__( 'Close dialog', 'woocommerce' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
							<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
						</svg>
					</button>
				</div>
				<div class="wc-block-product-gallery-dialog__content">
					<?php foreach ( $media_items as $index => $media ) : ?>
						<?php if ( 'video' === ( $media['media_type'] ?? '' ) ) : ?>
							<?php echo $this->get_dialog_video_html( $media ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img
								data-image-id="<?php echo esc_attr( $media['id'] ); ?>"
								data-wp-watch="callbacks.toggleImageVisibility"
								src="<?php echo esc_url( $media['src'] ); ?>"
								srcset="<?php echo esc_attr( $media['srcset'] ); ?>"
								sizes="<?php echo esc_attr( $media['sizes'] ); ?>"
								decoding="async"
								alt="<?php echo esc_attr( $media['alt'] ); ?>" />
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</dialog>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get video HTML for the product gallery dialog.
	 *
	 * @param array $media Video media data.
	 * @return string
	 */
	private function get_dialog_video_html( $media ) {
		return ProductGalleryUtils::get_video_html(
			ProductGalleryUtils::get_video_attributes( $media, 'dialog' )
		);
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

		return $gallery_html;
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
		$post_id = $block->context['postId'] ?? '';
		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$media_items       = ProductGalleryUtils::get_all_media_items( $product );
		$default_media_ids = ProductGalleryUtils::get_media_ids(
			ProductMediaGallery::get_product_media_gallery_items_for_display( $product )
		);

		$number_of_media        = count( $default_media_ids );
		$classname              = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );
		$initial_media_id       = $number_of_media > 0 ? $default_media_ids[0] : -1;
		$classname_single_image = $number_of_media < 2 ? 'is-single-product-gallery-image' : '';
		$product_id             = strval( $product->get_id() );
		$fullsize_media_data    = ProductGalleryUtils::get_media_src_data( $media_items, 'full', $product->get_title() );
		$gallery_with_dialog    = $this->inject_dialog( $content, $this->render_dialog( $fullsize_media_data ) );
		$p                      = new \WP_HTML_Tag_Processor( $gallery_with_dialog );
		$html                   = $gallery_with_dialog;

		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wp-interactive', $this->get_full_block_name() );
			$p->set_attribute(
				'data-wp-context',
				wp_json_encode(
					array(
						'imageData'               => $default_media_ids,
						'isDialogOpen'            => false,
						'isDragging'              => false,
						'touchStartX'             => 0,
						'touchCurrentX'           => 0,
						'productId'               => $product_id,
						'selectedImageId'         => $initial_media_id,
						'thumbnailsOverflow'      => [
							'top'    => false,
							'bottom' => false,
							'left'   => false,
							'right'  => false,
						],
						// Next/Previous Buttons block context.
						'hideNextPreviousButtons' => $number_of_media <= 1,
						'isDisabledPrevious'      => true,
						'isDisabledNext'          => $number_of_media <= 1,
						'ariaLabelPrevious'       => __( 'Previous image', 'woocommerce' ),
						'ariaLabelNext'           => __( 'Next image', 'woocommerce' ),
					),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			);

			if ( $product->is_type( ProductType::VARIABLE ) ) {
				$formatted_variations_data = ProductGalleryUtils::get_product_variation_gallery_data( $product );

				if ( ! empty( $formatted_variations_data ) ) {
					wp_interactivity_config(
						'woocommerce',
						array(
							'products' => array(
								$product->get_id() => array(
									'image_id'   => $initial_media_id,
									'image_ids'  => $default_media_ids,
									'variations' => $formatted_variations_data,
								),
							),
						)
					);

					// Support legacy Add to Cart with Options block.
					$p->set_attribute( 'data-wp-init--watch-changes-on-add-to-cart-form', 'callbacks.watchForChangesOnAddToCartForm' );
					// Support blockified Add to Cart + Options block.
					$p->set_attribute( 'data-wp-watch', 'callbacks.listenToProductDataChanges' );
				}
			}

			$p->add_class( $classname );
			$p->add_class( $classname_single_image );
			$html = $p->get_updated_html();
		}

		return $html;
	}
}
