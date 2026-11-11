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
	 * Widest the dots row may get, in px, so it fits a 320px phone screen after the email’s padding.
	 * The editor CSS in theme.json uses the same limit.
	 */
	private const MAX_DOTS_WIDTH = 240;

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
			$font_size = Styles_Helper::parse_value( Styles_Helper::get_static_font_size( (string) ( $parsed_block['email_attrs']['font-size'] ?? '' ) ) ?? '16px' );
			$dots_size = (int) round( 1.5 * $font_size );
			// The cell grows to fit its content, so cap the spacing to fit the column and a phone screen.
			// The row is 3 dots (counted as 1em each, a safe overestimate) plus 4 spacings.
			$max_width = min( self::MAX_DOTS_WIDTH, (int) Styles_Helper::parse_value( $parsed_block['email_attrs']['width'] ?? self::MAX_DOTS_WIDTH ) );
			$spacing   = max( 0, min( 2 * $dots_size, (int) floor( ( $max_width - 3 * $dots_size ) / 4 ) ) );
			return Table_Wrapper_Helper::render_table_wrapper(
				'&#183;&#183;&#183;',
				array(
					Html2Text::HR_ATTRIBUTE => '1',
					'align'                 => 'center',
					'width'                 => '100%',
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
				Html2Text::HR_ATTRIBUTE => '1',
				'align'                 => 'center',
				'width'                 => $is_wide ? '100%' : '100',
				// min-width stops the Gmail app from shrinking the table on mobile, as in the Text block.
				'style'                 => 'width:' . ( $is_wide ? '100%;min-width:100%' : '100px' ) . ';border-collapse:collapse;',
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
		$styles = Styles_Helper::get_block_styles( $attributes, $rendering_context, array( 'background-color' ) );
		$color  = (string) ( $styles['declarations']['background-color'] ?? $email_attrs['color'] ?? '#000000' );

		// Outlook can’t read alpha, so output plain 6-digit hex, blending a see-through color onto the
		// email background. That’s the email background, not a parent Group’s: preprocessors don’t
		// pass that down.
		$rgba = $this->parse_color( $color );
		if ( null === $rgba ) {
			return Html_Processing_Helper::sanitize_color( $color );
		}
		$theme_styles = $rendering_context->get_theme_styles();
		$background   = (string) ( $theme_styles['color']['background'] ?? '' );
		// A palette background is stored as a preset reference; look its slug up in the palette.
		if ( preg_match( '/^(?:var:preset\|color\||var\(--wp--preset--color--)([a-z0-9-]+)\)?$/i', $background, $matches ) ) {
			$background = $rendering_context->translate_slug_to_color( $matches[1] );
		}
		$background = $this->parse_color( $background ) ?? array( 255, 255, 255, 1.0 );
		$hex        = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			$hex .= sprintf( '%02x', (int) round( $rgba[ $i ] * $rgba[3] + $background[ $i ] * ( 1 - $rgba[3] ) ) );
		}
		return $hex;
	}

	/**
	 * Reads a hex or rgb()/rgba() color as [ red, green, blue, alpha ], or null for any other format.
	 *
	 * @param string $color Color.
	 * @return array{0: int, 1: int, 2: int, 3: float}|null
	 */
	private function parse_color( string $color ): ?array {
		$color = trim( $color );
		if ( preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color, $matches ) ) {
			$hex = $matches[1];
			if ( strlen( $hex ) <= 4 ) {
				$hex = preg_replace( '/(.)/', '$1$1', $hex ) ?? $hex;
			}
			return array(
				(int) hexdec( substr( $hex, 0, 2 ) ),
				(int) hexdec( substr( $hex, 2, 2 ) ),
				(int) hexdec( substr( $hex, 4, 2 ) ),
				8 === strlen( $hex ) ? hexdec( substr( $hex, 6, 2 ) ) / 255 : 1.0,
			);
		}
		if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(\d*\.?\d+)\s*)?\)$/i', $color, $matches ) ) {
			return array(
				min( 255, (int) $matches[1] ),
				min( 255, (int) $matches[2] ),
				min( 255, (int) $matches[3] ),
				isset( $matches[4] ) ? min( 1.0, (float) $matches[4] ) : 1.0,
			);
		}
		return null;
	}
}
