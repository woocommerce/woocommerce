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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Gallery;

/**
 * Integration test for Gallery class
 */
class Gallery_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Gallery renderer instance
	 *
	 * @var Gallery
	 */
	private $gallery_renderer;

	/**
	 * Gallery block configuration
	 *
	 * @var array
	 */
	private $parsed_gallery = array(
		'blockName'   => 'core/gallery',
		'attrs'       => array(
			'columns'     => 2,
			'randomOrder' => true,
			'linkTo'      => 'none',
		),
		'innerHTML'   => '<figure class="wp-block-gallery has-nested-images columns-2 is-cropped"></figure>',
		'innerBlocks' => array(
			0 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 1,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image1.jpg" alt="Image 1" class="wp-image-1"/><figcaption class="wp-element-caption">Caption 1</figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image1.jpg" alt="Image 1" class="wp-image-1"/><figcaption class="wp-element-caption">Caption 1</figcaption></figure>',
				),
			),
			1 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 2,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image2.jpg" alt="Image 2" class="wp-image-2"/><figcaption class="wp-element-caption">Caption 2</figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image2.jpg" alt="Image 2" class="wp-image-2"/><figcaption class="wp-element-caption">Caption 2</figcaption></figure>',
				),
			),
			2 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 3,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image3.jpg" alt="Image 3" class="wp-image-3"/><figcaption class="wp-element-caption">Caption 3</figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image3.jpg" alt="Image 3" class="wp-image-3"/><figcaption class="wp-element-caption">Caption 3</figcaption></figure>',
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
		$this->gallery_renderer  = new Gallery();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders gallery content
	 */
	public function testItRendersGalleryContent(): void {
		$rendered = $this->gallery_renderer->render( '', $this->parsed_gallery, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered );
		$this->assertStringContainsString( 'image2.jpg', $rendered );
		$this->assertStringContainsString( 'image3.jpg', $rendered );
	}

	/**
	 * Test it handles different column counts
	 */
	public function testItHandlesDifferentColumnCounts(): void {
		// Test 1 column.
		$parsed_gallery_1_col                     = $this->parsed_gallery;
		$parsed_gallery_1_col['attrs']['columns'] = 1;

		$rendered_1_col = $this->gallery_renderer->render( '', $parsed_gallery_1_col, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered_1_col );

		// Test 3 columns.
		$parsed_gallery_3_col                     = $this->parsed_gallery;
		$parsed_gallery_3_col['attrs']['columns'] = 3;

		$rendered_3_col = $this->gallery_renderer->render( '', $parsed_gallery_3_col, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered_3_col );

		// Test 5 columns (maximum).
		$parsed_gallery_5_col                     = $this->parsed_gallery;
		$parsed_gallery_5_col['attrs']['columns'] = 5;

		$rendered_5_col = $this->gallery_renderer->render( '', $parsed_gallery_5_col, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered_5_col );
	}

	/**
	 * Test it handles invalid column counts
	 */
	public function testItHandlesInvalidColumnCounts(): void {
		// Test 0 columns (should default to 3).
		$parsed_gallery_0_col                     = $this->parsed_gallery;
		$parsed_gallery_0_col['attrs']['columns'] = 0;

		$rendered_0_col = $this->gallery_renderer->render( '', $parsed_gallery_0_col, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered_0_col );

		// Test 10 columns (should be limited to 5).
		$parsed_gallery_10_col                     = $this->parsed_gallery;
		$parsed_gallery_10_col['attrs']['columns'] = 10;

		$rendered_10_col = $this->gallery_renderer->render( '', $parsed_gallery_10_col, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered_10_col );
	}

	/**
	 * Test it handles custom styling
	 */
	public function testItHandlesCustomStyling(): void {
		$parsed_gallery                   = $this->parsed_gallery;
		$parsed_gallery['attrs']['style'] = array(
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

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
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
		$parsed_gallery                                    = $this->parsed_gallery;
		$parsed_gallery['attrs']['style']['color']['text'] = '#123456';
		$parsed_gallery['attrs']['style']['color']['background'] = '#654321';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		$this->assertStringContainsString( 'color:#123456;', $rendered );
		$this->assertStringContainsString( 'background-color:#654321;', $rendered );
	}

	/**
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_gallery = $this->parsed_gallery;
		$content        = '<figure class="wp-block-gallery has-nested-images columns-2 is-cropped editor-class-1 another-class"></figure>';

		$rendered = $this->gallery_renderer->render( $content, $parsed_gallery, $this->rendering_context );
		$this->assertStringContainsString( 'wp-block-gallery has-nested-images columns-2 is-cropped editor-class-1 another-class', $rendered );
	}


	/**
	 * Test it correctly extracts images and removes figure wrappers
	 */
	public function testItCorrectlyExtractsImagesAndRemovesFigureWrappers(): void {
		$parsed_gallery = $this->parsed_gallery;
		// Update innerBlocks with test image.
		$parsed_gallery['innerBlocks'] = array(
			0 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 1,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="test-image.jpg" alt="Test Image" class="wp-image-1"/><figcaption class="wp-element-caption">Test Caption</figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="test-image.jpg" alt="Test Image" class="wp-image-1"/><figcaption class="wp-element-caption">Test Caption</figcaption></figure>',
				),
			),
		);

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		$this->assertStringContainsString( 'test-image.jpg', $rendered );
		$this->assertStringContainsString( 'Test Caption', $rendered );
		// Should not contain the figure wrapper.
		$this->assertStringNotContainsString( '<figure class="wp-block-image', $rendered );
	}

	/**
	 * Test it handles gallery with captions
	 */
	public function testItHandlesGalleryWithCaptions(): void {
		$parsed_gallery = $this->parsed_gallery;
		// Update innerBlocks with test images with captions.
		$parsed_gallery['innerBlocks'] = array(
			0 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 1,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image1.jpg" alt="Image 1" class="wp-image-1"/><figcaption class="wp-element-caption">Photo by <a href="https://example.com">Photographer</a></figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image1.jpg" alt="Image 1" class="wp-image-1"/><figcaption class="wp-element-caption">Photo by <a href="https://example.com">Photographer</a></figcaption></figure>',
				),
			),
			1 => array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 2,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image2.jpg" alt="Image 2" class="wp-image-2"/><figcaption class="wp-element-caption">Another photo</figcaption></figure>',
				'innerContent' => array(
					0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image2.jpg" alt="Image 2" class="wp-image-2"/><figcaption class="wp-element-caption">Another photo</figcaption></figure>',
				),
			),
		);

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		$this->assertStringContainsString( 'image1.jpg', $rendered );
		$this->assertStringContainsString( 'image2.jpg', $rendered );
		$this->assertStringContainsString( 'Photo by', $rendered );
		$this->assertStringContainsString( 'Another photo', $rendered );
		$this->assertStringContainsString( 'href="https://example.com"', $rendered );
	}

	/**
	 * Test it handles image links when present in innerHTML.
	 */
	public function testItHandlesImageLinks(): void {
		// Test with linked images in innerHTML.
		$parsed_gallery_with_links = $this->parsed_gallery;

		// Update first image to have a link in innerHTML.
		$parsed_gallery_with_links['innerBlocks'][0]['innerHTML'] = '<figure class="wp-block-image size-large"><a href="https://example.com/image1.jpg"><img src="https://example.com/image1.jpg" alt="Image 1" class="wp-image-1"/></a><figcaption class="wp-element-caption">Caption 1</figcaption></figure>';

		// Update second image to have a different link in innerHTML.
		$parsed_gallery_with_links['innerBlocks'][1]['innerHTML'] = '<figure class="wp-block-image size-large"><a href="https://example.com/?attachment_id=2"><img src="https://example.com/image2.jpg" alt="Image 2" class="wp-image-2"/></a><figcaption class="wp-element-caption">Caption 2</figcaption></figure>';

		// Third image has no link.
		$parsed_gallery_with_links['innerBlocks'][2]['innerHTML'] = '<figure class="wp-block-image size-large"><img src="https://example.com/image3.jpg" alt="Image 3" class="wp-image-3"/><figcaption class="wp-element-caption">Caption 3</figcaption></figure>';

		$rendered_with_links = $this->gallery_renderer->render( '', $parsed_gallery_with_links, $this->rendering_context );

		// Verify that links are preserved when present in innerHTML.
		$this->assertStringContainsString( '<a href="https://example.com/image1.jpg">', $rendered_with_links );
		$this->assertStringContainsString( '<a href="https://example.com/?attachment_id=2">', $rendered_with_links );

		// Verify that images without links don't get wrapped in anchor tags.
		$this->assertStringContainsString( '<img src="https://example.com/image3.jpg"', $rendered_with_links );
		$this->assertStringNotContainsString( '<a href="https://example.com/image3.jpg">', $rendered_with_links );
	}

	/**
	 * Test gallery caption support.
	 */
	public function testItHandlesGalleryCaption(): void {
		// Create a gallery with caption.
		$gallery_with_caption = '<!-- wp:gallery {"columns":2} -->
<figure class="wp-block-gallery has-nested-images columns-2 is-cropped">
<!-- wp:image {"id":1,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="image1.jpg" alt=""/></figure>
<!-- /wp:image -->
<!-- wp:image {"id":2,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="image2.jpg" alt=""/></figure>
<!-- /wp:image -->
<figcaption class="blocks-gallery-caption wp-element-caption">Gallery caption</figcaption>
</figure>
<!-- /wp:gallery -->';

		$parsed_gallery                = parse_blocks( $gallery_with_caption )[0];
		$parsed_gallery['innerBlocks'] = array(
			array(
				'blockName' => 'core/image',
				'attrs'     => array(
					'id'              => 1,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerHTML' => '<figure class="wp-block-image size-large"><img src="image1.jpg" alt=""/></figure>',
			),
			array(
				'blockName' => 'core/image',
				'attrs'     => array(
					'id'              => 2,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerHTML' => '<figure class="wp-block-image size-large"><img src="image2.jpg" alt=""/></figure>',
			),
		);

		$rendered = $this->gallery_renderer->render( $gallery_with_caption, $parsed_gallery, $this->rendering_context );

		// Check that gallery caption is rendered and centered.
		$this->assertStringContainsString( 'Gallery caption', $rendered );
		$this->assertStringContainsString( 'blocks-gallery-caption', $rendered );
		$this->assertStringContainsString( 'text-align: center', $rendered );
	}

	/**
	 * Test it applies a gallery-level aspect ratio crop to every image.
	 */
	public function testItAppliesGalleryLevelAspectRatioCrop(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		// Every image should be cropped square via inline CSS the sanitizer would otherwise strip.
		$this->assertSame( 3, substr_count( $rendered, 'object-fit: cover' ), 'Each image should get object-fit: cover.' );
		$this->assertSame( 3, substr_count( $rendered, 'aspect-ratio: 1' ), 'Each image should get the gallery aspect ratio.' );
	}

	/**
	 * Test a per-image aspect ratio overrides the gallery-level one.
	 */
	public function testItLetsPerImageAspectRatioOverrideGalleryLevel(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';
		$parsed_gallery['innerBlocks'][0]['attrs']['aspectRatio'] = '4/3';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		// The overriding image uses its own ratio, the others fall back to the gallery ratio.
		$this->assertStringContainsString( 'aspect-ratio: 4/3', $rendered );
		$this->assertSame( 2, substr_count( $rendered, 'aspect-ratio: 1;' ), 'The two non-overridden images keep the gallery ratio.' );
		$this->assertSame( 3, substr_count( $rendered, 'object-fit: cover' ), 'All three images are still cropped.' );
	}

	/**
	 * Test an invalid per-image aspect ratio falls back to the gallery ratio rather than
	 * disabling the crop for that image.
	 */
	public function testItFallsBackToGalleryRatioWhenPerImageOverrideIsInvalid(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';
		$parsed_gallery['innerBlocks'][0]['attrs']['aspectRatio'] = 'not-a-ratio';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		// All three images (including the one with the malformed override) use the gallery ratio.
		$this->assertSame( 3, substr_count( $rendered, 'aspect-ratio: 1;' ), 'The malformed override falls back to the gallery ratio.' );
		$this->assertSame( 3, substr_count( $rendered, 'object-fit: cover' ), 'Every image is still cropped.' );
		$this->assertStringNotContainsString( 'not-a-ratio', $rendered, 'The invalid value is never emitted into the markup.' );
	}

	/**
	 * Test galleries without an aspect ratio are left uncropped (no regression).
	 */
	public function testItDoesNotCropWhenNoAspectRatioIsSet(): void {
		$rendered = $this->gallery_renderer->render( '', $this->parsed_gallery, $this->rendering_context );

		$this->assertStringNotContainsString( 'object-fit', $rendered );
		$this->assertStringNotContainsString( 'aspect-ratio', $rendered );
	}

	/**
	 * Test an invalid aspect ratio value is ignored rather than injected into the markup.
	 */
	public function testItIgnoresInvalidAspectRatioValues(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = 'cover; background:url(javascript:alert(1))';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		$this->assertStringNotContainsString( 'object-fit', $rendered );
		$this->assertStringNotContainsString( 'aspect-ratio', $rendered );
		$this->assertStringNotContainsString( 'javascript:', $rendered );
	}

	/**
	 * Test the crop filter receives the image URL, ratio and target dimensions.
	 */
	public function testItPassesCropContextToTheImageUrlFilter(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';
		$parsed_gallery['attrs']['columns']     = 3;

		$received = array();
		$filter   = function ( $url, $aspect_ratio, $width, $height, $attrs ) use ( &$received ) {
			$received[] = array(
				'url'          => $url,
				'aspect_ratio' => $aspect_ratio,
				'width'        => $width,
				'height'       => $height,
				'attrs'        => $attrs,
			);
			return $url;
		};
		add_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10, 5 );
		$this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		remove_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10 );

		$this->assertCount( 3, $received, 'The filter runs once per gallery image.' );
		$this->assertSame( 'https://example.com/image1.jpg', $received[0]['url'] );
		$this->assertSame( '1', $received[0]['aspect_ratio'] );
		// Square crop: derived height equals the target width.
		$this->assertSame( $received[0]['width'], $received[0]['height'] );
		$this->assertGreaterThan( 0, $received[0]['width'], 'A concrete cell width is passed for CDN sizing.' );
		$this->assertSame( 1, $received[0]['attrs']['id'], 'The image block attributes are forwarded.' );
	}

	/**
	 * Test a server-cropped URL is used with concrete width/height dimensions.
	 */
	public function testItUsesServerCroppedUrlWithConcreteDimensions(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';

		$filter = function ( $url, $aspect_ratio, $width, $height ) {
			return $url . '?resize=' . $width . ',' . $height . '&crop=1';
		};
		add_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10, 4 );
		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		remove_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10 );

		// The rewritten (cropped) URL is used.
		$this->assertStringContainsString( 'crop=1', $rendered );
		$this->assertSame( 3, substr_count( $rendered, 'crop=1' ), 'Every image URL is rewritten.' );

		// Every image (not just the first) gets equal, positive, concrete width/height attributes so
		// a square crop renders everywhere.
		$image_count = 0;
		$html        = new \WP_HTML_Tag_Processor( $rendered );
		while ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			++$image_count;
			$width  = $html->get_attribute( 'width' );
			$height = $html->get_attribute( 'height' );
			$this->assertIsString( $width, 'Each server-cropped image has a concrete width.' );
			$this->assertIsString( $height, 'Each server-cropped image has a concrete height.' );
			$this->assertGreaterThan( 0, (int) $width, 'The width is positive.' );
			$this->assertSame( (int) $width, (int) $height, 'A square crop has equal width and height.' );
		}
		$this->assertSame( 3, $image_count, 'All three images are present.' );
	}

	/**
	 * Test an unchanged URL from the filter falls back to CSS-only cropping (no dimensions).
	 */
	public function testItFallsBackToCssCropWhenFilterLeavesUrlUnchanged(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';

		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		// Without a cropping integration, images keep CSS cropping and are not given fixed dimensions.
		$this->assertStringContainsString( 'object-fit: cover', $rendered );

		$image_count = 0;
		$html        = new \WP_HTML_Tag_Processor( $rendered );
		while ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			++$image_count;
			$this->assertNull( $html->get_attribute( 'height' ), 'Uncropped images must not get a fixed height that would distort them.' );
		}
		$this->assertSame( 3, $image_count, 'All three images are present.' );
	}

	/**
	 * Test an image whose src has a multi-param query string (e.g. a CDN URL with "&") is not
	 * misclassified as server-cropped when no integration rewrites it.
	 *
	 * Regression: get_attribute() returns the entity-decoded src while esc_url() re-encodes "&" to
	 * "&#038;", so comparing an escaped URL against the decoded original wrongly flagged any "&"-in-src
	 * image as cropped and stamped it with distorting fixed dimensions.
	 */
	public function testItDoesNotMisclassifyAmpersandSrcAsServerCropped(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';
		// A CDN-style src with more than one query parameter, so the URL contains an ampersand.
		$parsed_gallery['innerBlocks'][0]['innerHTML'] = '<figure class="wp-block-image size-large"><img src="https://example.com/image1.jpg?w=600&h=600" alt="Image 1" class="wp-image-1"/></figure>';

		// No filter is attached, so nothing is server-cropped.
		$rendered = $this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );

		// The ampersand image keeps CSS-only cropping: no fixed width/height that would distort it.
		$image_count = 0;
		$html        = new \WP_HTML_Tag_Processor( $rendered );
		while ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			++$image_count;
			$this->assertNull( $html->get_attribute( 'width' ), 'An uncropped ampersand-src image must not get a fixed width.' );
			$this->assertNull( $html->get_attribute( 'height' ), 'An uncropped ampersand-src image must not get a fixed height.' );
		}
		$this->assertSame( 3, $image_count, 'All three images are present.' );
		$this->assertStringContainsString( 'object-fit: cover', $rendered, 'Images still get CSS cropping.' );
	}

	/**
	 * Test the crop is sized to the actual rendered cell width, so an incomplete final row
	 * (e.g. a lone trailing image spanning the full width) is not served an undersized file.
	 */
	public function testItSizesCropToTheRenderedRowWidthForIncompleteRows(): void {
		$parsed_gallery                         = $this->parsed_gallery;
		$parsed_gallery['attrs']['aspectRatio'] = '1';
		$parsed_gallery['attrs']['columns']     = 3;
		// Add a fourth image so the final row holds a single, full-width image.
		$parsed_gallery['innerBlocks'][3] = array(
			'blockName'    => 'core/image',
			'attrs'        => array(
				'id'              => 4,
				'sizeSlug'        => 'large',
				'linkDestination' => 'none',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.com/image4.jpg" alt="Image 4" class="wp-image-4"/></figure>',
			'innerContent' => array(
				0 => '<figure class="wp-block-image size-large"><img src="https://example.com/image4.jpg" alt="Image 4" class="wp-image-4"/></figure>',
			),
		);

		$widths = array();
		$filter = function ( $url, $aspect_ratio, $width ) use ( &$widths ) {
			$widths[] = $width;
			return $url;
		};
		add_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10, 3 );
		$this->gallery_renderer->render( '', $parsed_gallery, $this->rendering_context );
		remove_filter( 'woocommerce_email_editor_gallery_cropped_image_url', $filter, 10 );

		$this->assertCount( 4, $widths, 'The filter runs once per gallery image.' );
		// The first three images share a full row (one-third width each); the fourth is alone in the
		// final row and spans the full width.
		$this->assertSame( $widths[0], $widths[1], 'Images in the same full row share a width.' );
		$this->assertSame( $widths[1], $widths[2], 'Images in the same full row share a width.' );
		$this->assertGreaterThan( $widths[0], $widths[3], 'A lone trailing image is sized to the full row width.' );
		$this->assertGreaterThanOrEqual( 2 * $widths[0], $widths[3], 'The full-width cell is substantially wider than a one-third cell.' );
	}
}
