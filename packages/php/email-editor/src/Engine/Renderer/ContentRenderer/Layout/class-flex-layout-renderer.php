<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * This class provides functionality to render inner blocks of a block that supports reduced flex layout.
 */
class Flex_Layout_Renderer {
	/**
	 * Render inner blocks in flex layout.
	 *
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	public function render_inner_blocks_in_layout( array $parsed_block, Settings_Controller $settings_controller ): string {
		$theme_styles    = $settings_controller->get_email_styles();
		$flex_gap        = $theme_styles['spacing']['blockGap'] ?? '0px';
		$flex_gap_number = $settings_controller->parse_number_from_string_with_pixels( $flex_gap );

		$margin_top = $parsed_block['email_attrs']['margin-top'] ?? '0px';
		$justify    = $parsed_block['attrs']['layout']['justifyContent'] ?? 'left';
		$styles     = wp_style_engine_get_styles( $parsed_block['attrs']['style'] ?? array() )['css'] ?? '';
		$styles    .= 'margin-top: ' . $margin_top . ';';
		$styles    .= 'text-align: ' . $justify;

		// MS Outlook doesn't support style attribute in divs so we conditionally wrap the buttons in a table and repeat styles.
		$output_html = sprintf(
			'<!--[if mso | IE]><table align="%2$s" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%%"><tr><td style="%1$s" ><![endif]-->
      <div style="%1$s"><table class="layout-flex-wrapper" style="display:inline-block"><tbody><tr>',
			esc_attr( $styles ),
			esc_attr( $justify )
		);

		$inner_blocks = $this->compute_widths_for_flex_layout( $parsed_block, $settings_controller, $flex_gap_number );

		foreach ( $inner_blocks as $key => $block ) {
			$styles = array();
			if ( $block['email_attrs']['layout_width'] ?? null ) {
				$styles['width'] = $block['email_attrs']['layout_width'];
			}
			if ( $key > 0 ) {
				$styles['padding-left'] = $flex_gap;
			}
			$output_html .= '<td class="layout-flex-item" style="' . esc_attr( \WP_Style_Engine::compile_css( $styles, '' ) ) . '">' . render_block( $block ) . '</td>';
		}
		$output_html .= '</tr></table></div>
    <!--[if mso | IE]></td></tr></table><![endif]-->';

		return $output_html;
	}

	/**
	 * Compute widths for blocks in flex layout.
	 *
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @param float               $flex_gap Flex gap.
	 * @return array
	 */
	private function compute_widths_for_flex_layout( array $parsed_block, Settings_Controller $settings_controller, float $flex_gap ): array {
		// When there is no parent width we can't compute widths so auto width will be used.
		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			return $parsed_block['innerBlocks'] ?? array();
		}
		$blocks_count     = count( $parsed_block['innerBlocks'] );
		$total_used_width = 0; // Total width assuming items without set width would consume proportional width.
		$parent_width     = $settings_controller->parse_number_from_string_with_pixels( $parsed_block['email_attrs']['width'] );
		$inner_blocks     = $parsed_block['innerBlocks'] ?? array();

		foreach ( $inner_blocks as $key => $block ) {
			$block_width_percent = ( $block['attrs']['width'] ?? 0 ) ? intval( $block['attrs']['width'] ) : 0;
			$block_width         = floor( $parent_width * ( $block_width_percent / 100 ) );
			// If width is not set, we assume it's 25% of the parent width.
			$total_used_width += $block_width ? $block_width : floor( $parent_width * ( 25 / 100 ) );

			if ( ! $block_width ) {
				$inner_blocks[ $key ]['email_attrs']['layout_width'] = null; // Will be rendered as auto.
				continue;
			}
			$inner_blocks[ $key ]['email_attrs']['layout_width'] = $this->get_width_without_gap( $block_width, $flex_gap, $block_width_percent ) . 'px';
		}

		// When there is only one block, or percentage is set reasonably we don't need to adjust and just render as set by user.
		if ( $blocks_count <= 1 || ( $total_used_width <= $parent_width ) ) {
			return $inner_blocks;
		}

		foreach ( $inner_blocks as $key => $block ) {
			$proportional_space_overflow   = $parent_width / $total_used_width;
			$block_width                   = $block['email_attrs']['layout_width'] ? $settings_controller->parse_number_from_string_with_pixels( $block['email_attrs']['layout_width'] ) : 0;
			$block_proportional_width      = $block_width * $proportional_space_overflow;
			$block_proportional_percentage = ( $block_proportional_width / $parent_width ) * 100;
			$inner_blocks[ $key ]['email_attrs']['layout_width'] = $block_width ? $this->get_width_without_gap( $block_proportional_width, $flex_gap, $block_proportional_percentage ) . 'px' : null;
		}
		return $inner_blocks;
	}

	/**
	 * How much of width we will strip to keep some space for the gap
	 * This is computed based on CSS rule used in the editor:
	 * For block with width set to X percent
	 * width: calc(X% - (var(--wp--style--block-gap) * (100 - X)/100)));
	 *
	 * @param float $block_width Block width in pixels.
	 * @param float $flex_gap Flex gap in pixels.
	 * @param float $block_width_percent Block width in percent.
	 * @return int
	 */
	private function get_width_without_gap( float $block_width, float $flex_gap, float $block_width_percent ): int {
		$width_gap_reduction = $flex_gap * ( ( 100 - $block_width_percent ) / 100 );
		return intval( floor( $block_width - $width_gap_reduction ) );
	}
}
