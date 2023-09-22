<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryLargeImage extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-large-image';


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
		return [ 'postId', 'hoverZoom' ];
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		if ( $block->context['hoverZoom'] ) {
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

		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			$frontend_scripts = new \WC_Frontend_Scripts();
			$frontend_scripts::load_scripts();
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		$processor->next_tag();
		$processor->remove_class( 'wp-block-woocommerce-product-gallery-large-image' );
		$content = $processor->get_updated_html();

		[ $visible_main_image, $main_images ] = $this->get_main_images_html( $block->context, $post_id );

		$directives = $this->get_directives( $block->context );

		return strtr(
			'<div class="wc-block-product-gallery-large-image wp-block-woocommerce-product-gallery-large-image" {directives}>
				{visible_main_image}
				{main_images}
				<div class="wc-block-woocommerce-product-gallery-large-image__content">
					{content}
				</div>
			</div>',
			array(
				'{visible_main_image}' => $visible_main_image,
				'{main_images}'        => implode( ' ', $main_images ),
				'{content}'            => $content,
				'{directives}'         => array_reduce(
					array_keys( $directives ),
					function( $carry, $key ) use ( $directives ) {
						return $carry . ' ' . $key . '="' . esc_attr( $directives[ $key ] ) . '"';
					},
					''
				),
			)
		);
	}

	/**
	 * Get the main images html code. The first element of the array contains the HTML of the first image that is visible, the second element contains the HTML of the other images that are hidden.
	 *
	 * @param array $context The block context.
	 * @param int   $product_id The product id.
	 *
	 * @return array
	 */
	private function get_main_images_html( $context, $product_id ) {
		$attributes = array(
			'data-wc-bind--hidden' => '!selectors.woocommerce.isSelected',
			'hidden'               => true,
			'class'                => 'wc-block-woocommerce-product-gallery-large-image__image',

		);

		if ( $context['hoverZoom'] ) {
			$attributes['class']              .= ' wc-block-woocommerce-product-gallery-large-image__image--hoverZoom';
			$attributes['data-wc-bind--style'] = 'selectors.woocommerce.styles';
		}

		$main_images = ProductGalleryUtils::get_product_gallery_images(
			$product_id,
			'full',
			$attributes,
			'wc-block-woocommerce-product-gallery-large-image__container'
		);

		$visible_main_image           = array_shift( $main_images );
		$visible_main_image_processor = new \WP_HTML_Tag_Processor( $visible_main_image );
		$visible_main_image_processor->next_tag();
		$visible_main_image_processor->remove_attribute( 'hidden' );
		$visible_main_image = $visible_main_image_processor->get_updated_html();

		return array( $visible_main_image, $main_images );

	}

	/**
	 * Get directives for the hover zoom.
	 *
	 * @param array $block_context The block context.
	 *
	 * @return array
	 */
	private function get_directives( $block_context ) {
		if ( ! $block_context['hoverZoom'] ) {
			return array();
		}

		$context = array(
			'woocommerce' => array(
				'styles' => array(
					'transform'        => 'scale(1.0)',
					'transform-origin' => '',
				),
			),
		);

		return array(
			'data-wc-on--mousemove'  => 'actions.woocommerce.handleMouseMove',
			'data-wc-on--mouseleave' => 'actions.woocommerce.handleMouseLeave',
			'data-wc-on--click'      => 'actions.woocommerce.handleClick',
			'data-wc-context'        => wp_json_encode( $context, JSON_NUMERIC_CHECK ),
		);
	}
}
