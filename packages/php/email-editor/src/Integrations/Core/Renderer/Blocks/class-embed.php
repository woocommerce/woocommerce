<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Audio;

/**
 * Embed block renderer.
 * This renderer handles core/embed blocks, specifically detecting audio provider embeds (Spotify, SoundCloud, Pocket Casts) and rendering them as audio players.
 */
class Embed extends Abstract_Block_Renderer {
	/**
	 * Renders the embed block.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Validate input parameters and required dependencies.
		if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ||
			! class_exists( '\Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper' ) ) {
			return '';
		}

		$attr = $parsed_block['attrs'];

		// Check if this is a supported audio provider embed and has a valid URL.
		$provider = $this->get_supported_provider( $attr, $block_content );
		if ( empty( $provider ) ) {
			// For non-audio embeds, try to render as a simple link fallback.
			return $this->render_link_fallback( $attr, $block_content, $parsed_block, $rendering_context );
		}

		$url = $this->extract_provider_url( $attr, $block_content, $provider );
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		// If we have a valid audio provider embed, proceed with normal rendering.
		return $this->add_spacer(
			$this->render_content( $block_content, $parsed_block, $rendering_context ),
			$parsed_block['email_attrs'] ?? array()
		);
	}

	/**
	 * Renders the embed block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$attr = $parsed_block['attrs'] ?? array();

		// Get provider and URL (validation already done in render method).
		$provider = $this->get_supported_provider( $attr, $block_content );
		$url      = $this->extract_provider_url( $attr, $block_content, $provider );

		// Get appropriate label for the provider.
		$label = $this->get_provider_label( $provider, $attr );

		// Create a mock audio block structure to reuse the Audio renderer.
		$mock_audio_block = array(
			'blockName' => 'core/audio',
			'attrs'     => array(
				'src'   => $url,
				'label' => $label,
			),
			'innerHTML' => '<figure class="wp-block-audio"><audio controls src="' . esc_attr( $url ) . '"></audio></figure>',
		);

		// Copy email attributes to the mock block.
		if ( isset( $parsed_block['email_attrs'] ) ) {
			$mock_audio_block['email_attrs'] = $parsed_block['email_attrs'];
		}

		// Use the Audio renderer to render the audio provider embed.
		$audio_renderer = new Audio();
		return $audio_renderer->render( $mock_audio_block['innerHTML'], $mock_audio_block, $rendering_context );
	}

	/**
	 * Get supported audio provider from block attributes or content.
	 *
	 * @param array  $attr Block attributes.
	 * @param string $block_content Block content.
	 * @return string Provider name or empty string if not supported.
	 */
	private function get_supported_provider( array $attr, string $block_content ): string {
		$supported_providers = array( 'pocket-casts', 'spotify', 'soundcloud' );

		// Check provider name slug.
		if ( isset( $attr['providerNameSlug'] ) && in_array( $attr['providerNameSlug'], $supported_providers, true ) ) {
			return $attr['providerNameSlug'];
		}

		// Check URL for supported domains.
		$url = $attr['url'] ?? '';
		if ( ! empty( $url ) ) {
			if ( str_contains( $url, 'open.spotify.com' ) ) {
				return 'spotify';
			}
			if ( str_contains( $url, 'soundcloud.com' ) ) {
				return 'soundcloud';
			}
			if ( str_contains( $url, 'pca.st' ) ) {
				return 'pocket-casts';
			}
		}

		// Check block content for supported URLs.
		if ( str_contains( $block_content, 'open.spotify.com' ) ) {
			return 'spotify';
		}
		if ( str_contains( $block_content, 'soundcloud.com' ) ) {
			return 'soundcloud';
		}
		if ( str_contains( $block_content, 'pca.st' ) ) {
			return 'pocket-casts';
		}

		return '';
	}

	/**
	 * Extract provider URL from block attributes or content.
	 *
	 * @param array  $attr Block attributes.
	 * @param string $block_content Block content.
	 * @param string $provider Provider name.
	 * @return string Provider URL or empty string.
	 */
	private function extract_provider_url( array $attr, string $block_content, string $provider ): string {
		// First, try to get URL from attributes.
		if ( ! empty( $attr['url'] ) ) {
			return $attr['url'];
		}

		// If not in attributes, extract from block content based on provider.
		$patterns = array(
			'spotify'      => '/https:\/\/open\.spotify\.com\/[^\s<>"]+/',
			'soundcloud'   => '/https:\/\/soundcloud\.com\/[^\s<>"]+/',
			'pocket-casts' => '/https:\/\/pca\.st\/[^\s<>"]+/',
		);

		if ( isset( $patterns[ $provider ] ) && preg_match( $patterns[ $provider ], $block_content, $matches ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * Get appropriate label for the provider.
	 *
	 * @param string $provider Provider name.
	 * @param array  $attr Block attributes.
	 * @return string Label for the provider.
	 */
	private function get_provider_label( string $provider, array $attr ): string {
		// Use custom label if provided.
		if ( ! empty( $attr['label'] ) ) {
			return $attr['label'];
		}

		// Use default label based on provider.
		switch ( $provider ) {
			case 'spotify':
				return __( 'Listen on Spotify', 'woocommerce' );
			case 'soundcloud':
				return __( 'Listen on SoundCloud', 'woocommerce' );
			case 'pocket-casts':
				return __( 'Listen on Pocket Casts', 'woocommerce' );
			default:
				return __( 'Listen to the audio', 'woocommerce' );
		}
	}

	/**
	 * Render a simple link fallback for non-audio embeds.
	 *
	 * @param array             $attr Block attributes.
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered link or empty string if no valid URL.
	 */
	private function render_link_fallback( array $attr, string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Try to get URL from attributes first.
		$url = $attr['url'] ?? '';

		// If no URL in attributes, try to extract from block content.
		if ( empty( $url ) ) {
			// Look for any HTTP/HTTPS URL in the content.
			if ( preg_match( '/https?:\/\/[^\s<>"]+/', $block_content, $matches ) ) {
				$url = $matches[0];
			}
		}

		// Validate URL.
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		// Get link text - use custom label if provided, otherwise use URL.
		$link_text = ! empty( $attr['label'] ) ? $attr['label'] : $url;

		// Create a simple link.
		$link_html = sprintf(
			'<a href="%s" target="_blank" rel="noopener nofollow" style="color: #0073aa; text-decoration: underline;">%s</a>',
			esc_url( $url ),
			esc_html( $link_text )
		);

		// Wrap with spacer if we have email attributes.
		return $this->add_spacer(
			$link_html,
			$parsed_block['email_attrs'] ?? array()
		);
	}
}
