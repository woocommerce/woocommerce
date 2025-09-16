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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;

/**
 * Video block renderer.
 * This renderer handles core/video blocks by reusing the cover block renderer
 * to show a thumbnail with a play button overlay.
 */
class Video extends Cover {
	/**
	 * Renders the video block content by transforming it into a cover block structure.
	 * Shows the video poster/thumbnail with a play button overlay using the parent cover renderer.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$block_attrs = $parsed_block['attrs'] ?? array();

		// Extract poster URL from video attributes.
		$poster_url = $this->extract_poster_url( $block_attrs, $block_content );

		// If no poster image, return empty content.
		if ( empty( $poster_url ) ) {
			return '';
		}

		// Transform video block into cover block structure and delegate to parent.
		$cover_block = $this->transform_to_cover_block( $parsed_block, $poster_url );
		return parent::render_content( $block_content, $cover_block, $rendering_context );
	}

	/**
	 * Extract poster URL from block attributes.
	 *
	 * @param array  $block_attrs Block attributes.
	 * @param string $block_content Original block content (unused, kept for consistency).
	 * @return string Poster URL or empty string.
	 */
	private function extract_poster_url( array $block_attrs, string $block_content ): string {
		// Check for poster attribute.
		if ( ! empty( $block_attrs['poster'] ) ) {
			return esc_url( $block_attrs['poster'] );
		}

		return '';
	}

	/**
	 * Transform a video block into a cover block structure.
	 *
	 * @param array  $video_block Original video block.
	 * @param string $poster_url Poster URL to use as background.
	 * @return array Cover block structure.
	 */
	private function transform_to_cover_block( array $video_block, string $poster_url ): array {
		$block_attrs = $video_block['attrs'] ?? array();
		$post_url    = $this->get_current_post_url();

		return array(
			'blockName'   => 'core/cover',
			'attrs'       => array(
				'url'       => $poster_url,
				'minHeight' => '390px', // Custom attribute for video blocks.
			),
			'innerBlocks' => array(
				array(
					'blockName'    => 'core/html',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $this->create_play_button_html( $post_url ),
					'innerContent' => array( $this->create_play_button_html( $post_url ) ),
				),
			),
			'innerHTML'   => $video_block['innerHTML'] ?? '',
		);
	}

	/**
	 * Create the play button HTML with optional link.
	 *
	 * @param string $link_url Optional URL to link to.
	 * @return string Play button HTML.
	 */
	private function create_play_button_html( string $link_url = '' ): string {
		$play_icon_url = $this->get_play_icon_url();

		$play_button = sprintf(
			'<img src="%s" alt="%s" style="width: 48px; height: 48px; display: inline-block;" />',
			esc_url( $play_icon_url ),
			// translators: Alt text for video play button icon.
			esc_attr( __( 'Play', 'woocommerce' ) )
		);

		// Wrap the play button in a link if URL is provided.
		if ( ! empty( $link_url ) ) {
			$play_button = sprintf(
				'<a href="%s" target="_blank" rel="noopener nofollow" style="display: inline-block; text-decoration: none;">%s</a>',
				esc_url( $link_url ),
				$play_button
			);
		}

		return sprintf(
			'<p style="text-align: center;">%s</p>',
			$play_button
		);
	}

	/**
	 * Get the URL for the play button icon.
	 *
	 * @return string Play button icon URL.
	 */
	private function get_play_icon_url(): string {
		// Get the plugin directory URL.
		$plugin_dir_url = plugins_url( '', __DIR__ );

		// Build the path to the play icon.
		return $plugin_dir_url . '/icons/video/play2x.png';
	}

	/**
	 * Get the current post permalink with security validation.
	 *
	 * @return string Post permalink or empty string if invalid.
	 */
	private function get_current_post_url(): string {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$permalink = get_permalink( $post->ID );
		if ( empty( $permalink ) ) {
			return '';
		}

		// Validate URL type and format (following audio block pattern).
		if ( ! str_starts_with( $permalink, 'https://' ) && ! str_starts_with( $permalink, 'http://' ) ) {
			// Reject non-HTTP protocols for security.
			return '';
		}

		// For all HTTP(S) URLs, validate with wp_http_validate_url.
		if ( ! wp_http_validate_url( $permalink ) ) {
			return '';
		}

		return $permalink;
	}
}
