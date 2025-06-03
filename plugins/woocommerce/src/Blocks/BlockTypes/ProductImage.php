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

		$image_id = $product->get_image_id();
		$alt_text = '';
		$title    = '';
		if ( $image_id ) {
			$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$title    = get_the_title( $image_id );
		}

		return $product->get_image(
			$image_size,
			array(
				'alt'         => empty( $alt_text ) ? $product->get_title() : $alt_text,
				'data-testid' => 'product-image',
				'style'       => $image_style,
				'title'       => $title,
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
		$this->asset_data_registry->add( 'isBlockTheme', wp_is_block_theme() );
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
		$parsed_attributes = $this->parse_attributes( $attributes );
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		
		$has_image_src = isset( $attributes['image'] ) &&  is_array( $attributes['image'] ) && ! empty( $attributes['image']['src'] );

		$product = null; // Initialize product as null

		if ( $has_image_src ) {
			$image_attr = $attributes['image'];
			$image_id   = isset( $image_attr['id'] ) ? (int) $image_attr['id'] : 0;
			$alt_text   = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';

			$image_style = 'max-width:none;';
			// Add style attributes from block settings (height, width, scale, aspectRatio)
			if ( ! empty( $attributes['height'] ) ) { $image_style .= sprintf( 'height:%s;', esc_attr( $attributes['height'] ) ); }
			if ( ! empty( $attributes['width'] ) ) { $image_style .= sprintf( 'width:%s;', esc_attr( $attributes['width'] ) ); }
			if ( ! empty( $attributes['scale'] ) ) { $image_style .= sprintf( 'object-fit:%s;', esc_attr( $attributes['scale'] ) ); }
			if ( ! empty( $attributes['style']['dimensions']['aspectRatio'] ) ) {
				$image_style .= sprintf( 'aspect-ratio:%s;', esc_attr( $attributes['style']['dimensions']['aspectRatio'] ) );
			} elseif ( ! empty( $attributes['aspectRatio'] ) ) {
				$image_style .= sprintf( 'aspect-ratio:%s;', esc_attr( $attributes['aspectRatio'] ) );
			}
			if ( ! empty( $attributes['style']['dimensions']['minHeight'] ) ) { 
				$image_style .= sprintf( 'min-height:%s;', esc_attr( $attributes['style']['dimensions']['minHeight'] ) ); 
			}

			$product_image_html = sprintf(
				'<img src="%1$s" alt="%2$s" %3$s %4$s style="%5$s" data-image-id="%6$s" data-testid="product-image" loading="lazy" decoding="async" />',
				esc_url( $image_attr['src'] ),
				esc_attr( $alt_text ),
				isset( $image_attr['srcset'] ) ? sprintf( 'srcset="%s"', esc_attr( $image_attr['srcset'] ) ) : '',
				isset( $image_attr['sizes'] ) ? sprintf( 'sizes="%s"', esc_attr( $image_attr['sizes'] ) ) : '',
				esc_attr( $image_style ),
				esc_attr( $image_id )
			);

			// Force is_link to false as we don't have product context for the permalink
			$attributes['showProductLink'] = false; 
			// The On Sale Badge is only supported as an inner block when using an image src
			$on_sale_badge_html = '';

		} else {
			$product = wc_get_product( $post_id );
			if ( $product ) {
				$product_image_html = $this->render_image( $product, $parsed_attributes );
				$on_sale_badge_html = $this->render_on_sale_badge( $product, $parsed_attributes );
			} else {
				return '';
			}
		}

		$classes = implode(
			' ',
			array_filter(
				array(
					'wc-block-components-product-image wc-block-grid__product-image',
					esc_attr( $classes_and_styles['classes'] ),
				)
			)
		);
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		if ( $product ) {
			$inner_content = $this->render_anchor(
				$product,
				$this->render_on_sale_badge( $product, $parsed_attributes ),
				$this->render_image( $product, $parsed_attributes ),
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
