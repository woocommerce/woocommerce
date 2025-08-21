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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;

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

		// Do not render empty blocks.
		if ( empty( trim( wp_strip_all_tags( $table_content ) ) ) ) {
			return '';
		}

		return $this->get_block_wrapper( $table_content, $parsed_block, $rendering_context );
	}

	/**
	 * Returns the block wrapper.
	 *
	 * @param string            $table_content    Table content.
	 * @param array             $parsed_block     Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function get_block_wrapper( string $table_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$original_classname = ( new Dom_Document_Helper( $table_content ) )->get_attribute_value_by_tag_name( 'table', 'class' ) ?? '';
		$block_attributes   = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style'           => array(),
				'backgroundColor' => '',
				'textColor'       => '',
				'borderColor'     => '',
			)
		);

		// Layout, background, borders need to be on the outer table element.
		$table_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background', 'background-color', 'color', 'text-align' ) );
		$table_styles = Styles_Helper::extend_block_styles(
			$table_styles,
			array(
				'border-collapse' => 'separate',
				'background-size' => $table_styles['declarations']['background-size'] ?? 'cover',
			)
		);

		// Padding properties need to be added to the table cell.
		$cell_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'padding' ) );

		$table_attrs = array(
			'class' => 'email-table-block ' . $original_classname,
			'style' => $table_styles['css'],
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-table-block-content',
			'style' => $cell_styles['css'],
			'width' => '100%',
		);

		// Add basic table styling to ensure it renders properly.
		$table_content = $this->add_table_styling( $table_content, $rendering_context );

		return Table_Wrapper_Helper::render_table_wrapper( $table_content, $table_attrs, $cell_attrs );
	}

	/**
	 * Add basic table styling to ensure proper rendering.
	 *
	 * @param string            $table_content Table content.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function add_table_styling( string $table_content, Rendering_Context $rendering_context ): string {
		// Get the theme's text color to use for borders.
		$theme_styles = $rendering_context->get_theme_styles();
		$border_color = $theme_styles['color']['text'] ?? '#333';

		$html = new \WP_HTML_Tag_Processor( $table_content );

		// Process table elements.
		while ( $html->next_tag() ) {
			$tag_name = $html->get_tag();

			if ( 'TABLE' === $tag_name ) {
				// Ensure table has proper email attributes for compatibility.
				$html->set_attribute( 'role', 'presentation' );
				$html->set_attribute( 'width', '100%' );
				$html->set_attribute( 'style', 'border-collapse: collapse; width: 100%;' );
			} elseif ( 'TD' === $tag_name || 'TH' === $tag_name ) {
				// Ensure table cells have proper email attributes.
				$html->set_attribute( 'valign', 'top' );
				$html->set_attribute( 'style', "border: 1px solid {$border_color}; padding: 8px; text-align: left;" );
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
