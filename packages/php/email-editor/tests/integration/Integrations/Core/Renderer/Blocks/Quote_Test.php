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
