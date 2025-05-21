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
		$this->heading_renderer    = new Text();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test it renders content
	 */
	public function testItRendersContent(): void {
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->settings_controller );
		$this->assertStringContainsString( 'This is Heading 1', $rendered );
		$this->assertStringContainsString( 'width:100%;', $rendered );
		$this->assertStringContainsString( 'font-size:24px;', $rendered );
		$this->assertStringNotContainsString( 'width:640px;', $rendered );
	}

	/**
	 * Test it renders block attributes
	 */
	public function testItRendersBlockAttributes(): void {
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->settings_controller );
		$this->assertStringContainsString( 'text-transform:lowercase;', $rendered );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
	}

	/**
	 * Test it renders custom set colors
	 */
	public function testItRendersCustomSetColors(): void {
		$this->parsed_heading['attrs']['style']['color']['background'] = '#000000';
		$this->parsed_heading['attrs']['style']['color']['text']       = '#ff0000';
		$rendered = $this->heading_renderer->render( '<h1>This is Heading 1</h1>', $this->parsed_heading, $this->settings_controller );
		$this->assertStringContainsString( 'background-color:#000000', $rendered );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
	}

	/**
	 * Test it replaces fluid font size in content
	 */
	public function testItReplacesFluidFontSizeInContent(): void {
		$rendered = $this->heading_renderer->render( '<h1 style="font-size:clamp(10px, 20px, 24px)">This is Heading 1</h1>', $this->parsed_heading, $this->settings_controller );
		$this->assertStringContainsString( 'font-size:24px', $rendered );
	}
}
