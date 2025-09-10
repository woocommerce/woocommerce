<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Audio block renderer.
 * This renderer handles core/audio blocks for email.
 */
class Audio extends Abstract_Block_Renderer {
	/**
	 * Render the block.
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Validate input parameters and required dependencies.
		if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ||
			! class_exists( '\Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper' ) ) {
			return '';
		}

		$attr = $parsed_block['attrs'];

		// Check if we have a valid audio source - return empty string immediately if not.
		// For attachments, check the 'id' attribute. For external URLs, check if src exists in HTML content.
		$has_attachment_id = ! empty( $attr['id'] );
		$has_src_in_html   = preg_match( '#<audio[^>]*\ssrc=["\']([^"\']*)["\'][^>]*/?>#', $block_content );

		// If we have neither an attachment ID nor a src in the HTML content, return empty.
		if ( ! $has_attachment_id && ! $has_src_in_html ) {
			return '';
		}

		// If we have a valid source, proceed with normal rendering.
		$rendered_content = $this->render_content( $block_content, $parsed_block, $rendering_context );

		// If render_content returns empty (e.g., invalid URL), return empty string.
		if ( empty( $rendered_content ) ) {
			return '';
		}

		return $this->add_spacer( $rendered_content, $parsed_block['email_attrs'] ?? array() );
	}
	/**
	 * Renders the audio block content as an audio player for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context (required by parent contract but unused in this implementation).
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$attr = $parsed_block['attrs'];

		// Get URL and length.
		if ( isset( $attr['id'] ) ) {
			// Audio file from site's media library.
			$url    = \wp_get_attachment_url( $attr['id'] );
			$meta   = \get_post_meta( $attr['id'], '_wp_attachment_metadata', true );
			$length = ( is_array( $meta ) && isset( $meta['length_formatted'] ) && is_string( $meta['length_formatted'] ) ) ? $meta['length_formatted'] : '';
		} else {
			// Audio file from external URL.
			preg_match( '#<audio[^>]*\ssrc=["\']([^"\']*)["\'][^>]*/?>#', $block_content, $audio );
			$url    = isset( $audio[1] ) ? $audio[1] : $attr['src'] ?? '';
			$length = null;
		}

		// Validate URL with proper ordering and comprehensive checks.
		if ( empty( $url ) ) {
			return '';
		}

		// Validate URL type and format.
		if ( ! str_starts_with( $url, 'data:audio/' ) &&
			! str_starts_with( $url, '/' ) &&
			! str_starts_with( $url, 'https://' ) ) {
			// Reject everything else (http://, ftp://, relative paths, etc.).
			return '';
		}

		// For HTTPS URLs, validate with wp_http_validate_url.
		if ( str_starts_with( $url, 'https://' ) && ! wp_http_validate_url( $url ) ) {
			return '';
		}

		// Get spacing from email_attrs for better consistency with core blocks.
		$email_attrs        = $parsed_block['email_attrs'] ?? array();
		$table_margin_style = '';

		if ( ! empty( $email_attrs ) && class_exists( '\WP_Style_Engine' ) ) {
			// Get margin for table styling.
			$table_margin_style = \WP_Style_Engine::compile_css( array_intersect_key( $email_attrs, array_flip( array( 'margin' ) ) ), '' );
		}

		$icon_image = $this->get_audio_icon_url();
		$label      = ! empty( $attr['label'] ) ? $attr['label'] : __( 'Listen to the audio', 'woocommerce' );

		// Add duration to label if available.
		if ( ! empty( $length ) ) {
			$label .= ' (' . esc_html( (string) $length ) . ')';
		}

		$audio_url = esc_url( $url );

		// Define pill-style colors and styling.
		$background_color = '#f6f7f7';
		$border_color     = '#AAA';
		$icon_size        = '18px';
		$font_size        = '14px';

		// Generate the icon content.
		$icon_content = sprintf(
			'<a href="%1$s" rel="noopener nofollow" target="_blank" style="padding: 0.25em; padding-left: 17px; display: inline-block; vertical-align: middle;"><img height="%2$s" src="%3$s" style="display:block;margin-right:0;vertical-align:middle;" width="%2$s" alt="%4$s"></a>',
			$audio_url,
			esc_attr( $icon_size ),
			esc_url( $icon_image ),
			// translators: %s is the audio player icon.
			sprintf( __( '%s icon', 'woocommerce' ), __( 'Audio', 'woocommerce' ) )
		);
		$icon_content = Table_Wrapper_Helper::render_table_cell( $icon_content, array( 'style' => sprintf( 'vertical-align:middle;font-size:%s;', $font_size ) ) );

		// Generate the label content.
		$label_content    = sprintf(
			'<a href="%1$s" rel="noopener nofollow" target="_blank" style="text-decoration:none; padding: 0.25em; padding-right: 17px; display: inline-block;"><span style="margin-left:.5em;margin-right:.5em;font-weight:bold"> %2$s </span></a>',
			$audio_url,
			esc_html( $label )
		);
		$label_cell_style = sprintf(
			'vertical-align:middle;font-size:%s;',
			$font_size
		);
		$label_content    = Table_Wrapper_Helper::render_table_cell( $label_content, array( 'style' => $label_cell_style ) );

		// Combine icon and label tables.
		$audio_content = $icon_content . $label_content;

		// Create the main pill-style table.
		$main_table_styles = sprintf(
			'background-color: %s; border-radius: 9999px; float: none; border: 1px solid %s; border-collapse: separate;',
			$background_color,
			$border_color
		);

		$main_table_attrs = array(
			'align' => 'left',
			'style' => $main_table_styles,
		);

		$main_table = Table_Wrapper_Helper::render_table_wrapper( $audio_content, $main_table_attrs, array(), array(), false );

		// Create the main wrapper table.
		$table_style = 'width: 100%;';
		if ( ! empty( $table_margin_style ) ) {
			$table_style = $table_margin_style . '; ' . $table_style;
		} else {
			$table_style = 'margin: 16px 0; ' . $table_style;
		}

		$table_attrs = array(
			'style' => $table_style,
		);

		$cell_attrs = array(
			'style' => 'min-width: 100%; vertical-align: middle; word-break: break-word; text-align: left;',
		);

		$main_wrapper = Table_Wrapper_Helper::render_table_wrapper( $main_table, $table_attrs, $cell_attrs );

		return Table_Wrapper_Helper::render_outlook_table_wrapper( $main_wrapper, array( 'align' => 'left' ) );
	}

	/**
	 * Gets the audio icon URL.
	 *
	 * @return string The audio icon URL.
	 */
	private function get_audio_icon_url(): string {
		$file_name = '/icons/audio/audio-play.png';
		return plugins_url( $file_name, __FILE__ );
	}
}
