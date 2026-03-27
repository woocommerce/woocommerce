<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Dummy_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

require_once __DIR__ . '/../../../../Engine/Renderer/ContentRenderer/Dummy_Block_Renderer.php';

/**
 * Integration test for Buttons class
 */
class Buttons_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Buttons renderer instance.
	 *
	 * @var Buttons
	 */
	private $buttons_renderer;

	/**
	 * Rendering context instance.
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
		$this->buttons_renderer  = new Buttons( new Flex_Layout_Renderer() );
		register_block_type( 'dummy/block', array() );
		add_filter( 'render_block', array( $this, 'renderDummyBlock' ), 10, 2 );
	}

	/**
	 * Test it does not double margin-top between flex renderer and add_spacer().
	 */
	public function testItDoesNotDoubleMarginTop(): void {
		$parsed_block = array(
			'blockName'   => 'core/buttons',
			'attrs'       => array(),
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Click me',
				),
			),
			'email_attrs' => array(
				'margin-top' => '20px',
			),
		);
		$rendered     = $this->buttons_renderer->render( '', $parsed_block, $this->rendering_context );
		// The inner flex div has margin-top (for Gmail).
		$this->assertStringContainsString( 'margin-top: 20px', $rendered );
		// The outer email-block-layout wrapper should not have margin-top.
		$this->assertStringNotContainsString( 'email-block-layout" style="margin-top', $rendered );
	}

	/**
	 * Render a dummy block.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block data.
	 * @return string
	 */
	public function renderDummyBlock( $block_content, $parsed_block ): string {
		$dummy_renderer = new Dummy_Block_Renderer();
		return $dummy_renderer->render( $block_content, $parsed_block, $this->rendering_context );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		unregister_block_type( 'dummy/block' );
		remove_filter( 'render_block', array( $this, 'renderDummyBlock' ), 10 );
	}
}
