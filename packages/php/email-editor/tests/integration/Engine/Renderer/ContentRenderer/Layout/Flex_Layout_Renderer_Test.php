<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Dummy_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

require_once __DIR__ . '/../Dummy_Block_Renderer.php';

/**
 * Integration test for Flex_Layout_Renderer
 */
class Flex_Layout_Renderer_Test extends \Email_Editor_Integration_Test_Case {

	/**
	 * Instance of the renderer.
	 *
	 * @var Flex_Layout_Renderer
	 */
	private $renderer;

	/**
	 * Instance of the rendering context.
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
		$this->renderer          = new Flex_Layout_Renderer();
		register_block_type( 'dummy/block', array() );
		add_filter( 'render_block', array( $this, 'renderDummyBlock' ), 10, 2 );
	}

	/**
	 * Test it renders inner blocks.
	 */
	public function testItRendersInnerBlocks(): void {
		$parsed_block = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
				),
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 2',
				),
			),
			'email_attrs' => array(),
		);
		$output       = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringContainsString( 'Dummy 1', $output );
		$this->assertStringContainsString( 'Dummy 2', $output );
	}

	/**
	 * Test it handles justifying the content.
	 */
	public function testItHandlesJustification(): void {
		$parsed_block = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
				),
			),
			'email_attrs' => array(),
		);
		// Default justification is left.
		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringContainsString( 'text-align: left', $output );
		$this->assertStringContainsString( 'align="left"', $output );
		// Right justification.
		$parsed_block['attrs']['layout']['justifyContent'] = 'right';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringContainsString( 'text-align: right', $output );
		$this->assertStringContainsString( 'align="right"', $output );
		// Center justification.
		$parsed_block['attrs']['layout']['justifyContent'] = 'center';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringContainsString( 'text-align: center', $output );
		$this->assertStringContainsString( 'align="center"', $output );
	}

	/**
	 * Test it uses RTL defaults when no justification is authored.
	 */
	public function testItUsesRtlDefaultJustificationAndGapSide(): void {
		$theme_controller = $this->di_container->get( Theme_Controller::class );
		$rtl_context      = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
		$parsed_block     = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
				),
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 2',
				),
			),
			'email_attrs' => array(),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $rtl_context );

		$this->assertStringContainsString( 'text-align: right', $output );
		$this->assertStringContainsString( 'align="right"', $output );
		$this->assertStringContainsString( 'padding-right', $output );
		$this->assertStringNotContainsString( 'padding-left', $output );
	}

	/**
	 * Test it applies margin-top from email_attrs on the inner div for Gmail compatibility.
	 */
	public function testItAppliesMarginTopFromEmailAttrs(): void {
		$parsed_block = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
				),
			),
			'email_attrs' => array(
				'margin-top' => '16px',
			),
		);
		$output       = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringContainsString( 'margin-top: 16px', $output );
	}

	/**
	 * Test it escapes attributes.
	 */
	public function testItEscapesAttributes(): void {
		$parsed_block                                      = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
				),
			),
			'email_attrs' => array(),
		);
		$parsed_block['attrs']['layout']['justifyContent'] = '"> <script>alert("XSS")</script><div style="text-align: right';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$this->assertStringNotContainsString( '<script>alert("XSS")</script>', $output );
	}

	/**
	 * Test that the renderer computes proper widths for reasonable settings.
	 */
	public function testInComputesProperWidthsForReasonableSettings(): void {
		$parsed_block = array(
			'innerBlocks' => array(),
			'email_attrs' => array(
				'width' => '640px',
			),
		);

		// 50% and 25%
		$parsed_block['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '50' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
		);
		$output                      = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$flex_items                  = $this->getFlexItemsFromOutput( $output );
		$this->assertStringContainsString( 'width:312px;', $flex_items[0] );
		$this->assertStringContainsString( 'width:148px;', $flex_items[1] );

		// 25% and 25% and auto
		$parsed_block['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '25' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 3',
				'attrs'     => array(),
			),
		);
		$output                      = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$flex_items                  = $this->getFlexItemsFromOutput( $output );
		$this->assertStringContainsString( 'width:148px;', $flex_items[0] );
		$this->assertStringContainsString( 'width:148px;', $flex_items[1] );
		$this->assertStringNotContainsString( 'width:', $flex_items[2] );

		// 50% and 50%
		$parsed_block['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '50' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 2',
				'attrs'     => array( 'width' => '50' ),
			),
		);
		$output                      = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$flex_items                  = $this->getFlexItemsFromOutput( $output );
		$this->assertStringContainsString( 'width:312px;', $flex_items[0] );
		$this->assertStringContainsString( 'width:312px;', $flex_items[1] );
	}

	/**
	 * Test that the renderer computes proper widths for strange settings values.
	 */
	public function testInComputesWidthsForStrangeSettingsValues(): void {
		$parsed_block = array(
			'innerBlocks' => array(),
			'email_attrs' => array(
				'width' => '640px',
			),
		);

		// 100% and 25%
		$parsed_block['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '100' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
		);
		$output                      = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$flex_items                  = $this->getFlexItemsFromOutput( $output );
		$this->assertStringContainsString( 'width:508px;', $flex_items[0] );
		$this->assertStringContainsString( 'width:105px;', $flex_items[1] );

		// 100% and 100%
		$parsed_block['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '100' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 2',
				'attrs'     => array( 'width' => '100' ),
			),
		);
		$output                      = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );
		$flex_items                  = $this->getFlexItemsFromOutput( $output );
		$this->assertStringContainsString( 'width:312px;', $flex_items[0] );
		$this->assertStringContainsString( 'width:312px;', $flex_items[1] );
	}

	/**
	 * A row of auto-width buttons whose combined width exceeds the parent (e.g. a footer nav menu)
	 * is rendered as wrapping inline-block items instead of a single non-wrapping row, so it can't
	 * stretch the email past its content width in Gmail/Outlook (NL-737).
	 */
	public function testItWrapsAutoWidthButtonsThatOverflow(): void {
		// Five auto-width items at a 25% estimate each = 125% of the parent, so they overflow.
		$inner_blocks = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$inner_blocks[] = array(
				'blockName' => 'dummy/block',
				'innerHTML' => "Dummy $i",
				'attrs'     => array(),
			);
		}
		$parsed_block = array(
			'innerBlocks' => $inner_blocks,
			'email_attrs' => array( 'width' => '640px' ),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

		// Items are inline-block divs (which wrap in clients that support it) rather than table cells.
		$this->assertStringContainsString( '<div class="layout-flex-item"', $output );
		$this->assertStringContainsString( 'display:inline-block', $output );
		$this->assertStringNotContainsString( '<td class="layout-flex-item"', $output );
		// Outlook can't wrap a row, so a conditional <br> forces it to stack the buttons vertically.
		$this->assertStringContainsString( '<!--[if mso | IE]><br><![endif]-->', $output );
		// All buttons are still rendered.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->assertStringContainsString( "Dummy $i", $output );
		}
		// For start-aligned (and centered) rows the gap pads the end side of all items but the last,
		// so every wrapped line starts flush at the aligned edge.
		$item_styles = $this->getWrapItemStylesFromOutput( $output );
		$this->assertCount( 5, $item_styles );
		foreach ( array_slice( $item_styles, 0, 4 ) as $style ) {
			$this->assertStringContainsString( 'padding-right', $style );
		}
		$this->assertStringNotContainsString( 'padding-right', $item_styles[4] );
		$this->assertStringNotContainsString( 'padding-left', $output );
	}

	/**
	 * When a wrapped row is end-aligned (right in LTR), the gap must pad the start side of every
	 * item after the first instead — end-side padding would push each wrapped line away from the
	 * flush right edge and misalign the lines against each other.
	 */
	public function testItPadsTheStartSideWhenWrappedButtonsAlignToTheEndSide(): void {
		$inner_blocks = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$inner_blocks[] = array(
				'blockName' => 'dummy/block',
				'innerHTML' => "Dummy $i",
				'attrs'     => array(),
			);
		}
		$parsed_block = array(
			'innerBlocks' => $inner_blocks,
			'attrs'       => array( 'layout' => array( 'justifyContent' => 'right' ) ),
			'email_attrs' => array( 'width' => '640px' ),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

		$this->assertStringContainsString( '<div class="layout-flex-item"', $output );
		$item_styles = $this->getWrapItemStylesFromOutput( $output );
		$this->assertCount( 5, $item_styles );
		$this->assertStringNotContainsString( 'padding-left', $item_styles[0] );
		foreach ( array_slice( $item_styles, 1 ) as $style ) {
			$this->assertStringContainsString( 'padding-left', $style );
		}
		$this->assertStringNotContainsString( 'padding-right', $output );
	}

	/**
	 * A row that mixes an explicit-width button with auto-width buttons and overflows also wraps:
	 * the auto-width items can't be shrunk to fit, so setting a width on a single button in the row
	 * must not defeat the wrapping (NL-737). The explicit-width item keeps its width in the wrap.
	 */
	public function testItWrapsAMixedRowWithAnAutoWidthItemThatOverflows(): void {
		// One 50% item (312px) + three auto items (25% estimate each) = ~125% of the parent, so the
		// row overflows even though one button has an explicit width.
		$inner_blocks = array(
			array(
				'blockName' => 'dummy/block',
				'innerHTML' => 'Dummy 1',
				'attrs'     => array( 'width' => '50' ),
			),
		);
		for ( $i = 2; $i <= 4; $i++ ) {
			$inner_blocks[] = array(
				'blockName' => 'dummy/block',
				'innerHTML' => "Dummy $i",
				'attrs'     => array(),
			);
		}
		$parsed_block = array(
			'innerBlocks' => $inner_blocks,
			'email_attrs' => array( 'width' => '640px' ),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

		// The row wraps (inline-block divs) rather than staying a single non-wrapping table row.
		$this->assertStringContainsString( '<div class="layout-flex-item"', $output );
		$this->assertStringContainsString( 'display:inline-block', $output );
		$this->assertStringNotContainsString( '<td class="layout-flex-item"', $output );
		// The explicit-width button keeps its computed width in the wrapped layout.
		$this->assertStringContainsString( 'width:312px', $output );
		// Outlook still stacks the buttons vertically via the conditional <br>.
		$this->assertStringContainsString( '<!--[if mso | IE]><br><![endif]-->', $output );
		// All buttons are still rendered.
		for ( $i = 1; $i <= 4; $i++ ) {
			$this->assertStringContainsString( "Dummy $i", $output );
		}
	}

	/**
	 * Auto-width buttons that fit within the parent width keep the default single-row layout — the
	 * wrap path must not kick in for a small button group (e.g. Comment/Like).
	 */
	public function testItKeepsAutoWidthButtonsOnOneRowWhenTheyFit(): void {
		// Two auto-width items at a 25% estimate each = 50% of the parent, so they fit.
		$parsed_block = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 1',
					'attrs'     => array(),
				),
				array(
					'blockName' => 'dummy/block',
					'innerHTML' => 'Dummy 2',
					'attrs'     => array(),
				),
			),
			'email_attrs' => array( 'width' => '640px' ),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

		$this->assertStringContainsString( '<td class="layout-flex-item"', $output );
		$this->assertStringNotContainsString( '<div class="layout-flex-item"', $output );
		$this->assertStringNotContainsString( '<!--[if mso | IE]><br><![endif]-->', $output );
	}

	/**
	 * Without a parent width we can't tell whether the items overflow, so we conservatively keep the
	 * single-row layout even for many auto-width items rather than wrapping unnecessarily.
	 */
	public function testItDoesNotWrapWhenParentWidthIsUnknown(): void {
		$inner_blocks = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$inner_blocks[] = array(
				'blockName' => 'dummy/block',
				'innerHTML' => "Dummy $i",
				'attrs'     => array(),
			);
		}
		$parsed_block = array(
			'innerBlocks' => $inner_blocks,
			'email_attrs' => array(),
		);

		$output = $this->renderer->render_inner_blocks_in_layout( $parsed_block, $this->rendering_context );

		$this->assertStringContainsString( '<td class="layout-flex-item"', $output );
		$this->assertStringNotContainsString( '<div class="layout-flex-item"', $output );
	}

	/**
	 * Get the style attribute of each item in a wrapping layout output.
	 *
	 * @param string $output Output.
	 * @return string[]
	 */
	private function getWrapItemStylesFromOutput( string $output ): array {
		$matches = array();
		preg_match_all( '/<div class="layout-flex-item" style="([^"]*)"/', $output, $matches );
		return $matches[1];
	}

	/**
	 * Get flex items from the output.
	 *
	 * @param string $output Output.
	 */
	private function getFlexItemsFromOutput( string $output ): array {
		$matches = array();
		preg_match_all( '/<td class="layout-flex-item" style="(.*)">/', $output, $matches );
		$match = $matches[0][0];
		$this->assertIsString( $match );
		return explode( '><', $match );
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
