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
 * Integration test for Button class
 */
class Button_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Instance of Button class
	 *
	 * @var Button
	 */
	private $button_renderer;

	/**
	 * Configuration for parsed button block
	 *
	 * @var array
	 */
	private $parsed_button = array(
		'blockName'    => 'core/button',
		'attrs'        => array(
			'width' => 50,
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'left'   => '10px',
						'right'  => '10px',
						'top'    => '10px',
						'bottom' => '10px',
					),
				),
				'color'   => array(
					'background' => '#dddddd',
					'text'       => '#111111',
				),
			),
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>',
		'innerContent' => array( '<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>' ),
		'email_attrs'  => array(
			'color' => '#111111',
			'width' => '320px',
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
		$this->button_renderer     = new Button();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test in renders link
	 */
	public function testItRendersLink(): void {
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'href="http://example.com"', $output );
		$this->assertStringContainsString( 'Button Text', $output );
	}

	/**
	 * Test it renders padding based on attributes value
	 */
	public function testItRendersPaddingBasedOnAttributesValue(): void {
		$this->parsed_button['attrs']['style']['spacing']['padding'] = array(
			'left'   => '10px',
			'right'  => '20px',
			'top'    => '30px',
			'bottom' => '40px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'padding-left:10px;', $output );
		$this->assertStringContainsString( 'padding-right:20px;', $output, $output );
		$this->assertStringContainsString( 'padding-top:30px;', $output );
		$this->assertStringContainsString( 'padding-bottom:40px;', $output );
	}

	/**
	 * Test it renders colors
	 */
	public function testItRendersColors(): void {
		$this->parsed_button['attrs']['style']['color'] = array(
			'background' => '#000000',
			'text'       => '#111111',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'background-color:#000000;', $output );
		$this->assertStringContainsString( 'color:#111111;', $output );
	}

	/**
	 * Test it renders border
	 */
	public function testItRendersBorder(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'width' => '10px',
			'color' => '#111111',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'border-color:#111111;', $output );
		$this->assertStringContainsString( 'border-width:10px;', $output );
		$this->assertStringContainsString( 'border-style:solid;', $output );
	}

	/**
	 * Test it renders each side specific border
	 */
	public function testItRendersEachSideSpecificBorder(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'top'    => array(
				'width' => '1px',
				'color' => '#111111',
			),
			'right'  => array(
				'width' => '2px',
				'color' => '#222222',
			),
			'bottom' => array(
				'width' => '3px',
				'color' => '#333333',
			),
			'left'   => array(
				'width' => '4px',
				'color' => '#444444',
			),
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'border-top-width:1px;', $output );
		$this->assertStringContainsString( 'border-top-color:#111111;', $output );

		$this->assertStringContainsString( 'border-right-width:2px;', $output );
		$this->assertStringContainsString( 'border-right-color:#222222;', $output );

		$this->assertStringContainsString( 'border-bottom-width:3px;', $output );
		$this->assertStringContainsString( 'border-bottom-color:#333333;', $output );

		$this->assertStringContainsString( 'border-left-width:4px;', $output );
		$this->assertStringContainsString( 'border-left-color:#444444;', $output );

		$this->assertStringContainsString( 'border-style:solid;', $output );
	}

	/**
	 * Test it renders border radius
	 */
	public function testItRendersBorderRadius(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'radius' => '10px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'border-radius:10px;', $output );
	}

	/**
	 * Test it renders font size
	 */
	public function testItRendersFontSize(): void {
		$this->parsed_button['attrs']['style']['typography']['fontSize'] = '10px';
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'font-size:10px;', $output );
	}

	/**
	 * Test in renders corner specific border radius
	 */
	public function testItRendersCornerSpecificBorderRadius(): void {
		$this->parsed_button['attrs']['style']['border']['radius'] = array(
			'topLeft'     => '1px',
			'topRight'    => '2px',
			'bottomLeft'  => '3px',
			'bottomRight' => '4px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		$this->assertStringContainsString( 'border-top-left-radius:1px;', $output );
		$this->assertStringContainsString( 'border-top-right-radius:2px;', $output );
		$this->assertStringContainsString( 'border-bottom-left-radius:3px;', $output );
		$this->assertStringContainsString( 'border-bottom-right-radius:4px;', $output );
	}

	/**
	 * Test it renders background color set by slug
	 */
	public function testItRendersBackgroundColorSetBySlug(): void {
		unset( $this->parsed_button['attrs']['style']['color'] );
		unset( $this->parsed_button['attrs']['style']['spacing']['padding'] );
		$this->parsed_button['attrs']['backgroundColor'] = 'black';
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		// For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
		// because of special email HTML markup.
		$this->assertStringContainsString( 'background-color:#000000;', $output );
	}

	/**
	 * Test if it renders font color set by slug
	 */
	public function testItRendersFontColorSetBySlug(): void {
		unset( $this->parsed_button['attrs']['style']['color'] );
		unset( $this->parsed_button['attrs']['style']['spacing']['padding'] );
		$this->parsed_button['attrs']['textColor'] = 'white';
		$output                                    = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		// For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
		// because of special email HTML markup.
		$this->assertStringContainsString( 'color:#fff', $output );
	}
}
