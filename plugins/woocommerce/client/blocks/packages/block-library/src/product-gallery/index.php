<?php
/**
 * Server-side rendering of the `woocommerce/product-gallery` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * Renders the `woocommerce/product-gallery` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered product gallery block.
 */
function render_block_woocommerce_product_gallery( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? '';
	$product = wc_get_product( $post_id );

	if ( ! $product instanceof \WC_Product ) {
		return '';
	}

	$image_ids         = ProductGalleryUtils::get_all_image_ids( $product );
	$default_image_ids = array_map( 'intval', ProductGalleryUtils::get_product_gallery_image_ids( $product ) );

	$images_html = '';
	foreach ( ProductGalleryUtils::get_image_src_data( $image_ids, 'full', $product->get_title() ) as $image ) {
		$id           = esc_attr( $image['id'] );
		$src          = esc_url( $image['src'] );
		$srcset       = esc_attr( $image['srcset'] );
		$sizes        = esc_attr( $image['sizes'] );
		$alt          = esc_attr( $image['alt'] );
		$images_html .= "<img data-image-id='{$id}' data-wp-watch='callbacks.toggleImageVisibility' src='{$src}' srcset='{$srcset}' sizes='{$sizes}' decoding='async' alt='{$alt}' />";
	}

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
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute values are escaped above when building $images_html.
				echo $images_html;
				?>
			</div>
		</dialog>
	<?php
	$dialog_html  = ob_get_clean();
	$gallery_html = $content;
	$position     = strrpos( $gallery_html, '</div>' );

	if ( false !== $position ) {
		$gallery_html = substr_replace( $gallery_html, $dialog_html, $position, 0 );
	}

	$number_of_images       = count( $default_image_ids );
	$classname              = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );
	$initial_image_id       = $number_of_images > 0 ? $default_image_ids[0] : -1;
	$classname_single_image = $number_of_images < 2 ? 'is-single-product-gallery-image' : '';
	$processor              = new \WP_HTML_Tag_Processor( $gallery_html );
	$html                   = $gallery_html;

	if ( $processor->next_tag() ) {
		$processor->set_attribute( 'data-wp-interactive', 'woocommerce/product-gallery' );
		$processor->set_attribute(
			'data-wp-context',
			wp_json_encode(
				array(
					'imageData'               => $default_image_ids,
					'isDialogOpen'            => false,
					'isDragging'              => false,
					'touchStartX'             => 0,
					'touchCurrentX'           => 0,
					'productId'               => strval( $product->get_id() ),
					'selectedImageId'         => $initial_image_id,
					'thumbnailsOverflow'      => array(
						'top'    => false,
						'bottom' => false,
						'left'   => false,
						'right'  => false,
					),
					'hideNextPreviousButtons' => $number_of_images <= 1,
					'isDisabledPrevious'      => true,
					'isDisabledNext'          => $number_of_images <= 1,
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
								'image_id'   => (int) $product->get_image_id(),
								'image_ids'  => $default_image_ids,
								'variations' => $formatted_variations_data,
							),
						),
					)
				);

				$processor->set_attribute( 'data-wp-init--watch-changes-on-add-to-cart-form', 'callbacks.watchForChangesOnAddToCartForm' );
				$processor->set_attribute( 'data-wp-watch', 'callbacks.listenToProductDataChanges' );
			}
		}

		$processor->add_class( $classname );
		$processor->add_class( $classname_single_image );
		$html = $processor->get_updated_html();
	}

	return $html;
}

/**
 * Registers the `woocommerce/product-gallery` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_gallery(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/product-gallery' ) ) {
		return;
	}

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_gallery',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_gallery' );
