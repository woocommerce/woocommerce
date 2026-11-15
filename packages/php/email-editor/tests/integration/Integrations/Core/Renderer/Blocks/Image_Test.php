<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Image class
 */
class Image_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Image renderer instance
	 *
	 * @var Image
	 */
	private $image_renderer;

	/**
	 * Content of the image block
	 *
	 * @var string
	 */
	private $image_content = '
    <figure class="wp-block-image alignleft size-full is-style-default">
        <img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
    </figure>
  ';

	/**
	 * Parse image block configuration
	 *
	 * @var array
	 */
	private $parsed_image = array(
		'blockName'    => 'core/image',
		'attrs'        => array(
			'align'           => 'left',
			'id'              => 1,
			'scale'           => 'cover',
			'sizeSlug'        => 'full',
			'linkDestination' => 'none',
			'className'       => 'is-style-default',
			'width'           => '640px',
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	);
	/**
	 * Instance of Rendering_Context class
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
		$this->image_renderer    = new Image();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders mandatory image styles
	 */
	public function testItRendersMandatoryImageStyles(): void {
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $this->image_content; // To avoid repetition of the image content in the test we need to add it to the parsed block.

		$rendered = $this->image_renderer->render( $this->image_content, $parsed_image, $this->rendering_context );
		$this->assertStringNotContainsString( '<figure', $rendered );
		$this->assertStringNotContainsString( '<figcaption', $rendered );
		$this->assertStringNotContainsString( '</figure>', $rendered );
		$this->assertStringNotContainsString( '</figcaption>', $rendered );
		$this->assertStringNotContainsString( 'srcset', $rendered );
		$this->assertStringContainsString( 'width="640"', $rendered );
		$this->assertStringContainsString( 'width:640px;', $rendered );
		$this->assertStringContainsString( '<img ', $rendered );
	}

	/**
	 * Test it renders border radius style
	 */
	public function testItRendersBorderRadiusStyle(): void {
		$parsed_image                       = $this->parsed_image;
		$parsed_image['attrs']['className'] = 'is-style-rounded';
		$parsed_image['innerHTML']          = $this->image_content; // To avoid repetition of the image content in the test we need to add it to the parsed block.

		$rendered = $this->image_renderer->render( $this->image_content, $parsed_image, $this->rendering_context );
		$this->assertStringNotContainsString( '<figure', $rendered );
		$this->assertStringNotContainsString( '<figcaption', $rendered );
		$this->assertStringNotContainsString( '</figure>', $rendered );
		$this->assertStringNotContainsString( '</figcaption>', $rendered );
		$this->assertStringContainsString( 'width="640"', $rendered );
		$this->assertStringContainsString( 'width:640px;', $rendered );
		$this->assertStringContainsString( '<img ', $rendered );
		$this->assertStringContainsString( 'border-radius: 9999px;', $rendered );
	}

	/**
	 * Test it renders caption
	 */
	public function testItRendersCaption(): void {
		$image_content             = str_replace( '</figure>', '<figcaption class="wp-element-caption">Caption</figcaption></figure>', $this->image_content );
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $image_content; // To avoid repetition of the image content in the test we need to add it to the parsed block.

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );
		$this->assertStringContainsString( '>Caption</span>', $rendered );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
	}

	/**
	 * Test a caption saved without core's caption class still gets it.
	 *
	 * Image captions saved before WordPress 6.2 carry no class, and the email's caption styles key off
	 * that class, so without it a saved caption color never reaches them.
	 */
	public function testItAddsTheCaptionClassToACaptionSavedWithoutIt(): void {
		$image_content             = str_replace( '</figure>', '<figcaption>Caption</figcaption></figure>', $this->image_content );
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertStringContainsString( '<span class="wp-element-caption">Caption</span>', $rendered );
	}

	/**
	 * Test it keeps a caption's existing classes and does not repeat the caption class.
	 */
	public function testItKeepsExistingCaptionClasses(): void {
		$image_content             = str_replace( '</figure>', '<figcaption class="custom-caption wp-element-caption">Caption</figcaption></figure>', $this->image_content );
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertStringContainsString( '<span class="custom-caption wp-element-caption">Caption</span>', $rendered );
		$this->assertSame( 1, substr_count( $rendered, 'wp-element-caption' ) );
	}

	/**
	 * Test it renders image alignment
	 */
	public function testItRendersImageAlignment(): void {
		$image_content                  = str_replace( 'style=""', 'style="width:400px;height:300px;"', $this->image_content );
		$parsed_image                   = $this->parsed_image;
		$parsed_image['attrs']['align'] = 'center';
		$parsed_image['attrs']['width'] = '400px';
		$parsed_image['innerHTML']      = $image_content; // To avoid repetition of the image content in the test we need to add it to the parsed block.

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );
		$this->assertStringContainsString( 'align="center"', $rendered );
		$this->assertStringContainsString( 'width="400"', $rendered );
		$this->assertStringContainsString( 'height="300"', $rendered );
		$this->assertStringContainsString( 'height:300px;', $rendered );
		$this->assertStringContainsString( 'width:400px;', $rendered );

		// Verify alignment is also applied to the inner image cell for mobile responsiveness.
		$html = new \WP_HTML_Tag_Processor( $rendered );
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-cell',
			)
		);
		$this->assertEquals( 'center', $html->get_attribute( 'align' ) );
	}

	/**
	 * Test it renders full alignment as center
	 */
	public function testItRendersFullAlignmentAsCenter(): void {
		$image_content                  = str_replace( 'alignleft', 'alignfull', $this->image_content );
		$parsed_image                   = $this->parsed_image;
		$parsed_image['attrs']['align'] = 'full';
		$parsed_image['attrs']['width'] = '640px';
		$parsed_image['innerHTML']      = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// "full" is not a valid HTML align value, so it should be mapped to "center".
		$this->assertStringNotContainsString( 'align="full"', $rendered );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-cell',
			)
		);
		$this->assertEquals( 'center', $html->get_attribute( 'align' ) );
	}

	/**
	 * Test it renders image with borders
	 */
	public function testItRendersBorders(): void {
		$image_content                            = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="border-width:10px;border-color:#000001;border-radius:20px;height:auto;" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
			</figure>
		';
		$parsed_image                             = $this->parsed_image;
		$parsed_image['attrs']['style']['border'] = array(
			'width'  => '10px',
			'color'  => '#000001',
			'radius' => '20px',
		);

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		// Border is rendered on the dedicated border cell wrapper (not the outer image cell).
		// This ensures borders stay tight around the image on mobile.
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-border-cell',
			)
		);
		$table_cell_style = $html->get_attribute( 'style' );
		$this->assertIsString( $table_cell_style );
		$this->assertStringContainsString( 'border-color:#000001', $table_cell_style );
		$this->assertStringContainsString( 'border-radius:20px', $table_cell_style );
		$this->assertStringContainsString( 'border-style:solid;', $table_cell_style );
		$html->next_tag( array( 'tag_name' => 'img' ) );
		$img_style = $html->get_attribute( 'style' );
		$this->assertIsString( $img_style );
		// The image keeps a rounded corner reduced by the border width so it stays flush inside the border.
		$this->assertStringContainsString( 'border-radius:10px;', $img_style );
		$this->assertStringNotContainsString( 'border-style', $img_style );
		$this->assertStringNotContainsString( 'border-width', $img_style );
		$this->assertStringNotContainsString( 'border-color', $img_style );
	}

	/**
	 * Test the image radius is clamped to 0 when the border width exceeds the radius
	 */
	public function testItClampsImageBorderRadiusToZero(): void {
		$image_content                            = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="border-width:10px;border-color:#000001;border-radius:5px;height:auto;" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
			</figure>
		';
		$parsed_image                             = $this->parsed_image;
		$parsed_image['attrs']['style']['border'] = array(
			'width'  => '10px',
			'color'  => '#000001',
			'radius' => '5px',
		);

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-border-cell',
			)
		);
		$table_cell_style = $html->get_attribute( 'style' );
		$this->assertIsString( $table_cell_style );
		$this->assertStringContainsString( 'border-radius:5px', $table_cell_style );
		$html->next_tag( array( 'tag_name' => 'img' ) );
		$img_style = $html->get_attribute( 'style' );
		$this->assertIsString( $img_style );
		$this->assertStringContainsString( 'border-radius:0px;', $img_style );
	}

	/**
	 * Test it moves border related classes
	 */
	public function testItMovesBorderRelatedClasses(): void {
		$image_content                            = str_replace( '<img', '<img class="custom-class has-border-color has-border-red-color"', $this->image_content );
		$parsed_image                             = $this->parsed_image;
		$parsed_image['attrs']['style']['border'] = array(
			'width'  => '10px',
			'color'  => '#000001',
			'radius' => '20px',
		);

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		// Border is rendered on the dedicated border cell wrapper and the border classes are moved there.
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-border-cell',
			)
		);
		$table_cell_class = $html->get_attribute( 'class' );
		$this->assertIsString( $table_cell_class );
		$this->assertStringContainsString( 'has-border-red-color', $table_cell_class );
		$this->assertStringContainsString( 'has-border-color', $table_cell_class );
		$this->assertStringNotContainsString( 'custom-class', $table_cell_class );
	}

	/**
	 * Test it renders image with link
	 */
	public function testItRendersImageWithLink(): void {
		$image_content_with_link   = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<a href="https://example.com/target-page">
					<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
				</a>
			</figure>
		';
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $image_content_with_link;

		$rendered = $this->image_renderer->render( $image_content_with_link, $parsed_image, $this->rendering_context );

		// Check that the anchor tag is present with correct attributes.
		$this->assertStringContainsString( '<a href="https://example.com/target-page"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		// Check that the image is present.
		$this->assertStringContainsString( '<img ', $rendered );
	}

	/**
	 * Test it renders image without link when no anchor tag is present
	 */
	public function testItRendersImageWithoutLinkWhenNoAnchorTag(): void {
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $this->image_content; // Original content without link.

		$rendered = $this->image_renderer->render( $this->image_content, $parsed_image, $this->rendering_context );

		// Check that no anchor tag is present.
		$this->assertStringNotContainsString( '<a href=', $rendered );
		$this->assertStringNotContainsString( 'rel="noopener nofollow"', $rendered );
		$this->assertStringNotContainsString( 'target="_blank"', $rendered );

		// But image should still be present.
		$this->assertStringContainsString( '<img ', $rendered );
	}

	/**
	 * Test it properly escapes anchor tag href
	 */
	public function testItEscapesAnchorTagHref(): void {
		$malicious_url                     = 'javascript:alert("xss")';
		$image_content_with_malicious_link = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<a href="' . $malicious_url . '">
					<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
				</a>
			</figure>
		';
		$parsed_image                      = $this->parsed_image;
		$parsed_image['innerHTML']         = $image_content_with_malicious_link;

		$rendered = $this->image_renderer->render( $image_content_with_malicious_link, $parsed_image, $this->rendering_context );

		// The malicious URL should be escaped/sanitized by esc_url().
		// esc_url() should remove javascript: protocol.
		$this->assertStringNotContainsString( 'javascript:', $rendered );
		$this->assertStringNotContainsString( 'alert("xss")', $rendered );
	}

	/**
	 * Test it extracts width from URL query parameter
	 */
	public function testItExtractsWidthFromUrlQueryParameter(): void {
		$image_content = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg?w=500" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] ); // Remove width to test fallback logic.
		$parsed_image['email_attrs']['width'] = '600px'; // Set max width.
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// Should use width from URL parameter (500px), which is less than max (600px).
		$this->assertStringContainsString( 'width="500"', $rendered );
		$this->assertStringContainsString( 'width:500px;', $rendered );
	}

	/**
	 * Test it respects max width when URL parameter is larger
	 */
	public function testItRespectsMaxWidthWhenUrlParameterIsLarger(): void {
		$image_content = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg?w=800" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] ); // Remove width to test fallback logic.
		$parsed_image['email_attrs']['width'] = '600px'; // Set max width.
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// Should use max width (600px) when URL parameter (800px) is larger.
		$this->assertStringContainsString( 'width="600"', $rendered );
		$this->assertStringContainsString( 'width:600px;', $rendered );
	}

	/**
	 * Test it falls back to 100% when no width information is available
	 */
	public function testItFallsBackTo100PercentWhenNoWidthInfoAvailable(): void {
		$image_content = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="" srcset=""/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] ); // Remove width to test fallback logic.
		unset( $parsed_image['email_attrs']['width'] ); // Remove email_attrs width to trigger 100% fallback.
		$parsed_image['innerHTML'] = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// Should fall back to 100% width when no width information is available.
		$this->assertStringContainsString( 'width:100%;', $rendered );
	}

	/**
	 * Test it ignores invalid URL width parameters
	 */
	public function testItIgnoresInvalidUrlWidthParameters(): void {
		$image_content = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg?w=invalid" alt="" style="" srcset=""/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] ); // Remove width to test fallback logic.
		$parsed_image['email_attrs']['width'] = '600px'; // Set max width.
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// Should fall back to max width when URL parameter is invalid.
		$this->assertStringContainsString( 'width="600"', $rendered );
		$this->assertStringContainsString( 'width:600px;', $rendered );
	}

	/**
	 * Test it ignores negative or zero width parameters
	 */
	public function testItIgnoresNegativeOrZeroWidthParameters(): void {
		$image_content = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg?w=0" alt="" style="" srcset=""/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] ); // Remove width to test fallback logic.
		$parsed_image['email_attrs']['width'] = '600px'; // Set max width.
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		// Should fall back to max width when URL parameter is 0 or negative.
		$this->assertStringContainsString( 'width="600"', $rendered );
		$this->assertStringContainsString( 'width:600px;', $rendered );
	}

	/**
	 * Test it preserves data-link-href attribute for personalization tags
	 */
	public function testItPreservesDataLinkHref(): void {
		$image_content_with_data_link = '
			<figure class="wp-block-image alignleft size-full is-style-default">
				<a href="#" data-link-href="[trackable-link url=\'test\']">
					<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style=""/>
				</a>
			</figure>
		';
		$parsed_image                 = $this->parsed_image;
		$parsed_image['innerHTML']    = $image_content_with_data_link;

		$rendered = $this->image_renderer->render( $image_content_with_data_link, $parsed_image, $this->rendering_context );

		// Note: esc_attr() encodes single quotes as &#039;.
		$this->assertStringContainsString( 'data-link-href="[trackable-link url=&#039;test&#039;]"', $rendered );
	}

	/**
	 * Test that only attributes useful in an email stay on the image.
	 */
	public function testItRemovesWebOnlyAttributesFromImage(): void {
		$image_content = '<figure class="wp-block-image"><img class="wp-image-1" src="https://test.com/image.jpg?a=1&b=2" alt="Alt" title="Title" width="640" height="320" loading="lazy" decoding="async" fetchpriority="low" srcset="https://test.com/image.jpg 1000w" sizes="(max-width: 640px) 100vw, 640px" data-id="1" style="aspect-ratio:2;object-fit:cover"/></figure>';

		$rendered = $this->image_renderer->render( $image_content, $this->parsed_image, $this->rendering_context );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'img' ) ) );
		$this->assertEqualsCanonicalizing(
			array( 'src', 'alt', 'title', 'width', 'height', 'style' ),
			$html->get_attribute_names_with_prefix( '' )
		);
		$this->assertSame( 'https://test.com/image.jpg?a=1&b=2', $html->get_attribute( 'src' ) );
		$this->assertStringContainsString( 'object-fit:cover', (string) $html->get_attribute( 'style' ) );
		$this->assertStringContainsString( 'aspect-ratio:2', (string) $html->get_attribute( 'style' ) );
	}

	/**
	 * Test the saved height is scaled when capping the width shrinks the image.
	 *
	 * Outlook sizes from the width and height attributes, so a saved height left beside the capped
	 * width would stretch the image vertically.
	 */
	public function testItScalesTheSavedHeightWhenItCapsTheWidth(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" width="1024" height="555" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		$parsed_image['email_attrs']['width'] = '660px';
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '660', $this->get_image_attribute( $rendered, 'width' ) );
		// 555 * ( 660 / 1024 ) = 357.76.
		$this->assertSame( '358', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test the saved height is scaled to a width set on the block.
	 */
	public function testItScalesTheSavedHeightToABlockWidth(): void {
		$image_content                  = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" width="1024" height="555" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image                   = $this->parsed_image;
		$parsed_image['attrs']['width'] = '400px';
		$parsed_image['innerHTML']      = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '400', $this->get_image_attribute( $rendered, 'width' ) );
		// 555 * ( 400 / 1024 ) = 216.8.
		$this->assertSame( '217', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test an image narrower than the content column keeps its saved dimensions.
	 */
	public function testItKeepsTheSavedHeightWhenTheImageFitsTheColumn(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg?w=400" alt="" width="400" height="200" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		$parsed_image['email_attrs']['width'] = '660px';
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '400', $this->get_image_attribute( $rendered, 'width' ) );
		$this->assertSame( '200', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test a fixed height in the image's style wins over the scaled height.
	 */
	public function testItKeepsAFixedStyleHeight(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" width="1024" height="555" style="max-width:100%;height:300px;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		$parsed_image['email_attrs']['width'] = '660px';
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '660', $this->get_image_attribute( $rendered, 'width' ) );
		$this->assertSame( '300', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test the saved height is kept when the width falls back to a percentage.
	 *
	 * There is no pixel width to scale the height against.
	 */
	public function testItKeepsTheSavedHeightWhenTheWidthFallsBackToAPercentage(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" width="1024" height="555" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		unset( $parsed_image['email_attrs']['width'] );
		$parsed_image['innerHTML'] = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '555', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test the saved height is kept when the image has no saved width to scale it against.
	 */
	public function testItKeepsTheSavedHeightWhenTheImageHasNoSavedWidth(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" height="555" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		$parsed_image['email_attrs']['width'] = '660px';
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '660', $this->get_image_attribute( $rendered, 'width' ) );
		$this->assertSame( '555', $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Test no height attribute is invented for an image saved without dimensions.
	 *
	 * This is how current core saves an image block: the size lives in the style, not in attributes.
	 */
	public function testItAddsNoHeightWhenTheImageHasNoSavedDimensions(): void {
		$image_content = '
			<figure class="wp-block-image size-large">
				<img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="max-width:100%;height:auto;"/>
			</figure>
		';
		$parsed_image  = $this->parsed_image;
		unset( $parsed_image['attrs']['width'] );
		$parsed_image['email_attrs']['width'] = '660px';
		$parsed_image['innerHTML']            = $image_content;

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->rendering_context );

		$this->assertSame( '660', $this->get_image_attribute( $rendered, 'width' ) );
		$this->assertNull( $this->get_image_attribute( $rendered, 'height' ) );
	}

	/**
	 * Read an attribute off the rendered image.
	 *
	 * @param string $rendered Rendered block content.
	 * @param string $attribute Attribute name.
	 * @return string|null The attribute value, or null when the image doesn't carry it.
	 */
	private function get_image_attribute( string $rendered, string $attribute ): ?string {
		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'img' ) ), 'The rendered content has no image.' );
		$value = $html->get_attribute( $attribute );
		return is_string( $value ) ? $value : null;
	}
}
