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
 * Integration test for Column class
 */
class Column_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Column renderer instance
	 *
	 * @var Column
	 */
	private $column_renderer;
	/**
	 * Parsed column block
	 *
	 * @var array
	 */
	private $parsed_column = array(
		'blockName'    => 'core/column',
		'email_attrs'  => array(
			'width' => '300px',
		),
		'attrs'        => array(),
		'innerBlocks'  => array(
			0 => array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Column content</p>',
				'innerContent' => array(
					0 => '<p>Column content</p>',
				),
			),
		),
		'innerHTML'    => '<div class="wp-block-column"></div>',
		'innerContent' => array(
			0 => '<div class="wp-block-column">',
			1 => null,
			2 => '</div>',
		),
	);
	/**
	 * Instance of Settings_Controller class
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
		$this->column_renderer     = new Column();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test it renders column content
	 */
	public function testItRendersColumnContent() {
		$rendered = $this->column_renderer->render( '', $this->parsed_column, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Column content', $rendered );
	}

	/**
	 * Test it contains columns styles
	 */
	public function testItContainsColumnsStyles(): void {
		$parsed_column          = $this->parsed_column;
		$parsed_column['attrs'] = array(
			'style' => array(
				'border'  => array(
					'bottom' => array(
						'color' => '#111111',
						'width' => '1px',
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
		$rendered               = $this->column_renderer->render( '', $parsed_column, $this->settings_controller );
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
		$this->assertStringContainsString( 'border-left-color:#222222;', $rendered );
		$this->assertStringContainsString( 'border-left-width:2px;', $rendered );
		$this->assertStringContainsString( 'border-style:solid;', $rendered );
		$this->assertStringContainsString( 'padding-bottom:5px;', $rendered );
		$this->assertStringContainsString( 'padding-left:15px;', $rendered );
		$this->assertStringContainsString( 'padding-right:20px;', $rendered );
		$this->assertStringContainsString( 'padding-top:10px;', $rendered );
		$this->assertStringContainsString( 'vertical-align:top;', $rendered ); // Check for the default value of vertical alignment.
	}

	/**
	 * Test it contains expected vertical alignment
	 */
	public function testItContainsExpectedVerticalAlignment(): void {
		$parsed_column                               = $this->parsed_column;
		$parsed_column['attrs']['verticalAlignment'] = 'bottom';
		$rendered                                    = $this->column_renderer->render( '', $parsed_column, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'vertical-align:bottom;', $rendered );
	}

	/**
	 * Test it sets custom color and background
	 */
	public function testItSetsCustomColorAndBackground(): void {
		$parsed_column                                    = $this->parsed_column;
		$parsed_column['attrs']['style']['color']['text'] = '#123456';
		$parsed_column['attrs']['style']['color']['background'] = '#654321';
		$rendered = $this->column_renderer->render( '', $parsed_column, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'color:#123456;', $rendered );
		$this->assertStringContainsString( 'background-color:#654321;', $rendered );
	}

	/**
	 * Test it preserves classes set by editor
	 */
	public function testItPreservesClassesSetByEditor(): void {
		$parsed_column = $this->parsed_column;
		$content       = '<div class="wp-block-column editor-class-1 another-class"></div>';
		$parsed_column['attrs']['style']['color']['background'] = '#654321';
		$rendered = $this->column_renderer->render( $content, $parsed_column, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'wp-block-column editor-class-1 another-class', $rendered );
	}
}
