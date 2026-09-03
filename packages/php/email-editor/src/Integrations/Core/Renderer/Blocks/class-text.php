<?php
/**
 * This file is part of the WooCommerce Email Editor package.
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
 * This renderer covers both core/paragraph, core/heading and core/site-title blocks.
 */
class Text extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Do not render empty blocks.
		if ( empty( trim( wp_strip_all_tags( $block_content ) ) ) ) {
			return '';
		}

		$block_content        = $this->adjustStyleAttribute( $block_content );
		$block_attributes     = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style' => array(),
			)
		);
		$html                 = new \WP_HTML_Tag_Processor( $block_content );
		$classes              = 'email-text-block';
		$alignment_from_class = null;
		if ( $html->next_tag() ) {
			/** @var string $block_classes */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$block_classes = $html->get_attribute( 'class' ) ?? '';
			$classes      .= ' ' . $block_classes;

			// Extract text alignment from has-text-align-* classes before they're potentially modified.
			$class_attr = (string) $block_classes;
			if ( false !== strpos( $class_attr, 'has-text-align-center' ) ) {
				$alignment_from_class = 'center';
			} elseif ( false !== strpos( $class_attr, 'has-text-align-right' ) ) {
				$alignment_from_class = 'right';
			} elseif ( false !== strpos( $class_attr, 'has-text-align-left' ) ) {
				$alignment_from_class = 'left';
			}

			// Remove the background and border classes because we render both on the wrapping table cell.
			Html_Processing_Helper::remove_wrapper_handled_classes( $html );
			$block_content = $html->get_updated_html();
		}

		$block_styles      = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing', 'border', 'background-color', 'color', 'typography' ) );
		$additional_styles = array(
			'min-width'  => '100%', // prevent Gmail App from shrinking the table on mobile devices.
			'word-break' => 'break-word', // prevent long unbreakable words (e.g. URLs) from expanding the table and breaking the email layout.
		);

		// Add fallback text color when no custom text color or preset text color is set.
		if ( empty( $block_styles['declarations']['color'] ) ) {
			$email_styles               = $rendering_context->get_theme_styles();
			$additional_styles['color'] = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000'; // Fallback for the text color.
		}

		$additional_styles['text-align'] = $rendering_context->get_default_text_align();
		if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // in this case, textAlign needs to be one of 'left', 'center', 'right'.
			$additional_styles['text-align'] = $rendering_context->resolve_text_align( $parsed_block['attrs']['textAlign'] );
		} elseif ( null !== $rendering_context->sanitize_text_align( $parsed_block['attrs']['align'] ?? null ) ) {
			$additional_styles['text-align'] = $rendering_context->resolve_text_align( $parsed_block['attrs']['align'] );
		} elseif ( null !== $alignment_from_class ) {
			$additional_styles['text-align'] = $alignment_from_class;
		}

		$block_styles = Styles_Helper::extend_block_styles( $block_styles, $additional_styles );

		$table_attrs = array(
			'style' => 'border-collapse: separate;', // Needed because of border radius.
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => $classes,
			'style' => $block_styles['css'],
			'align' => $additional_styles['text-align'],
		);

		return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
	}

	/**
	 * 1) We need to remove padding because we render padding on wrapping table cell
	 * 2) We also need to replace font-size to avoid clamp() because clamp() is not supported in many email clients.
	 * The font size values is automatically converted to clamp() when WP site theme is configured to use fluid layouts.
	 * Currently (WP 6.5), there is no way to disable this behavior.
	 *
	 * @param string $block_content Block content.
	 */
	private function adjustStyleAttribute( string $block_content ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );

		if ( $html->next_tag() ) {
			$element_style_value = $html->get_attribute( 'style' );
			$element_style       = isset( $element_style_value ) ? strval( $element_style_value ) : '';
			// Padding may contain value like 10px or variable like var(--spacing-10).
			$element_style = (string) preg_replace( '/padding[^:]*:.?[0-9a-z-()]+;?/', '', $element_style );

			// Margin is not supported in email renderer, so we need to remove it.
			$element_style = (string) preg_replace( '/margin[^:]*:.?[0-9a-z-()]+;?/', '', $element_style );

			// Remove border styles. We apply border styles on the wrapping table cell.
			$element_style = (string) preg_replace( '/border[^:]*:.?[0-9a-z-()#]+;?/', '', $element_style );

			// Remove the background color for the same reason we remove the background classes: it is
			// rendered on the wrapping table cell, and a translucent color left here paints twice.
			// This assumes the cell gets the same color from the block attributes, which is true for
			// anything the editor saves. Markup that carries an inline background without the matching
			// attribute loses it, the same way padding, margin, and border above already behave.
			// The lookbehind keeps the match anchored to the start of a property name, so a longer
			// property that merely ends in "background-color" (a custom property, say) is not cut in
			// half, which would leave its prefix fused to the following declaration. Property names
			// are case-insensitive in CSS and a colon may be surrounded by whitespace, so both are
			// matched — unlike the class names above, which the CSS inliner matches case-sensitively.
			$element_style = (string) preg_replace( '/(?<![a-z-])background-color\s*:\s*[^;]+;?/i', '', $element_style );

			// We define the font-size on the wrapper element, but we need to keep font-size definition here
			// to prevent CSS Inliner from adding a default value and overriding the value set by user, which is on the wrapper element.
			// The value provided by WP uses clamp() function which is not supported in many email clients.
			$element_style = (string) preg_replace( '/font-size:[^;]+;?/', 'font-size: inherit;', $element_style );
			/** @var string $element_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$html->set_attribute( 'style', esc_attr( $element_style ) );
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}
}
