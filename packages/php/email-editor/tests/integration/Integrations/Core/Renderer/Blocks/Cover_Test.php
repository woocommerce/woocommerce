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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Cover;

/**
 * Integration test for Cover class
 */
class Cover_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Cover renderer instance
	 *
	 * @var Cover
	 */
	private $cover_renderer;

	/**
	 * Cover block configuration
	 *
	 * @var array
	 */
	private $parsed_cover = array(
		'blockName'   => 'core/cover',
		'attrs'       => array(
			'overlayColor' => 'pale-pink',
		),
		'innerHTML'   => '<div class="wp-block-cover is-light"><span aria-hidden="true" class="wp-block-cover__background has-pale-pink-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><p class="has-text-align-center has-large-font-size">Cover block</p></div></div>',
		'innerBlocks' => array(
			0 => array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(
					'align'    => 'center',
					'fontSize' => 'large',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p class="has-text-align-center has-large-font-size">Cover block</p>',
				'innerContent' => array(
					0 => '<p class="has-text-align-center has-large-font-size">Cover block</p>',
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
		$this->cover_renderer    = new Cover();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders cover content
	 */
	public function testItRendersCoverContent(): void {
		$rendered = $this->cover_renderer->render( '', $this->parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'Cover block', $rendered );
		$this->assertStringContainsString( 'wp-block-cover', $rendered );
		$this->assertStringContainsString( 'wp-block-cover__inner-container', $rendered );
	}

	/**
	 * Test it handles background image
	 */
	public function testItHandlesBackgroundImage(): void {
		$parsed_cover                 = $this->parsed_cover;
		$parsed_cover['attrs']['url'] = 'https://example.com/background.jpg';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/background.jpg&quot;)', $rendered );
		$this->assertStringContainsString( 'background-size:cover', $rendered );
		$this->assertStringContainsString( 'background-position:center', $rendered );
	}

	/**
	 * Test it handles custom background color
	 */
	public function testItHandlesCustomBackgroundColor(): void {
		$parsed_cover                                = $this->parsed_cover;
		$parsed_cover['attrs']['customOverlayColor'] = '#4b74b2';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		// Should apply background color directly to the cover div when no background image.
		$this->assertStringContainsString( 'background-color:#4b74b2', $rendered );
	}

	/**
	 * Test it handles background color slug
	 */
	public function testItHandlesBackgroundColorSlug(): void {
		$parsed_cover                          = $this->parsed_cover;
		$parsed_cover['attrs']['overlayColor'] = 'pale-pink';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		// Should apply background color directly to the cover div when no background image.
		$this->assertStringContainsString( 'background-color:', $rendered );
	}

	/**
	 * Test it handles custom styling
	 */
	public function testItHandlesCustomStyling(): void {
		$parsed_cover                   = $this->parsed_cover;
		$parsed_cover['attrs']['style'] = array(
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

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'background-color:#f78da7', $rendered );
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
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_cover = $this->parsed_cover;
		$content      = '<div class="wp-block-cover is-light editor-class-1 another-class"></div>';

		$rendered = $this->cover_renderer->render( $content, $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'wp-block-cover is-light editor-class-1 another-class', $rendered );
	}


	/**
	 * Test it returns empty when no inner content
	 */
	public function testItReturnsEmptyWhenNoInnerContent(): void {
		$parsed_cover                = $this->parsed_cover;
		$parsed_cover['innerBlocks'] = array();

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		// The abstract block renderer adds spacers even for empty content, so we check for empty inner content.
		$this->assertStringNotContainsString( 'wp-block-cover__inner-container', $rendered );
	}

	/**
	 * Test it extracts background image from HTML content
	 */
	public function testItExtractsBackgroundImageFromHtml(): void {
		$parsed_cover              = $this->parsed_cover;
		$parsed_cover['innerHTML'] = '<div class="wp-block-cover"><img class="wp-block-cover__image-background wp-image-537 size-full" alt="" src="https://example.com/background.jpg" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#4b74b2"></span><div class="wp-block-cover__inner-container"><p>Cover block</p></div></div>';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/background.jpg&quot;)', $rendered );
	}

	/**
	 * Test it extracts background image from URL attribute
	 */
	public function testItExtractsBackgroundImageFromUrlAttribute(): void {
		$parsed_cover                 = $this->parsed_cover;
		$parsed_cover['attrs']['url'] = 'https://example.com/background.jpg';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/background.jpg&quot;)', $rendered );
	}

	/**
	 * Test it handles background image without overlay (email compatibility)
	 */
	public function testItHandlesBackgroundImageWithoutOverlay(): void {
		$parsed_cover                                = $this->parsed_cover;
		$parsed_cover['attrs']['url']                = 'https://example.com/background.jpg';
		$parsed_cover['attrs']['customOverlayColor'] = '#4b74b2';

		$rendered = $this->cover_renderer->render( '', $parsed_cover, $this->rendering_context );
		$this->assertStringContainsString( 'background-image:url(&quot;https://example.com/background.jpg&quot;)', $rendered );
		// Should not have overlay elements for email compatibility.
		$this->assertStringNotContainsString( 'wp-block-cover__background', $rendered );
		$this->assertStringNotContainsString( 'background-color:#4b74b2', $rendered );
	}
}
