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
	 * @param array $block_styles Block styles.
	 * @return object{css: string, classname: string}
	 */
	private function get_wrapper_styles( array $block_styles ) {
		$properties = array( 'border', 'color', 'typography', 'spacing' );
		$styles     = $this->get_styles_from_block( array_intersect_key( $block_styles, array_flip( $properties ) ) );
		return (object) array(
			'css'       => $this->compile_css(
				$styles['declarations'],
				array(
					'word-break' => 'break-word',
					'display'    => 'block',
				)
			),
			'classname' => $styles['classnames'],
		);
	}

	/**
	 * Get styles for the link element.
	 *
	 * @param array $block_styles Block styles.
	 * @return object{css: string, classname: string}
	 */
	private function get_link_styles( array $block_styles ) {
		$styles = $this->get_styles_from_block(
			array(
				'color'      => array(
					'text' => $block_styles['color']['text'] ?? '',
				),
				'typography' => $block_styles['typography'] ?? array(),
			)
		);
		return (object) array(
			'css'       => $this->compile_css( $styles['declarations'], array( 'display' => 'block' ) ),
			'classname' => $styles['classnames'],
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

		$block_styles = array_replace_recursive(
			array(
				'color' => array_filter(
					array(
						'background' => $block_attributes['backgroundColor'] ? $rendering_context->translate_slug_to_color( $block_attributes['backgroundColor'] ) : null,
						'text'       => $block_attributes['textColor'] ? $rendering_context->translate_slug_to_color( $block_attributes['textColor'] ) : null,
					)
				),
			),
			$block_attributes['style'] ?? array()
		);

		if ( ! empty( $block_styles['border'] ) && empty( $block_styles['border']['style'] ) ) {
			$block_styles['border']['style'] = 'solid';
		}

		$wrapper_styles = $this->get_wrapper_styles( $block_styles );
		$link_styles    = $this->get_link_styles( $block_styles );

		$table_attrs = array(
			'style' => 'width:' . ( $block_attributes['width'] ? '100%' : 'auto' ) . ';',
		);

		$cell_attrs = array(
			'class'  => $wrapper_styles->classname . ' ' . $block_classname,
			'style'  => $wrapper_styles->css,
			'align'  => $block_attributes['textAlign'],
			'valign' => 'middle',
			'role'   => 'presentation',
		);

		$button_content = sprintf(
			'<a class="button-link %1$s" style="%2$s" href="%3$s" target="_blank">%4$s</a>',
			esc_attr( $link_styles->classname ),
			esc_attr( $link_styles->css ),
			esc_url( $button_url ),
			$button_text
		);

		return Table_Wrapper_Helper::render_table_wrapper( $button_content, $table_attrs, $cell_attrs );
	}
}
