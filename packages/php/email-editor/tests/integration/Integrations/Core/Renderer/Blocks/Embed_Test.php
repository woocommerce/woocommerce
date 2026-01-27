<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Embed;

/**
 * Integration test for Embed class
 */
class Embed_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Embed renderer instance
	 *
	 * @var Embed
	 */
	private $embed_renderer;

	/**
	 * Spotify embed block configuration
	 *
	 * @var array
	 */
	private $parsed_spotify_embed = array(
		'blockName' => 'core/embed',
		'attrs'     => array(
			'url'              => 'https://open.spotify.com/playlist/6nJ2YD6eYJvsjqOBvDjWwB?si=a5c73423d57e43de',
			'type'             => 'rich',
			'providerNameSlug' => 'spotify',
			'responsive'       => true,
			'className'        => 'wp-embed-aspect-21-9 wp-has-aspect-ratio',
		),
		'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-spotify wp-block-embed-spotify wp-embed-aspect-21-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">https://open.spotify.com/playlist/6nJ2YD6eYJvsjqOBvDjWwB?si=a5c73423d57e43de</div></figure>',
	);

	/**
	 * SoundCloud embed block configuration
	 *
	 * @var array
	 */
	private $parsed_soundcloud_embed = array(
		'blockName' => 'core/embed',
		'attrs'     => array(
			'url'              => 'https://soundcloud.com/trending-music-us/sets/electronic-1',
			'type'             => 'rich',
			'providerNameSlug' => 'soundcloud',
			'responsive'       => true,
			'className'        => 'wp-embed-aspect-4-3 wp-has-aspect-ratio',
		),
		'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-soundcloud wp-block-embed-soundcloud wp-embed-aspect-4-3 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">https://soundcloud.com/trending-music-us/sets/electronic-1</div></figure>',
	);

	/**
	 * Pocket Casts embed block configuration
	 *
	 * @var array
	 */
	private $parsed_pocket_casts_embed = array(
		'blockName' => 'core/embed',
		'attrs'     => array(
			'url'              => 'https://pca.st/episode/1a84a361-a387-42e5-b65e-70adacc563ea',
			'type'             => 'rich',
			'providerNameSlug' => 'pocket-casts',
			'responsive'       => true,
		),
		'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-pocket-casts wp-block-embed-pocket-casts"><div class="wp-block-embed__wrapper">https://pca.st/episode/1a84a361-a387-42e5-b65e-70adacc563ea</div></figure>',
	);

	/**
	 * YouTube embed block configuration (non-audio provider for link fallback testing)
	 *
	 * @var array
	 */
	private $parsed_youtube_embed = array(
		'blockName' => 'core/embed',
		'attrs'     => array(
			'url'              => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
			'type'             => 'video',
			'providerNameSlug' => 'youtube',
			'responsive'       => true,
		),
		'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=dQw4w9WgXcQ</div></figure>',
	);


	/**
	 * Rendering context instance.
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up the test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->embed_renderer    = new Embed();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test that Spotify embed renders as audio player
	 */
	public function test_renders_spotify_embed_as_audio_player(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $this->parsed_spotify_embed, $this->rendering_context );

		// Check that the rendered content contains Spotify player elements.
		$this->assertStringContainsString( 'Listen on Spotify', $rendered );
		$this->assertStringContainsString( 'audio-play.png', $rendered );
		$this->assertStringContainsString( 'https://open.spotify.com/playlist/6nJ2YD6eYJvsjqOBvDjWwB', $rendered );
		$this->assertStringContainsString( 'border-radius: 9999px', $rendered );
		$this->assertStringContainsString( 'background-color: #f6f7f7', $rendered );
	}


	/**
	 * Test that embed block uses custom label when provided
	 */
	public function test_uses_custom_label_when_provided(): void {
		$parsed_spotify_custom_label                   = $this->parsed_spotify_embed;
		$parsed_spotify_custom_label['attrs']['label'] = 'Play this playlist';

		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $parsed_spotify_custom_label, $this->rendering_context );

		$this->assertStringContainsString( 'Play this playlist', $rendered );
		$this->assertStringNotContainsString( 'Listen on Spotify', $rendered );
	}

	/**
	 * Test that embed block handles email attributes for spacing
	 */
	public function test_handles_email_attributes_for_spacing(): void {
		$parsed_spotify_with_spacing                = $this->parsed_spotify_embed;
		$parsed_spotify_with_spacing['email_attrs'] = array(
			'margin' => '20px 0',
		);

		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $parsed_spotify_with_spacing, $this->rendering_context );

		$this->assertStringContainsString( 'margin:20px 0', $rendered );
	}

	/**
	 * Test that embed block uses default spacing when no email attributes
	 */
	public function test_uses_default_spacing_when_no_email_attributes(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $this->parsed_spotify_embed, $this->rendering_context );

		$this->assertStringContainsString( 'margin: 16px 0', $rendered );
	}

	/**
	 * Test that embed block includes proper security attributes
	 */
	public function test_includes_proper_security_attributes(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $this->parsed_spotify_embed, $this->rendering_context );

		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
	}

	/**
	 * Test that embed block includes proper accessibility attributes
	 */
	public function test_includes_proper_accessibility_attributes(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $this->parsed_spotify_embed, $this->rendering_context );

		$this->assertStringContainsString( 'alt="', $rendered );
		$this->assertStringContainsString( 'Audio icon', $rendered );
	}

	/**
	 * Test that embed block detects Spotify by providerNameSlug
	 */
	public function test_detects_spotify_by_provider_name_slug(): void {
		$parsed_spotify_by_slug = $this->parsed_spotify_embed;
		unset( $parsed_spotify_by_slug['attrs']['url'] );
		$parsed_spotify_by_slug['innerHTML'] = '<figure class="wp-block-embed is-type-rich is-provider-spotify"><div class="wp-block-embed__wrapper">Some content</div></figure>';

		$rendered = $this->embed_renderer->render( $parsed_spotify_by_slug['innerHTML'], $parsed_spotify_by_slug, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available.
		$this->assertStringContainsString( '<a href="https://open.spotify.com/"', $rendered );
		$this->assertStringContainsString( 'Listen on Spotify', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that embed block detects Spotify by URL in attributes
	 */
	public function test_detects_spotify_by_url_in_attributes(): void {
		$parsed_spotify_by_url = $this->parsed_spotify_embed;
		unset( $parsed_spotify_by_url['attrs']['providerNameSlug'] );

		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $parsed_spotify_by_url, $this->rendering_context );

		$this->assertStringContainsString( 'Listen on Spotify', $rendered );
		$this->assertStringContainsString( 'https://open.spotify.com/playlist/6nJ2YD6eYJvsjqOBvDjWwB', $rendered );
	}

	/**
	 * Test that embed block detects Spotify by URL in content
	 */
	public function test_detects_spotify_by_url_in_content(): void {
		$parsed_spotify_by_content = $this->parsed_spotify_embed;
		unset( $parsed_spotify_by_content['attrs']['providerNameSlug'] );
		unset( $parsed_spotify_by_content['attrs']['url'] );

		$rendered = $this->embed_renderer->render( $this->parsed_spotify_embed['innerHTML'], $parsed_spotify_by_content, $this->rendering_context );

		$this->assertStringContainsString( 'Listen on Spotify', $rendered );
		$this->assertStringContainsString( 'https://open.spotify.com/playlist/6nJ2YD6eYJvsjqOBvDjWwB', $rendered );
	}

	/**
	 * Test that SoundCloud embed renders as audio player
	 */
	public function test_renders_soundcloud_embed_as_audio_player(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_soundcloud_embed['innerHTML'], $this->parsed_soundcloud_embed, $this->rendering_context );

		// Check that the rendered content contains SoundCloud player elements.
		$this->assertStringContainsString( 'Listen on SoundCloud', $rendered );
		$this->assertStringContainsString( 'audio-play.png', $rendered );
		$this->assertStringContainsString( 'https://soundcloud.com/trending-music-us/sets/electronic-1', $rendered );
		$this->assertStringContainsString( 'border-radius: 9999px', $rendered );
		$this->assertStringContainsString( 'background-color: #f6f7f7', $rendered );
	}

	/**
	 * Test that Pocket Casts embed renders as audio player
	 */
	public function test_renders_pocket_casts_embed_as_audio_player(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_pocket_casts_embed['innerHTML'], $this->parsed_pocket_casts_embed, $this->rendering_context );

		// Check that the rendered content contains Pocket Casts player elements.
		$this->assertStringContainsString( 'Listen on Pocket Casts', $rendered );
		$this->assertStringContainsString( 'audio-play.png', $rendered );
		$this->assertStringContainsString( 'https://pca.st/episode/1a84a361-a387-42e5-b65e-70adacc563ea', $rendered );
		$this->assertStringContainsString( 'border-radius: 9999px', $rendered );
		$this->assertStringContainsString( 'background-color: #f6f7f7', $rendered );
	}

	/**
	 * Test that embed block detects SoundCloud by providerNameSlug
	 */
	public function test_detects_soundcloud_by_provider_name_slug(): void {
		$parsed_soundcloud_by_slug = $this->parsed_soundcloud_embed;
		unset( $parsed_soundcloud_by_slug['attrs']['url'] );
		$parsed_soundcloud_by_slug['innerHTML'] = '<figure class="wp-block-embed is-type-rich is-provider-soundcloud"><div class="wp-block-embed__wrapper">Some content</div></figure>';

		$rendered = $this->embed_renderer->render( $parsed_soundcloud_by_slug['innerHTML'], $parsed_soundcloud_by_slug, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available.
		$this->assertStringContainsString( '<a href="https://soundcloud.com/"', $rendered );
		$this->assertStringContainsString( 'Listen on SoundCloud', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that embed block detects Pocket Casts by providerNameSlug
	 */
	public function test_detects_pocket_casts_by_provider_name_slug(): void {
		$parsed_pocket_casts_by_slug = $this->parsed_pocket_casts_embed;
		unset( $parsed_pocket_casts_by_slug['attrs']['url'] );
		$parsed_pocket_casts_by_slug['innerHTML'] = '<figure class="wp-block-embed is-type-rich is-provider-pocket-casts"><div class="wp-block-embed__wrapper">Some content</div></figure>';

		$rendered = $this->embed_renderer->render( $parsed_pocket_casts_by_slug['innerHTML'], $parsed_pocket_casts_by_slug, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available.
		$this->assertStringContainsString( '<a href="https://pca.st/"', $rendered );
		$this->assertStringContainsString( 'Listen on Pocket Casts', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that non-audio embeds render as link fallback
	 */
	public function test_renders_non_audio_embeds_as_link_fallback(): void {
		// Use a non-supported embed provider for this test.
		$parsed_unsupported_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://example.com/embed',
				'type'             => 'rich',
				'providerNameSlug' => 'example',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-example"><div class="wp-block-embed__wrapper">https://example.com/embed</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_unsupported_embed['innerHTML'], $parsed_unsupported_embed, $this->rendering_context );

		// Check that the rendered content contains a link.
		$this->assertStringContainsString( '<a href="https://example.com/embed"', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
		$this->assertStringContainsString( 'https://example.com/embed', $rendered );
	}

	/**
	 * Test that link fallback uses custom label when provided
	 */
	public function test_link_fallback_uses_custom_label(): void {
		$parsed_unsupported_with_label = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://example.com/embed',
				'type'             => 'rich',
				'providerNameSlug' => 'example',
				'responsive'       => true,
				'label'            => 'Watch this video',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-example"><div class="wp-block-embed__wrapper">https://example.com/embed</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_unsupported_with_label['innerHTML'], $parsed_unsupported_with_label, $this->rendering_context );

		$this->assertStringContainsString( 'Watch this video', $rendered );
		$this->assertStringContainsString( '<a href="https://example.com/embed"', $rendered );
		// The link text should be the custom label, not the URL.
		$this->assertStringContainsString( '>Watch this video</a>', $rendered );
	}

	/**
	 * Test that link fallback extracts URL from content when not in attributes
	 */
	public function test_link_fallback_extracts_url_from_content(): void {
		$parsed_unsupported_no_url_attr = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'type'             => 'rich',
				'providerNameSlug' => 'example',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-example"><div class="wp-block-embed__wrapper">https://example.com/embed</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_unsupported_no_url_attr['innerHTML'], $parsed_unsupported_no_url_attr, $this->rendering_context );

		$this->assertStringContainsString( '<a href="https://example.com/embed"', $rendered );
		$this->assertStringContainsString( 'https://example.com/embed', $rendered );
	}

	/**
	 * Test that link fallback returns base URL when no valid URL is found but provider is known
	 */
	public function test_link_fallback_returns_base_url_when_no_valid_url(): void {
		$parsed_embed_no_url = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'providerNameSlug' => 'youtube',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper">Some content without URL</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_embed_no_url['innerHTML'], $parsed_embed_no_url, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available.
		$this->assertStringContainsString( '<a href="https://www.youtube.com/"', $rendered );
		$this->assertStringContainsString( 'Watch on YouTube', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that Mixcloud embed renders correctly
	 */
	public function test_mixcloud_embed_renders_correctly(): void {
		$parsed_mixcloud_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://mixcloud.com/user/example-track/',
				'type'             => 'rich',
				'providerNameSlug' => 'mixcloud',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-mixcloud wp-block-embed-mixcloud"><div class="wp-block-embed__wrapper">https://mixcloud.com/user/example-track/</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_mixcloud_embed['innerHTML'], $parsed_mixcloud_embed, $this->rendering_context );

		$this->assertNotEmpty( $rendered );
		$this->assertStringContainsString( 'Listen on Mixcloud', $rendered );
		$this->assertStringContainsString( 'https://mixcloud.com/user/example-track/', $rendered );
	}

	/**
	 * Test that ReverbNation embed renders correctly
	 */
	public function test_reverbnation_embed_renders_correctly(): void {
		$parsed_reverbnation_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://reverbnation.com/artist/example-song',
				'type'             => 'rich',
				'providerNameSlug' => 'reverbnation',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-rich is-provider-reverbnation wp-block-embed-reverbnation"><div class="wp-block-embed__wrapper">https://reverbnation.com/artist/example-song</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_reverbnation_embed['innerHTML'], $parsed_reverbnation_embed, $this->rendering_context );

		$this->assertNotEmpty( $rendered );
		$this->assertStringContainsString( 'Listen on ReverbNation', $rendered );
		$this->assertStringContainsString( 'https://reverbnation.com/artist/example-song', $rendered );
	}

	/**
	 * Test that YouTube embed renders as video player
	 */
	public function test_renders_youtube_embed_as_video_player(): void {
		$rendered = $this->embed_renderer->render( $this->parsed_youtube_embed['innerHTML'], $this->parsed_youtube_embed, $this->rendering_context );

		// Check that the rendered content contains YouTube video elements.
		$this->assertStringContainsString( 'https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
		$this->assertStringContainsString( 'background-image:url(&quot;https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg&quot;)', $rendered );
		$this->assertStringContainsString( 'background-size:cover', $rendered );
		$this->assertStringContainsString( 'min-height:390px', $rendered );
	}

	/**
	 * Test that YouTube embed uses custom label when provided
	 */
	public function test_youtube_embed_uses_custom_label_when_provided(): void {
		$parsed_youtube_custom_label                   = $this->parsed_youtube_embed;
		$parsed_youtube_custom_label['attrs']['label'] = 'Watch this video';

		$rendered = $this->embed_renderer->render( $this->parsed_youtube_embed['innerHTML'], $parsed_youtube_custom_label, $this->rendering_context );

		// Custom labels are not used in video rendering - the play button is always "Play".
		$this->assertStringContainsString( 'alt="Play"', $rendered );
	}

	/**
	 * Test that YouTube embed detects YouTube by providerNameSlug
	 */
	public function test_youtube_embed_detects_youtube_by_provider_name_slug(): void {
		$parsed_youtube_by_slug = $this->parsed_youtube_embed;
		unset( $parsed_youtube_by_slug['attrs']['url'] );
		$parsed_youtube_by_slug['innerHTML'] = '<figure class="wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper">Some content</div></figure>';

		$rendered = $this->embed_renderer->render( $parsed_youtube_by_slug['innerHTML'], $parsed_youtube_by_slug, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available for thumbnail extraction.
		$this->assertStringContainsString( '<a href="https://www.youtube.com/"', $rendered );
		$this->assertStringContainsString( 'Watch on YouTube', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that YouTube embed detects YouTube by URL in attributes
	 */
	public function test_youtube_embed_detects_youtube_by_url_in_attributes(): void {
		$parsed_youtube_by_url = $this->parsed_youtube_embed;
		unset( $parsed_youtube_by_url['attrs']['providerNameSlug'] );

		$rendered = $this->embed_renderer->render( $this->parsed_youtube_embed['innerHTML'], $parsed_youtube_by_url, $this->rendering_context );

		$this->assertStringContainsString( 'https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test that YouTube embed detects YouTube by URL in content
	 */
	public function test_youtube_embed_detects_youtube_by_url_in_content(): void {
		$parsed_youtube_by_content = $this->parsed_youtube_embed;
		unset( $parsed_youtube_by_content['attrs']['providerNameSlug'] );
		unset( $parsed_youtube_by_content['attrs']['url'] );

		$rendered = $this->embed_renderer->render( $this->parsed_youtube_embed['innerHTML'], $parsed_youtube_by_content, $this->rendering_context );

		$this->assertStringContainsString( 'https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test that YouTube embed handles youtu.be URLs
	 */
	public function test_youtube_embed_handles_youtu_be_urls(): void {
		$parsed_youtube_short_url = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://youtu.be/dQw4w9WgXcQ',
				'type'             => 'video',
				'providerNameSlug' => 'youtube',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://youtu.be/dQw4w9WgXcQ</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_youtube_short_url['innerHTML'], $parsed_youtube_short_url, $this->rendering_context );

		$this->assertStringContainsString( 'https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test that YouTube embed falls back to link when thumbnail extraction fails
	 */
	public function test_youtube_embed_falls_back_to_link_when_thumbnail_fails(): void {
		$parsed_youtube_invalid = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://www.youtube.com/watch?v=invalid',
				'type'             => 'video',
				'providerNameSlug' => 'youtube',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=invalid</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_youtube_invalid['innerHTML'], $parsed_youtube_invalid, $this->rendering_context );

		// Should still render as video block even with invalid video ID (the thumbnail URL will be generated).
		$this->assertStringContainsString( 'https://img.youtube.com/vi/invalid/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test that VideoPress embed is detected and renders as video player, including handling URLs with query parameters.
	 */
	public function test_renders_videopress_embed(): void {
		// Mock the oEmbed HTTP response to avoid external calls in CI.
		// Return a thumbnail URL with query parameters to test the URL encoding fix.
		$mock_thumbnail_url   = 'https://videos.files.wordpress.com/BZHMfMfN/thumbnail.jpg?w=500&h=281';
		$mock_oembed_response = wp_json_encode(
			array(
				'type'          => 'video',
				'thumbnail_url' => $mock_thumbnail_url,
				'title'         => 'Test Video',
			)
		);

		// Use pre_http_request filter to intercept oEmbed HTTP calls.
		$filter_callback = function ( $preempt, $args, $url ) use ( $mock_oembed_response ) {
			// Intercept VideoPress oEmbed requests.
			if ( strpos( $url, 'public-api.wordpress.com/oembed' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => $mock_oembed_response,
				);
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		$parsed_videopress_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://videopress.com/v/BZHMfMfN?w=500&h=281',
				'type'             => 'video',
				'providerNameSlug' => 'videopress',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-videopress wp-block-embed-videopress"><div class="wp-block-embed__wrapper">https://videopress.com/v/BZHMfMfN?w=500&h=281</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_videopress_embed['innerHTML'], $parsed_videopress_embed, $this->rendering_context );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}

		// Should detect VideoPress and render as video with thumbnail.
		$this->assertNotEmpty( $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered, 'VideoPress embed should render with play button' );
		// Verify background-image is present (our fix ensures it's not stripped).
		$this->assertStringContainsString( 'background-image', $rendered, 'Background image should be present in CSS' );
		// Verify query parameters are present (as &amp; in HTML, which is correct).
		$this->assertStringContainsString( 'w=500', $rendered, 'Query parameters should be present' );
		$this->assertStringContainsString( 'h=281', $rendered, 'Query parameters should be present' );
	}

	/**
	 * Test that VideoPress embed detects VideoPress by providerNameSlug
	 */
	public function test_videopress_embed_detects_videopress_by_provider_name_slug(): void {
		$parsed_videopress_by_slug = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'providerNameSlug' => 'videopress',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-videopress"><div class="wp-block-embed__wrapper">Some content</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_videopress_by_slug['innerHTML'], $parsed_videopress_by_slug, $this->rendering_context );

		// Should return graceful fallback link since provider is detected but no URL is available for thumbnail extraction.
		$this->assertStringContainsString( '<a href="https://videopress.com/"', $rendered );
		$this->assertStringContainsString( 'Watch on VideoPress', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test that VideoPress embed detects VideoPress by URL in attributes
	 */
	public function test_videopress_embed_detects_videopress_by_url_in_attributes(): void {
		// Mock the oEmbed HTTP response to avoid external calls in CI.
		$mock_thumbnail_url   = 'https://videos.files.wordpress.com/BZHMfMfN/thumbnail.jpg';
		$mock_oembed_response = wp_json_encode(
			array(
				'type'          => 'video',
				'thumbnail_url' => $mock_thumbnail_url,
				'title'         => 'Test Video',
			)
		);

		// Use pre_http_request filter to intercept oEmbed HTTP calls.
		$filter_callback = function ( $preempt, $args, $url ) use ( $mock_oembed_response ) {
			if ( strpos( $url, 'public-api.wordpress.com/oembed' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => $mock_oembed_response,
				);
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		$parsed_videopress_by_url = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url' => 'https://videopress.com/v/BZHMfMfN',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-videopress"><div class="wp-block-embed__wrapper">https://videopress.com/v/BZHMfMfN</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_videopress_by_url['innerHTML'], $parsed_videopress_by_url, $this->rendering_context );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}

		// Should detect VideoPress by URL domain and render with thumbnail.
		$this->assertNotEmpty( $rendered );
		$this->assertStringContainsString( 'background-image', $rendered, 'VideoPress embed should have background image' );
	}

	/**
	 * Test that VideoPress embed detects video.wordpress.com domain
	 */
	public function test_videopress_embed_detects_video_wordpress_com_domain(): void {
		// Mock the oEmbed HTTP response to avoid external calls in CI.
		$mock_thumbnail_url   = 'https://videos.files.wordpress.com/BZHMfMfN/thumbnail.jpg';
		$mock_oembed_response = wp_json_encode(
			array(
				'type'          => 'video',
				'thumbnail_url' => $mock_thumbnail_url,
				'title'         => 'Test Video',
			)
		);

		// Use pre_http_request filter to intercept oEmbed HTTP calls.
		$filter_callback = function ( $preempt, $args, $url ) use ( $mock_oembed_response ) {
			if ( strpos( $url, 'public-api.wordpress.com/oembed' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => $mock_oembed_response,
				);
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		$parsed_videopress_wordpress_com = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url' => 'https://video.wordpress.com/v/BZHMfMfN',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video"><div class="wp-block-embed__wrapper">https://video.wordpress.com/v/BZHMfMfN</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_videopress_wordpress_com['innerHTML'], $parsed_videopress_wordpress_com, $this->rendering_context );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}

		// Should detect VideoPress by video.wordpress.com domain and render with thumbnail.
		$this->assertNotEmpty( $rendered );
		$this->assertStringContainsString( 'background-image', $rendered, 'VideoPress embed should have background image' );
	}
}
