<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	protected $api_version = '2';

	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'__experimentalBorder'   =>
			array(
				'radius'                          => true,
				'__experimentalSkipSerialization' => true,
			),
			'typography'             =>
			array(
				'fontSize'                        => true,
				'__experimentalSkipSerialization' => true,
			),
			'spacing'                =>
			array(
				'margin'                          => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-image',
		);
	}

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
			'showProductLink'         => true,
			'showSaleBadge'           => true,
			'saleBadgeAlign'          => 'right',
			'imageSizing'             => 'single',
			'productId'               => 'number',
			'isDescendentOfQueryLoop' => 'false',
			'scale'                   => 'cover',
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
		if ( ! $product->is_on_sale() || false === $attributes['showSaleBadge'] ) {
			return '';
		}

		$font_size = StyleAttributesUtils::get_font_size_class_and_style( $attributes );

		$on_sale_badge = sprintf(
			'
		<div class="wc-block-components-product-sale-badge wc-block-components-product-sale-badge--align-%s wc-block-grid__product-onsale %s" style="%s">
			<span aria-hidden="true">%s</span>
			<span class="screen-reader-text">Product on sale</span>
		</div>
	',
			esc_attr( $attributes['saleBadgeAlign'] ),
			isset( $font_size['class'] ) ? esc_attr( $font_size['class'] ) : '',
			isset( $font_size['style'] ) ? esc_attr( $font_size['style'] ) : '',
			esc_html__( 'Sale', 'woocommerce' )
		);
		return $on_sale_badge;
	}

	/**
	 * Render anchor.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param string      $on_sale_badge Return value from $render_image.
	 * @param string      $product_image Return value from $render_on_sale_badge.
	 * @param array       $attributes    Attributes.
	 * @return string
	 */
	private function render_anchor( $product, $on_sale_badge, $product_image, $attributes ) {
		$product_permalink = $product->get_permalink();

		$pointer_events = false === $attributes['showProductLink'] ? 'pointer-events: none;' : '';

		return sprintf(
			'<a href="%1$s" style="%2$s">%3$s %4$s</a>',
			$product_permalink,
			$pointer_events,
			$on_sale_badge,
			$product_image
		);
	}



	/**
	 * Render Image.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $attributes Parsed attributes.
	 * @return string
	 */
	private function render_image( $product, $attributes ) {
		$image_size = 'single' === $attributes['imageSizing'] ? 'woocommerce_single' : 'woocommerce_thumbnail';

		$image_style = 'max-width:none;';
		if ( ! empty( $attributes['height'] ) ) {
			$image_style .= sprintf( 'height:%s;', $attributes['height'] );
		}
		if ( ! empty( $attributes['width'] ) ) {
			$image_style .= sprintf( 'width:%s;', $attributes['width'] );
		}
		if ( ! empty( $attributes['scale'] ) ) {
			$image_style .= sprintf( 'object-fit:%s;', $attributes['scale'] );
		}
		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$image_style .= sprintf( 'aspect-ratio:%s;', $attributes['aspectRatio'] );
		}

		return $product->get_image(
			$image_size,
			array(
				'alt'         => $product->get_title(),
				'data-testid' => 'product-image',
				'style'       => $image_style,
			)
		);
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$this->asset_data_registry->add( 'isBlockThemeEnabled', wc_current_theme_is_fse_theme(), true );
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
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}
		$parsed_attributes = $this->parse_attributes( $attributes );

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( $product ) {
			return sprintf(
				'<div class="wc-block-components-product-image wc-block-grid__product-image %1$s" style="%2$s">
					%3$s
				</div>',
				esc_attr( $classes_and_styles['classes'] ),
				esc_attr( $classes_and_styles['styles'] ),
				$this->render_anchor(
					$product,
					$this->render_on_sale_badge( $product, $parsed_attributes ),
					$this->render_image( $product, $parsed_attributes ),
					$parsed_attributes
				)
			);

		}
	}
}
