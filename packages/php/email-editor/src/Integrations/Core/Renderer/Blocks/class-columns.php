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
 * Renders a columns block.
 */
class Columns extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for columns.
	 * Spacing is applied on wrapping columns block. Columns are rendered side by side so no spacer is needed.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	protected function render_content( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$content = '';
		foreach ( $parsed_block['innerBlocks'] ?? array() as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{columns_content}',
			$content,
			$this->getBlockWrapper( $block_content, $parsed_block, $settings_controller )
		);
	}

	/**
	 * Based on MJML <mj-section>
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	private function getBlockWrapper( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes           = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'align' => null,
				'width' => $settings_controller->get_layout_width_without_padding(),
				'style' => array(),
			)
		);

		$columns_styles = $this->get_styles_from_block(
			array(
				'spacing'    => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
				'color'      => $block_attributes['style']['color'] ?? array(),
				'background' => $block_attributes['style']['background'] ?? array(),
			)
		)['declarations'];

		$border_styles = $this->get_styles_from_block( array( 'border' => $block_attributes['style']['border'] ?? array() ) )['declarations'];

		if ( ! empty( $border_styles ) ) {
			$columns_styles = array_merge( $columns_styles, array( 'border-style' => 'solid' ), $border_styles );
		}

		if ( empty( $columns_styles['background-size'] ) ) {
			$columns_styles['background-size'] = 'cover';
		}

		$rendered_columns = '<table class="' . esc_attr( 'email-block-columns ' . $original_wrapper_classname ) . '" style="width:100%;border-collapse:separate;text-align:left;' . esc_attr( WP_Style_Engine::compile_css( $columns_styles, '' ) ) . '" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tbody>
        <tr>{columns_content}</tr>
      </tbody>
    </table>';

		// Margins are not supported well in outlook for tables, so wrap in another table.
		$margins = $block_attributes['style']['spacing']['margin'] ?? array();

		if ( ! empty( $margins ) ) {
			$margin_to_padding_styles = $this->get_styles_from_block(
				array(
					'spacing' => array( 'margin' => $margins ),
				)
			)['css'];
			$rendered_columns         = '<table class="email-block-columns-wrapper" style="width:100%;border-collapse:separate;text-align:left;' . esc_attr( $margin_to_padding_styles ) . '" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
          <tr>
            <td>' . $rendered_columns . '</td>
          </tr>
        </tbody>
      </table>';
		}

		return $rendered_columns;
	}
}
