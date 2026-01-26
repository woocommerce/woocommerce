<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce product image block for email.
 */
class Product_Image extends Abstract_Product_Block_Renderer {
	/**
	 * Render the product image block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$product = $this->get_product_from_context( $parsed_block );
		if ( ! $product ) {
			return '';
		}

		$attributes = $this->parse_attributes( $parsed_block['attrs'] ?? array() );

		$image_data = $this->get_product_image_data( $product, $attributes );
		if ( ! $image_data ) {
			return '';
		}

		$parsed_block = $this->add_image_size_when_missing( $parsed_block, $rendering_context );
		$attributes   = $this->parse_attributes( $parsed_block['attrs'] ?? array() );

		$image_html = $this->build_image_html( $image_data, $attributes, $rendering_context );

		$inner_blocks = $this->process_inner_blocks( $parsed_block, $product, $rendering_context );

		$combined_content = $this->create_overlay_structure(
			$image_html,
			$inner_blocks['badges'],
			$inner_blocks['other_content'],
			$inner_blocks['badge_alignment'],
			$product,
			$attributes['showProductLink']
		);

		return $this->apply_email_wrapper( $combined_content, $parsed_block, $rendering_context );
	}

	/**
	 * Process inner blocks (like sale badges) from block content.
	 * Handles special positioning for email compatibility.
	 *
	 * @param array             $parsed_block Parsed block.
	 * @param \WC_Product       $product Product object.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array Array with 'badges' and 'other_content' keys
	 */
	private function process_inner_blocks( array $parsed_block, \WC_Product $product, Rendering_Context $rendering_context ): array {
		$badges          = '';
		$other_content   = '';
		$badge_alignment = 'left';

		if ( ! empty( $parsed_block['innerBlocks'] ) ) {
			foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
				$inner_block['context']           = $inner_block['context'] ?? array();
				$inner_block['context']['postId'] = $product->get_id();

				if ( 'woocommerce/product-sale-badge' === $inner_block['blockName'] ) {
					$badges         .= $this->render_overlay_badge( $inner_block, $product, $rendering_context );
					$badge_alignment = $inner_block['attrs']['align'] ?? 'left';
				} else {
					$other_content .= render_block( $inner_block );
				}
			}
		}

