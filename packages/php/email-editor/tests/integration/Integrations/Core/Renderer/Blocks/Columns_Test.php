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
 * Integration test for Columns class
 */
class Columns_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Columns renderer instance
	 *
	 * @var Columns
	 */
	private $columns_renderer;

	/**
	 * Columns block configuration
	 *
	 * @var array
	 */
	private $parsed_columns = array(
		'blockName'   => 'core/columns',
		'attrs'       => array(),
		'email_attrs' => array(
			'width' => '784px',
		),
		'innerHTML'   => '<div class="wp-block-columns"></div>',
		'innerBlocks' => array(
			0 => array(
				'blockName'    => 'core/column',
				'attrs'        => array(),
				'innerBlocks'  => array(
					0 => array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array(),
						'innerBlocks'  => array(),
						'innerHTML'    => '<p>Column 1</p>',
						'innerContent' => array(
							0 => '<p>Column 1</p>',
						),
					),
				),
				'innerHTML'    => '<div class="wp-block-column"></div>',
				'innerContent' => array(
					0 => '<div class="wp-block-column">',
					1 => null,
					2 => '</div>',
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
		$this->columns_renderer  = new Columns();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders inner column
	 */
	public function testItRendersInnerColumn(): void {
		$rendered = $this->columns_renderer->render( '<p>Column 1</p>', $this->parsed_columns, $this->rendering_context );
		$this->assertStringContainsString( 'Column 1', $rendered );
	}

	/**
	 * Test it contains columns styles
	 */
	public function testItContainsColumnsStyles(): void {
		$parsed_columns          = $this->parsed_columns;
		$parsed_columns['attrs'] = array(
			'style' => array(
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
			),
		);
		$rendered                = $this->columns_renderer->render( '', $parsed_columns, $this->rendering_context );
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
	 * Test it sets custom color and background
	 */
	public function testItSetsCustomColorAndBackground(): void {
		$parsed_columns                                    = $this->parsed_columns;
		$parsed_columns['attrs']['style']['color']['text'] = '#123456';
		$parsed_columns['attrs']['style']['color']['background'] = '#654321';
		$rendered = $this->columns_renderer->render( '', $parsed_columns, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'color:#123456;', $rendered );
		$this->assertStringContainsString( 'background-color:#654321;', $rendered );
	}

	/**
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_columns = $this->parsed_columns;
		$content        = '<div class="wp-block-columns editor-class-1 another-class"></div>';
		$parsed_columns['attrs']['style']['color']['background'] = '#654321';
		$rendered = $this->columns_renderer->render( $content, $parsed_columns, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'wp-block-columns editor-class-1 another-class', $rendered );
	}
}
