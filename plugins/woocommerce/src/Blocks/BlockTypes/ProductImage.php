<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductImage class.
 */
class ProductImage extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-image';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * It is necessary to register and enqueues assets during the render phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	private function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'showProductLink'                  => true,
			'imageSizing'                      => 'single',
			'productId'                        => 'number',
			'isDescendentOfQueryLoop'          => 'false',
			'isDescendentOfSingleProductBlock' => 'false',
			'scale'                            => 'cover',
		);

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Render on Sale Badge.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $attributes Attributes.
	 * @return string
	 */
	private function render_on_sale_badge( $product, $attributes ) {
		if (
			! $product->is_on_sale()
			|| ! isset( $attributes['showSaleBadge'] )
			|| ( isset( $attributes['showSaleBadge'] ) && false === $attributes['showSaleBadge'] )
		) {
			return '';
		}

		$align = $attributes['saleBadgeAlign'] ?? 'right';

		$block = new \WP_Block(
			array(
				'blockName' => 'woocommerce/product-sale-badge',
				'attrs'     => array(
					'align' => $align,
				),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		return $block->render();
	}

	/**
	 * Render anchor.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param string      $on_sale_badge Return value from $render_image.
	 * @param string      $product_image Return value from $render_on_sale_badge.
	 * @param array       $attributes    Attributes.
	 * @param string      $inner_blocks_content Rendered HTML of inner blocks.
	 * @return string
	 */
	private function render_anchor( $product, $on_sale_badge, $product_image, $attributes, $inner_blocks_content ) {
		$product_permalink = $product->get_permalink();

		$is_link        = isset( $attributes['showProductLink'] ) ? $attributes['showProductLink'] : true;
		$href_attribute = $is_link ? sprintf( 'href="%s"', esc_url( $product_permalink ) ) : 'href="#" onclick="return false;"';
		$wrapper_style  = ! $is_link ? 'pointer-events: none; cursor: default;' : '';
		$directive      = $is_link ? 'data-wp-on--click="woocommerce/product-collection::actions.viewProduct"' : '';

		$inner_blocks_container = sprintf(
			'<div class="wc-block-components-product-image__inner-container">%s</div>',
			$inner_blocks_content
		);

		return sprintf(
			'<a %1$s style="%2$s" %3$s>%4$s%5$s%6$s</a>',
			$href_attribute,
			esc_attr( $wrapper_style ),
			$directive,
			$on_sale_badge,
			$product_image,
			$inner_blocks_container
		);
	}

	/**
	 * Render Image.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $attributes Parsed attributes.
	 * @param int|null    $image_id Optional image ID from context.
	 * @return string
	 */
	private function render_image( $product, $attributes, $image_id = null ) {
		$image_size = 'single' === $attributes['imageSizing'] ? 'woocommerce_single' : 'woocommerce_thumbnail';

		$image_style = '';

		if ( ! empty( $attributes['height'] ) ) {
			$image_style .= sprintf( 'height:%s;', $attributes['height'] );
		}
		if ( ! empty( $attributes['width'] ) ) {
			$image_style .= sprintf( 'width:%s;', $attributes['width'] );
		}
		if ( ! empty( $attributes['scale'] ) ) {
			$image_style .= sprintf( 'object-fit:%s;', $attributes['scale'] );
		}

		// Keep this aspect ratio for backward compatibility.
		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$image_style .= sprintf( 'aspect-ratio:%s;', $attributes['aspectRatio'] );
		}

		if ( ! empty( $attributes['style']['dimensions']['aspectRatio'] ) ) {
			$image_style .= sprintf( 'aspect-ratio:%s;', $attributes['style']['dimensions']['aspectRatio'] );
		}

		if ( ! empty( $attributes['style']['dimensions']['minHeight'] ) ) {
			$image_style .= sprintf( 'min-height:%s;', $attributes['style']['dimensions']['minHeight'] );
		}

		$featured_image_id          = (int) $product->get_image_id();
		$provided_image_id_is_valid = false;

		if ( $image_id ) {
			$gallery_image_ids          = ProductGalleryUtils::get_all_image_ids( $product );
			$available_image_ids        = array_merge( [ $featured_image_id ], $gallery_image_ids );
			$provided_image_id_is_valid = in_array( $image_id, $available_image_ids, true );
		}

		$target_image_id = $provided_image_id_is_valid ? $image_id : $featured_image_id;

		if ( ! $target_image_id ) {
			return wc_placeholder_img( $image_size, array( 'style' => $image_style ) );
		}

		$alt_text = get_post_meta( $target_image_id, '_wp_attachment_image_alt', true );

		$attr = array(
			'alt'           => empty( $alt_text ) ? $product->get_title() : $alt_text,
			'data-testid'   => 'product-image',
			'data-image-id' => $target_image_id,
			'style'         => $image_style,
		);

		return $provided_image_id_is_valid ? wp_get_attachment_image( $image_id, $image_size, false, $attr ) : $product->get_image( $image_size, $attr );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$this->asset_data_registry->add( 'isBlockTheme', wp_is_block_theme() );
		$this->asset_data_registry->add( 'placeholderImgSrcFullSize', wc_placeholder_img_src( 'woocommerce_single' ) );
	}

	/**
	 * Include and render the block
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$parsed_attributes  = $this->parse_attributes( $attributes );
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$post_id            = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$image_id           = isset( $block->context['imageId'] ) ? (int) $block->context['imageId'] : null;
		$product            = wc_get_product( $post_id );
		$aspect_ratio       = $parsed_attributes['aspectRatio'] ?? $parsed_attributes['style']['dimensions']['aspectRatio'] ?? 'auto';
		$aspect_ratio_class = 'wc-block-components-product-image--aspect-ratio-' . str_replace( '/', '-', $aspect_ratio );

		$classes = implode(
			' ',
			array_filter(
				array(
					'wc-block-components-product-image wc-block-grid__product-image',
					$aspect_ratio_class,
					esc_attr( $classes_and_styles['classes'] ),
				)
			)
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => esc_attr( $classes ),
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		if ( $product ) {
			$inner_content = $this->render_anchor(
				$product,
				$this->render_on_sale_badge( $product, $parsed_attributes ),
				$this->render_image( $product, $parsed_attributes, $image_id ),
				$attributes,
				$content
			);

			return sprintf(
				'<div %1$s>%2$s</div>',
				$wrapper_attributes,
				$inner_content
			);
		}

		return '';
	}
}
