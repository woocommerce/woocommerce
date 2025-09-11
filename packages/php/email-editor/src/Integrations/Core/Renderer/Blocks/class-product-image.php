<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce product image block for email.
 */
class Product_Image extends Abstract_Block_Renderer {
	/**
	 * Render the product image block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$post_id = $parsed_block['context']['postId'] ?? 0;
		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return '';
		}

		$attributes = $this->parse_attributes( $parsed_block['attrs'] ?? array() );

		$image_data = $this->get_product_image_data( $product, $attributes );
		if ( ! $image_data ) {
			return '';
		}

		$parsed_block = $this->add_image_size_when_missing( $parsed_block, $image_data['url'] );
		$attributes   = $this->parse_attributes( $parsed_block['attrs'] ?? array() );

		$image_html = $this->build_image_html( $image_data, $attributes );

		$inner_blocks = $this->process_inner_blocks( $block_content, $parsed_block, $product );

		$combined_content = $this->create_overlay_structure(
			$image_html,
			$inner_blocks['badges'],
			$inner_blocks['other_content']
		);

		if ( $attributes['showProductLink'] ) {
			$combined_content = $this->wrap_with_link( $combined_content, $product );
		}

		return $this->apply_email_wrapper( $combined_content, $parsed_block, $rendering_context );
	}

	/**
	 * Process inner blocks (like sale badges) from block content.
	 * Handles special positioning for email compatibility.
	 *
	 * @param string      $block_content Original block content.
	 * @param array       $parsed_block Parsed block.
	 * @param \WC_Product $product Product object.
	 * @return array Array with 'badges' and 'other_content' keys
	 */
	private function process_inner_blocks( string $block_content, array $parsed_block, \WC_Product $product ): array {
		$badges        = '';
		$other_content = '';

		if ( ! empty( $parsed_block['innerBlocks'] ) ) {
			foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
				$inner_block['context']           = $inner_block['context'] ?? array();
				$inner_block['context']['postId'] = $product->get_id();

				if ( 'woocommerce/product-sale-badge' === $inner_block['blockName'] ) {
					$badges .= $this->render_overlay_badge( $inner_block, $product );
				} else {
					$other_content .= render_block( $inner_block );
				}
			}
		}

