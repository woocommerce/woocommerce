<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration tests for RTL defaults in core renderers.
 */
class Core_Renderers_Rtl_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * RTL rendering context.
	 *
	 * @var Rendering_Context
	 */
	private Rendering_Context $rtl_context;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$theme_controller  = $this->di_container->get( Theme_Controller::class );
		$this->rtl_context = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
	}

	/**
	 * Test fallback renderer uses RTL default alignment and sanitizes invalid values.
	 */
	public function testFallbackUsesRtlDefaultAlignment(): void {
		$renderer = new Fallback();
		$rendered = $renderer->render(
			'<div>Fallback</div>',
			array(
				'blockName' => 'test/unknown',
				'attrs'     => array( 'align' => 'start' ),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'align="right"', $rendered );
	}

	/**
	 * Test columns and column renderers use RTL default alignment and gap side.
	 */
	public function testColumnsAndColumnUseRtlDefaults(): void {
		$columns = new Columns();
		$column  = new Column();

		$columns_rendered = $columns->render(
			'<div class="wp-block-columns"><div class="wp-block-column">Column</div></div>',
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'email_attrs' => array(),
			),
			$this->rtl_context
		);
		$column_rendered  = $column->render(
			'<div class="wp-block-column">Column</div>',
			array(
				'blockName'   => 'core/column',
				'attrs'       => array(),
				'email_attrs' => array( 'padding-right' => '24px' ),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'text-align:right;', $columns_rendered );
		$this->assertStringContainsString( 'align="right"', $column_rendered );
		$this->assertStringContainsString( 'padding-right:24px;', $column_rendered );
	}

	/**
	 * Test media-text keeps explicit position and uses RTL alignment defaults.
	 */
	public function testMediaTextUsesRtlAlignmentAndPreservesExplicitPosition(): void {
		$renderer = new Media_Text();
		$content  = '<div class="wp-block-media-text"><figure class="wp-block-media-text__media"><img src="https://example.com/image.jpg" alt=""></figure></div>';
		$block    = array(
			'blockName'   => 'core/media-text',
			'attrs'       => array( 'mediaPosition' => 'left' ),
			'innerHTML'   => $content,
			'innerBlocks' => array(
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(),
					'innerHTML'    => '<p>Text</p>',
					'innerContent' => array( '<p>Text</p>' ),
				),
			),
			'email_attrs' => array(),
		);

		$rendered = $renderer->render( $content, $block, $this->rtl_context );

		$media_position = strpos( $rendered, 'https://example.com/image.jpg' );
		$text_position  = strpos( $rendered, 'Text' );

		$this->assertNotFalse( $media_position );
		$this->assertNotFalse( $text_position );
		$this->assertLessThan( $text_position, $media_position );
		$this->assertWrapperIsRtlAlignedWithoutFloat( $rendered, 'email-block-media-text' );
	}

	/**
	 * Test image renderer uses RTL default alignment.
	 */
	public function testImageUsesRtlDefaultAlignment(): void {
		$renderer = new Image();
		$content  = '<figure class="wp-block-image"><img src="https://example.com/image.jpg" alt=""></figure>';
		$rendered = $renderer->render(
			$content,
			array(
				'blockName'   => 'core/image',
				'attrs'       => array( 'width' => '100px' ),
				'email_attrs' => array( 'width' => '600px' ),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'align="right"', $rendered );
	}

	/**
	 * Test social links renderer uses RTL default alignment.
	 */
	public function testSocialLinksUseRtlDefaultAlignment(): void {
		$renderer = new Social_Links();
		$content  = '<ul class="wp-block-social-links"></ul>';
		$rendered = $renderer->render(
			$content,
			array(
				'blockName'   => 'core/social-links',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/social-link',
						'attrs'     => array(
							'service' => 'wordpress',
							'url'     => 'https://example.com',
						),
					),
				),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'align="right"', $rendered );
	}

	/**
	 * Test audio renderer uses RTL physical sides.
	 */
	public function testAudioUsesRtlPhysicalSides(): void {
		$renderer = new Audio();
		$content  = '<figure class="wp-block-audio"><audio controls src="data:audio/mpeg;base64,AAAA"></audio></figure>';
		$rendered = $renderer->render(
			$content,
			array(
				'blockName' => 'core/audio',
				'attrs'     => array( 'src' => 'data:audio/mpeg;base64,AAAA' ),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'align="right"', $rendered );
		$this->assertStringContainsString( 'padding-right: 17px', $rendered );
		$this->assertStringContainsString( 'padding-left: 17px', $rendered );
		$this->assertOuterSpacerAligned( $rendered, 'right' );
	}

	/**
	 * Test gallery renderer uses RTL default wrapper alignment.
	 */
	public function testGalleryUsesRtlDefaultAlignment(): void {
		$renderer = new Gallery();
		$content  = '<figure class="wp-block-gallery"><figure class="wp-block-image"><img src="https://example.com/image.jpg" alt=""></figure></figure>';
		$rendered = $renderer->render(
			$content,
			array(
				'blockName'   => 'core/gallery',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/image',
						'innerHTML' => '<figure><img src="https://example.com/image.jpg" alt=""></figure>',
					),
				),
			),
			$this->rtl_context
		);

		$this->assertWrapperIsRtlAlignedWithoutFloat( $rendered, 'email-block-gallery' );
	}

	/**
	 * Test embed fallback uses RTL wrapper alignment.
	 */
	public function testEmbedFallbackUsesRtlAlignment(): void {
		$renderer = new Embed();
		$content  = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">https://example.com</div></figure>';
		$rendered = $renderer->render(
			$content,
			array(
				'blockName' => 'core/embed',
				'attrs'     => array( 'url' => 'https://example.com' ),
			),
			$this->rtl_context
		);

		$this->assertStringContainsString( 'align="right"', $rendered );
		$this->assertOuterSpacerAligned( $rendered, 'right' );
	}

	/**
	 * Assert that a block's wrapper table carries its RTL alignment as a `text-align` declaration
	 * rather than as an `align` attribute.
	 *
	 * `align="right"` on a table renders as `float: right` in email clients, taking the block out of
	 * normal flow so the block that follows fails to clear it. Searching the whole render for the
	 * substring `align="right"` does not pin this down: the Outlook-only spacer table emitted by
	 * Abstract_Block_Renderer::add_spacer() carries one inside a conditional comment, so such an
	 * assertion keeps passing after the wrapper's own attribute is gone. Inspect the wrapper tag.
	 *
	 * @param string $rendered Rendered HTML.
	 * @param string $wrapper_class Class identifying the block's wrapper table.
	 */
	private function assertWrapperIsRtlAlignedWithoutFloat( string $rendered, string $wrapper_class ): void {
		$processor = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'table',
					'class_name' => $wrapper_class,
				)
			),
			sprintf( 'Expected a wrapper table with the %s class.', $wrapper_class )
		);

		// No float-triggering align attribute ( get_attribute() is null when the attribute is absent ).
		$this->assertNull( $processor->get_attribute( 'align' ) );

		// RTL alignment is carried by the text-align declaration instead, keeping the block in flow.
		$style = $processor->get_attribute( 'style' );
		$this->assertIsString( $style );
		$this->assertStringContainsString( 'text-align:right;', $style );
	}

	/**
	 * Assert that the outer spacer wrapper uses the expected alignment.
	 *
	 * @param string $rendered Rendered HTML.
	 * @param string $alignment Expected alignment.
	 */
	private function assertOuterSpacerAligned( string $rendered, string $alignment ): void {
		$alignment_position = strpos( $rendered, 'align="' . $alignment . '"' );
		$layout_position    = strpos( $rendered, 'email-block-layout' );

		$this->assertNotFalse( $alignment_position );
		$this->assertNotFalse( $layout_position );
		$this->assertLessThan( $layout_position, $alignment_position );
	}
}
