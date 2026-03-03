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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;

/**
 * Renders a table block.
 */
class Table extends Abstract_Block_Renderer {
	/**
	 * Valid text alignment values.
	 */
	private const VALID_TEXT_ALIGNMENTS = array( 'left', 'center', 'right' );

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
			$block_classes = (string) ( $html->get_attribute( 'class' ) ?? '' );
			$classes      .= ' ' . $block_classes;
			// Clean classes for table element.
			$block_classes = Html_Processing_Helper::clean_css_classes( $block_classes );
			$html->set_attribute( 'class', $block_classes );
			$table_content = $html->get_updated_html();
		}

		// Clean wrapper classes.
		$classes = Html_Processing_Helper::clean_css_classes( $classes );

		// Get spacing styles for wrapper and table-specific styles separately.
		$spacing_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing' ) );
		$table_styles   = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'background-color', 'color', 'typography' ) );

		// Ensure background styles are completely removed from spacing styles and force transparent background.
		$spacing_css = $spacing_styles['css'] ?? '';
		$spacing_css = (string) ( preg_replace( '/background[^;]*;?/', '', $spacing_css ) ?? '' );
		$spacing_css = (string) ( preg_replace( '/\s*;\s*;/', ';', $spacing_css ) ?? '' ); // Clean up double semicolons.
		$spacing_css = trim( $spacing_css, '; ' );

		// Force transparent background on wrapper to prevent any background leakage.
		$spacing_styles['css'] = $spacing_css ? $spacing_css . '; background: transparent !important;' : 'background: transparent !important;';

		$additional_styles = array(
			'min-width' => '100%', // Prevent Gmail App from shrinking the table on mobile devices.
		);

		// Add fallback text color when no custom text color or preset text color is set.
		if ( empty( $table_styles['declarations']['color'] ) ) {
			$email_styles = $rendering_context->get_theme_styles();
			$color        = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000';
			// Sanitize color value to ensure it's a valid hex color.
			$additional_styles['color'] = Html_Processing_Helper::sanitize_color( $color );
		}

		$additional_styles['text-align'] = 'left';
		if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // In this case, textAlign needs to be one of 'left', 'center', 'right'.
			$text_align = $parsed_block['attrs']['textAlign'];
			if ( in_array( $text_align, self::VALID_TEXT_ALIGNMENTS, true ) ) {
				$additional_styles['text-align'] = $text_align;
			}
		} elseif ( in_array( $parsed_block['attrs']['align'] ?? null, self::VALID_TEXT_ALIGNMENTS, true ) ) {
			$additional_styles['text-align'] = $parsed_block['attrs']['align'];
		}

		$table_styles = Styles_Helper::extend_block_styles( $table_styles, $additional_styles );

		// Check if this is a striped table style.
		$is_striped_table = $this->is_striped_table( $block_content, $parsed_block );

		// Process the table content to ensure email compatibility BEFORE wrapping.
		$table_content = $this->process_table_content( $table_content, $parsed_block, $rendering_context, $is_striped_table );

		// Apply table-specific styles (background, color, typography) directly to the table element.
		$table_content_with_styles = $this->apply_styles_to_table_element( $table_content, $table_styles['css'] );

		// Add wp-block-table class to the table element for theme.json CSS rules.
		if ( false !== strpos( $block_content, 'wp-block-table' ) ) {
			$table_content_with_styles = $this->add_class_to_table_element( $table_content_with_styles, 'wp-block-table' );
		}

		// Build complete content (table + caption).
		$complete_content = $table_content_with_styles;
		if ( ! empty( $caption ) ) {
			// Use HTML API to safely allow specific tags in caption.
			$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption );
			// Extract typography styles from table styles (not spacing styles) and apply to caption.
			$caption_styles    = $this->extract_typography_styles_for_caption( $table_styles['css'] );
			$complete_content .= '<div style="text-align: center; margin-top: 8px; ' . $caption_styles . '">' . $sanitized_caption . '</div>';
		}

		$table_attrs = array(
			'style' => 'border-collapse: separate;', // Needed because of border radius.
			'width' => '100%',
		);

		// Use spacing styles only for the wrapper.
		$cell_attrs = array(
			'class' => $classes,
			'style' => $spacing_styles['css'],
			'align' => $additional_styles['text-align'],
		);

		$rendered_table = Table_Wrapper_Helper::render_table_wrapper( $complete_content, $table_attrs, $cell_attrs );

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

		// Extract custom border color and width from block attributes.
		$custom_border_color = $this->get_custom_border_color( $parsed_block, $rendering_context );
		$custom_border_width = $this->get_custom_border_width( $parsed_block );

		// Use custom border color if available, otherwise fall back to default.
		if ( $custom_border_color ) {
			$border_color = $custom_border_color;
		} else {
			// Get theme styles once to avoid repeated calls.
			$email_styles = $rendering_context->get_theme_styles();
			$border_color = Html_Processing_Helper::sanitize_color( $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000' );
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
				$existing_style = (string) ( $html->get_attribute( 'style' ) ?? '' );

				// Check for fixed layout class and apply table-layout: fixed.
				$class_attr   = (string) ( $html->get_attribute( 'class' ) ?? '' );
				$table_layout = $this->has_fixed_layout( $class_attr ) ? 'table-layout: fixed; ' : '';

				// Use border-collapse: collapse to ensure consistent borders between table and cells.
				$email_table_styles = "{$table_layout}border-collapse: collapse; width: 100%;";
				$existing_style     = rtrim( $existing_style, "; \t\n\r\0\x0B" );
				$new_style          = $existing_style ? $existing_style . '; ' . $email_table_styles : $email_table_styles;
				$html->set_attribute( 'style', $new_style );

				// Remove problematic classes from the table but keep has-fixed-layout and alignment classes for editor UI.
				$class_attr = Html_Processing_Helper::clean_css_classes( $class_attr );
				$html->set_attribute( 'class', $class_attr );
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
				$existing_style = (string) ( $html->get_attribute( 'style' ) ?? '' );
				$existing_style = rtrim( $existing_style, "; \t\n\r\0\x0B" );
				$border_width   = $custom_border_width ? $custom_border_width : '1px';
				$border_style   = $this->get_custom_border_style( $parsed_block );

				// Extract cell-specific text alignment.
				$cell_text_align = $this->get_cell_text_alignment( $html );

				$email_cell_styles = "vertical-align: top; border: {$border_width} {$border_style} {$border_color}; padding: 8px; text-align: {$cell_text_align};";

				// Add thicker borders for header and footer cells when no custom border is set.
				$email_cell_styles = $this->add_header_footer_borders( $html, $email_cell_styles, $border_color, $current_section, $custom_border_width );

				// Add striped styling for tbody rows (first row gets background, then alternates).
				if ( $is_striped_table && 'tbody' === $current_section && 1 === $row_count % 2 ) {
					$email_cell_styles .= ' background-color: #f8f9fa;';
				}

				$new_cell_style = $existing_style ? $existing_style . '; ' . $email_cell_styles : $email_cell_styles;
				$html->set_attribute( 'style', $new_cell_style );
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
			return Html_Processing_Helper::sanitize_color( $border_color );
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

			// Sanitize the border width value.
			$border_width = Html_Processing_Helper::sanitize_css_value( $border_width );
			if ( empty( $border_width ) ) {
				return null;
			}

			// Ensure the border width has a unit, default to px if not specified.
			if ( is_numeric( $border_width ) ) {
				return $border_width . 'px';
			}
			// Validate that the border width contains only valid CSS units and numbers.
			if ( preg_match( '/^[0-9]+\.?[0-9]*(px|em|rem|pt|pc|in|cm|mm|ex|ch|vw|vh|vmin|vmax)$/', $border_width ) ) {
				return $border_width;
			}
			// If invalid, return null to use default.
			return null;
		}

		return null;
	}

	/**
	 * Get custom border style from block attributes.
	 *
	 * @param array $parsed_block Parsed block.
	 * @return string Custom border style or 'solid' as default.
	 */
	private function get_custom_border_style( array $parsed_block ): string {
		$style   = strtolower( (string) ( $parsed_block['attrs']['style']['border']['style'] ?? '' ) );
		$allowed = array( 'solid', 'dashed', 'dotted' ); // Email-safe subset.
		return in_array( $style, $allowed, true ) ? $style : 'solid';
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
			$base_styles .= " border-bottom: 3px solid {$border_color};";
		}

		// Add thicker top border to footer cells (TD elements in tfoot).
		if ( 'TD' === $tag_name && 'tfoot' === $current_section ) {
			$base_styles .= " border-top: 3px solid {$border_color};";
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
		if ( $data_align && in_array( $data_align, self::VALID_TEXT_ALIGNMENTS, true ) ) {
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

		$figure_class_attr = $dom_helper->get_attribute_value( $figure_tag, 'class' );
		$figure_class      = (string) ( $figure_class_attr ? $figure_class_attr : '' );
		if ( false === strpos( $figure_class, 'wp-block-table' ) ) {
			// If figure doesn't have wp-block-table class, return original content as table.
			return array(
				'table'   => $block_content,
				'caption' => '',
			);
		}

		// Extract table element from within the matched figure only.
		$figure_html = $dom_helper->get_outer_html( $figure_tag );

		// Use regex to extract table from within the figure to avoid document conflicts.
		if ( ! preg_match( '/<table[^>]*>.*?<\/table>/is', $figure_html, $table_matches ) ) {
			return array(
				'table'   => $block_content,
				'caption' => '',
			);
		}
		$table_html = $table_matches[0];

		// Extract figcaption if present (scoped to the figure).
		$caption = '';
		if ( preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/is', $figure_html, $figcaption_matches ) ) {
			$caption = $figcaption_matches[1];
		}

		return array(
			'table'   => $table_html,
			'caption' => $caption,
		);
	}

	/**
	 * Apply CSS styles directly to the table element.
	 *
	 * @param string $table_content Table HTML content.
	 * @param string $styles CSS styles to apply.
	 * @return string Table content with styles applied.
	 */
	private function apply_styles_to_table_element( string $table_content, string $styles ): string {
		$html = new \WP_HTML_Tag_Processor( $table_content );
		if ( $html->next_tag( array( 'tag_name' => 'TABLE' ) ) ) {
			$existing_style = (string) ( $html->get_attribute( 'style' ) ?? '' );
			$existing_style = rtrim( $existing_style, "; \t\n\r\0\x0B" );

			// Add default border widths if individual border colors are present but no widths.
			$border_width_styles = $this->get_default_border_widths( $existing_style );

			$new_style = $existing_style;
			if ( ! empty( $border_width_styles ) ) {
				$new_style = $new_style ? $new_style . '; ' . $border_width_styles : $border_width_styles;
			}
			if ( ! empty( $styles ) ) {
				$new_style = $new_style ? $new_style . '; ' . $styles : $styles;
			}

			$html->set_attribute( 'style', $new_style );
			return $html->get_updated_html();
		}
		return $table_content;
	}

	/**
	 * Get default border widths for table element when individual border colors are present.
	 *
	 * @param string $existing_style Existing style attribute of the table element.
	 * @return string CSS border width styles or empty string if not needed.
	 */
	private function get_default_border_widths( string $existing_style ): string {
		// Check if individual border colors are present but no corresponding widths.
		$sides               = array( 'top', 'right', 'bottom', 'left' );
		$border_width_styles = array();

		foreach ( $sides as $side ) {
			$has_color = strpos( $existing_style, "border-{$side}-color:" ) !== false;
			$has_width = strpos( $existing_style, "border-{$side}-width:" ) !== false;

			// If border color is present but no width, add default width.
			if ( $has_color && ! $has_width ) {
				$border_width_styles[] = "border-{$side}-width: 1.5px";
			}
		}

		return implode( '; ', $border_width_styles );
	}

	/**
	 * Add a CSS class to the table element.
	 *
	 * @param string $table_content Table HTML content.
	 * @param string $class_name CSS class to add.
	 * @return string Table content with class added.
	 */
	private function add_class_to_table_element( string $table_content, string $class_name ): string {
		// Validate class name to prevent XSS.
		if ( ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $class_name ) ) {
			return $table_content;
		}

		$html = new \WP_HTML_Tag_Processor( $table_content );
		if ( $html->next_tag( array( 'tag_name' => 'TABLE' ) ) ) {
			$existing_class = (string) ( $html->get_attribute( 'class' ) ?? '' );
			$existing_class = trim( $existing_class );

			// Only add if not already present.
			if ( false === strpos( $existing_class, $class_name ) ) {
				$new_class = $existing_class ? $existing_class . ' ' . $class_name : $class_name;
				$html->set_attribute( 'class', $new_class );
			}
			return $html->get_updated_html();
		}
		return $table_content;
	}

	/**
	 * Extract typography styles from CSS string for caption.
	 *
	 * @param string $css CSS string to extract typography from.
	 * @return string Typography CSS for caption.
	 */
	private function extract_typography_styles_for_caption( string $css ): string {
		$typography_properties = Html_Processing_Helper::get_caption_css_properties();

		$caption_styles = array();

		foreach ( $typography_properties as $property ) {
			// Use regex to extract each typography property.
			if ( preg_match( '/' . preg_quote( $property, '/' ) . '\s*:\s*([^;]+)/i', $css, $matches ) ) {
				$value = trim( $matches[1] );
				// Sanitize the CSS value to prevent injection.
				$sanitized_value = Html_Processing_Helper::sanitize_css_value( $value );
				if ( ! empty( $sanitized_value ) ) {
					$caption_styles[] = $property . ': ' . $sanitized_value;
				}
			}
		}

		return implode( '; ', $caption_styles );
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
}
