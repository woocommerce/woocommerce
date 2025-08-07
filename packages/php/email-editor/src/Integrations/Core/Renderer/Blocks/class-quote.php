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
 * Renders a quote block.
 */
class Quote extends Abstract_Block_Renderer {
	/**
	 * Renders the block content
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$content    = '';
		$dom_helper = new Dom_Document_Helper( $block_content );

		// Extract citation if present.
		$citation_content = '';
		$cite_element     = $dom_helper->find_element( 'cite' );
		if ( $cite_element ) {
			$citation_content = $this->get_citation_wrapper(
				$dom_helper->get_element_inner_html( $cite_element ),
				$parsed_block,
				$rendering_context
			);
		}

		// Process inner blocks for main content.
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();
		foreach ( $inner_blocks as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			array( '{quote_content}', '{citation_content}' ),
			array( $content, $citation_content ),
			$this->get_block_wrapper( $block_content, $parsed_block, $rendering_context )
		);
	}

	/**
	 * Returns the citation content with a wrapper.
	 *
	 * @param string            $citation_content The citation text.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context instance.
	 * @return string The wrapped citation HTML or empty string if no citation.
	 */
	private function get_citation_wrapper( string $citation_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		if ( empty( $citation_content ) ) {
			return '';
		}

		// The HTML cite tag should use block gap as margin-top.
		$theme_styles    = $rendering_context->get_theme_styles();
		$margin_top      = $theme_styles['spacing']['blockGap'] ?? '0px';
		$citation_styles = Styles_Helper::get_block_styles( $parsed_block['attrs'], $rendering_context, array( 'text-align' ) );
		$citation_styles = Styles_Helper::extend_block_styles( $citation_styles, array( 'margin' => "{$margin_top} 0px 0px 0px" ) );

		return $this->add_spacer(
			sprintf(
				'<p style="%2$s"><cite class="email-block-quote-citation" style="display: block; margin: 0;">%1$s</cite></p>',
				$citation_content,
				$citation_styles['css'],
			),
			$parsed_block['email_attrs'] ?? array()
		);
	}

	/**
	 * Returns the block wrapper.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 */
	private function get_block_wrapper( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
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
			'class' => 'email-block-quote ' . $original_classname,
			'style' => $table_styles['css'],
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-block-quote-content',
			'style' => $cell_styles['css'],
			'width' => '100%',
		);

		return Table_Wrapper_Helper::render_table_wrapper( '{quote_content}{citation_content}', $table_attrs, $cell_attrs );
	}
}
