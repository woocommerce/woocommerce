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
		// Extract table content from figure wrapper if present.
		$table_content = $this->extract_table_from_figure( $block_content );

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

		return Table_Wrapper_Helper::render_table_wrapper( $table_content, $table_attrs, $cell_attrs );
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
				$existing_style    = $html->get_attribute( 'style' ) ?? '';
				$email_cell_styles = "vertical-align: top; border: 1px solid {$border_color}; padding: 8px; text-align: left;";

				// Add thicker borders for header and footer cells.
				$email_cell_styles = $this->add_header_footer_borders( $html, $email_cell_styles, $border_color, $current_section );

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
	 * Add thicker borders for table headers and footers.
	 *
	 * @param \WP_HTML_Tag_Processor $html HTML tag processor.
	 * @param string                 $base_styles Base cell styles.
	 * @param string                 $border_color Border color.
	 * @param string                 $current_section Current table section (thead, tbody, tfoot).
	 * @return string Updated cell styles.
	 */
	private function add_header_footer_borders( \WP_HTML_Tag_Processor $html, string $base_styles, string $border_color, string $current_section = '' ): string {
		$tag_name = $html->get_tag();

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
	 * Extract table content from figure wrapper if present.
	 *
	 * @param string $block_content Block content.
	 * @return string
	 */
	private function extract_table_from_figure( string $block_content ): string {
		$dom_helper = new Dom_Document_Helper( $block_content );

		// Look for figure element with wp-block-table class.
		$figure_tag = $dom_helper->find_element( 'figure' );
		if ( ! $figure_tag ) {
			// If no figure wrapper found, return original content.
			return $block_content;
		}

		$figure_class = $dom_helper->get_attribute_value( $figure_tag, 'class' ) ?? '';
		if ( ! str_contains( $figure_class, 'wp-block-table' ) ) {
			// If figure doesn't have wp-block-table class, return original content.
			return $block_content;
		}

		// Extract table element.
		$table_tag = $dom_helper->find_element( 'table' );
		if ( ! $table_tag ) {
			return $block_content;
		}

		$table_html = $dom_helper->get_outer_html( $table_tag );

		// Extract figcaption if present and convert to caption.
		$figcaption_tag = $dom_helper->find_element( 'figcaption' );
		if ( $figcaption_tag ) {
			$caption_content = $dom_helper->get_element_inner_html( $figcaption_tag );
			if ( '' !== $caption_content ) {
				// Append <caption> as the last child of <table> using regex replacement.
				// Add CSS to ensure caption appears below the table.
				$table_html = preg_replace(
					'/<\/table>/',
					'<caption style="caption-side: bottom; text-align: center; margin-top: 8px;">' . $caption_content . '</caption></table>',
					$table_html,
					1
				);
			}
		}

		return $table_html;
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
		if ( isset( $parsed_block['attrs']['className'] ) && str_contains( $parsed_block['attrs']['className'], 'is-style-stripes' ) ) {
			return true;
		}

		// Check for is-style-stripes in figure classes.
		if ( str_contains( $block_content, 'is-style-stripes' ) ) {
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
