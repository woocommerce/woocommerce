<?php
/**
 * Server-side rendering of the `woocommerce/product-gallery-large-image` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

$post_id = $block->context['postId'] ?? null;

if ( ! isset( $post_id ) ) {
	return;
}

global $product;

$previous_product = $product;
$product          = wc_get_product( $post_id );

if ( ! $product instanceof \WC_Product ) {
	$product = $previous_product;

	return;
}

$context = array_merge(
	array(
		'hoverZoom'         => true,
		'fullScreenOnClick' => true,
	),
	(array) $block->context
);

$update_single_image = static function ( string $image_html, array $context, int $index ): string {
	$processor = new \WP_HTML_Tag_Processor( $image_html );

	if ( $processor->next_tag( 'a' ) ) {
		$processor->remove_attribute( 'onclick' );
		$processor->remove_attribute( 'style' );
		$processor->set_attribute( 'tabindex', '-1' );
	} else {
		$processor = new \WP_HTML_Tag_Processor( $image_html );
	}

	if ( ! $processor->next_tag( 'img' ) ) {
		return $image_html;
	}

	$processor->set_attribute( 'tabindex', '-1' );
	$processor->set_attribute( 'draggable', 'false' );
	$processor->set_attribute( 'data-wp-watch', 'callbacks.toggleImageVisibility' );
	$processor->set_attribute( 'data-wp-on--click', 'actions.onViewerClick' );
	$processor->set_attribute( 'data-wp-on--touchstart', 'actions.onTouchStart' );
	$processor->set_attribute( 'data-wp-on--touchmove', 'actions.onTouchMove' );
	$processor->set_attribute( 'data-wp-on--touchend', 'actions.onTouchEnd' );

	if ( 0 === $index ) {
		$processor->set_attribute( 'fetchpriority', 'high' );
		$processor->set_attribute( 'loading', 'eager' );
	} else {
		$processor->set_attribute( 'fetchpriority', 'low' );
		$processor->set_attribute( 'loading', 'lazy' );
	}

	$img_classes = 'wc-block-woocommerce-product-gallery-large-image__image';

	if ( ! empty( $context['fullScreenOnClick'] ) ) {
		$img_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--full-screen-on-click';

		$processor->set_attribute( 'data-wp-on--click', 'actions.openDialog' );
	}

	if ( ! empty( $context['hoverZoom'] ) ) {
		$img_classes .= ' wc-block-woocommerce-product-gallery-large-image__image--hoverZoom';

		$processor->set_attribute( 'data-wp-on--mousemove', 'actions.startZoom' );
		$processor->set_attribute( 'data-wp-on--mouseleave', 'actions.resetZoom' );
	}

	$processor->add_class( $img_classes );

	return $processor->get_updated_html();
};

$images_html       = '';
$inner_blocks_html = '';

foreach ( $block->inner_blocks as $inner_block ) {
	if ( 'woocommerce/product-image' === $inner_block->name ) {
		$image_data = ProductGalleryUtils::get_product_gallery_image_data( $product, 'woocommerce_single' );

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
				<?php foreach ( $image_data as $index => $image ) : ?>
					<li
						class="wc-block-product-gallery-large-image__wrapper"
					>
						<?php
						$image_html = (
							new \WP_Block(
								$inner_block->parsed_block,
								array_merge( $context, array( 'imageId' => $image['id'] ) )
							)
						)->render( array( 'dynamic' => true ) );

						echo $update_single_image( $image_html, $context, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php
		$images_html .= wp_interactivity_process_directives( ob_get_clean() );
		continue;
	}

	if ( 'woocommerce/product-gallery-large-image-next-previous' === $inner_block->name ) {
		$product_gallery_image_count = ProductGalleryUtils::get_product_gallery_image_count( $product );

		if ( $product_gallery_image_count <= 1 ) {
			continue;
		}
	}

	$inner_blocks_html .= (
		new \WP_Block(
			$inner_block->parsed_block,
			array_merge(
				$context,
				array( 'iapi/provider' => 'woocommerce/product-gallery' )
			)
		)
	)->render( array( 'dynamic' => true ) );
}

ob_start();
?>
	<div class="wc-block-product-gallery-large-image wp-block-woocommerce-product-gallery-large-image">
		<?php echo $images_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="wc-block-product-gallery-large-image__inner-blocks">
			<?php echo $inner_blocks_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
<?php

echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
