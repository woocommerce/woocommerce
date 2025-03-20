<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

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
	 * Settings controller instance
	 *
	 * @var Settings_Controller
	 */
	private $settings_controller;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->image_renderer      = new Image();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test it renders mandatory image styles
	 */
	public function testItRendersMandatoryImageStyles(): void {
		$parsed_image              = $this->parsed_image;
		$parsed_image['innerHTML'] = $this->image_content; // To avoid repetition of the image content in the test we need to add it to the parsed block.

		$rendered = $this->image_renderer->render( $this->image_content, $parsed_image, $this->settings_controller );
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

		$rendered = $this->image_renderer->render( $this->image_content, $parsed_image, $this->settings_controller );
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

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->settings_controller );
		$this->assertStringContainsString( '>Caption</span>', $rendered );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
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

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->settings_controller );
		$this->assertStringContainsString( 'align="center"', $rendered );
		$this->assertStringContainsString( 'width="400"', $rendered );
		$this->assertStringContainsString( 'height="300"', $rendered );
		$this->assertStringContainsString( 'height:300px;', $rendered );
		$this->assertStringContainsString( 'width:400px;', $rendered );
	}

	/**
	 * Test it renders image with borders
	 */
	public function testItRendersBorders(): void {
		$image_content                            = $this->image_content;
		$parsed_image                             = $this->parsed_image;
		$parsed_image['attrs']['style']['border'] = array(
			'width'  => '10px',
			'color'  => '#000001',
			'radius' => '20px',
		);

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->settings_controller );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		// Border is rendered on the wrapping table cell.
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-cell',
			)
		);
		$table_cell_style = $html->get_attribute( 'style' );
		$this->assertStringContainsString( 'border-color:#000001', $table_cell_style );
		$this->assertStringContainsString( 'border-radius:20px', $table_cell_style );
		$this->assertStringContainsString( 'border-style:solid;', $table_cell_style );
		$html->next_tag( array( 'tag_name' => 'img' ) );
		$img_style = $html->get_attribute( 'style' );
		$this->assertStringNotContainsString( 'border', $img_style );
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

		$rendered = $this->image_renderer->render( $image_content, $parsed_image, $this->settings_controller );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		// Border is rendered on the wrapping table cell and the border classes are moved to the wrapping table cell.
		$html->next_tag(
			array(
				'tag_name'   => 'td',
				'class_name' => 'email-image-cell',
			)
		);
		$table_cell_class = $html->get_attribute( 'class' );
		$this->assertStringContainsString( 'has-border-red-color', $table_cell_class );
		$this->assertStringContainsString( 'has-border-color', $table_cell_class );
		$this->assertStringNotContainsString( 'custom-class', $table_cell_class );
	}
}
