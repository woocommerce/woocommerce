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
 * Integration test for Heading class
 */
class Heading_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Text renderer instance
	 *
	 * @var Text
	 */
	private $heading_renderer;

	/**
	 * Heading block configuration
	 *
	 * @var array
	 */
	private $parsed_heading = array(
		'blockName'    => 'core/heading',
		'attrs'        => array(
			'level'           => 1,
			'backgroundColor' => 'vivid-red',
			'textColor'       => 'pale-cyan-blue',
			'textAlign'       => 'center',
			'style'           => array(
				'typography' => array(
					'textTransform' => 'lowercase',
					'fontSize'      => '24px',
				),
			),
		),
		'email_attrs'  => array(
			'width' => '640px',
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
		'innerContent' => array(
			0 => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
		),
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
		$this->heading_renderer  = new Text();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders content
	 */
	public function testItRendersContent(): void {
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'This is Heading 1', $rendered );
		$this->assertStringContainsString( 'width:100%;', $rendered );
		$this->assertStringContainsString( 'font-size:24px;', $rendered );
		$this->assertStringNotContainsString( 'width:640px;', $rendered );
	}

	/**
	 * Test it renders block attributes
	 */
	public function testItRendersBlockAttributes(): void {
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'text-transform:lowercase;', $rendered );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
	}

	/**
	 * Test it renders custom set colors
	 */
	public function testItRendersCustomSetColors(): void {
		$this->parsed_heading['attrs']['style']['color']['background'] = '#000000';
		$this->parsed_heading['attrs']['style']['color']['text']       = '#ff0000';
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'background-color:#000000', $rendered );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
	}

	/**
	 * Test it replaces fluid font size in content
	 */
	public function testItReplacesFluidFontSizeInContent(): void {
		$rendered = $this->heading_renderer->render( '<h1 style="font-size:clamp(10px, 20px, 24px)">This is Heading 1</h1>', $this->parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'font-size:24px', $rendered );
	}

	/**
	 * Test it removes inline margin styles (not supported in email renderer).
	 */
	public function testItRemovesInlineMarginStyles(): void {
		$content                        = '<h1 style="margin-top:10px;margin-bottom:12px;">This is Heading 1</h1>';
		$parsed_heading                 = $this->parsed_heading;
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );
		$html     = new \WP_HTML_Tag_Processor( $rendered );
		$html->next_tag( array( 'tag_name' => 'h1' ) );

		$heading_style = $html->get_attribute( 'style' );
		$this->assertIsString( $heading_style );
		$this->assertStringNotContainsString( 'margin', $heading_style );

		// Margin styles should also not leak to the wrapper table cell.
		$html = new \WP_HTML_Tag_Processor( $rendered );
		$html->next_tag( array( 'tag_name' => 'td' ) );
		$table_cell_style = $html->get_attribute( 'style' );
		$this->assertIsString( $table_cell_style );
		$this->assertStringNotContainsString( 'margin', $table_cell_style );
	}

	/**
	 * The preset background class must not stay on the inner element.
	 *
	 * The wrapping table cell keeps the block's classes and also carries the resolved background
	 * inline. Leaving `has-<slug>-background-color` on the heading made the CSS inliner paint the
	 * same color a second time, which composites to a darker band for a translucent palette color.
	 */
	public function testItRemovesPresetBackgroundClassFromInnerElement(): void {
		$content                        = '<h1 class="wp-block-heading has-vivid-red-background-color has-background">This is Heading 1</h1>';
		$parsed_heading                 = $this->parsed_heading;
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'h1' ) ) );
		$heading_classes = (string) $html->get_attribute( 'class' );
		$this->assertStringNotContainsString( 'has-vivid-red-background-color', $heading_classes );
		$this->assertStringNotContainsString( 'has-background', $heading_classes );
		// Unrelated classes are untouched.
		$this->assertStringContainsString( 'wp-block-heading', $heading_classes );

		// The background is still rendered exactly once, on the wrapping table cell.
		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'td' ) ) );
		$this->assertStringContainsString( 'background-color', (string) $html->get_attribute( 'style' ) );
	}

	/**
	 * A custom background color set inline must not stay on the inner element either.
	 */
	public function testItRemovesInlineBackgroundColorFromInnerElement(): void {
		$content                        = '<h1 class="wp-block-heading has-background" style="background-color:#c284426b;">This is Heading 1</h1>';
		$parsed_heading                 = $this->parsed_heading;
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );
		$parsed_heading['attrs']['style']['color']['background'] = '#c284426b';
		unset( $parsed_heading['attrs']['backgroundColor'] );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'h1' ) ) );
		$this->assertStringNotContainsString( 'background-color', (string) $html->get_attribute( 'style' ) );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'td' ) ) );
		$this->assertStringContainsString( 'background-color:#c284426b', (string) $html->get_attribute( 'style' ) );
	}

	/**
	 * Only the background-color declaration itself is removed from the inner element.
	 *
	 * The removal is anchored to the start of a property name. Without that anchor a longer
	 * property ending in "background-color" is cut in half and its prefix is left fused to the
	 * next declaration, turning `--brand-background-color:#fff;color:red` into `--brand-color:red`
	 * — a different, valid-looking declaration rather than a visibly broken one.
	 */
	public function testItRemovesOnlyTheBackgroundColorDeclarationFromInnerElement(): void {
		$content                        = '<h1 class="wp-block-heading" style="--brand-background-color:#ffffff;background-color:#c284426b;color:#ff0000;">This is Heading 1</h1>';
		$parsed_heading                 = $this->parsed_heading;
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'h1' ) ) );
		$heading_style = (string) $html->get_attribute( 'style' );

		// The custom property survives intact, and does not get welded onto the next declaration.
		$this->assertStringContainsString( '--brand-background-color:#ffffff', $heading_style );
		$this->assertStringNotContainsString( '--brand-color', $heading_style );
		// The real background is still gone, and the unrelated declaration is untouched.
		$this->assertStringNotContainsString( '#c284426b', $heading_style );
		$this->assertStringContainsString( 'color:#ff0000', $heading_style );
	}

	/**
	 * The background is removed however the declaration is spelled.
	 *
	 * CSS property names are case-insensitive and a colon may be surrounded by whitespace, so
	 * `BACKGROUND-COLOR : x` is the same declaration as `background-color:x`. The editor's style
	 * engine only ever emits the lowercase, unspaced form, but block markup is hand-editable.
	 */
	public function testItRemovesBackgroundColorRegardlessOfDeclarationSpelling(): void {
		$content                        = '<h1 class="wp-block-heading" style="BACKGROUND-COLOR : #c284426b;color:#ff0000;">This is Heading 1</h1>';
		$parsed_heading                 = $this->parsed_heading;
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );

		$html = new \WP_HTML_Tag_Processor( $rendered );
		$this->assertTrue( $html->next_tag( array( 'tag_name' => 'h1' ) ) );
		$heading_style = (string) $html->get_attribute( 'style' );

		$this->assertStringNotContainsStringIgnoringCase( 'background-color', $heading_style );
		$this->assertStringContainsString( 'color:#ff0000', $heading_style );
	}

	/**
	 * Test it uses inherited color from email_attrs when no color is specified
	 */
	public function testItUsesInheritedColorFromEmailAttrs(): void {
		$parsed_heading = $this->parsed_heading;

		unset( $parsed_heading['attrs']['style']['color'] );
		unset( $parsed_heading['attrs']['textColor'] );

		$parsed_heading['email_attrs'] = array(
			'color' => '#ff0000',
		);

		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
	}

	/**
	 * Test it renders site title block.
	 */
	public function testItRendersSiteTitle(): void {
		$parsed_heading = array(
			'blockName'    => 'core/site-title',
			'attrs'        => array(
				'level'      => 5,
				'textAlign'  => 'center',
				'isLink'     => false,
				'linkTarget' => '_blank',
				'style'      => array(
					'typography' => array(
						'fontStyle'      => 'normal',
						'fontWeight'     => '900',
						'lineHeight'     => '2',
						'letterSpacing'  => '1px',
						'textDecoration' => 'none',
						'textTransform'  => 'none',
						'fontSize'       => '28px',
					),
				),
				'fontSize'   => 'medium',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
			'email_attrs'  => array(
				'font-size'       => '28px',
				'text-decoration' => 'none',
				'width'           => '580px',
				'color'           => 'var(--wp--preset--color--accent-3)',
			),
		);
		$rendered       = $this->heading_renderer->render( '<h3>My Site Title</h3>', $parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'My Site Title', $rendered );
		$this->assertStringContainsString( 'font-size:28px;', $rendered );
		$this->assertStringContainsString( 'font-weight:900;', $rendered );
	}

	/**
	 * Test it extracts alignment from has-text-align-center class when no textAlign attribute is set
	 */
	public function testItExtractsAlignmentFromHasTextAlignCenterClass(): void {
		$parsed_heading = $this->parsed_heading;
		// Ensure no textAlign or align attributes are set.
		unset( $parsed_heading['attrs']['textAlign'] );
		unset( $parsed_heading['attrs']['align'] );

		$content                        = '<h1 class="has-text-align-center">Centered heading</h1>';
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
		$this->assertStringContainsString( 'align="center"', $rendered );
	}

	/**
	 * Test it extracts alignment from has-text-align-right class when no textAlign attribute is set
	 */
	public function testItExtractsAlignmentFromHasTextAlignRightClass(): void {
		$parsed_heading = $this->parsed_heading;
		// Ensure no textAlign or align attributes are set.
		unset( $parsed_heading['attrs']['textAlign'] );
		unset( $parsed_heading['attrs']['align'] );

		$content                        = '<h1 class="has-text-align-right">Right aligned heading</h1>';
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );
		$this->assertStringContainsString( 'text-align:right;', $rendered );
		$this->assertStringContainsString( 'align="right"', $rendered );
	}

	/**
	 * Test it prioritizes textAlign attribute over has-text-align-* class
	 */
	public function testItPrioritizesTextAlignAttributeOverClass(): void {
		$parsed_heading                       = $this->parsed_heading;
		$parsed_heading['attrs']['textAlign'] = 'right';
		unset( $parsed_heading['attrs']['align'] );

		$content                        = '<h1 class="has-text-align-center">Heading with center class but right attribute</h1>';
		$parsed_heading['innerHTML']    = $content;
		$parsed_heading['innerContent'] = array( $content );

		$rendered = $this->heading_renderer->render( $content, $parsed_heading, $this->rendering_context );
		// Should use the attribute, not the class.
		$this->assertStringContainsString( 'text-align:right;', $rendered );
		$this->assertStringContainsString( 'align="right"', $rendered );
		$this->assertStringNotContainsString( 'text-align:center;', $rendered );
		$this->assertStringNotContainsString( 'align="center"', $rendered );
	}
}
