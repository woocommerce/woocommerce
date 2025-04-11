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
 * Renders a group block.
 */
class Group extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$content      = '';
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		foreach ( $inner_blocks as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{group_content}',
			$content,
			$this->get_block_wrapper( $block_content, $parsed_block, $settings_controller )
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
		$original_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes   = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style'           => array(),
				'backgroundColor' => '',
				'textColor'       => '',
				'borderColor'     => '',
				'layout'          => array(),
			)
		);

		// Layout, background, borders need to be on the outer table element.
		$table_styles = $this->get_styles_from_block(
			array(
				'color'      => array_filter(
					array(
						'background' => $block_attributes['backgroundColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['backgroundColor'] ) : null,
						'text'       => $block_attributes['textColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['textColor'] ) : null,
						'border'     => $block_attributes['borderColor'] ? $settings_controller->translate_slug_to_color( $block_attributes['borderColor'] ) : null,
					)
				),
				'background' => $block_attributes['style']['background'] ?? array(),
				'border'     => $block_attributes['style']['border'] ?? array(),
				'spacing'    => array( 'padding' => $block_attributes['style']['spacing']['margin'] ?? array() ),
			)
		)['declarations'];

		$table_styles['border-collapse'] = 'separate'; // Needed for the border radius to work.

		// Padding properties need to be added to the table cell.
		$cell_styles = $this->get_styles_from_block(
			array(
				'spacing' => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
			)
		)['declarations'];

		$table_styles['background-size'] = empty( $table_styles['background-size'] ) ? 'cover' : $table_styles['background-size'];
		$width                           = $parsed_block['email_attrs']['width'] ?? '100%';

		return sprintf(
			'<table class="email-block-group %3$s" style="%1$s" width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
          <tr>
            <td class="email-block-group-content" style="%2$s" width="%4$s">
              {group_content}
            </td>
          </tr>
        </tbody>
      </table>',
			esc_attr( WP_Style_Engine::compile_css( $table_styles, '' ) ),
			esc_attr( WP_Style_Engine::compile_css( $cell_styles, '' ) ),
			esc_attr( $original_classname ),
			esc_attr( $width ),
		);
	}
}
