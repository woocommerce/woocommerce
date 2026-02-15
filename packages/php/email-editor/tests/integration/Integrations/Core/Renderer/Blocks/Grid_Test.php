<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Grid layout rendering via the Group block renderer.
 */
class Grid_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Group renderer instance (delegates to Grid for grid layout).
	 *
	 * @var Group
	 */
	private $group_renderer;

	/**
	 * Instance of Rendering_Context class.
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Base parsed block for a group with grid layout.
	 *
	 * @var array
	 */
	private $parsed_grid_block = array(
		'blockName'   => 'core/group',
		'attrs'       => array(
			'layout' => array(
				'type'        => 'grid',
				'columnCount' => 3,
			),
		),
		'email_attrs' => array(
			'width' => '660px',
		),
		'innerBlocks' => array(),
		'innerHTML'   => '<div class="wp-block-group is-layout-grid"></div>',
	);

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->group_renderer    = new Group();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * @testdox Should render grid content with children in a table structure.
	 */
	public function test_renders_grid_with_children(): void {
		$block_content = $this->build_grid_block_content(
			array( '<table><tbody><tr><td>Child 1</td></tr></tbody></table>', '<table><tbody><tr><td>Child 2</td></tr></tbody></table>' )
		);

		$rendered = $this->group_renderer->render( $block_content, $this->parsed_grid_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Child 1', $rendered );
		$this->assertStringContainsString( 'Child 2', $rendered );
		$this->assertStringContainsString( 'email-block-grid', $rendered );
	}

	/**
	 * @testdox Should render correct number of cells per row based on columnCount.
	 */
	public function test_renders_correct_column_count(): void {
		$parsed_block                              = $this->parsed_grid_block;
		$parsed_block['attrs']['layout']['columnCount'] = 2;

		$block_content = $this->build_grid_block_content(
			array(
				'<table><tbody><tr><td>A</td></tr></tbody></table>',
				'<table><tbody><tr><td>B</td></tr></tbody></table>',
				'<table><tbody><tr><td>C</td></tr></tbody></table>',
			)
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'width: 50.00%', $rendered, 'Two-column grid should have 50% width cells' );
	}

	/**
	 * @testdox Should calculate column count from minimumColumnWidth.
	 */
	public function test_calculates_columns_from_minimum_width(): void {
		$parsed_block          = $this->parsed_grid_block;
		$parsed_block['attrs'] = array(
			'layout' => array(
				'type'               => 'grid',
				'minimumColumnWidth' => '200px',
			),
		);

		$block_content = $this->build_grid_block_content(
			array(
				'<table><tbody><tr><td>A</td></tr></tbody></table>',
				'<table><tbody><tr><td>B</td></tr></tbody></table>',
				'<table><tbody><tr><td>C</td></tr></tbody></table>',
			)
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'A', $rendered );
		$this->assertStringContainsString( 'B', $rendered );
		$this->assertStringContainsString( 'C', $rendered );
	}

	/**
	 * @testdox Should apply block styles to the grid wrapper.
	 */
	public function test_applies_block_styles(): void {
		$parsed_block          = $this->parsed_grid_block;
		$parsed_block['attrs'] = array_merge(
			$parsed_block['attrs'],
			array(
				'style' => array(
					'border' => array(
						'color' => '#123456',
						'width' => '2px',
						'style' => 'solid',
					),
					'color'  => array(
						'background' => '#abcdef',
					),
				),
			)
		);

		$block_content = $this->build_grid_block_content(
			array( '<table><tbody><tr><td>Content</td></tr></tbody></table>' )
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'background-color:#abcdef', $rendered );
		$this->assertStringContainsString( 'border-color:#123456', $rendered );
		$this->assertStringContainsString( 'border-width:2px', $rendered );
		$this->assertStringContainsString( 'border-style:solid', $rendered );
	}

	/**
	 * @testdox Should apply padding styles to the grid wrapper cell.
	 */
	public function test_applies_padding_styles(): void {
		$parsed_block          = $this->parsed_grid_block;
		$parsed_block['attrs'] = array_merge(
			$parsed_block['attrs'],
			array(
				'style' => array(
					'spacing' => array(
						'padding' => array(
							'top'    => '10px',
							'right'  => '20px',
							'bottom' => '15px',
							'left'   => '25px',
						),
					),
				),
			)
		);

		$block_content = $this->build_grid_block_content(
			array( '<table><tbody><tr><td>Content</td></tr></tbody></table>' )
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'padding-top:10px', $rendered );
		$this->assertStringContainsString( 'padding-right:20px', $rendered );
		$this->assertStringContainsString( 'padding-bottom:15px', $rendered );
		$this->assertStringContainsString( 'padding-left:25px', $rendered );
	}

	/**
	 * @testdox Should preserve classes set by the editor.
	 */
	public function test_preserves_editor_classes(): void {
		$parsed_block = $this->parsed_grid_block;
		$block_content = $this->build_grid_block_content(
			array( '<table><tbody><tr><td>Content</td></tr></tbody></table>' ),
			'wp-block-group is-layout-grid custom-class-1 another-class'
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'is-layout-grid custom-class-1 another-class', $rendered );
	}

	/**
	 * @testdox Should return empty when grid has no children.
	 */
	public function test_returns_empty_for_no_children(): void {
		$block_content = '<div class="wp-block-group is-layout-grid"></div>';

		$rendered = $this->group_renderer->render( $block_content, $this->parsed_grid_block, $this->rendering_context );

		$this->assertStringNotContainsString( 'email-block-grid', $rendered );
	}

	/**
	 * @testdox Should render single child at full width.
	 */
	public function test_renders_single_child_full_width(): void {
		$block_content = $this->build_grid_block_content(
			array( '<table><tbody><tr><td>Only child</td></tr></tbody></table>' )
		);

		$rendered = $this->group_renderer->render( $block_content, $this->parsed_grid_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'width: 100.00%', $rendered );
		$this->assertStringContainsString( 'Only child', $rendered );
	}

	/**
	 * @testdox Should cap columns at maximum of 4.
	 */
	public function test_caps_columns_at_maximum(): void {
		$parsed_block                                   = $this->parsed_grid_block;
		$parsed_block['attrs']['layout']['columnCount'] = 10;

		$children = array();
		for ( $i = 1; $i <= 8; $i++ ) {
			$children[] = "<table><tbody><tr><td>Item $i</td></tr></tbody></table>";
		}
		$block_content = $this->build_grid_block_content( $children );

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'width: 25.00%', $rendered, 'Should cap at 4 columns (25% each)' );
	}

	/**
	 * @testdox Should still render as group when layout type is not grid.
	 */
	public function test_renders_as_group_for_non_grid_layout(): void {
		$parsed_block = $this->parsed_grid_block;
		unset( $parsed_block['attrs']['layout'] );
		$block_content = '<div class="wp-block-group"><p>Group content</p></div>';

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'email-block-group', $rendered );
		$this->assertStringNotContainsString( 'email-block-grid', $rendered );
	}

	/**
	 * @testdox Should apply gap spacing between grid cells.
	 */
	public function test_applies_gap_spacing(): void {
		$parsed_block          = $this->parsed_grid_block;
		$parsed_block['attrs'] = array_merge(
			$parsed_block['attrs'],
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => '20px',
					),
				),
			)
		);
		$parsed_block['attrs']['layout']['columnCount'] = 2;

		$block_content = $this->build_grid_block_content(
			array(
				'<table><tbody><tr><td>A</td></tr></tbody></table>',
				'<table><tbody><tr><td>B</td></tr></tbody></table>',
			)
		);

		$rendered = $this->group_renderer->render( $block_content, $parsed_block, $this->rendering_context );

		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'padding-right: 10px', $rendered, 'Gap of 20px should produce 10px padding on each side' );
	}

	/**
	 * Builds a simulated rendered grid block content string.
	 *
	 * Each child is wrapped in a div.email-block-layout to simulate
	 * the output of Abstract_Block_Renderer::add_spacer().
	 *
	 * @param array  $children Array of child block HTML strings.
	 * @param string $wrapper_class CSS class for the wrapper div.
	 * @return string Simulated rendered block content.
	 */
	private function build_grid_block_content( array $children, string $wrapper_class = 'wp-block-group is-layout-grid' ): string {
		$inner = '';
		foreach ( $children as $child ) {
			$inner .= '<div class="email-block-layout" style="margin-top:16px;">' . $child . '</div>';
		}
		return '<div class="' . esc_attr( $wrapper_class ) . '">' . $inner . '</div>';
	}
}
