<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a table block.
 */
class Table extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Extract table content from figure wrapper if present.
		$table_content = $this->extract_table_from_figure( $block_content );

		// Do not render empty blocks or tables with no content.
		$stripped_content = trim( wp_strip_all_tags( $table_content ) );
		if ( empty( $stripped_content ) ) {
			return '';
		}

		// Check for empty table structures - tables with no th or td elements.
		if ( ! preg_match( '/<(th|td)/i', $table_content ) ) {
			return '';
		}

		$block_attributes = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'textAlign' => 'left',
				'style'     => array(),
			)
		);

		$html    = new \WP_HTML_Tag_Processor( $table_content );
		$classes = 'email-table-block';

		if ( $html->next_tag() ) {
			/** @var string $block_classes */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$block_classes = $html->get_attribute( 'class' ) ?? '';
			$classes      .= ' ' . $block_classes;
			// Remove has-background to prevent double padding applied for wrapper and inner element.
			$block_classes = str_replace( 'has-background', '', $block_classes );
			// Remove border related classes because we handle border on wrapping table cell.
			$block_classes = preg_replace( '/has-[a-z-]*border[a-z-]*/', '', $block_classes );
			$block_classes = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $block_classes );
			$block_classes = preg_replace( '/\s+/', ' ', $block_classes ); // Clean up multiple spaces.
			/** @var string $block_classes */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$html->set_attribute( 'class', trim( $block_classes ) );
			$table_content = $html->get_updated_html();
		}

		// Also remove classes from the wrapper classes.
		$classes = str_replace( 'has-background', '', $classes );
		$classes = preg_replace( '/has-[a-z-]*border[a-z-]*/', '', $classes );
		$classes = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $classes );
		$classes = preg_replace( '/\s+/', ' ', $classes ); // Clean up multiple spaces.
		$classes = trim( $classes );

		$block_styles      = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing', 'border', 'background-color', 'color', 'typography' ) );
		$additional_styles = array(
			'min-width' => '100%', // Prevent Gmail App from shrinking the table on mobile devices.
		);

		// Add fallback text color when no custom text color or preset text color is set.
		if ( empty( $block_styles['declarations']['color'] ) ) {
			$email_styles               = $rendering_context->get_theme_styles();
			$additional_styles['color'] = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000'; // Fallback for the text color.
		}

		$additional_styles['text-align'] = 'left';
		if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // In this case, textAlign needs to be one of 'left', 'center', 'right'.
			$additional_styles['text-align'] = $parsed_block['attrs']['textAlign'];
		} elseif ( in_array( $parsed_block['attrs']['align'] ?? null, array( 'left', 'center', 'right' ), true ) ) {
			$additional_styles['text-align'] = $parsed_block['attrs']['align'];
		}

		$block_styles = Styles_Helper::extend_block_styles( $block_styles, $additional_styles );

		// Process the table content to ensure email compatibility BEFORE wrapping.
		$table_content = $this->process_table_content( $table_content, $parsed_block, $rendering_context );

		$table_attrs = array(
			'style' => 'border-collapse: separate;', // Needed because of border radius.
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => $classes,
			'style' => $block_styles['css'],
			'align' => $additional_styles['text-align'],
		);

		return Table_Wrapper_Helper::render_table_wrapper( $table_content, $table_attrs, $cell_attrs );
	}

	/**
	 * Process table content to ensure email client compatibility.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function process_table_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );

		// Get theme styles once to avoid repeated calls.
		$email_styles = $rendering_context->get_theme_styles();
		$border_color = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000';

		// Process table elements.
		while ( $html->next_tag() ) {
			$tag_name = $html->get_tag();

			if ( 'TABLE' === $tag_name ) {
				// Ensure table has proper email attributes.
				$html->set_attribute( 'border', '1' );
				$html->set_attribute( 'cellpadding', '8' );
				$html->set_attribute( 'cellspacing', '0' );
				$html->set_attribute( 'role', 'presentation' );
				$html->set_attribute( 'width', '100%' );

				// Get existing style and add email-specific styles with borders.
				$existing_style     = $html->get_attribute( 'style' ) ?? '';
				$email_table_styles = "border-collapse: collapse; width: 100%; border: 1px solid {$border_color};";
				$html->set_attribute( 'style', $existing_style . '; ' . $email_table_styles );

				// Remove problematic classes from the table.
				$class_attr = $html->get_attribute( 'class' ) ?? '';
				$class_attr = str_replace( 'has-background', '', $class_attr );
				$class_attr = preg_replace( '/has-[a-z-]*border[a-z-]*/', '', $class_attr );
				$class_attr = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $class_attr );
				$class_attr = preg_replace( '/\s+/', ' ', $class_attr ); // Clean up multiple spaces.
				$html->set_attribute( 'class', trim( $class_attr ) );
			} elseif ( 'TD' === $tag_name || 'TH' === $tag_name ) {
				// Ensure table cells have proper email attributes with borders and padding.
				$html->set_attribute( 'valign', 'top' );

				// Get existing style and add email-specific styles with borders and padding.
				$existing_style    = $html->get_attribute( 'style' ) ?? '';
				$email_cell_styles = "vertical-align: top; border: 1px solid {$border_color}; padding: 8px; text-align: left;";
				$html->set_attribute( 'style', $existing_style . '; ' . $email_cell_styles );
			}
		}

		return $html->get_updated_html();
	}

	/**
	 * Extract table content from figure wrapper if present.
	 *
	 * @param string $block_content Block content.
	 * @return string
	 */
	private function extract_table_from_figure( string $block_content ): string {
		// Simple regex approach to extract table from figure.
		if ( preg_match( '/<figure[^>]*class="[^"]*wp-block-table[^"]*"[^>]*>(.*?)<\/figure>/s', $block_content, $matches ) ) {
			$figure_content = $matches[1];

			// Extract just the table element.
			if ( preg_match( '/<table[^>]*>.*?<\/table>/s', $figure_content, $table_matches ) ) {
				return $table_matches[0];
			}
		}

		// If no figure wrapper found, return original content.
		return $block_content;
	}
}
