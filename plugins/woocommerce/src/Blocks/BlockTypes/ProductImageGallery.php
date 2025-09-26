<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductImageGallery class.
 */
class ProductImageGallery extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-image-gallery';

	/**
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return array( 'query', 'queryId', 'postId' );
	}

	/**
	 * Enqueue assets specific to this block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 */
	protected function enqueue_assets( $attributes, $content, $block ) {
		parent::enqueue_assets( $attributes, $content, $block );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_legacy_assets' ], 20 );
	}

	/**
	 * Enqueue legacy assets when this block is used as we don't enqueue them for block themes anymore.
	 *
	 * Note: This enqueue logic is intentionally duplicated in ClassicTemplate.php
	 * to keep legacy blocks independent and allow for separate deprecation paths.
	 *
	 * @see https://github.com/woocommerce/woocommerce/pull/60223
	 */
	public function enqueue_legacy_assets() {
		// Legacy script dependencies for backward compatibility.
		wp_enqueue_script( 'zoom' );
		wp_enqueue_script( 'flexslider' );
		wp_enqueue_script( 'photoswipe-ui-default' );
		wp_enqueue_style( 'photoswipe-default-skin' );
		wp_enqueue_script( 'wc-single-product' );

		add_action(
			'wp_footer',
			function () {
				wc_get_template( 'single-product/photoswipe.php' );
			}
		);
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

		add_filter( 'woocommerce_single_product_zoom_enabled', '__return_true' );
		add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_true' );
		add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_true' );

		ob_start();
		woocommerce_show_product_sale_flash();
		$sale_badge_html = ob_get_clean();

		ob_start();
		woocommerce_show_product_images();
		$product_image_gallery_html = ob_get_clean();

		$product   = $previous_product;
		$classname = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );
		return sprintf(
			'<div class="wp-block-woocommerce-product-image-gallery %1$s">%2$s %3$s</div>',
			esc_attr( $classname ),
			$sale_badge_html,
			$product_image_gallery_html
		);
	}
}
