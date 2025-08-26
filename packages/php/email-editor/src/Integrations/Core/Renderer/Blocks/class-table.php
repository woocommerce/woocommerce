<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
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
		// Extract table content and caption from figure wrapper if present.
		$extracted_data = $this->extract_table_and_caption_from_figure( $block_content );
		$table_content  = $extracted_data['table'];
		$caption        = $extracted_data['caption'];

		// Validate that we have actual table content.
		if ( ! $this->is_valid_table_content( $table_content ) ) {
			return '';
		}

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
			$block_classes = $html->get_attribute( 'class' ) ?? '';
			$classes      .= ' ' . $block_classes;
			// Remove has-background to prevent double padding applied for wrapper and inner element.
			$block_classes = (string) str_replace( 'has-background', '', (string) $block_classes );
			// Remove border related classes because we handle border on wrapping table cell.
			$block_classes = (string) preg_replace( '/has-[a-z-]*border[a-z-]*/', '', $block_classes );
			$block_classes = (string) preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $block_classes );
			$block_classes = (string) preg_replace( '/\s+/', ' ', $block_classes ); // Clean up multiple spaces.
			$html->set_attribute( 'class', trim( $block_classes ) );
			$table_content = $html->get_updated_html();
		}

		// Also remove classes from the wrapper classes.
		$classes = (string) str_replace( 'has-background', '', (string) $classes );
		$classes = (string) preg_replace( '/has-[a-z-]*border[a-z-]*/', '', (string) $classes );
		$classes = (string) preg_replace( '/[a-z-]+-border-[a-z-]+/', '', (string) $classes );
		$classes = (string) preg_replace( '/\s+/', ' ', (string) $classes ); // Clean up multiple spaces.
		$classes = trim( $classes );

		$block_styles      = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing', 'background-color', 'color', 'typography' ) );
		$additional_styles = array(
			'min-width' => '100%', // Prevent Gmail App from shrinking the table on mobile devices.
		);

		// Add fallback text color when no custom text color or preset text color is set.
		if ( empty( $block_styles['declarations']['color'] ) ) {
			$email_styles = $rendering_context->get_theme_styles();
			$color        = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000';
			// Sanitize color value to ensure it's a valid hex color.
			$additional_styles['color'] = $this->sanitize_color( $color );
		}

		$additional_styles['text-align'] = 'left';
		if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // In this case, textAlign needs to be one of 'left', 'center', 'right'.
			$additional_styles['text-align'] = $parsed_block['attrs']['textAlign'];
		} elseif ( in_array( $parsed_block['attrs']['align'] ?? null, array( 'left', 'center', 'right' ), true ) ) {
			$additional_styles['text-align'] = $parsed_block['attrs']['align'];
		}

		$block_styles = Styles_Helper::extend_block_styles( $block_styles, $additional_styles );

		// Check if this is a striped table style.
		$is_striped_table = $this->is_striped_table( $block_content, $parsed_block );

		// Process the table content to ensure email compatibility BEFORE wrapping.
		$table_content = $this->process_table_content( $table_content, $parsed_block, $rendering_context, $is_striped_table );

		$table_attrs = array(
			'style' => 'border-collapse: separate;', // Needed because of border radius.
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => $classes,
			'style' => $block_styles['css'],
			'align' => $additional_styles['text-align'],
		);

		$rendered_table = Table_Wrapper_Helper::render_table_wrapper( $table_content, $table_attrs, $cell_attrs );

		// Add caption outside the table if present.
		if ( ! empty( $caption ) ) {
			$rendered_table .= '<div style="text-align: center; margin-top: 8px; font-size: 0.9em; color: #666;">' . $caption . '</div>';
		}

		return $rendered_table;
	}

	/**
	 * Process table content to ensure email client compatibility.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @param bool              $is_striped_table Whether this is a striped table.
	 * @return string
	 */
	private function process_table_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context, bool $is_striped_table = false ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );

		// Get theme styles once to avoid repeated calls.
		$email_styles = $rendering_context->get_theme_styles();
		$color        = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000';
		$border_color = $this->sanitize_color( $color );

		// Extract custom border color and width from block attributes.
		$custom_border_color = $this->get_custom_border_color( $parsed_block, $rendering_context );
		$custom_border_width = $this->get_custom_border_width( $parsed_block );

		// Use custom border color if available, otherwise fall back to default.
		if ( $custom_border_color ) {
			$border_color = $custom_border_color;
		}

		// Track row context for striped styling.
		$current_section = ''; // Table sections: thead, tbody, tfoot.
		$row_count       = 0;

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

				// Get existing style and add email-specific styles.
				$existing_style = $html->get_attribute( 'style' ) ?? '';
				$border_width   = $custom_border_width ? $custom_border_width : '1px';

				// Check for fixed layout class and apply table-layout: fixed.
				$class_attr   = (string) ( $html->get_attribute( 'class' ) ?? '' );
				$table_layout = $this->has_fixed_layout( $class_attr ) ? 'table-layout: fixed; ' : '';

				// Use border-collapse: collapse to ensure consistent borders between table and cells.
				$email_table_styles = "{$table_layout}border-collapse: collapse; width: 100%;";
				$html->set_attribute( 'style', $existing_style . '; ' . $email_table_styles );

				// Remove problematic classes from the table but keep has-fixed-layout for reference.
				$class_attr = (string) str_replace( 'has-background', '', (string) $class_attr );
				$class_attr = (string) preg_replace( '/has-[a-z-]*border[a-z-]*/', '', (string) $class_attr );
				$class_attr = (string) preg_replace( '/[a-z-]+-border-[a-z-]+/', '', (string) $class_attr );
				$class_attr = (string) preg_replace( '/\s+/', ' ', (string) $class_attr ); // Clean up multiple spaces.
				$html->set_attribute( 'class', trim( $class_attr ) );
			} elseif ( 'THEAD' === $tag_name ) {
				$current_section = 'thead';
				$row_count       = 0;
			} elseif ( 'TBODY' === $tag_name ) {
				$current_section = 'tbody';
				$row_count       = 0;
			} elseif ( 'TFOOT' === $tag_name ) {
				$current_section = 'tfoot';
				$row_count       = 0;
			} elseif ( 'TR' === $tag_name ) {
				++$row_count;
			} elseif ( 'TD' === $tag_name || 'TH' === $tag_name ) {
				// Ensure table cells have proper email attributes with borders and padding.
				$html->set_attribute( 'valign', 'top' );

				// Get existing style and add email-specific styles with borders and padding.
				$existing_style = $html->get_attribute( 'style' ) ?? '';
				$border_width   = $custom_border_width ? $custom_border_width : '1px';

				// Extract cell-specific text alignment.
				$cell_text_align = $this->get_cell_text_alignment( $html );

				$email_cell_styles = "vertical-align: top; border: {$border_width} solid {$border_color}; padding: 8px; text-align: {$cell_text_align};";

				// Add thicker borders for header and footer cells when no custom border is set.
				$email_cell_styles = $this->add_header_footer_borders( $html, $email_cell_styles, $border_color, $current_section, $custom_border_width );

				// Add striped styling for tbody rows (first row gets background, then alternates).
				if ( $is_striped_table && 'tbody' === $current_section && 1 === $row_count % 2 ) {
					$email_cell_styles .= '; background-color: #f8f9fa;';
				}

				$html->set_attribute( 'style', $existing_style . '; ' . $email_cell_styles );
			}
		}

		return $html->get_updated_html();
	}

	/**
	 * Get custom border color from block attributes.
	 *
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string|null Custom border color or null if not set.
	 */
	private function get_custom_border_color( array $parsed_block, Rendering_Context $rendering_context ): ?string {
		$block_attributes = $parsed_block['attrs'] ?? array();

		if ( ! empty( $block_attributes['borderColor'] ) ) {
			$border_color = $rendering_context->translate_slug_to_color( $block_attributes['borderColor'] );
			return $this->sanitize_color( $border_color );
		}

		return null;
	}

	/**
	 * Get custom border width from block attributes.
	 *
	 * @param array $parsed_block Parsed block.
	 * @return string|null Custom border width or null if not set.
	 */
	private function get_custom_border_width( array $parsed_block ): ?string {
		$block_attributes = $parsed_block['attrs'] ?? array();

		if ( ! empty( $block_attributes['style']['border']['width'] ) ) {
			$border_width = $block_attributes['style']['border']['width'];
			// Ensure the border width has a unit, default to px if not specified.
			if ( is_numeric( $border_width ) ) {
				return $border_width . 'px';
			}
			return $border_width;
		}

		return null;
	}

	/**
	 * Add thicker borders for table headers and footers when no custom border is set.
	 *
	 * @param \WP_HTML_Tag_Processor $html HTML tag processor.
	 * @param string                 $base_styles Base cell styles.
	 * @param string                 $border_color Border color.
	 * @param string                 $current_section Current table section (thead, tbody, tfoot).
	 * @param string|null            $custom_border_width Custom border width if set.
	 * @return string Updated cell styles.
	 */
	private function add_header_footer_borders( \WP_HTML_Tag_Processor $html, string $base_styles, string $border_color, string $current_section = '', ?string $custom_border_width = null ): string {
		$tag_name = $html->get_tag();

		// Only add thicker borders if no custom border width is set.
		if ( $custom_border_width ) {
			return $base_styles;
		}

		// Add thicker bottom border to all TH elements (headers).
		if ( 'TH' === $tag_name ) {
			$base_styles .= "; border-bottom: 3px solid {$border_color};";
		}

		// Add thicker top border to footer cells (TD elements in tfoot).
		if ( 'TD' === $tag_name && 'tfoot' === $current_section ) {
			$base_styles .= "; border-top: 3px solid {$border_color};";
		}

		return $base_styles;
	}

	/**
	 * Get text alignment for a table cell.
	 *
	 * @param \WP_HTML_Tag_Processor $html HTML tag processor.
	 * @return string Text alignment value (left, center, right).
	 */
	private function get_cell_text_alignment( \WP_HTML_Tag_Processor $html ): string {
		// Check for data-align attribute first.
		$data_align = $html->get_attribute( 'data-align' );
		if ( $data_align && in_array( $data_align, array( 'left', 'center', 'right' ), true ) ) {
			return $data_align;
		}

		// Check for has-text-align-* classes.
		$class_attr = (string) ( $html->get_attribute( 'class' ) ?? '' );
		if ( false !== strpos( $class_attr, 'has-text-align-center' ) ) {
			return 'center';
		}
		if ( false !== strpos( $class_attr, 'has-text-align-right' ) ) {
			return 'right';
		}
		if ( false !== strpos( $class_attr, 'has-text-align-left' ) ) {
			return 'left';
		}

		// Default to left alignment.
		return 'left';
	}

	/**
	 * Check if table has fixed layout class.
	 *
	 * @param string $class_attr Class attribute string.
	 * @return bool True if has-fixed-layout class is present.
	 */
	private function has_fixed_layout( string $class_attr ): bool {
		return false !== strpos( $class_attr, 'has-fixed-layout' );
	}

	/**
	 * Extract table content and caption from figure wrapper if present.
	 *
	 * @param string $block_content Block content.
	 * @return array Array with 'table' and 'caption' keys.
	 */
	private function extract_table_and_caption_from_figure( string $block_content ): array {
		$dom_helper = new Dom_Document_Helper( $block_content );

		// Look for figure element with wp-block-table class.
		$figure_tag = $dom_helper->find_element( 'figure' );
		if ( ! $figure_tag ) {
			// If no figure wrapper found, return original content as table.
			return array(
				'table'   => $block_content,
				'caption' => '',
			);
		}

		$figure_class = $dom_helper->get_attribute_value( $figure_tag, 'class' );
		if ( false === strpos( $figure_class, 'wp-block-table' ) ) {
			// If figure doesn't have wp-block-table class, return original content as table.
			return array(
				'table'   => $block_content,
				'caption' => '',
			);
		}

		// Extract table element.
		$table_tag = $dom_helper->find_element( 'table' );
		if ( ! $table_tag ) {
			return array(
				'table'   => $block_content,
				'caption' => '',
			);
		}

		$table_html = $dom_helper->get_outer_html( $table_tag );

		// Extract figcaption if present.
		$figcaption_tag = $dom_helper->find_element( 'figcaption' );
		$caption        = '';
		if ( $figcaption_tag ) {
			$caption = $dom_helper->get_element_inner_html( $figcaption_tag );
		}

		return array(
			'table'   => $table_html,
			'caption' => $caption,
		);
	}

	/**
	 * Extract table content from figure wrapper if present.
	 *
	 * @param string $block_content Block content.
	 * @return string
	 */
	private function extract_table_from_figure( string $block_content ): string {
		$extracted_data = $this->extract_table_and_caption_from_figure( $block_content );
		return $extracted_data['table'];
	}

	/**
	 * Check if the table has striped styling.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block.
	 * @return bool True if it's a striped table, false otherwise.
	 */
	private function is_striped_table( string $block_content, array $parsed_block ): bool {
		// Check for is-style-stripes in block attributes.
		if ( isset( $parsed_block['attrs']['className'] ) && false !== strpos( $parsed_block['attrs']['className'], 'is-style-stripes' ) ) {
			return true;
		}

		// Check for is-style-stripes in figure classes.
		if ( false !== strpos( $block_content, 'is-style-stripes' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate if the content is a valid table HTML.
	 *
	 * @param string $content The content to validate.
	 * @return bool True if it's a valid table, false otherwise.
	 */
	private function is_valid_table_content( string $content ): bool {
		// Only assert that a <table> exists; downstream checks handle emptiness and KSES handles sanitization.
		return (bool) preg_match( '/<table[^>]*>.*?<\/table>/is', $content );
	}

	/**
	 * Sanitize color value to ensure it's a valid hex color.
	 *
	 * @param string $color The color value to sanitize.
	 * @return string Sanitized color value.
	 */
	private function sanitize_color( string $color ): string {
		// Remove any whitespace and convert to lowercase.
		$color = strtolower( trim( $color ) );

		// Check if it's a valid hex color (3 or 6 characters).
		if ( preg_match( '/^#([a-f0-9]{3}){1,2}$/i', $color ) ) {
			return $color;
		}

		// If not a valid hex color, return a safe default.
		return '#000000';
	}
}
