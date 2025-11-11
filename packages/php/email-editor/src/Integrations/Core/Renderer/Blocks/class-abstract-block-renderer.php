<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use WP_Style_Engine;

/**
 * Shared functionality for block renderers.
 */
abstract class Abstract_Block_Renderer implements Block_Renderer {
	/**
	 * Wrapper for wp_style_engine_get_styles which ensures all values are returned.
	 *
	 * @param array $block_styles Array of block styles.
	 * @param bool  $skip_convert_vars If true, --wp_preset--spacing--x type values will be left in the original var:preset:spacing:x format.
	 * @return array
	 */
	protected function get_styles_from_block( array $block_styles, $skip_convert_vars = false ) {
		return Styles_Helper::get_styles_from_block( $block_styles, $skip_convert_vars );
	}

	/**
	 * Compile objects containing CSS properties to a string.
	 *
	 * @param array ...$styles Style arrays to compile.
	 * @return string
	 */
	protected function compile_css( ...$styles ): string {
		return WP_Style_Engine::compile_css( array_merge( ...$styles ), '' );
	}

	/**
	 * Extract inner content from a wrapper element.
	 *
	 * Removes the outer wrapper element (e.g., div) and returns only the inner HTML content.
	 * This is useful when you need to strip the wrapper and use only the inner content.
	 *
	 * @param string $block_content Block content with wrapper element.
	 * @param string $tag_name      Tag name of the wrapper element (default: 'div').
	 * @return string Inner content without the wrapper element, or original content if wrapper not found.
	 */
	protected function get_inner_content( string $block_content, string $tag_name = 'div' ): string {
		$dom_helper = new Dom_Document_Helper( $block_content );
		$element    = $dom_helper->find_element( $tag_name );

		return $element ? $dom_helper->get_element_inner_html( $element ) : $block_content;
	}

	/**
	 * Add a spacer around the block.
	 *
	 * @param string $content The block content.
	 * @param array  $email_attrs The email attributes.
	 * @return string
	 */
	protected function add_spacer( $content, $email_attrs ): string {
		$gap_style     = WP_Style_Engine::compile_css( array_intersect_key( $email_attrs, array_flip( array( 'margin-top' ) ) ), '' ) ?? '';
		$padding_style = WP_Style_Engine::compile_css( array_intersect_key( $email_attrs, array_flip( array( 'padding-left', 'padding-right' ) ) ), '' ) ?? '';

		$table_attrs = array(
			'align' => 'left',
			'width' => '100%',
			'style' => $gap_style,
		);

		$cell_attrs = array(
			'style' => $padding_style,
		);

		$div_content = sprintf(
			'<div class="email-block-layout" style="%1$s %2$s">%3$s</div>',
			esc_attr( $gap_style ),
			esc_attr( $padding_style ),
			$content
		);

		return Table_Wrapper_Helper::render_outlook_table_wrapper( $div_content, $table_attrs, $cell_attrs );
	}

	/**
	 * Render the block.
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		return $this->add_spacer(
			$this->render_content( $block_content, $parsed_block, $rendering_context ),
			$parsed_block['email_attrs'] ?? array()
		);
	}

	/**
	 * Render the block content.
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string
	 */
	abstract protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string;
}
