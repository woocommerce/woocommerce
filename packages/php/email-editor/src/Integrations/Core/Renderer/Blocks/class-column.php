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
 * Renders a column block.
 */
class Column extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for columns.
	 * Spacing is applied on wrapping columns block. Columns are rendered side by side so no spacer is needed.
	 *
	 * @param string $content Content.
	 * @param array  $email_attrs Email attributes.
	 */
	protected function add_spacer( $content, $email_attrs ): string {
		return $content;
	}

	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$content = '';
		foreach ( $parsed_block['innerBlocks'] ?? array() as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{column_content}',
			$content,
			$this->get_block_wrapper( $block_content, $parsed_block, $settings_controller )
		);
	}

	/**
	 * Based on MJML <mj-column>
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	private function get_block_wrapper( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes           = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'verticalAlignment' => 'stretch',
				'width'             => $settings_controller->get_layout_width_without_padding(),
				'style'             => array(),
			)
		);

		// The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
		// to create a feeling of a stretched column. This also needs to apply to CSS classnames which can also apply styles.
		$is_stretched = empty( $block_attributes['verticalAlignment'] ) || 'stretch' === $block_attributes['verticalAlignment'];

		$padding_css = $this->get_styles_from_block( array( 'spacing' => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ) ) )['css'];
		$cell_styles = $this->get_styles_from_block(
			array(
				'color'      => $block_attributes['style']['color'] ?? array(),
				'background' => $block_attributes['style']['background'] ?? array(),
			)
		)['declarations'];

		$border_styles = $this->get_styles_from_block( array( 'border' => $block_attributes['style']['border'] ?? array() ) )['declarations'];

		if ( ! empty( $border_styles ) ) {
			$cell_styles = array_merge( $cell_styles, array( 'border-style' => 'solid' ), $border_styles );
		}

		if ( ! empty( $cell_styles['background-image'] ) && empty( $cell_styles['background-size'] ) ) {
			$cell_styles['background-size'] = 'cover';
		}

		$wrapper_classname = 'block wp-block-column email-block-column';
		$content_classname = 'email-block-column-content';
		$wrapper_css       = WP_Style_Engine::compile_css(
			array(
				'vertical-align' => $is_stretched ? 'top' : $block_attributes['verticalAlignment'],
			),
			''
		);
		$content_css       = 'vertical-align: top;';

		if ( $is_stretched ) {
			$wrapper_classname .= ' ' . $original_wrapper_classname;
			$wrapper_css       .= ' ' . WP_Style_Engine::compile_css( $cell_styles, '' );
		} else {
			$content_classname .= ' ' . $original_wrapper_classname;
			$content_css       .= ' ' . WP_Style_Engine::compile_css( $cell_styles, '' );
		}

		return '
      <td class="' . esc_attr( $wrapper_classname ) . '" style="' . esc_attr( $wrapper_css ) . '" width="' . esc_attr( $block_attributes['width'] ) . '">
        <table class="' . esc_attr( $content_classname ) . '" style="' . esc_attr( $content_css ) . '" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
          <tbody>
            <tr>
              <td align="left" style="text-align:left;' . esc_attr( $padding_css ) . '">
                {column_content}
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    ';
	}
}
