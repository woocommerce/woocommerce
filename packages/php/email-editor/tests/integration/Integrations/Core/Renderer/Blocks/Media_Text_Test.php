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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Media_Text;

/**
 * Integration test for Media_Text class
 */
class Media_Text_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Media renderer instance
	 *
	 * @var Media_Text
	 */
	private $media_renderer;

	/**
	 * Media block configuration
	 *
	 * @var array
	 */
	private $parsed_media = array(
		'blockName'   => 'core/media-text',
		'attrs'       => array(
			'mediaPosition'     => 'left',
			'mediaWidth'        => 60,
			'verticalAlignment' => 'top',
		),
		'innerHTML'   => '<figure class="wp-block-media-text__media"><img src="test-image.jpg" alt="Test Image" /></figure><div class="wp-block-media-text__content"><p>Media content</p></div>',
		'innerBlocks' => array(
			0 => array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Media content</p>',
				'innerContent' => array(
					0 => '<p>Media content</p>',
				),
			),
		),
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
		$this->media_renderer    = new Media_Text();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders media content
	 */
	public function testItRendersMediaContent(): void {
		$rendered = $this->media_renderer->render( '', $this->parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'Media content', $rendered );
	}

	/**
	 * Test it handles media positioning
	 */
	public function testItHandlesMediaPositioning(): void {
		$parsed_media                               = $this->parsed_media;
		$parsed_media['attrs']['mediaPosition']     = 'right';
		$parsed_media['attrs']['mediaWidth']        = 60;
		$parsed_media['attrs']['verticalAlignment'] = 'middle';

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'Media content', $rendered );
		$this->assertStringContainsString( '60%', $rendered );
		$this->assertStringContainsString( '40%', $rendered );
	}

	/**
	 * Test it handles custom styling
	 */
	public function testItHandlesCustomStyling(): void {
		$parsed_media                   = $this->parsed_media;
		$parsed_media['attrs']['style'] = array(
			'border'  => array(
				'color'  => '#123456',
				'radius' => '10px',
				'width'  => '2px',
				'style'  => 'solid',
			),
			'color'   => array(
				'background' => '#abcdef',
			),
			'spacing' => array(
				'padding' => array(
					'bottom' => '5px',
					'left'   => '15px',
					'right'  => '20px',
					'top'    => '10px',
				),
			),
		);

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'background-color:#abcdef;', $rendered );
		$this->assertStringContainsString( 'border-color:#123456;', $rendered );
		$this->assertStringContainsString( 'border-radius:10px;', $rendered );
		$this->assertStringContainsString( 'border-width:2px;', $rendered );
		$this->assertStringContainsString( 'border-style:solid;', $rendered );
		$this->assertStringContainsString( 'padding-bottom:5px;', $rendered );
		$this->assertStringContainsString( 'padding-left:15px;', $rendered );
		$this->assertStringContainsString( 'padding-right:20px;', $rendered );
		$this->assertStringContainsString( 'padding-top:10px;', $rendered );
	}

	/**
	 * Test it handles custom color and background
	 */
	public function testItHandlesCustomColorAndBackground(): void {
		$parsed_media                                    = $this->parsed_media;
		$parsed_media['attrs']['style']['color']['text'] = '#123456';
		$parsed_media['attrs']['style']['color']['background'] = '#654321';

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'color:#123456;', $rendered );
		$this->assertStringContainsString( 'background-color:#654321;', $rendered );
	}

	/**
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_media = $this->parsed_media;
		$content      = '<div class="wp-block-media-text editor-class-1 another-class"></div>';

		$rendered = $this->media_renderer->render( $content, $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'wp-block-media-text editor-class-1 another-class', $rendered );
	}

	/**
	 * Test it handles attachment linkDestination
	 */
	public function testItHandlesAttachmentLinkDestination(): void {
		$parsed_media                             = $this->parsed_media;
		$parsed_media['attrs']['linkDestination'] = 'attachment';
		$parsed_media['attrs']['href']            = 'https://example.com/?attachment_id=123';

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'href="https://example.com/?attachment_id=123"', $rendered );
		$this->assertStringContainsString( 'Media content', $rendered );
	}

	/**
	 * Test it handles media linkDestination
	 */
	public function testItHandlesMediaLinkDestination(): void {
		$parsed_media                             = $this->parsed_media;
		$parsed_media['attrs']['linkDestination'] = 'media';
		$parsed_media['attrs']['href']            = 'https://example.com/image.jpg';

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'href="https://example.com/image.jpg"', $rendered );
		$this->assertStringContainsString( 'Media content', $rendered );
	}

	/**
	 * Test it correctly extracts media from media column and ignores figures in text content
	 */
	public function testItIgnoresFiguresInTextContent(): void {
		$parsed_media = $this->parsed_media;
		// HTML with a figure in text content and proper media figure.
		$parsed_media['innerHTML'] = '<figure class="wp-block-media-text__media"><img src="media-image.jpg" alt="Media Image" /></figure><div class="wp-block-media-text__content"><figure class="wp-block-image"><img src="text-image.jpg" alt="Text Image" /></figure><p>Text with image</p></div>';

		$rendered = $this->media_renderer->render( '', $parsed_media, $this->rendering_context );
		$this->assertStringContainsString( 'media-image.jpg', $rendered );
		$this->assertStringNotContainsString( 'text-image.jpg', $rendered );
	}
}
