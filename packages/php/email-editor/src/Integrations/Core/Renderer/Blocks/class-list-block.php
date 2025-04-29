<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * Renders a list block.
 * We have to avoid using keyword `List`
 */
class List_Block extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$html     = new \WP_HTML_Tag_Processor( $block_content );
		$tag_name = ( $parsed_block['attrs']['ordered'] ?? false ) ? 'ol' : 'ul';
		if ( $html->next_tag( array( 'tag_name' => $tag_name ) ) ) {
			/** @var string $styles */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$styles = $html->get_attribute( 'style' ) ?? '';
			$styles = $settings_controller->parse_styles_to_array( $styles );

			// Font size.
			if ( isset( $parsed_block['email_attrs']['font-size'] ) ) {
				$styles['font-size'] = $parsed_block['email_attrs']['font-size'];
			} else {
				// Use font-size from email theme when those properties are not set.
				$theme_data          = $settings_controller->get_theme()->get_data();
				$styles['font-size'] = $theme_data['styles']['typography']['fontSize'];
			}

			$html->set_attribute( 'style', esc_attr( \WP_Style_Engine::compile_css( $styles, '' ) ) );
			$block_content = $html->get_updated_html();
		}

		$wrapper_style = \WP_Style_Engine::compile_css(
			array(
				'margin-top' => $parsed_block['email_attrs']['margin-top'] ?? '0px',
			),
			''
		);

		// \WP_HTML_Tag_Processor escapes the content, so we have to replace it back
		$block_content = str_replace( '&#039;', "'", $block_content );

		return sprintf(
			'<div style="%1$s">%2$s</div>',
			esc_attr( $wrapper_style ),
			$block_content
		);
	}
}
