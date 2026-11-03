<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a separator block as a table, since email clients style <hr> inconsistently.
 */
class Separator extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$attributes = $parsed_block['attrs'] ?? array();
		$class_name = $attributes['className'] ?? '';
		$color      = $this->get_color( $attributes, $parsed_block['email_attrs'] ?? array(), $rendering_context );

		if ( false !== strpos( $class_name, 'is-style-dots' ) ) {
			// Match the editor: dots are 1.5× the text size, spaced 2em apart.
			$theme_styles = $rendering_context->get_theme_styles();
			$font_size    = Styles_Helper::parse_value( Styles_Helper::convert_to_px( (string) ( $theme_styles['typography']['fontSize'] ?? '16px' ) ) ?? '16px' );
			$dots_size    = (int) round( 1.5 * $font_size );
			$spacing      = 2 * $dots_size;
			return Table_Wrapper_Helper::render_table_wrapper(
				'&#183;&#183;&#183;',
				array(
					'align' => 'center',
					'width' => '100%',
				),
				array(
					'align' => 'center',
					'style' => "color:{$color};font-family:serif;font-size:{$dots_size}px;line-height:1;letter-spacing:{$spacing}px;padding-left:{$spacing}px;",
				)
			);
		}

		$is_wide = false !== strpos( $class_name, 'is-style-wide' );
		return Table_Wrapper_Helper::render_table_wrapper(
			'&nbsp;',
			array(
				'align' => 'center',
				'width' => $is_wide ? '100%' : '100',
				'style' => 'width:' . ( $is_wide ? '100%' : '100px' ) . ';border-collapse:collapse;',
			),
			array(
				'style' => "border-top:2px solid {$color};font-size:0;line-height:0;height:0;mso-line-height-rule:exactly;",
			)
		);
	}

	/**
	 * Returns the separator color: the color picked in the editor, or the inherited text color.
	 *
	 * The editor stores the separator color as the background color.
	 *
	 * @param array             $attributes Block attributes.
	 * @param array             $email_attrs Email attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 */
	private function get_color( array $attributes, array $email_attrs, Rendering_Context $rendering_context ): string {
		$styles       = Styles_Helper::get_block_styles( $attributes, $rendering_context, array( 'background-color' ) );
		$theme_styles = $rendering_context->get_theme_styles();
		$color        = $styles['declarations']['background-color'] ?? $email_attrs['color'] ?? $theme_styles['color']['text'] ?? '#000000';

		return Html_Processing_Helper::sanitize_color( (string) $color );
	}
}
