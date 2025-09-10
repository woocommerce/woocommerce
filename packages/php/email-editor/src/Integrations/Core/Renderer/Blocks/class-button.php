<?php
/**
 * This file is part of the WooCommerce Email Editor package.
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
 * Renders a button block.
 *
 * @see https://www.activecampaign.com/blog/email-buttons
 * @see https://documentation.mjml.io/#mj-button
 */
class Button extends Abstract_Block_Renderer {
	/**
	 * Get styles for the wrapper element.
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array
	 */
	private function get_wrapper_styles( array $block_attributes, Rendering_Context $rendering_context ) {
		$block_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background-color', 'color', 'typography', 'spacing' ) );

		return Styles_Helper::extend_block_styles(
			$block_styles,
			array(
				'word-break' => 'break-word',
				'display'    => 'block',
			)
		);
	}

	/**
	 * Get styles for the link element.
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array
	 */
	private function get_link_styles( array $block_attributes, Rendering_Context $rendering_context ) {
		$block_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'color', 'typography' ) );

		return Styles_Helper::extend_block_styles(
			$block_styles,
			array( 'display' => 'block' )
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		return $this->render_content( $block_content, $parsed_block, $rendering_context );
	}

	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		if ( empty( $parsed_block['innerHTML'] ) ) {
			return '';
		}

		$dom_helper      = new Dom_Document_Helper( $parsed_block['innerHTML'] );
		$block_classname = $dom_helper->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$button_link     = $dom_helper->find_element( 'a' );

		if ( ! $button_link ) {
			return '';
		}

		$button_text = $dom_helper->get_element_inner_html( $button_link ) ? $dom_helper->get_element_inner_html( $button_link ) : '';
		$button_url  = $button_link->getAttribute( 'href' ) ? $button_link->getAttribute( 'href' ) : '#';

		$block_attributes = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'width'           => '',
				'style'           => array(),
				'textAlign'       => 'center',
				'backgroundColor' => '',
				'textColor'       => '',
			)
		);

		$wrapper_styles = $this->get_wrapper_styles( $block_attributes, $rendering_context );
		$link_styles    = $this->get_link_styles( $block_attributes, $rendering_context );

		$table_attrs = array(
			'style' => 'width:' . ( $block_attributes['width'] ? '100%' : 'auto' ) . ';',
		);

		$cell_attrs = array(
			'class'  => $wrapper_styles['classnames'] . ' ' . $block_classname,
			'style'  => $wrapper_styles['css'],
			'align'  => $block_attributes['textAlign'],
			'valign' => 'middle',
			'role'   => 'presentation',
		);

		$button_content = sprintf(
			'<a class="button-link %1$s" style="%2$s" href="%3$s" target="_blank">%4$s</a>',
			esc_attr( $link_styles['classnames'] ),
			esc_attr( $link_styles['css'] ),
			esc_url( $button_url ),
			$button_text
		);

		return Table_Wrapper_Helper::render_table_wrapper( $button_content, $table_attrs, $cell_attrs );
	}
}
