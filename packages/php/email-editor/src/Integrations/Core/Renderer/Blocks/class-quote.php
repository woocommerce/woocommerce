<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use WP_Style_Engine;

/**
 * Renders a quote block.
 */
class Quote extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$content    = '';
		$dom_helper = new Dom_Document_Helper( $block_content );

		// Extract citation if present.
		$citation_content = '';
		$cite_element     = $dom_helper->find_element( 'cite' );
		if ( $cite_element ) {
			$citation_content = $this->get_citation_wrapper( $dom_helper->get_element_inner_html( $cite_element ), $parsed_block );
		}

		// Process inner blocks for main content.
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();
		foreach ( $inner_blocks as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			array( '{quote_content}', '{citation_content}' ),
			array( $content, $citation_content ),
			$this->get_block_wrapper( $block_content, $parsed_block, $settings_controller )
		);
	}

	/**
	 * Returns the citation content with a wrapper.
	 *
	 * @param string $citation_content The citation text.
	 * @param array  $parsed_block Parsed block.
	 * @return string The wrapped citation HTML or empty string if no citation.
	 */
	private function get_citation_wrapper( string $citation_content, array $parsed_block ): string {
		if ( empty( $citation_content ) ) {
			return '';
		}

		return $this->add_spacer(
			sprintf(
				'<p style="margin: 0; %2$s"><cite class="email-block-quote-citation" style="display: block; margin: 0;">%1$s</cite></p>',
				$citation_content,
				WP_Style_Engine::compile_css( array( 'text-align' => $parsed_block['attrs']['textAlign'] ?? '' ), '' ),
			),
			$parsed_block['email_attrs'] ?? array()
		);
	}

	/**
	 * Returns the block wrapper.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	private function get_block_wrapper( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$original_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'blockquote', 'class' ) ?? '';
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
		$border                 = $block_attributes['style']['border'] ?? array();
		$border_color_attribute = $block_attributes['borderColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['borderColor'] ) : null;
		if ( ! isset( $border['color'] ) && ! is_null( $border_color_attribute ) ) {
			$border['color'] = $border_color_attribute;
		}

		$table_styles = $this->get_styles_from_block(
			array(
				'color'      => array_filter(
					array(
						'background' => $block_attributes['backgroundColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['backgroundColor'] ) : null,
						'text'       => $block_attributes['textColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['textColor'] ) : null,
					)
				),
				'background' => $block_attributes['style']['background'] ?? array(),
				'border'     => $border,
			)
		)['declarations'];

		// Set the text align attribute to the wrapper if present.
		if ( isset( $parsed_block['attrs']['textAlign'] ) ) {
			$table_styles['text-align'] = $parsed_block['attrs']['textAlign'];
		}

		$table_styles['border-collapse'] = 'separate'; // Needed for the border radius to work.

		// Add default background size.
		$table_styles['background-size'] = empty( $table_styles['background-size'] ) ? 'cover' : $table_styles['background-size'];

		// Padding properties need to be added to the table cell.
		$cell_styles = $this->get_styles_from_block(
			array(
				'spacing' => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
			)
		)['declarations'];

		return sprintf(
			'<table class="email-block-quote %3$s" style="%1$s" width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation">
				<tbody>
					<tr>
						<td class="email-block-quote-content" style="%2$s" width="100%%">
							{quote_content}
							{citation_content}
						</td>
					</tr>
				</tbody>
			</table>',
			esc_attr( WP_Style_Engine::compile_css( $table_styles, '' ) ),
			esc_attr( WP_Style_Engine::compile_css( $cell_styles, '' ) ),
			esc_attr( $original_classname ),
		);
	}
}
