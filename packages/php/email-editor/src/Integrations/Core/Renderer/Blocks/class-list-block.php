<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;

/**
 * Renders a list block.
 * We have to avoid using keyword `List`
 */
class List_Block extends Abstract_Block_Renderer {
	/**
	 * Render the block.
	 *
	 * The render_content() method applies margin-top on its inner wrapper div
	 * where Gmail preserves it. Strip margin-top from email_attrs so
	 * add_spacer() doesn't apply it again on the outer wrapper.
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$content     = $this->render_content( $block_content, $parsed_block, $rendering_context );
		$email_attrs = $parsed_block['email_attrs'] ?? array();
		unset( $email_attrs['margin-top'] );
		return $this->add_spacer( $content, $email_attrs );
	}

	/**
	 * Renders the block content
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$html     = new \WP_HTML_Tag_Processor( $block_content );
		$tag_name = ( $parsed_block['attrs']['ordered'] ?? false ) ? 'ol' : 'ul';
		if ( $html->next_tag( array( 'tag_name' => $tag_name ) ) ) {
			/** @var string $styles */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$styles = $html->get_attribute( 'style' ) ?? '';
			$styles = Styles_Helper::parse_styles_to_array( $styles );

			// Font size.
			if ( isset( $parsed_block['email_attrs']['font-size'] ) ) {
				$styles['font-size'] = $parsed_block['email_attrs']['font-size'];
			} else {
				// Use font-size from email theme when those properties are not set.
				$theme_data          = $rendering_context->get_theme_json()->get_data();
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
