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
 * Integration test for Quote class
 */
class Quote_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Quote renderer instance
	 *
	 * @var Quote
	 */
	private $quote_renderer;
	/**
	 * Parsed Quote block
	 *
	 * @var array
	 */
	private $parsed_quote = array(
		'blockName'    => 'core/quote',
		'attrs'        => array(),
		'innerBlocks'  => array(
			0 => array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Quote content</p>',
				'innerContent' => array(
					0 => '<p>Quote content</p>',
				),
			),
		),
		'innerHTML'    => '<blockquote class="wp-block-quote"></blockquote>',
		'innerContent' => array(
			0 => '<blockquote class="wp-block-quote">',
			1 => null,
			2 => '</blockquote>',
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

		$this->quote_renderer    = new Quote();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders quote content
	 */
	public function testItRendersQuoteContent(): void {
		$rendered = $this->quote_renderer->render( '<p>Quote content</p>', $this->parsed_quote, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Quote content', $rendered );
	}

	/**
	 * Test it renders every child block when the quote contains multiple paragraphs
	 * that have already been wrapped by the email editor's per-block renderer.
	 */
	public function testItRendersAllChildBlocksWhenQuoteContainsMultipleParagraphs(): void {
		// Mirrors the shape of $block_content received by Quote::render_content in
		// production: each child paragraph has already gone through the email editor's
		// render_block filter, which wraps it in a table + .email-block-layout div.
		$block_content = <<<'HTML'
<blockquote class="wp-block-quote">
<table align="left" width="100%" style=""><tr><td><div class="email-block-layout" style=""><p>FIRST_CHILD_MARKER</p></div></td></tr></table>
<table align="left" width="100%" style=""><tr><td><div class="email-block-layout" style=""><p>SECOND_CHILD_MARKER</p></div></td></tr></table>
<table align="left" width="100%" style=""><tr><td><div class="email-block-layout" style=""><p>THIRD_CHILD_MARKER</p></div></td></tr></table>
</blockquote>
HTML;

		$parsed_quote = array(
			'blockName'    => 'core/quote',
			'attrs'        => array(),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '<p>FIRST_CHILD_MARKER</p>',
					'innerContent' => array( '<p>FIRST_CHILD_MARKER</p>' ),
				),
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '<p>SECOND_CHILD_MARKER</p>',
					'innerContent' => array( '<p>SECOND_CHILD_MARKER</p>' ),
				),
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '<p>THIRD_CHILD_MARKER</p>',
					'innerContent' => array( '<p>THIRD_CHILD_MARKER</p>' ),
				),
			),
			'innerHTML'    => '<blockquote class="wp-block-quote"></blockquote>',
			'innerContent' => array(
				'<blockquote class="wp-block-quote">',
				null,
				null,
				null,
				'</blockquote>',
			),
		);

		$rendered = $this->quote_renderer->render( $block_content, $parsed_quote, $this->rendering_context );

		$this->assertStringContainsString( 'FIRST_CHILD_MARKER', $rendered );
		$this->assertStringContainsString( 'SECOND_CHILD_MARKER', $rendered );
		$this->assertStringContainsString( 'THIRD_CHILD_MARKER', $rendered );

		// The visual quote indent comes from the wrapping email-block-quote table;
		// the inner <blockquote> must be stripped to avoid a quote-within-a-quote.
		$this->assertStringNotContainsString( '<blockquote', $rendered );
	}

	/**
	 * Test it renders the citation exactly once when the quote contains a <cite>.
	 *
	 * The <cite> is rendered as a separate styled citation block; it must also be
	 * removed from the inline quote content so it does not appear twice.
	 */
	public function testItRendersCitationOnlyOnce(): void {
		$block_content = '<blockquote class="wp-block-quote"><p>Quote body</p><cite>UNIQUE_CITATION_TOKEN</cite></blockquote>';

		$parsed_quote                = $this->parsed_quote;
		$parsed_quote['innerBlocks'] = array(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Quote body</p>',
				'innerContent' => array( '<p>Quote body</p>' ),
			),
		);

		$rendered = $this->quote_renderer->render( $block_content, $parsed_quote, $this->rendering_context );

		$this->assertStringContainsString( 'UNIQUE_CITATION_TOKEN', $rendered );
		$this->assertSame( 1, substr_count( $rendered, 'UNIQUE_CITATION_TOKEN' ), 'citation text appears more than once in rendered output' );
		$this->assertStringContainsString( 'email-block-quote-citation', $rendered );
	}

	/**
	 * Test it moves default quote border to the right in RTL.
	 *
	 * The visual quote indent is provided by the email-block-quote table; the
	 * inner <blockquote> tag is stripped (see get_quote_content) so that the
	 * RTL right-side border on the table is the only border the recipient sees.
	 */
	public function testItUsesRightDefaultBorderInRtl(): void {
		$theme_controller = $this->di_container->get( Theme_Controller::class );
		$rtl_context      = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
		$content          = '<blockquote class="wp-block-quote" style="border-color: currentColor; border-width: 0 0 0 1px; border-left-style: solid;"><p>Quote content</p></blockquote>';

		$rendered = $this->quote_renderer->render( $content, $this->parsed_quote, $rtl_context );

		$this->assertStringContainsString( 'border-width:0 1px 0 0;', $rendered );
		$this->assertStringContainsString( 'border-style:solid;', $rendered );
		$this->assertStringNotContainsString( '<blockquote', $rendered );
	}

	/**
	 * Test it preserves authored quote borders in RTL.
	 */
	public function testItPreservesAuthoredQuoteBorderInRtl(): void {
		$theme_controller      = $this->di_container->get( Theme_Controller::class );
		$rtl_context           = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
		$parsed_quote          = $this->parsed_quote;
		$parsed_quote['attrs'] = array(
			'style' => array(
				'border' => array(
					'width' => '0 0 0 2px',
					'style' => 'dashed',
				),
			),
		);

		$rendered = $this->quote_renderer->render( '<p>Quote content</p>', $parsed_quote, $rtl_context );

		$this->assertStringContainsString( 'border-width:0 0 0 2px;', $rendered );
		$this->assertStringContainsString( 'border-style:dashed;', $rendered );
		$this->assertStringNotContainsString( 'border-width:0 1px 0 0;', $rendered );
	}

	/**
	 * Test it preserves explicit authored quote alignment in RTL.
	 */
	public function testItPreservesAuthoredQuoteAlignmentInRtl(): void {
		$theme_controller = $this->di_container->get( Theme_Controller::class );
		$rtl_context      = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
		$expected_borders = array(
			'left'   => 'border-width:0 0 0 1px;',
			'center' => 'border-width:0;',
			'right'  => 'border-width:0 1px 0 0;',
		);

		foreach ( array( 'left', 'center', 'right' ) as $alignment ) {
			$parsed_quote                       = $this->parsed_quote;
			$parsed_quote['attrs']['textAlign'] = $alignment;
			$content                            = '<blockquote class="wp-block-quote has-text-align-' . $alignment . '"></blockquote>';
			$rendered                           = $this->quote_renderer->render( $content, $parsed_quote, $rtl_context );

			$this->assertStringContainsString( 'text-align:' . $alignment . ';', $rendered );
			$this->assertStringContainsString( 'has-text-align-' . $alignment, $rendered );
			$this->assertStringContainsString( $expected_borders[ $alignment ], $rendered );
		}
	}

	/**
	 * Test it contains quote styles
	 */
	public function testItContainsQuoteStyles(): void {
		$parsed_quote = $this->parsed_quote;

		// Quote block with uniform border styles.
		$parsed_quote['attrs'] = array(
			'backgroundColor' => '#abcdef',
			'borderColor'     => '#012345',
			'style'           => array(
				'border' => array(
					'width'  => '1px',
					'style'  => 'solid',
					'radius' => '5px',
				),
			),
		);
		$rendered              = $this->quote_renderer->render( '', $parsed_quote, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'border-width:1px;', $rendered );
		$this->assertStringContainsString( 'border-style:solid;', $rendered );
		$this->assertStringContainsString( 'border-color:#012345;', $rendered );
		$this->assertStringContainsString( 'border-radius:5px;', $rendered );

		// Quote block with mixed border styles on each side.
		$parsed_quote['attrs'] = array(
			'backgroundColor' => '#abcdef',
			'style'           => array(
				'border'  => array(
					'bottom' => array(
						'color' => '#111111',
						'width' => '1px',
						'style' => 'dotted',
					),
					'left'   => array(
						'color' => '#222222',
						'width' => '2px',
					),
					'right'  => array(
						'color' => '#333333',
						'width' => '3px',
					),
					'top'    => array(
						'color' => '#444444',
						'width' => '4px',
					),
					'radius' => array(
						'bottomLeft'  => '5px',
						'bottomRight' => '10px',
						'topLeft'     => '15px',
						'topRight'    => '20px',
					),
				),
				'spacing' => array(
					'padding' => array(
						'bottom' => '5px',
						'left'   => '15px',
						'right'  => '20px',
						'top'    => '10px',
					),
				),
			),
		);
		$rendered              = $this->quote_renderer->render( '', $parsed_quote, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'background-color:#abcdef;', $rendered );
		$this->assertStringContainsString( 'border-bottom-left-radius:5px;', $rendered );
		$this->assertStringContainsString( 'border-bottom-right-radius:10px;', $rendered );
		$this->assertStringContainsString( 'border-top-left-radius:15px;', $rendered );
		$this->assertStringContainsString( 'border-top-right-radius:20px;', $rendered );
		$this->assertStringContainsString( 'border-top-color:#444444;', $rendered );
		$this->assertStringContainsString( 'border-top-width:4px;', $rendered );
		$this->assertStringContainsString( 'border-right-color:#333333;', $rendered );
		$this->assertStringContainsString( 'border-right-width:3px;', $rendered );
		$this->assertStringContainsString( 'border-bottom-color:#111111;', $rendered );
		$this->assertStringContainsString( 'border-bottom-width:1px;', $rendered );
		$this->assertStringContainsString( 'border-bottom-style:dotted;', $rendered );
		$this->assertStringContainsString( 'border-left-color:#222222;', $rendered );
		$this->assertStringContainsString( 'border-left-width:2px;', $rendered );
		$this->assertStringContainsString( 'padding-bottom:5px;', $rendered );
		$this->assertStringContainsString( 'padding-left:15px;', $rendered );
		$this->assertStringContainsString( 'padding-right:20px;', $rendered );
		$this->assertStringContainsString( 'padding-top:10px;', $rendered );
	}

	/**
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_quote = $this->parsed_quote;
		$content      = '<blockquote class="wp-block-quote editor-class-1 another-class"></blockquote>';
		$parsed_quote['attrs']['style']['color']['background'] = '#654321';
		$rendered = $this->quote_renderer->render( $content, $parsed_quote, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'wp-block-quote editor-class-1 another-class', $rendered );
	}
}
