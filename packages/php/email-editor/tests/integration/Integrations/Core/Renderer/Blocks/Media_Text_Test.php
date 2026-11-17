<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
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
	 * The media & text wrapper table must not carry an `align="left"`/`align="right"` attribute: those
	 * render as `float` in email clients, pulling the block out of normal flow so the block that follows
	 * fails to clear it. A button placed after it then paints its background across the media & text
	 * block above. Horizontal alignment must instead come from the `text-align` CSS declaration, which
	 * keeps the block in normal flow.
	 */
	public function testItDoesNotFloatWrapperTableWithAlignAttribute(): void {
		$rendered = $this->media_renderer->render( '', $this->parsed_media, $this->rendering_context );

		// Locate the wrapper table by its class. Parsing the tag with WP_HTML_Tag_Processor is more
		// robust than matching the raw HTML string, and matches the convention used elsewhere here.
		$processor = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'table',
					'class_name' => 'email-block-media-text',
				)
			),
			'Expected a media & text wrapper table with the email-block-media-text class.'
		);

		// No float-triggering align attribute on the wrapper table ( get_attribute() is null when absent ).
		$this->assertNull( $processor->get_attribute( 'align' ) );

		// Alignment is preserved via the text-align CSS declaration instead, keeping the block in normal flow.
		$style = $processor->get_attribute( 'style' );
		$this->assertIsString( $style );
		$this->assertStringContainsString( 'text-align', $style );
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

	/**
	 * A 1200px image in a 50% media cell of a 612px block is capped at the cell's inner width
	 * (612 * 50% - 2 * 10px padding = 286px), with its height scaled to keep the aspect ratio.
	 */
	public function testItCapsOversizedImageToMediaCellWidth(): void {
		$img = $this->render_and_get_img( $this->get_block_with_image( 1200, 800, 50 ) );

		$this->assertSame( '286', $img['width'] );
		$this->assertSame( '191', $img['height'] );
		$this->assertStringContainsString( 'max-width:286px;width:100%;height:auto;', $img['style'] );
	}

	/**
	 * An image that already fits its media cell keeps its saved dimensions.
	 */
	public function testItLeavesImageThatFitsUnchanged(): void {
		$img = $this->render_and_get_img( $this->get_block_with_image( 200, 100, 50 ) );

		$this->assertSame( '200', $img['width'] );
		$this->assertSame( '100', $img['height'] );
		$this->assertSame( 'max-width:100%', $img['style'] );
	}

	/**
	 * The cap follows the media width share: 612 * 30% = 183px, minus 20px of cell padding.
	 */
	public function testItAppliesMediaWidthShareToCap(): void {
		$img = $this->render_and_get_img( $this->get_block_with_image( 1200, 800, 30 ) );

		$this->assertSame( '163', $img['width'] );
		$this->assertSame( '109', $img['height'] );
	}

	/**
	 * The block's own padding and border sit on the wrapper table, so they're taken off the width
	 * first: ( 612 - 2 * 20px - 2 * 2px ) * 50% - 20px = 264px.
	 */
	public function testItSubtractsBlockPaddingAndBorderFromCap(): void {
		$parsed_block                   = $this->get_block_with_image( 1200, 800, 50 );
		$parsed_block['attrs']['style'] = array(
			'spacing' => array(
				'padding' => array(
					'left'  => '20px',
					'right' => '20px',
				),
			),
			'border'  => array( 'width' => '2px' ),
		);

		$img = $this->render_and_get_img( $parsed_block );

		$this->assertSame( '264', $img['width'] );
	}

	/**
	 * Padding saved as a spacing preset is resolved through the theme's variables.
	 */
	public function testItResolvesPresetPaddingForCap(): void {
		$variables = Preset_Variable_Resolver::get_variables_map( $this->rendering_context->get_theme_json() );
		$preset    = (float) ( $variables['--wp--preset--spacing--20'] ?? 0 );
		$this->assertGreaterThan( 0, $preset, 'Expected the email theme to define spacing preset 20 in px.' );

		$parsed_block                   = $this->get_block_with_image( 1200, 800, 50 );
		$parsed_block['attrs']['style'] = array(
			'spacing' => array(
				'padding' => array(
					'left'  => 'var:preset|spacing|20',
					'right' => 'var:preset|spacing|20',
				),
			),
		);

		$img = $this->render_and_get_img( $parsed_block );

		$this->assertSame( (string) ( (int) floor( ( 612 - 2 * $preset ) * 0.5 ) - 20 ), $img['width'] );
	}

	/**
	 * Core saves the media image without `width`/`height`; WordPress only adds them later from the
	 * attachment metadata. The cap must use those dimensions, or the 1024px size slips through.
	 */
	public function testItCapsCoreMarkupImageUsingAttachmentDimensions(): void {
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/landscape.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$this->assertIsInt( $attachment_id );
		wp_update_attachment_metadata(
			$attachment_id,
			array(
				'width'  => 1600,
				'height' => 900,
				'file'   => '2026/11/landscape.jpg',
				'sizes'  => array(
					'large' => array(
						'file'      => 'landscape-1024x576.jpg',
						'width'     => 1024,
						'height'    => 576,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		$large_url = str_replace( 'landscape.jpg', 'landscape-1024x576.jpg', (string) wp_get_attachment_url( $attachment_id ) );

		$parsed_block                       = $this->get_block_with_image( 1, 1, 50 );
		$parsed_block['attrs']['mediaId']   = $attachment_id;
		$parsed_block['attrs']['mediaType'] = 'image';
		$parsed_block['innerHTML']          = sprintf(
			'<figure class="wp-block-media-text__media"><img src="%s" alt="" class="wp-image-%d size-large"/></figure><div class="wp-block-media-text__content"><p>Media content</p></div>',
			esc_url( $large_url ),
			$attachment_id
		);

		$img = $this->render_and_get_img( $parsed_block );

		$this->assertSame( '286', $img['width'] );
		$this->assertSame( '161', $img['height'] );
		$this->assertStringContainsString( 'max-width:286px;', $img['style'] );
	}

	/**
	 * Without a computed email width there's nothing to cap against, so the image is left alone.
	 */
	public function testItLeavesImageUnchangedWithoutEmailWidth(): void {
		$parsed_block = $this->get_block_with_image( 1200, 800, 50 );
		unset( $parsed_block['email_attrs'] );

		$img = $this->render_and_get_img( $parsed_block );

		$this->assertSame( '1200', $img['width'] );
		$this->assertSame( '800', $img['height'] );
	}

	/**
	 * Build a media & text block whose media is an image with the given saved dimensions.
	 *
	 * @param int $width Saved image width.
	 * @param int $height Saved image height.
	 * @param int $media_width Media width share in percent.
	 * @return array
	 */
	private function get_block_with_image( int $width, int $height, int $media_width ): array {
		$parsed_block                        = $this->parsed_media;
		$parsed_block['attrs']['mediaWidth'] = $media_width;
		$parsed_block['email_attrs']         = array( 'width' => '612px' );
		$parsed_block['innerHTML']           = sprintf(
			'<figure class="wp-block-media-text__media"><img src="test-image.jpg" alt="Test Image" width="%d" height="%d" style="max-width:100%%" /></figure><div class="wp-block-media-text__content"><p>Media content</p></div>',
			$width,
			$height
		);
		return $parsed_block;
	}

	/**
	 * Render the block and return the media image's width, height and style attributes.
	 *
	 * @param array $parsed_block Parsed block.
	 * @return array{width: string, height: string, style: string}
	 */
	private function render_and_get_img( array $parsed_block ): array {
		$rendered = $this->media_renderer->render( '', $parsed_block, $this->rendering_context );

		$processor = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'img' ) ), 'Expected the media image in the rendered output.' );

		return array(
			'width'  => (string) $processor->get_attribute( 'width' ),
			'height' => (string) $processor->get_attribute( 'height' ),
			'style'  => (string) $processor->get_attribute( 'style' ),
		);
	}
}
