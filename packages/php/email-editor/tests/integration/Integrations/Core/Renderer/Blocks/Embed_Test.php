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
	 * Test that YouTube embed correctly handles URLs with underscores in the video ID
	 */
	public function test_youtube_embed_handles_urls_with_underscores(): void {
		$parsed_youtube_underscore = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => 'https://www.youtube.com/watch?v=dQw4w9_WgXcQ',
				'type'             => 'video',
				'providerNameSlug' => 'youtube',
				'responsive'       => true,
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=dQw4w9_WgXcQ</div></figure>',
		);

		$rendered = $this->embed_renderer->render( $parsed_youtube_underscore['innerHTML'], $parsed_youtube_underscore, $this->rendering_context );

		// Should extract full video ID including underscore.
		$this->assertStringContainsString( 'https://img.youtube.com/vi/dQw4w9_WgXcQ/0.jpg', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
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

	/**
	 * Callback for oembed_providers filter.
	 *
	 * @var callable|null
	 */
	private $oembed_provider_callback;

	/**
	 * Helper to register example.com as an oEmbed provider and mock HTTP responses.
	 *
	 * @param string $mock_body      JSON-encoded oEmbed response body.
	 * @param string $embed_page_html Optional HTML for the embed page response. Empty string disables.
	 * @return callable The HTTP filter callback (for removal in cleanup).
	 */
	private function mock_oembed_for_example_com( string $mock_body, string $embed_page_html = '' ): callable {
		$this->oembed_provider_callback = function ( $providers ) {
			$providers['https://example.com/*'] = array( 'https://example.com/wp-json/oembed/1.0/embed', false );
			return $providers;
		};
		add_filter( 'oembed_providers', $this->oembed_provider_callback );

		$filter_callback = function ( $preempt, $args, $url ) use ( $mock_body, $embed_page_html ) {
			if ( strpos( $url, 'example.com/wp-json/oembed' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => $mock_body,
				);
			}
			// Intercept embed page requests (URLs ending with /embed/).
			if ( ! empty( $embed_page_html ) && preg_match( '#example\.com/.*/embed/?$#', $url ) ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => $embed_page_html,
				);
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );
		return $filter_callback;
	}

	/**
	 * Helper to register example.com as an oEmbed provider that returns errors.
	 *
	 * @return callable The HTTP filter callback (for removal in cleanup).
	 */
	private function mock_oembed_failure_for_example_com(): callable {
		$this->oembed_provider_callback = function ( $providers ) {
			$providers['https://example.com/*'] = array( 'https://example.com/wp-json/oembed/1.0/embed', false );
			return $providers;
		};
		add_filter( 'oembed_providers', $this->oembed_provider_callback );

		$filter_callback = function ( $preempt, $args, $url ) {
			if ( strpos( $url, 'example.com/wp-json/oembed' ) !== false ) {
				return new \WP_Error( 'http_request_failed', 'Connection refused' );
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );
		return $filter_callback;
	}

	/**
	 * Helper to clean up oEmbed mocks.
	 *
	 * @param callable $filter_callback The HTTP filter callback to remove.
	 * @param string   $url The URL whose transient should be deleted.
	 */
	private function cleanup_oembed_mock( callable $filter_callback, string $url ): void {
		remove_filter( 'pre_http_request', $filter_callback, 10 );
		delete_transient( 'wc_email_oembed_' . md5( $url ) );
		delete_transient( 'wc_email_embed_pg_' . md5( $url ) );
		if ( null !== $this->oembed_provider_callback ) {
			remove_filter( 'oembed_providers', $this->oembed_provider_callback );
			$this->oembed_provider_callback = null;
		}
	}

	/**
	 * Test that wp-embed link renders as rich card when oEmbed returns title and thumbnail
	 */
	public function test_renders_wp_embed_as_rich_card(): void {
		$url             = 'https://example.com/my-blog-post';
		$embed_page_html = '<html><body><div class="wp-embed">'
			. '<div class="wp-embed-excerpt"><p>A short excerpt about garlic roasted potatoes and other delicious things.</p></div>'
			. '<div class="wp-embed-site-title"><a href="https://example.com" target="_top">'
			. '<img src="https://example.com/icon-32.png" width="32" height="32" alt="" class="wp-embed-site-icon" />'
			. '<span>Example Blog</span></a></div>'
			. '</div></body></html>';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'My Blog Post Title',
					'thumbnail_url' => 'https://example.com/image.jpg',
					'provider_name' => 'Example Blog',
					'provider_url'  => 'https://example.com',
					'type'          => 'link',
				)
			),
			$embed_page_html
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'my-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed is-provider-my-blog"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'My Blog Post Title', $rendered, 'Card should contain the oEmbed title' );
		$this->assertStringContainsString( 'https://example.com/image.jpg', $rendered, 'Card should contain the thumbnail image' );
		$this->assertStringContainsString( 'garlic roasted potatoes', $rendered, 'Card should contain the excerpt from embed page' );
		$this->assertStringContainsString( 'Continue reading', $rendered, 'Card should contain a Continue reading link' );
		$this->assertStringContainsString( 'https://example.com/icon-32.png', $rendered, 'Card should contain the site icon' );
		$this->assertStringContainsString( 'width="16" height="16"', $rendered, 'Site icon should be scaled to 16px' );
		$this->assertStringContainsString( '<a href="https://example.com"', $rendered, 'Provider name should be linked' );
		$this->assertStringContainsString( 'Example Blog', $rendered, 'Card should contain the provider name' );
		$this->assertStringContainsString( '<table', $rendered, 'Card should use table-based layout' );
		$this->assertStringContainsString( 'border: 1px solid #ddd', $rendered, 'Card should have a border' );
	}

	/**
	 * Test that card renders without thumbnail when oEmbed has title but no thumbnail_url
	 */
	public function test_renders_card_without_thumbnail(): void {
		$url             = 'https://example.com/no-image-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'A Post Without Image',
					'provider_name' => 'No Image Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'no-image-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'A Post Without Image', $rendered, 'Card should contain the title' );
		$this->assertStringContainsString( 'No Image Blog', $rendered, 'Card should contain the provider name' );
		$this->assertStringNotContainsString( '<img', $rendered, 'Card should not contain an image tag' );
	}

	/**
	 * Test that card uses domain as provider name when oEmbed does not include provider_name
	 */
	public function test_renders_card_with_domain_fallback_for_provider(): void {
		$url             = 'https://example.com/domain-fallback';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title' => 'Domain Fallback Post',
					'type'  => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'example-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Domain Fallback Post', $rendered, 'Card should contain the title' );
		$this->assertStringContainsString( 'example.com', $rendered, 'Card should fall back to domain as provider' );
	}

	/**
	 * Test that embed falls back to bare link when oEmbed HTTP request fails
	 */
	public function test_falls_back_to_link_when_oembed_fails(): void {
		$url             = 'https://example.com/failing-post';
		$filter_callback = $this->mock_oembed_failure_for_example_com();

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'failing-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( '<a href="https://example.com/failing-post"', $rendered, 'Should fall back to bare link' );
		$this->assertStringNotContainsString( 'border: 1px solid #ddd', $rendered, 'Should not render as card' );
	}

	/**
	 * Test that embed falls back to bare link when oEmbed response has no title
	 */
	public function test_falls_back_to_link_when_oembed_has_no_title(): void {
		$url             = 'https://example.com/no-title';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'type'          => 'link',
					'provider_name' => 'Example',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'no-title-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( '<a href="https://example.com/no-title"', $rendered, 'Should fall back to bare link' );
		$this->assertStringNotContainsString( 'border: 1px solid #ddd', $rendered, 'Should not render as card' );
	}

	/**
	 * Test that card renders without thumbnail when thumbnail_url is invalid
	 */
	public function test_renders_card_without_thumbnail_when_thumbnail_url_invalid(): void {
		$url             = 'https://example.com/bad-thumbnail';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Post With Bad Thumbnail',
					'thumbnail_url' => 'javascript:alert(1)',
					'provider_name' => 'Sketchy Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'sketchy-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Post With Bad Thumbnail', $rendered, 'Card should still render with title' );
		$this->assertStringNotContainsString( '<img', $rendered, 'Card should not contain an image tag' );
		$this->assertStringNotContainsString( 'javascript:', $rendered, 'Card should not contain javascript URL' );
	}

	/**
	 * Test that oEmbed response is cached and reused on subsequent renders
	 */
	public function test_oembed_response_is_cached(): void {
		$url             = 'https://example.com/cached-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Cached Post',
					'provider_name' => 'Cache Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'cache-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$first_render = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );

			remove_filter( 'pre_http_request', $filter_callback, 10 );

			$second_render = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Cached Post', $first_render, 'First render should contain the title' );
		$this->assertStringContainsString( 'Cached Post', $second_render, 'Second render should also contain the title from cache' );
	}

	/**
	 * Test that link embed card respects email_attrs spacing
	 */
	public function test_link_embed_card_respects_email_attrs_spacing(): void {
		$url             = 'https://example.com/spaced-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Spaced Post',
					'provider_name' => 'Space Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName'   => 'core/embed',
			'attrs'       => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'space-blog',
			),
			'innerHTML'   => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
			'email_attrs' => array(
				'margin-top' => '30px',
			),
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Spaced Post', $rendered, 'Card should render' );
		$this->assertStringContainsString( 'margin-top:30px', $rendered, 'Card should respect email_attrs spacing' );
	}

	/**
	 * Test that card skips thumbnail when thumbnail_width is below minimum
	 */
	public function test_renders_card_without_thumbnail_when_too_small(): void {
		$url             = 'https://example.com/small-thumb-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'           => 'Small Thumbnail Post',
					'thumbnail_url'   => 'https://example.com/tiny.jpg',
					'thumbnail_width' => 150,
					'provider_name'   => 'Tiny Blog',
					'type'            => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'tiny-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Small Thumbnail Post', $rendered, 'Card should still render with title' );
		$this->assertStringContainsString( 'Tiny Blog', $rendered, 'Card should contain provider name' );
		$this->assertStringNotContainsString( '<img', $rendered, 'Card should not show small thumbnail' );
		$this->assertStringNotContainsString( 'tiny.jpg', $rendered, 'Small thumbnail URL should not appear' );
	}

	/**
	 * Test that card shows thumbnail when thumbnail_width meets minimum
	 */
	public function test_renders_card_with_thumbnail_when_large_enough(): void {
		$url             = 'https://example.com/large-thumb-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'           => 'Large Thumbnail Post',
					'thumbnail_url'   => 'https://example.com/big.jpg',
					'thumbnail_width' => 600,
					'provider_name'   => 'Big Blog',
					'type'            => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'big-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Large Thumbnail Post', $rendered, 'Card should render with title' );
		$this->assertStringContainsString( 'https://example.com/big.jpg', $rendered, 'Card should show large thumbnail' );
		$this->assertStringContainsString( '<img', $rendered, 'Card should contain an image tag' );
	}

	/**
	 * Test that card shows thumbnail when thumbnail_width is not provided by oEmbed
	 */
	public function test_renders_card_with_thumbnail_when_width_unknown(): void {
		$url             = 'https://example.com/unknown-width-post';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Unknown Width Post',
					'thumbnail_url' => 'https://example.com/mystery.jpg',
					'provider_name' => 'Mystery Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'mystery-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Unknown Width Post', $rendered, 'Card should render with title' );
		$this->assertStringContainsString( 'https://example.com/mystery.jpg', $rendered, 'Card should show thumbnail when width is unknown' );
		$this->assertStringContainsString( '<img', $rendered, 'Card should contain an image tag' );
	}

	/**
	 * Test that card renders without excerpt when embed page fetch fails
	 */
	public function test_renders_card_without_excerpt_when_embed_page_fails(): void {
		$url = 'https://example.com/no-embed-page';

		// Register oEmbed provider but do NOT provide embed page HTML — the default
		// pre_http_request will not intercept the /embed/ URL, so wp_safe_remote_get
		// will return an error in the test environment.
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Post Without Embed Page',
					'provider_name' => 'Example Blog',
					'type'          => 'link',
				)
			)
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'example-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Post Without Embed Page', $rendered, 'Card should still render title' );
		$this->assertStringContainsString( 'Example Blog', $rendered, 'Card should contain provider name' );
		$this->assertStringNotContainsString( 'font-size: 14px; color: #555', $rendered, 'Card should not contain excerpt styling' );
	}

	/**
	 * Test that card renders without excerpt when embed page has no wp-embed-excerpt element
	 */
	public function test_renders_card_without_excerpt_when_no_excerpt_element(): void {
		$url             = 'https://example.com/no-excerpt-element';
		$embed_page_html = '<html><body><div class="wp-embed"><p class="wp-embed-heading"><a href="' . $url . '">Post Title</a></p></div></body></html>';
		$filter_callback = $this->mock_oembed_for_example_com(
			wp_json_encode(
				array(
					'title'         => 'Post Without Excerpt Element',
					'provider_name' => 'Example Blog',
					'type'          => 'link',
				)
			),
			$embed_page_html
		);

		$parsed_wp_embed = array(
			'blockName' => 'core/embed',
			'attrs'     => array(
				'url'              => $url,
				'type'             => 'wp-embed',
				'providerNameSlug' => 'example-blog',
			),
			'innerHTML' => '<figure class="wp-block-embed is-type-wp-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure>',
		);

		try {
			$rendered = $this->embed_renderer->render( $parsed_wp_embed['innerHTML'], $parsed_wp_embed, $this->rendering_context );
		} finally {
			$this->cleanup_oembed_mock( $filter_callback, $url );
		}

		$this->assertStringContainsString( 'Post Without Excerpt Element', $rendered, 'Card should still render title' );
		$this->assertStringContainsString( 'Example Blog', $rendered, 'Card should contain provider name' );
		$this->assertStringNotContainsString( 'font-size: 14px; color: #555', $rendered, 'Card should not contain excerpt styling' );
	}
}
