<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Post_Template;

/**
 * Integration test for the Post_Template renderer.
 *
 * The renderer receives the already-rendered `<ul class="wp-block-post-template">…</ul>` (a Query
 * Loop's repeater output) and re-flows its list items into an email-safe table grid.
 */
class Post_Template_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Renderer under test.
	 *
	 * @var Post_Template
	 */
	private $renderer;

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
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->renderer          = new Post_Template();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Build a rendered post-template list from a set of item HTML fragments.
	 *
	 * @param array<int, string> $items       Inner HTML for each `<li>`.
	 * @param int                $columns     Column count (drives the `columns-N` class core stamps).
	 * @param bool               $is_grid     Whether to mark the list as a grid layout.
	 * @return string
	 */
	private function build_list( array $items, int $columns, bool $is_grid = true ): string {
		$layout_class = $is_grid ? 'is-layout-grid wp-block-post-template-is-layout-grid' : 'is-layout-flow wp-block-post-template-is-layout-flow';
		$lis          = '';
		foreach ( $items as $item ) {
			$lis .= '<li class="wp-block-post">' . $item . '</li>';
		}
		return sprintf(
			'<ul class="wp-block-post-template %s columns-%d">%s</ul>',
			$layout_class,
			$columns,
			$lis
		);
	}

	/**
	 * A featured-image `<li>` like the WordCamp sponsor grids use.
	 *
	 * @param string $src Image URL.
	 * @return string
	 */
	private function featured_image( string $src ): string {
		return '<figure class="wp-block-post-featured-image"><a href="https://example.com/sponsor"><img src="' . esc_url( $src ) . '" alt="Sponsor"/></a></figure>';
	}

	/**
	 * Count the grid cells produced.
	 *
	 * Matches the renderer's full cell style tail (not just `text-align: center;`), so passed-through
	 * item content that happens to contain a centered element can't inflate the count.
	 *
	 * @param string $rendered Rendered HTML.
	 * @return int
	 */
	private function count_cells( string $rendered ): int {
		return substr_count( $rendered, 'padding: 8px; vertical-align: top; text-align: center;' );
	}

	/**
	 * Count the per-row tables produced.
	 *
	 * Matches the renderer's full row-table style (the container table uses `border-collapse:
	 * collapse;` without `table-layout: fixed;`), so item content that contains a fixed-layout table
	 * can't inflate the count.
	 *
	 * @param string $rendered Rendered HTML.
	 * @return int
	 */
	private function count_rows( string $rendered ): int {
		return substr_count( $rendered, 'border-collapse: collapse; table-layout: fixed;' );
	}

	/**
	 * A 3-column grid re-flows its items into a table with three cells per row.
	 */
	public function testItRendersGridAsTableColumnsFromLayoutAttribute(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
			),
			'innerBlocks' => array(),
		);
		$content      = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/s1.png' ),
				$this->featured_image( 'https://example.com/s2.png' ),
				$this->featured_image( 'https://example.com/s3.png' ),
				$this->featured_image( 'https://example.com/s4.png' ),
			),
			3
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// Every sponsor image survives the transform.
		$this->assertStringContainsString( 's1.png', $rendered );
		$this->assertStringContainsString( 's4.png', $rendered );
		// It is now a table grid, not the original list.
		$this->assertStringContainsString( 'email-block-post-template', $rendered );
		$this->assertStringNotContainsString( '<ul', $rendered );
		// 4 items over 3 columns => 2 rows, and each row is padded to 3 cells (6 total).
		$this->assertSame( 2, $this->count_rows( $rendered ) );
		$this->assertSame( 6, $this->count_cells( $rendered ) );
	}

	/**
	 * When the layout attribute is missing, the `columns-N` class on the list drives the column count.
	 */
	public function testItFallsBackToColumnsClassWhenAttributeMissing(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(),
			'innerBlocks' => array(),
		);
		$content      = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/a.png' ),
				$this->featured_image( 'https://example.com/b.png' ),
				$this->featured_image( 'https://example.com/c.png' ),
				$this->featured_image( 'https://example.com/d.png' ),
			),
			2
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// 4 items over 2 columns => 2 rows of 2 cells (4 total).
		$this->assertSame( 2, $this->count_rows( $rendered ) );
		$this->assertSame( 4, $this->count_cells( $rendered ) );
	}

	/**
	 * A partial final row is padded with empty cells so items stay aligned to their column.
	 */
	public function testItPadsPartialRowToKeepColumnsAligned(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 4,
				),
			),
			'innerBlocks' => array(),
		);
		// 6 items over 4 columns => rows of 4 and 2; the second row is padded to 4 cells.
		$items = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$items[] = $this->featured_image( "https://example.com/img{$i}.png" );
		}
		$content = $this->build_list( $items, 4 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		$this->assertSame( 2, $this->count_rows( $rendered ) );
		$this->assertSame( 8, $this->count_cells( $rendered ) );
	}

	/**
	 * An empty list item (a sponsor tier slot with no featured image) is preserved as an empty cell
	 * rather than dropped, so the remaining items keep their column positions.
	 */
	public function testItKeepsEmptyItemAsCellToPreserveAlignment(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
			),
			'innerBlocks' => array(),
		);
		$content      = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/one.png' ),
				'', // A post with no featured image renders an empty <li>.
				$this->featured_image( 'https://example.com/three.png' ),
			),
			3
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// 3 items (one empty) over 3 columns => a single row of 3 cells; nothing is dropped.
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 3, $this->count_cells( $rendered ) );
		$this->assertStringContainsString( 'one.png', $rendered );
		$this->assertStringContainsString( 'three.png', $rendered );
	}

	/**
	 * A single-column (non-grid) layout already stacks correctly, so the original list is left as-is.
	 */
	public function testItLeavesSingleColumnLayoutUntouched(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array( 'layout' => array( 'type' => 'default' ) ),
			'innerBlocks' => array(),
		);
		$content      = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/x.png' ),
				$this->featured_image( 'https://example.com/y.png' ),
			),
			1,
			false
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// No grid table is built; the original list is preserved.
		$this->assertStringContainsString( 'wp-block-post-template', $rendered );
		$this->assertStringContainsString( '<ul', $rendered );
		$this->assertSame( 0, $this->count_rows( $rendered ) );
	}

	/**
	 * Content without the expected post-template list is returned untouched.
	 */
	public function testItReturnsUnrecognizedContentUntouched(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
			),
			'innerBlocks' => array(),
		);
		$content      = '<div class="something-else"><p>Not a post-template list.</p></div>';

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		$this->assertStringContainsString( 'Not a post-template list.', $rendered );
		$this->assertSame( 0, $this->count_rows( $rendered ) );
	}

	/**
	 * The post-template list is matched by class, not by being the first `<ul>`: a sibling list that
	 * appears earlier in the markup must not be mistaken for the repeater.
	 */
	public function testItFindsPostTemplateListEvenWhenAnotherListPrecedesIt(): void {
		$parsed_block  = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
			),
			'innerBlocks' => array(),
		);
		$post_template = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/real1.png' ),
				$this->featured_image( 'https://example.com/real2.png' ),
				$this->featured_image( 'https://example.com/real3.png' ),
			),
			3
		);
		// A decoy list (e.g. an unrelated block) rendered before the post-template list.
		$content = '<ul class="wp-block-list"><li>Decoy item</li></ul>' . $post_template;

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The real list is gridded (1 row of 3 cells); the decoy item is not pulled in as a cell.
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 3, $this->count_cells( $rendered ) );
		$this->assertStringContainsString( 'real1.png', $rendered );
		$this->assertStringNotContainsString( 'Decoy item', $rendered );
	}

	/**
	 * Item content that mimics the renderer's own cell/row style strings must not inflate the grid
	 * structure. This guards the test counters (and the grid) against passed-through markup such as a
	 * centered paragraph or a fixed-layout table inside a post.
	 */
	public function testItCountsGridStructureIgnoringContentThatMimicsCellStyles(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
			),
			'innerBlocks' => array(),
		);
		// Each item embeds a centered paragraph and a fixed-layout table — the exact substrings the
		// naive counters keyed on.
		$decoy_markup = '<p style="text-align: center;">Centered copy</p><table style="table-layout: fixed;"><tr><td>x</td></tr></table>';
		$content      = $this->build_list(
			array(
				$this->featured_image( 'https://example.com/p.png' ) . $decoy_markup,
				$this->featured_image( 'https://example.com/q.png' ) . $decoy_markup,
			),
			2
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// 2 items over 2 columns => exactly 1 row of 2 cells, regardless of the mimicking content.
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 2, $this->count_cells( $rendered ) );
	}
}
