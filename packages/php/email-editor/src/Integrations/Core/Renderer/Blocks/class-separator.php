<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;
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
		$classes    = explode( ' ', (string) ( $attributes['className'] ?? '' ) );
		$color      = $this->get_color( $attributes, $parsed_block['email_attrs'] ?? array(), $rendering_context );

		if ( in_array( 'is-style-dots', $classes, true ) ) {
			// Match the editor: dots are 1.5× the inherited text size, spaced 2em apart.
			$theme_styles = $rendering_context->get_theme_styles();
			$font_size    = (string) ( $parsed_block['email_attrs']['font-size'] ?? $theme_styles['typography']['fontSize'] ?? '16px' );
			$font_size    = Styles_Helper::parse_value( Styles_Helper::convert_to_px( $font_size ) ?? '16px' );
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
					'style' => "color:{$color};font-family:serif;font-size:{$dots_size}px;line-height:1;mso-line-height-rule:exactly;letter-spacing:{$spacing}px;padding-left:{$spacing}px;",
				)
			);
		}

		$is_wide = in_array( 'is-style-wide', $classes, true );
		return Table_Wrapper_Helper::render_table_wrapper(
			'&nbsp;',
			array(
				'class' => Html2Text::HR_CLASS,
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
