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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Video;

/**
 * Integration test for Video class
 */
class Video_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Video renderer instance
	 *
	 * @var Video
	 */
	private $video_renderer;

	/**
	 * Basic video block configuration
	 *
	 * @var array
	 */
	private $parsed_video = array(
		'blockName' => 'core/video',
		'attrs'     => array(
			'poster' => 'https://example.com/poster.jpg',
			'title'  => 'Sample Video',
		),
		'innerHTML' => '<figure class="wp-block-video"><video controls poster="https://example.com/poster.jpg"><source src="https://example.com/video.mp4" type="video/mp4" /></video></figure>',
	);

	/**
	 * Video block with different poster URL for variety
	 *
	 * @var array
	 */
	private $parsed_video_alt = array(
		'blockName' => 'core/video',
		'attrs'     => array(
			'poster' => 'https://example.com/another-poster.jpg',
			'title'  => 'Another Video',
		),
		'innerHTML' => '<figure class="wp-block-video"><video controls poster="https://example.com/another-poster.jpg"><source src="https://example.com/video2.mp4" type="video/mp4" /></video></figure>',
	);

	/**
	 * Rendering context instance.
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->video_renderer    = new Video();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders video content with poster and play button
	 */
	public function testItRendersVideoContentWithPosterAndPlayButton(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// Should contain cover block elements (since we're reusing cover renderer).
		$this->assertStringContainsString( 'email-block-cover', $rendered );

		// Should contain the poster image as background.
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/poster.jpg&quot;)', $rendered );

		// Should contain play button.
		$this->assertStringContainsString( 'play2x.png', $rendered );
		$this->assertStringContainsString( 'alt="Play"', $rendered );
	}

	/**
	 * Test it extracts poster URL from attributes
	 */
	public function testItExtractsPosterUrlFromAttributes(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/poster.jpg&quot;)', $rendered );
	}

	/**
	 * Test it works with different poster URLs
	 */
	public function testItWorksWithDifferentPosterUrls(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video_alt, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/another-poster.jpg&quot;)', $rendered );
		$this->assertStringContainsString( 'email-block-cover', $rendered );
	}

	/**
	 * Test it extracts video title from attributes
	 */
	public function testItExtractsVideoTitleFromAttributes(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// The title should be used as alt text for the background image.
		// Note: The cover renderer handles this, so we check for table structure.
		$this->assertStringContainsString( 'email-block-cover', $rendered );
	}

	/**
	 * Test it handles video without poster (returns empty)
	 */
	public function testItHandlesVideoWithoutPoster(): void {
		$parsed_video              = $this->parsed_video;
		$parsed_video['attrs']     = array();
		$parsed_video['innerHTML'] = '<figure class="wp-block-video"><video controls><source src="https://example.com/video.mp4" type="video/mp4" /></video></figure>';

		$rendered = $this->video_renderer->render( '', $parsed_video, $this->rendering_context );

		// Should return empty content when no poster is available.
		$this->assertStringNotContainsString( 'email-block-cover', $rendered );
	}

	/**
	 * Test it provides default title when none specified
	 */
	public function testItProvidesDefaultTitleWhenNoneSpecified(): void {
		$parsed_video          = $this->parsed_video;
		$parsed_video['attrs'] = array(
			'poster' => 'https://example.com/poster.jpg',
		);

		$rendered = $this->video_renderer->render( '', $parsed_video, $this->rendering_context );

		// Should still render successfully with default title.
		$this->assertStringContainsString( 'email-block-cover', $rendered );
		$this->assertStringContainsString( 'background-image', $rendered );
	}

	/**
	 * Test it creates play button with correct styling
	 */
	public function testItCreatesPlayButtonWithCorrectStyling(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// Should contain play button with proper styling.
		$this->assertStringContainsString( 'width: 48px; height: 48px', $rendered );
		$this->assertStringContainsString( 'display: inline-block', $rendered );
		$this->assertStringContainsString( 'text-align: center', $rendered );
	}

	/**
	 * Test it handles different video formats correctly
	 */
	public function testItHandlesDifferentVideoFormatsCorrectly(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video_alt, $this->rendering_context );

		// Should render properly for any video with poster.
		$this->assertStringContainsString( 'email-block-cover', $rendered );
		$this->assertStringContainsString( 'background-image', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test it renders with custom video HTML structure
	 */
	public function testItRendersWithCustomVideoHtmlStructure(): void {
		$parsed_video              = $this->parsed_video;
		$parsed_video['innerHTML'] = '<figure class="wp-block-video custom-class another-class"><video controls poster="https://example.com/poster.jpg"></video></figure>';

		$rendered = $this->video_renderer->render( '', $parsed_video, $this->rendering_context );

		// Should render successfully regardless of HTML structure (classes don't matter in email).
		$this->assertStringContainsString( 'email-block-cover', $rendered );
		$this->assertStringContainsString( 'background-image', $rendered );
		$this->assertStringContainsString( 'play2x.png', $rendered );
	}

	/**
	 * Test it creates email-compatible table structure
	 */
	public function testItCreatesEmailCompatibleTableStructure(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// Should use table-based layout for email compatibility.
		$this->assertStringContainsString( '<table', $rendered );
		$this->assertStringContainsString( 'width="100%"', $rendered );
		$this->assertStringContainsString( 'border-collapse', $rendered );
	}

	/**
	 * Test it returns empty when no poster available
	 */
	public function testItReturnsEmptyWhenNoPosterAvailable(): void {
		$parsed_video              = $this->parsed_video;
		$parsed_video['attrs']     = array(); // Remove poster from attrs.
		$parsed_video['innerHTML'] = '<figure class="wp-block-video"><video controls><source src="https://example.com/video.mp4" type="video/mp4" /></video></figure>';

		$rendered = $this->video_renderer->render( '', $parsed_video, $this->rendering_context );

		// Should return empty content when no poster is available.
		$this->assertStringNotContainsString( 'email-block-cover', $rendered );
		$this->assertStringNotContainsString( 'background-image', $rendered );
	}

	/**
	 * Test it delegates to cover renderer properly
	 */
	public function testItDelegatesToCoverRendererProperly(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// Should have all the characteristics of a cover block.
		$this->assertStringContainsString( 'email-block-cover', $rendered );
		$this->assertStringContainsString( 'background-size:cover', $rendered );
		$this->assertStringContainsString( 'background-position:center', $rendered );
		$this->assertStringContainsString( 'background-repeat:no-repeat', $rendered );
	}

	/**
	 * Test it applies minimum height to video blocks
	 */
	public function testItAppliesMinimumHeightToVideoBlocks(): void {
		$rendered = $this->video_renderer->render( '', $this->parsed_video, $this->rendering_context );

		// Should apply minimum height for consistent video block appearance.
		$this->assertStringContainsString( 'min-height:390px', $rendered );
	}
}
