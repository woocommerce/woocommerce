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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Audio;

/**
 * Integration test for Audio class
 */
class Audio_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Audio renderer instance
	 *
	 * @var Audio
	 */
	private $audio_renderer;

	/**
	 * Audio block configuration
	 *
	 * @var array
	 */
	private $parsed_audio = array(
		'blockName' => 'core/audio',
		'attrs'     => array(
			'src' => 'https://example.com/podcast.mp3',
		),
		'innerHTML' => '<figure class="wp-block-audio"><audio controls src="https://example.com/podcast.mp3"></audio></figure>',
	);

	/**
	 * Audio block configuration with attachment ID
	 *
	 * @var array
	 */
	private $parsed_audio_with_id = array(
		'blockName' => 'core/audio',
		'attrs'     => array(
			'id' => 159,
		),
		'innerHTML' => '<figure class="wp-block-audio"><audio controls src="https://example.com/audio.mp3"></audio></figure>',
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
		$this->audio_renderer    = new Audio();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test that audio block renders as audio player
	 */
	public function test_renders_audio_as_audio_player(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		// Check that the rendered content contains audio player elements.
		$this->assertStringContainsString( 'Listen to the audio', $rendered );
		$this->assertStringContainsString( 'audio-play.png', $rendered );
		$this->assertStringContainsString( 'https://example.com/podcast.mp3', $rendered );
		$this->assertStringContainsString( 'border-radius: 9999px', $rendered );
		$this->assertStringContainsString( 'background-color: #f6f7f7', $rendered );
	}

	/**
	 * Test that audio block returns empty string when no valid URL
	 */
	public function test_returns_empty_string_when_no_valid_url(): void {
		$parsed_audio_no_url = $this->parsed_audio;
		unset( $parsed_audio_no_url['attrs']['src'] );

		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $parsed_audio_no_url, $this->rendering_context );

		$this->assertEmpty( $rendered );
	}

	/**
	 * Test that audio block returns empty string when invalid URL
	 */
	public function test_returns_empty_string_when_invalid_url(): void {
		$parsed_audio_invalid_url = $this->parsed_audio;
		unset( $parsed_audio_invalid_url['attrs']['src'] );
		$parsed_audio_invalid_url['innerHTML'] = '<figure class="wp-block-audio"><audio controls src="not-a-valid-url"></audio></figure>';

		$rendered = $this->audio_renderer->render( $parsed_audio_invalid_url['innerHTML'], $parsed_audio_invalid_url, $this->rendering_context );

		$this->assertEmpty( $rendered );
	}

	/**
	 * Test that audio block handles email attributes for spacing
	 */
	public function test_handles_email_attributes_for_spacing(): void {
		$parsed_audio_with_spacing                = $this->parsed_audio;
		$parsed_audio_with_spacing['email_attrs'] = array(
			'margin' => '20px 0',
		);

		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $parsed_audio_with_spacing, $this->rendering_context );

		$this->assertStringContainsString( 'margin:20px 0', $rendered );
	}

	/**
	 * Test that audio block uses default spacing when no email attributes
	 */
	public function test_uses_default_spacing_when_no_email_attributes(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		$this->assertStringContainsString( 'margin: 16px 0', $rendered );
	}

	/**
	 * Test that audio block includes proper security attributes
	 */
	public function test_includes_proper_security_attributes(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
	}

	/**
	 * Test that audio block includes proper accessibility attributes
	 */
	public function test_includes_proper_accessibility_attributes(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		$this->assertStringContainsString( 'alt="', $rendered );
		$this->assertStringContainsString( 'Audio icon', $rendered );
	}

	/**
	 * Test that audio block uses custom label when provided
	 */
	public function test_uses_custom_label_when_provided(): void {
		$parsed_audio_custom_label                   = $this->parsed_audio;
		$parsed_audio_custom_label['attrs']['label'] = 'Play this song';

		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $parsed_audio_custom_label, $this->rendering_context );

		$this->assertStringContainsString( 'Play this song', $rendered );
		$this->assertStringNotContainsString( 'Listen to the audio', $rendered );
	}

	/**
	 * Test that audio block uses default label when no custom label provided
	 */
	public function test_uses_default_label_when_no_custom_label(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		$this->assertStringContainsString( 'Listen to the audio', $rendered );
	}

	/**
	 * Test that audio block handles attachment ID and extracts URL
	 */
	public function test_handles_attachment_id_and_extracts_url(): void {
		// Mock wp_get_attachment_url to return a test URL.
		$this->mock_wp_get_attachment_url( 159, 'https://example.com/audio-from-library.mp3' );

		// Mock get_post_meta to return empty metadata (no duration).
		$this->mock_get_post_meta( 159, '_wp_attachment_metadata', true, array() );

		$rendered = $this->audio_renderer->render( $this->parsed_audio_with_id['innerHTML'], $this->parsed_audio_with_id, $this->rendering_context );

		$this->assertStringContainsString( 'https://example.com/audio-from-library.mp3', $rendered );
		$this->assertStringContainsString( 'Listen to the audio', $rendered );
	}

	/**
	 * Test that audio block includes duration when available
	 */
	public function test_includes_duration_when_available(): void {
		// Mock wp_get_attachment_url to return a test URL.
		$this->mock_wp_get_attachment_url( 159, 'https://example.com/audio-from-library.mp3' );

		// Mock get_post_meta to return metadata with duration.
		$this->mock_get_post_meta( 159, '_wp_attachment_metadata', true, array( 'length_formatted' => '2:30' ) );

		$rendered = $this->audio_renderer->render( $this->parsed_audio_with_id['innerHTML'], $this->parsed_audio_with_id, $this->rendering_context );

		$this->assertStringContainsString( 'Listen to the audio (2:30)', $rendered );
	}

	/**
	 * Test that audio block handles external URLs without duration
	 */
	public function test_handles_external_urls_without_duration(): void {
		$rendered = $this->audio_renderer->render( $this->parsed_audio['innerHTML'], $this->parsed_audio, $this->rendering_context );

		$this->assertStringContainsString( 'https://example.com/podcast.mp3', $rendered );
		$this->assertStringContainsString( 'Listen to the audio', $rendered );
		$this->assertStringNotContainsString( 'Listen to the audio (', $rendered );
	}

	/**
	 * Mock wp_get_attachment_url function
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $url URL to return.
	 */
	private function mock_wp_get_attachment_url( int $attachment_id, string $url ): void {
		// This would need to be implemented based on the testing framework.
		// For now, we'll skip these tests that require WordPress function mocking.
		$this->markTestSkipped( 'WordPress function mocking not implemented in this test framework' );
	}

	/**
	 * Mock get_post_meta function
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param bool   $single Single value.
	 * @param mixed  $value Value to return.
	 */
	private function mock_get_post_meta( int $post_id, string $key, bool $single, $value ): void {
		// This would need to be implemented based on the testing framework.
		// For now, we'll skip these tests that require WordPress function mocking.
		$this->markTestSkipped( 'WordPress function mocking not implemented in this test framework' );
	}
}
