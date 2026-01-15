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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Video;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;

/**
 * Embed block renderer.
 * This renderer handles core/embed blocks, detecting audio and video provider embeds and rendering them appropriately.
 *
 * Audio providers: Spotify, SoundCloud, Pocket Casts, Mixcloud, ReverbNation - rendered as audio players.
 * Video providers: YouTube - rendered as video thumbnails with play buttons.
 */
class Embed extends Abstract_Block_Renderer {
	/**
	 * Supported audio providers with their configuration.
	 *
	 * @var array
	 */
	private const AUDIO_PROVIDERS = array(
		'pocket-casts' => array(
			'domains'  => array( 'pca.st' ),
			'base_url' => 'https://pca.st/',
		),
		'spotify'      => array(
			'domains'  => array( 'open.spotify.com' ),
			'base_url' => 'https://open.spotify.com/',
		),
		'soundcloud'   => array(
			'domains'  => array( 'soundcloud.com' ),
			'base_url' => 'https://soundcloud.com/',
		),
		'mixcloud'     => array(
			'domains'  => array( 'mixcloud.com' ),
			'base_url' => 'https://www.mixcloud.com/',
		),
		'reverbnation' => array(
			'domains'  => array( 'reverbnation.com' ),
			'base_url' => 'https://www.reverbnation.com/',
		),
	);

	/**
	 * Supported video providers with their configuration.
	 *
	 * @var array
	 */
	private const VIDEO_PROVIDERS = array(
		'youtube' => array(
			'domains'  => array( 'youtube.com', 'youtu.be' ),
			'base_url' => 'https://www.youtube.com/',
		),
	);

	/**
	 * Get all supported providers (audio and video).
	 *
	 * @return array All supported providers.
	 */
	private function get_all_supported_providers(): array {
		return array_merge( array_keys( self::AUDIO_PROVIDERS ), array_keys( self::VIDEO_PROVIDERS ) );
	}

	/**
	 * Get all provider configurations (audio and video).
	 *
	 * @return array All provider configurations.
	 */
	private function get_all_provider_configs(): array {
		return array_merge( self::AUDIO_PROVIDERS, self::VIDEO_PROVIDERS );
	}

	/**
	 * Detect provider from content by checking against known domains.
	 *
	 * @param string $content Content to check for provider domains.
	 * @return string Provider name or empty string if not found.
	 */
	private function detect_provider_from_domains( string $content ): string {
		$all_providers = $this->get_all_provider_configs();

		foreach ( $all_providers as $provider => $config ) {
			foreach ( $config['domains'] as $domain ) {
				if ( strpos( $content, $domain ) !== false ) {
					return $provider;
				}
			}
		}

		return '';
	}