		return array(
			'badges'          => $badges,
			'other_content'   => $other_content,
			'badge_alignment' => $badge_alignment,
		);
	}

	/**
	 * Render a sale badge with email-compatible overlay positioning.
	 *
	 * @param array             $badge_block Badge block data.
	 * @param \WC_Product       $product Product object.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function render_overlay_badge( array $badge_block, \WC_Product $product, Rendering_Context $rendering_context ): string {
		if ( ! $product->is_on_sale() ) {
			return '';
		}

		$sale_text        = apply_filters( 'woocommerce_sale_badge_text', __( 'Sale', 'woocommerce' ), $product );
		$badge_attributes = array_replace_recursive(
			array(
				'textColor'       => '#43454b',
				'backgroundColor' => '#fff',
				'style'           => array(
					'border'     => array(
						'width'  => '1px',
						'radius' => '4px',
						'color'  => '#43454b',
					),
					'spacing'    => array(
						'padding' => '4px 12px',
					),
					'typography' => array(
						'fontSize'      => '14px',
						'fontWeight'    => '600',
						'textTransform' => 'uppercase',
						'lineHeight'    => '1.5',
					),
				),
			),
			wp_parse_args( $badge_block['attrs'] ?? array() )
		);

		$block_styles = Styles_Helper::get_block_styles(
			$badge_attributes,
			$rendering_context,
			array( 'border', 'background-color', 'color', 'typography', 'spacing' )
		);

		$additional_styles = array(
			'display'    => 'inline-block',
			'width'      => 'fit-content',
			'box-sizing' => 'border-box',
		);

		$final_styles = Styles_Helper::extend_block_styles( $block_styles, $additional_styles );

		return sprintf(
			'<span class="wc-block-components-product-sale-badge__text" style="%s">%s</span>',
			esc_attr( $final_styles['css'] ),
			esc_html( $sale_text )
		);
	}

	/**
	 * Create overlay structure for email compatibility.
	 * Uses Faux Absolute Position with badge-below fallback for better cross-client support.
	 *
	 * @param string           $image_html Image HTML.
	 * @param string           $badges_html Badges HTML.
	 * @param string           $other_content Other inner content.
	 * @param string           $badge_alignment Badge alignment.
	 * @param \WC_Product|null $product Product object for link.
	 * @param bool             $show_product_link Whether to show product link.
	 * @return string
	 */
	private function create_overlay_structure(
		string $image_html,
		string $badges_html,
		string $other_content,
		string $badge_alignment,
		?\WC_Product $product = null,
		bool $show_product_link = false
	): string {
		if ( empty( $badges_html ) ) {
			$linked_image_html = $image_html;
			if ( $show_product_link && $product ) {
				$linked_image_html = $this->wrap_with_link( $image_html, $product );
			}
			return $linked_image_html . $other_content;
		}

		$image_width  = $this->extract_image_width( $image_html );
		$image_height = $this->extract_image_height( $image_html );

		$linked_image_html = $image_html;
		if ( $show_product_link && $product ) {
			$linked_image_html = $this->wrap_with_link( $image_html, $product );
		}

		$vml_side     = ( 'left' === $badge_alignment ) ? 'left' : 'right';
		$overlay_html = sprintf(
			'<table cellpadding="0" cellspacing="0" border="0" style="width: %dpx; height: %dpx; table-layout: fixed;">
				<tr>
					<td style="font-size: 0; line-height: 0; padding: 0; height: %dpx; width: %dpx;">
					<div style="max-height:0; position:relative; opacity:0.999;">
						<!--[if mso]>
						<v:rect xmlns:v="urn:schemas-microsoft-com:vml" stroked="false" filled="false" style="mso-width-percent: 1000; position:absolute; top:16px; ' . esc_attr( $vml_side ) . ':16px;">
						<v:textbox inset="0,0,0,0">
						<![endif]-->
						<div style="padding: 12px; box-sizing: border-box; display: inline-block; width: 100%%; text-align: %s;">
							%s
						</div>
						<!--[if mso]>
						</v:textbox>
						</v:rect>
						<![endif]-->
					</div>
						%s
					</td>
				</tr>
			</table>%s',
			$image_width,
			$image_height,
			$image_height,
			$image_width,
			$badge_alignment,
			$badges_html,
			$linked_image_html,
			$other_content
		);

		return $overlay_html;
	}

	/**
	 * Extract image width from HTML for positioning calculations.
	 *
	 * @param string $image_html Image HTML.
	 * @return float Image width in pixels.
	 */
	private function extract_image_width( string $image_html ): float {
		$width = ( new Dom_Document_Helper( $image_html ) )->get_attribute_value_by_tag_name( 'img', 'width' ) ?? '';
		if ( $width ) {
			return Styles_Helper::parse_value( $width );
		}

		return 300;
	}

	/**
	 * Extract image height from HTML for positioning calculations.
	 *
	 * @param string $image_html Image HTML.
	 * @return float Image height in pixels.
	 */
	private function extract_image_height( string $image_html ): float {
		$height = ( new Dom_Document_Helper( $image_html ) )->get_attribute_value_by_tag_name( 'img', 'height' ) ?? '';
		if ( $height ) {
			return Styles_Helper::parse_value( $height );
		}

		return 300;
	}


	/**
	 * When the width is not set, it's important to get it for the image to be displayed correctly.
	 * Based on the email Image renderer logic.
	 *
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array
	 */
	private function add_image_size_when_missing( array $parsed_block, Rendering_Context $rendering_context ): array {
		if ( isset( $parsed_block['attrs']['width'] ) ) {
			return $parsed_block;
		}

		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			$parsed_block['attrs']['width'] = '100%';
			return $parsed_block;
		}

		$parsed_block['attrs']['width'] = $rendering_context->get_layout_width_without_padding();

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
	 * @param array             $image_data Image data.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function build_image_html( array $image_data, array $attributes, Rendering_Context $rendering_context ): string {
		$style_parts = array(
			'max-width' => '100%',
			'height'    => 'auto',
			'display'   => 'block',
		);

		if ( ! empty( $attributes['scale'] ) ) {
			$style_parts['object-fit'] = $attributes['scale'];
		}

		if ( ! empty( $attributes['width'] ) ) {
			$style_parts['width'] = $attributes['width'];
		}

		if ( ! empty( $attributes['height'] ) ) {
			$style_parts['height'] = $attributes['height'];
		}

		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$style_parts['aspect-ratio'] = $attributes['aspectRatio'];
		}

		$width        = ! empty( $attributes['width'] ) ? Styles_Helper::parse_value( $attributes['width'] ) : $image_data['width'];
		$layout_width = Styles_Helper::parse_value( $rendering_context->get_layout_width_without_padding() );

		if ( $width > $layout_width ) {
			$width                = $layout_width;
			$aspect_ratio         = $image_data['height'] / $image_data['width'];
			$attributes['height'] = round( $width * $aspect_ratio ) . 'px';
		}

		$height = $image_data['height'];
		if ( ! empty( $attributes['height'] ) ) {
			$height = Styles_Helper::parse_value( $attributes['height'] );
		} elseif ( ! empty( $attributes['width'] ) && $image_data['width'] > 0 ) {
			$aspect_ratio = $image_data['height'] / $image_data['width'];
			$height       = round( $width * $aspect_ratio );
		}

		return sprintf(
			'<img class="email-editor-product-image skip-lazy" data-skip-lazy="1" loading="eager" decoding="auto" src="%s" alt="%s" style="%s" width="%d" height="%d" />',
			esc_url( $image_data['url'] ),
			esc_attr( $image_data['alt'] ),
			esc_attr( \WP_Style_Engine::compile_css( $style_parts, '' ) ),
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
		$width         = $parsed_block['attrs']['width'] ?? '';
		$wrapper_width = ( $width && '100%' !== $width ) ? $width : 'auto';
		$image_height  = $this->extract_image_height( $image_html ) . 'px';

		$wrapper_styles = array(
			'border-collapse' => 'separate',
			'width'           => $wrapper_width,
		);

		$cell_styles = array(
			'overflow'       => 'hidden',
			'vertical-align' => 'top',
		);

		$align                     = $parsed_block['attrs']['align'] ?? 'left';
		$cell_styles['text-align'] = $align;

		$outer_table_attrs = array(
			'style' => \WP_Style_Engine::compile_css(
				array(
					'border-collapse' => 'collapse',
					'border-spacing'  => '0px',
					'width'           => '100%',
					'height'          => $image_height,
				),
				''
			),
			'width' => '100%',
		);

		$outer_cell_attrs = array(
			'align' => $align,
		);

		$inner_table_attrs = array(
			'style'  => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width'  => $wrapper_width,
			'height' => $image_height,
		);

		$inner_cell_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $cell_styles, '' ),
		);

		$inner_content = Table_Wrapper_Helper::render_table_wrapper( $image_html, $inner_table_attrs, $inner_cell_attrs );
		return Table_Wrapper_Helper::render_table_wrapper( $inner_content, $outer_table_attrs, $outer_cell_attrs );
	}
}