		return array(
			'badges'        => $badges,
			'other_content' => $other_content,
		);
	}

	/**
	 * Render a sale badge with email-compatible overlay positioning.
	 *
	 * @param array       $badge_block Badge block data.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function render_overlay_badge( array $badge_block, \WC_Product $product ): string {
		if ( ! $product->is_on_sale() ) {
			return '';
		}

		$attributes = $badge_block['attrs'] ?? array();
		$sale_text  = apply_filters( 'woocommerce_sale_badge_text', __( 'Sale', 'woocommerce' ), $product );

		$badge_styles = array(
			'font-size'      => '0.875em',
			'padding'        => '0.25em 0.75em',
			'display'        => 'inline-block',
			'width'          => 'fit-content',
			'border'         => '1px solid #43454b',
			'border-radius'  => '4px',
			'box-sizing'     => 'border-box',
			'color'          => '#43454b',
			'background'     => '#fff',
			'text-align'     => 'center',
			'text-transform' => 'uppercase',
			'font-weight'    => '600',
			'z-index'        => '9',
			'margin'         => '4px',
		);

		if ( ! empty( $attributes['style'] ) ) {
			$custom_styles = $this->parse_badge_styles( $attributes['style'] );
			$badge_styles  = array_merge( $badge_styles, $custom_styles );
		}

		$style_attr = \WP_Style_Engine::compile_css( $badge_styles, '' );

		return sprintf(
			'<span style="%s">%s</span>',
			esc_attr( $style_attr ),
			esc_html( $sale_text )
		);
	}

	/**
	 * Create overlay structure for email compatibility.
	 * Uses background image technique to overlay badge on image.
	 *
	 * @param string $image_html Image HTML.
	 * @param string $badges_html Badges HTML.
	 * @param string $other_content Other inner content.
	 * @return string
	 */
	private function create_overlay_structure( string $image_html, string $badges_html, string $other_content ): string {
		if ( empty( $badges_html ) ) {
			return $image_html . $other_content;
		}

		$image_src    = $this->extract_image_src( $image_html );
		$image_width  = $this->extract_image_width( $image_html );
		$image_height = $this->extract_image_height( $image_html );

		if ( $image_src ) {
			$overlay_html = sprintf(
				'<table style="border-collapse: collapse; width: %dpx; height: %dpx; background-image: url(%s); background-size: cover; background-position: center;">
					<tr>
						<td style="vertical-align: top; text-align: right; padding: 8px;">
							%s
						</td>
					</tr>
				</table>%s',
				$image_width,
				$image_height,
				esc_url( $image_src ),
				$badges_html,
				$other_content
			);
		} else {
			$overlay_html = sprintf(
				'<div style="position: relative; display: inline-block;">
					%s
					<div style="position: absolute; top: 8px; right: 8px;">
						%s
					</div>
				</div>%s',
				$image_html,
				$badges_html,
				$other_content
			);
		}

		return $overlay_html;
	}

	/**
	 * Extract image src from HTML.
	 *
	 * @param string $image_html Image HTML.
	 * @return string|null Image src URL.
	 */
	private function extract_image_src( string $image_html ): ?string {
		if ( preg_match( '/src=["\']([^"\']+)["\']/', $image_html, $matches ) ) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * Extract image width from HTML for positioning calculations.
	 *
	 * @param string $image_html Image HTML.
	 * @return int Image width in pixels.
	 */
	private function extract_image_width( string $image_html ): int {
		if ( preg_match( '/width=["\']?(\d+)["\']?/i', $image_html, $matches ) ) {
			return (int) $matches[1];
		}

		return 300;
	}

	/**
	 * Extract image height from HTML for positioning calculations.
	 *
	 * @param string $image_html Image HTML.
	 * @return int Image height in pixels.
	 */
	private function extract_image_height( string $image_html ): int {
		if ( preg_match( '/height=["\']?(\d+)["\']?/i', $image_html, $matches ) ) {
			return (int) $matches[1];
		}

		return 300;
	}

	/**
	 * Parse badge-specific styles from block attributes.
	 *
	 * @param array $style_block Style block from attributes.
	 * @return array
	 */
	private function parse_badge_styles( array $style_block ): array {
		$styles = array();

		if ( ! empty( $style_block['color'] ) ) {
			$color = $style_block['color'];
			if ( ! empty( $color['text'] ) ) {
				$styles['color'] = $color['text'];
			}
			if ( ! empty( $color['background'] ) ) {
				$styles['background-color'] = $color['background'];
			}
		}

		if ( ! empty( $style_block['typography'] ) ) {
			$typography = $style_block['typography'];
			if ( ! empty( $typography['fontSize'] ) ) {
				$styles['font-size'] = $typography['fontSize'];
			}
			if ( ! empty( $typography['fontWeight'] ) ) {
				$styles['font-weight'] = $typography['fontWeight'];
			}
		}

		return $styles;
	}

	/**
	 * When the width is not set, it's important to get it for the image to be displayed correctly.
	 * Based on the email Image renderer logic.
	 *
	 * @param array  $parsed_block Parsed block.
	 * @param string $image_url Image URL.
	 * @return array
	 */
	private function add_image_size_when_missing( array $parsed_block, string $image_url ): array {
		if ( isset( $parsed_block['attrs']['width'] ) ) {
			return $parsed_block;
		}

		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			$parsed_block['attrs']['width'] = '100%';
			return $parsed_block;
		}

		$container_width                = Styles_Helper::parse_value( $parsed_block['email_attrs']['width'] );
		$parsed_block['attrs']['width'] = "{$container_width}px";

		return $parsed_block;
	}

	/**
	 * Parse block attributes with defaults.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	private function parse_attributes( array $attributes ): array {
		return wp_parse_args(
			$attributes,
			array(
				'showProductLink' => true,
				'imageSizing'     => 'single',
				'scale'           => 'cover',
				'showSaleBadge'   => false,
				'saleBadgeAlign'  => 'right',
			)
		);
	}

	/**
	 * Get product image data.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $attributes Parsed attributes.
	 * @return array|null
	 */
	private function get_product_image_data( \WC_Product $product, array $attributes ): ?array {
		$image_size = 'single' === $attributes['imageSizing'] ? 'woocommerce_single' : 'woocommerce_thumbnail';
		$image_id   = (int) $product->get_image_id();

		if ( ! $image_id ) {
			$placeholder = wc_placeholder_img_src( $image_size );
			return array(
				'url'    => $placeholder,
				'alt'    => $product->get_name(),
				'width'  => 300,
				'height' => 300,
			);
		}

		$image_url = wp_get_attachment_image_url( $image_id, $image_size );
		if ( ! $image_url ) {
			return null;
		}

		$alt_text   = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$image_meta = wp_get_attachment_metadata( $image_id );

		return array(
			'url'    => $image_url,
			'alt'    => $alt_text ? $alt_text : $product->get_name(),
			'width'  => $image_meta['width'] ?? 300,
			'height' => $image_meta['height'] ?? 300,
		);
	}

	/**
	 * Build email-compatible image HTML.
	 *
	 * @param array $image_data Image data.
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function build_image_html( array $image_data, array $attributes ): string {
		$style_parts = array(
			'max-width: 100%',
			'height: auto',
			'display: block',
		);

		if ( ! empty( $attributes['scale'] ) ) {
			$style_parts[] = sprintf( 'object-fit: %s', esc_attr( $attributes['scale'] ) );
		}

		if ( ! empty( $attributes['width'] ) ) {
			$style_parts[] = sprintf( 'width: %s', esc_attr( $attributes['width'] ) );
		}
		if ( ! empty( $attributes['height'] ) ) {
			$style_parts[] = sprintf( 'height: %s', esc_attr( $attributes['height'] ) );
		}

		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$style_parts[] = sprintf( 'aspect-ratio: %s', esc_attr( $attributes['aspectRatio'] ) );
		}

		$width = ! empty( $attributes['width'] ) ? Styles_Helper::parse_value( $attributes['width'] ) : $image_data['width'];

		$height = $image_data['height'];
		if ( ! empty( $attributes['height'] ) ) {
			$height = Styles_Helper::parse_value( $attributes['height'] );
		} elseif ( ! empty( $attributes['width'] ) && $image_data['width'] > 0 ) {
			$aspect_ratio = $image_data['height'] / $image_data['width'];
			$height       = round( $width * $aspect_ratio );
		}

		return sprintf(
			'<img src="%s" alt="%s" style="%s" width="%d" height="%d" />',
			esc_url( $image_data['url'] ),
			esc_attr( $image_data['alt'] ),
			implode( '; ', $style_parts ),
			$width,
			$height
		);
	}

	/**
	 * Wrap image with product link.
	 *
	 * @param string      $image_html Image HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function wrap_with_link( string $image_html, \WC_Product $product ): string {
		$product_url = $product->get_permalink();

		return sprintf(
			'<a href="%s" style="display: block; text-decoration: none;">%s</a>',
			esc_url( $product_url ),
			$image_html
		);
	}


	/**
	 * Apply email-compatible table wrapper (similar to Image renderer).
	 *
	 * @param string            $image_html Image HTML.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function apply_email_wrapper( string $image_html, array $parsed_block, Rendering_Context $rendering_context ): string {
		$width         = $rendering_context->get_layout_width_without_padding();
		$wrapper_width = ( $width && '100%' !== $width ) ? $width : 'auto';

		$wrapper_styles = array(
			'border-collapse' => 'separate',
			'width'           => $wrapper_width,
		);

		$cell_styles = array(
			'overflow' => 'hidden',
		);

		$align                     = $parsed_block['attrs']['align'] ?? 'left';
		$cell_styles['text-align'] = $align;

		$outer_table_attrs = array(
			'style' => \WP_Style_Engine::compile_css(
				array(
					'border-collapse' => 'collapse',
					'border-spacing'  => '0px',
					'width'           => '100%',
				),
				''
			),
			'width' => '100%',
		);

		$outer_cell_attrs = array(
			'align' => $align,
		);

		$inner_table_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width' => $wrapper_width,
		);

		$inner_cell_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $cell_styles, '' ),
		);

		$inner_content = Table_Wrapper_Helper::render_table_wrapper( $image_html, $inner_table_attrs, $inner_cell_attrs );
		return Table_Wrapper_Helper::render_table_wrapper( $inner_content, $outer_table_attrs, $outer_cell_attrs );
	}
}