	/**
	 * Validate URL using both filter_var and wp_http_validate_url.
	 *
	 * @param string $url URL to validate.
	 * @return bool True if URL is valid.
	 */
	private function is_valid_url( string $url ): bool {
		return ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) && wp_http_validate_url( $url );
	}

	/**
	 * Create fallback attributes for link rendering.
	 *
	 * @param string $url URL for the fallback.
	 * @param string $label Label for the fallback.
	 * @return array Fallback attributes.
	 */
	private function create_fallback_attributes( string $url, string $label ): array {
		return array(
			'url'   => $url,
			'label' => $label,
		);
	}

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

		// Check if this is a supported audio or video provider embed and has a valid URL.
		$provider = $this->get_supported_provider( $attr, $block_content );
		if ( empty( $provider ) ) {
			// For non-supported embeds, try to render as a simple link fallback.
			return $this->render_link_fallback( $attr, $block_content, $parsed_block, $rendering_context );
		}

		$url = $this->extract_provider_url( $attr, $block_content );
		if ( empty( $url ) ) {
			// Provider was detected but URL extraction failed - provide graceful fallback.
			return $this->render_link_fallback( $attr, $block_content, $parsed_block, $rendering_context );
		}

		// If we have a valid audio or video provider embed, proceed with normal rendering.
		return $this->render_content( $block_content, $parsed_block, $rendering_context );
	}

	/**
	 * Renders the embed block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context (required by parent contract but unused in this implementation).
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$attr = $parsed_block['attrs'] ?? array();

		// Get provider and URL (validation already done in render method).
		$provider = $this->get_supported_provider( $attr, $block_content );
		$url      = $this->extract_provider_url( $attr, $block_content );

		// Check if this is a video provider - render as video block.
		if ( $this->is_video_provider( $provider ) ) {
			return $this->render_video_embed( $url, $provider, $parsed_block, $rendering_context, $block_content );
		}

		// For audio providers, use the original audio rendering logic.
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
		$audio_result   = $audio_renderer->render( $mock_audio_block['innerHTML'], $mock_audio_block, $rendering_context );

		// If audio rendering fails, fall back to a simple link.
		if ( empty( $audio_result ) ) {
			$fallback_attr = $this->create_fallback_attributes( $url, $label );
			return $this->render_link_fallback( $fallback_attr, $block_content, $parsed_block, $rendering_context );
		}

		return $audio_result;
	}

	/**
	 * Get supported audio or video provider from block attributes or content.
	 *
	 * @param array  $attr Block attributes.
	 * @param string $block_content Block content.
	 * @return string Provider name or empty string if not supported.
	 */
	private function get_supported_provider( array $attr, string $block_content ): string {
		$all_supported_providers = $this->get_all_supported_providers();

		// Check provider name slug.
		if ( isset( $attr['providerNameSlug'] ) && in_array( $attr['providerNameSlug'], $all_supported_providers, true ) ) {
			return $attr['providerNameSlug'];
		}

		// Check for supported domains in URL or content.
		$url              = $attr['url'] ?? '';
		$content_to_check = ! empty( $url ) ? $url : $block_content;

		// Use sophisticated domain detection logic.
		return $this->detect_provider_from_domains( $content_to_check );
	}

	/**
	 * Extract URL from block content using DOM parsing.
	 *
	 * @param string $block_content Block content HTML.
	 * @return string Extracted URL or empty string.
	 */
	private function extract_url_from_content( string $block_content ): string {
		$dom_helper = new Dom_Document_Helper( $block_content );

		// Find the wp-block-embed__wrapper div.
		$wrapper_element = $dom_helper->find_element( 'div' );
		if ( $wrapper_element ) {
			// Check if this div has the correct class.
			$class_attr = $dom_helper->get_attribute_value( $wrapper_element, 'class' );
			if ( strpos( $class_attr, 'wp-block-embed__wrapper' ) !== false ) {
				// Get the text content (URL) from the div.
				$url = trim( $wrapper_element->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				// Decode HTML entities and validate URL.
				$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				// Validate the extracted URL.
				if ( $this->is_valid_url( $url ) ) {
					return $url;
				}
			}
		}

		return '';
	}

	/**
	 * Extract provider URL from block attributes or content.
	 *
	 * @param array  $attr Block attributes.
	 * @param string $block_content Block content.
	 * @return string Provider URL or empty string.
	 */
	private function extract_provider_url( array $attr, string $block_content ): string {
		// First, try to get URL from attributes.
		if ( ! empty( $attr['url'] ) ) {
			$url = $attr['url'];
			// Validate the URL from attributes.
			if ( $this->is_valid_url( $url ) ) {
				return $url;
			}
			return '';
		}

		// If not in attributes, extract from block content.
		return $this->extract_url_from_content( $block_content );
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

		// Get translated label for the provider.
		return $this->get_translated_provider_label( $provider );
	}

	/**
	 * Get translated label for a provider.
	 *
	 * @param string $provider Provider name.
	 * @return string Translated label for the provider.
	 */
	private function get_translated_provider_label( string $provider ): string {
		switch ( $provider ) {
			case 'spotify':
				return __( 'Listen on Spotify', 'woocommerce' );
			case 'soundcloud':
				return __( 'Listen on SoundCloud', 'woocommerce' );
			case 'pocket-casts':
				return __( 'Listen on Pocket Casts', 'woocommerce' );
			case 'mixcloud':
				return __( 'Listen on Mixcloud', 'woocommerce' );
			case 'reverbnation':
				return __( 'Listen on ReverbNation', 'woocommerce' );
			case 'youtube':
				return __( 'Watch on YouTube', 'woocommerce' );
			default:
				return __( 'Listen to the audio', 'woocommerce' );
		}
	}

	/**
	 * Render a simple link fallback for non-supported embeds.
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
			// First try the standard wrapper div extraction.
			$url = $this->extract_url_from_content( $block_content );

			// If still no URL, try to find any HTTP/HTTPS URL in the entire content.
			if ( empty( $url ) ) {
				$dom_helper   = new Dom_Document_Helper( $block_content );
				$body_element = $dom_helper->find_element( 'body' );
				if ( $body_element ) {
					$text_content = $body_element->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					// Look for HTTP/HTTPS URLs in the text content.
					if ( preg_match( '/(?<![a-zA-Z0-9.-])https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}[a-zA-Z0-9\/?=&%-]*(?![a-zA-Z0-9.-])/', $text_content, $matches ) ) {
						$url = $matches[0];
					}
				}
			}
		}

		// If still no URL, try to use provider-specific base URL if we have a provider.
		if ( empty( $url ) && isset( $attr['providerNameSlug'] ) ) {
			$url = $this->get_provider_base_url( $attr['providerNameSlug'] );
		}

		// Validate URL with both filter_var and wp_http_validate_url.
		if ( ! $this->is_valid_url( $url ) ) {
			return '';
		}

		// Get link text - use custom label if provided, otherwise use provider label for base URLs or URL.
		if ( ! empty( $attr['label'] ) ) {
			$link_text = $attr['label'];
		} else {
			// Check if this is a provider base URL (like https://open.spotify.com/).
			$provider = $attr['providerNameSlug'] ?? '';
			$base_url = $this->get_provider_base_url( $provider );

			if ( ! empty( $base_url ) && $url === $base_url ) {
				// Use provider-specific label for base URLs.
				$link_text = $this->get_provider_label( $provider, $attr );
			} else {
				// Use the URL itself for specific URLs.
				$link_text = $url;
			}
		}

		// Get color from email attributes or theme styles.
		$email_styles = $rendering_context->get_theme_styles();
		$link_color   = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#0073aa';
		// Sanitize color value to ensure it's a valid hex color or CSS variable.
		$link_color = Html_Processing_Helper::sanitize_color( $link_color );

		// Create a simple link.
		$link_html = sprintf(
			'<a href="%s" target="_blank" rel="noopener nofollow" style="color: %s; text-decoration: underline;">%s</a>',
			esc_url( $url ),
			esc_attr( $link_color ),
			esc_html( $link_text )
		);

		// Wrap with spacer if we have email attributes.
		return $this->add_spacer(
			$link_html,
			$parsed_block['email_attrs'] ?? array()
		);
	}

	/**
	 * Get base URL for a provider when specific URL extraction fails.
	 *
	 * @param string $provider Provider name.
	 * @return string Base URL for the provider or empty string.
	 */
	private function get_provider_base_url( string $provider ): string {
		$all_providers = $this->get_all_provider_configs();
		return $all_providers[ $provider ]['base_url'] ?? '';
	}

	/**
	 * Check if a provider is a video provider.
	 *
	 * @param string $provider Provider name.
	 * @return bool True if video provider.
	 */
	private function is_video_provider( string $provider ): bool {
		return array_key_exists( $provider, self::VIDEO_PROVIDERS );
	}

	/**
	 * Render a video embed using the Video renderer.
	 *
	 * @param string            $url URL of the video.
	 * @param string            $provider Provider name.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @param string            $block_content Original block content.
	 * @return string Rendered video embed or fallback.
	 */
	private function render_video_embed( string $url, string $provider, array $parsed_block, Rendering_Context $rendering_context, string $block_content ): string {
		// Try to get video thumbnail URL.
		$poster_url = $this->get_video_thumbnail_url( $url, $provider );

		// If no poster available, fall back to a simple link.
		if ( empty( $poster_url ) ) {
			$fallback_attr = $this->create_fallback_attributes( $url, $url );
			return $this->render_link_fallback( $fallback_attr, $block_content, $parsed_block, $rendering_context );
		}

		// Create a mock video block structure to reuse the Video renderer.
		$mock_video_block = array(
			'blockName' => 'core/video',
			'attrs'     => array(
				'poster' => $poster_url,
			),
			'innerHTML' => '<figure class="wp-block-video wp-block-embed is-type-video is-provider-' . esc_attr( $provider ) . '"><div class="wp-block-embed__wrapper">' . esc_url( $url ) . '</div></figure>',
		);

		// Copy email attributes to the mock block.
		if ( isset( $parsed_block['email_attrs'] ) ) {
			$mock_video_block['email_attrs'] = $parsed_block['email_attrs'];
		}

		// Use the Video renderer to render the video provider embed.
		$video_renderer = new Video();
		$video_result   = $video_renderer->render( $mock_video_block['innerHTML'], $mock_video_block, $rendering_context );

		// If video rendering fails, fall back to a simple link.
		if ( empty( $video_result ) ) {
			$fallback_attr = $this->create_fallback_attributes( $url, $url );
			return $this->render_link_fallback( $fallback_attr, $block_content, $parsed_block, $rendering_context );
		}

		return $video_result;
	}

	/**
	 * Get video thumbnail URL for supported providers.
	 *
	 * @param string $url Video URL.
	 * @param string $provider Provider name.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_video_thumbnail_url( string $url, string $provider ): string {
		// Currently only YouTube supports thumbnail extraction.
		if ( 'youtube' === $provider ) {
			return $this->get_youtube_thumbnail( $url );
		}

		// For other providers, we don't have thumbnail extraction implemented.
		// Return empty to trigger link fallback.
		return '';
	}

	/**
	 * Extract YouTube video thumbnail URL.
	 *
	 * @param string $url YouTube video URL.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_youtube_thumbnail( string $url ): string {
		// Extract video ID from various YouTube URL formats.
		$video_id = '';

		if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
			$video_id = $matches[1];
		}

		if ( empty( $video_id ) ) {
			return '';
		}

		// Return YouTube thumbnail URL.
		// Using 0.jpg format as shown in the example.
		return 'https://img.youtube.com/vi/' . $video_id . '/0.jpg';
	}
}
