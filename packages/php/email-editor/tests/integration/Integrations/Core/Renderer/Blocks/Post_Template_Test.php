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
	 * A featured-image `<li>` whose `<img>` carries the intrinsic width/height WordPress stores,
	 * wrapped in the fixed-width tables the block editor renders around a post-template image. This is
	 * the shape whose nested `width: 100%` collapses in email, so the renderer must hoist and rebuild
	 * the image.
	 *
	 * @param string $src    Image URL.
	 * @param int    $width  Intrinsic image width.
	 * @param int    $height Intrinsic image height.
	 * @return string
	 */
	private function nested_featured_image( string $src, int $width, int $height ): string {
		return '<div><table width="100%" style="border-collapse:separate"><tbody><tr>'
			. '<td width="520px" style="padding:30px">'
			. '<figure class="wp-block-post-featured-image size-full">'
			. '<a href="https://example.com/sponsor">'
			. '<img src="' . esc_url( $src ) . '" alt="Sponsor" width="' . $width . '" height="' . $height . '" class="wp-image-1" style="width:100%;max-width:100%;object-fit:contain"/>'
			. '</a></figure></td></tr></tbody></table></div>';
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
	 * Column counts above the Gallery block's 8 are honored: the grid layout control allows up to 16,
	 * so the renderer must not silently reduce a 12-column author choice.
	 */
	public function testItHonorsColumnCountsAboveEight(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 12,
				),
			),
			'innerBlocks' => array(),
		);
		$items        = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$items[] = $this->featured_image( "https://example.com/img{$i}.png" );
		}
		$content = $this->build_list( $items, 12 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// 12 items over 12 columns => a single row of 12 cells (not clamped down to 8).
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 12, $this->count_cells( $rendered ) );
	}

	/**
	 * A column count beyond the editor's maximum (e.g. a hand-edited value) is clamped to 16 so it
	 * can't emit a runaway number of cells.
	 */
	public function testItClampsColumnCountToMaximum(): void {
		$parsed_block = array(
			'blockName'   => 'core/post-template',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 99,
				),
			),
			'innerBlocks' => array(),
		);
		$items        = array();
		for ( $i = 1; $i <= 18; $i++ ) {
			$items[] = $this->featured_image( "https://example.com/img{$i}.png" );
		}
		$content = $this->build_list( $items, 99 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// Clamped to 16 columns: 18 items => a full row of 16 + a partial row padded to 16 (32 cells).
		$this->assertSame( 2, $this->count_rows( $rendered ) );
		$this->assertSame( 32, $this->count_cells( $rendered ) );
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

	/**
	 * Each item's image is hoisted out of its fixed-width wrapper tables and rebuilt as a clean,
	 * responsive `<img>` with a concrete pixel width — otherwise its `width: 100%` collapses to a few
	 * pixels in email once the CSS grid is gone.
	 */
	public function testItRebuildsNestedImagesAsResponsiveImages(): void {
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
				$this->nested_featured_image( 'https://example.com/logo1.png', 1024, 210 ),
				$this->nested_featured_image( 'https://example.com/logo2.png', 1024, 210 ),
				$this->nested_featured_image( 'https://example.com/logo3.png', 1024, 210 ),
			),
			3
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The collapsing web markup is gone: no fixed-width wrapper cell, no figure passthrough.
		$this->assertStringNotContainsString( 'width="520px"', $rendered );
		$this->assertStringNotContainsString( '<figure', $rendered );
		// The images are rebuilt with the responsive email style, not the web `width: 100%` alone.
		$this->assertStringContainsString( 'max-width: 100%; height: auto; display: block;', $rendered );
		// Each image keeps its link and gets a concrete (non-percentage) pixel width for Outlook.
		$this->assertSame( 3, substr_count( $rendered, 'href="https://example.com/sponsor"' ) );
		$this->assertSame( 3, preg_match_all( '/<img[^>]*\swidth="\d+"/', $rendered ) );
		$this->assertStringContainsString( 'logo1.png', $rendered );
		$this->assertStringContainsString( 'logo3.png', $rendered );
	}

	/**
	 * The rebuilt image's height is scaled from the intrinsic dimensions to the cell width, so it keeps
	 * its aspect ratio in clients that size by the width/height attributes (Outlook).
	 */
	public function testItScalesImageHeightToPreserveAspectRatio(): void {
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
		// A 1000x500 image (2:1) in a 2-column grid: whatever width the cell resolves to, the height
		// attribute must stay half of it.
		$content = $this->build_list(
			array(
				$this->nested_featured_image( 'https://example.com/wide.png', 1000, 500 ),
				$this->nested_featured_image( 'https://example.com/wide2.png', 1000, 500 ),
			),
			2
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		$this->assertSame( 1, preg_match( '/<img[^>]*\swidth="(\d+)"[^>]*\sheight="(\d+)"/', $rendered, $matches ) );
		$width  = (int) $matches[1];
		$height = (int) $matches[2];
		$this->assertGreaterThan( 0, $width );
		// 2:1 ratio preserved (allow +/-1px for rounding).
		$this->assertEqualsWithDelta( $width / 2, $height, 1 );
	}

	/**
	 * A post-card grid (featured image + date + title) keeps its text: the image is hoisted and
	 * rebuilt, and the title/date are preserved below it rather than dropped.
	 */
	public function testItPreservesCardTextAlongsideHoistedImage(): void {
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
		$card         = function ( string $src, string $title ) {
			return $this->nested_featured_image( $src, 1024, 512 )
				. '<div class="wp-block-post-date"><time datetime="2026-07-24T14:41:24-04:00">July 24, 2026</time></div>'
				. '<h2 class="wp-block-post-title">' . $title . '</h2>';
		};
		$content      = $this->build_list(
			array(
				$card( 'https://example.com/a.png', 'First post' ),
				$card( 'https://example.com/b.png', 'Second post' ),
			),
			2
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// Image is rebuilt (responsive style, no collapsing wrapper) AND the card text survives.
		$this->assertStringContainsString( 'max-width: 100%; height: auto; display: block;', $rendered );
		$this->assertStringNotContainsString( 'width="520px"', $rendered );
		$this->assertStringContainsString( 'First post', $rendered );
		$this->assertStringContainsString( 'Second post', $rendered );
		$this->assertStringContainsString( 'July 24, 2026', $rendered );
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 2, $this->count_cells( $rendered ) );
	}

	/**
	 * When a card's preserved (non-image) content carries unsafe markup — a `<script>` element or an
	 * inline event handler — it is stripped from the rebuilt cell, while legitimate content and styles
	 * are kept. Guards the reconstruct path against passing through markup that has no place in email.
	 */
	public function testItStripsScriptsAndEventHandlersFromPreservedContent(): void {
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
		$card         = $this->nested_featured_image( 'https://example.com/a.png', 800, 400 )
			. '<h2 class="wp-block-post-title" style="color:#111" onmouseover="alert(1)">Card Title</h2>'
			. '<script>alert(2)</script>';
		$content      = $this->build_list( array( $card, $card ), 2 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The image is rebuilt and the legitimate title (and its style) survive...
		$this->assertStringContainsString( 'a.png', $rendered );
		$this->assertStringContainsString( 'Card Title', $rendered );
		$this->assertStringContainsString( 'color:#111', $rendered );
		// ...but the handler and script are gone.
		$this->assertStringNotContainsString( 'onmouseover', $rendered );
		$this->assertStringNotContainsString( '<script', $rendered );
		$this->assertStringNotContainsString( 'alert(2)', $rendered );
	}

	/**
	 * A list whose class merely contains `wp-block-post-template` as a substring (not a whole token)
	 * must not be mistaken for the repeater and rebuilt — the content is left untouched.
	 */
	public function testItIgnoresListsWhoseClassOnlyContainsTheTokenAsSubstring(): void {
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
		// `my-wp-block-post-template-wrapper` contains the token as a substring but is not the token.
		$content = '<ul class="my-wp-block-post-template-wrapper columns-3">'
			. '<li>' . $this->featured_image( 'https://example.com/a.png' ) . '</li>'
			. '<li>' . $this->featured_image( 'https://example.com/b.png' ) . '</li>'
			. '</ul>';

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// Not recognized as a post-template list: no grid is built and the original list survives.
		$this->assertSame( 0, $this->count_rows( $rendered ) );
		$this->assertStringContainsString( '<ul', $rendered );
	}

	/**
	 * When every image in a card fails normalization, the images are still stripped (never restored)
	 * so an unrenderable tag with a dangerous attribute can't reach the email through the remainder.
	 */
	public function testItStripsAllImagesWhenNoneCanBeRebuilt(): void {
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
		// The card's only image has an unsafe src (esc_url rejects it) plus a dangerous handler.
		$card    = '<figure class="wp-block-image"><img src="javascript:alert(1)" alt="x" onerror="alert(1)"/></figure>';
		$content = $this->build_list( array( $card, $card ), 2 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The grid is built, but the unrenderable image (and its handler / unsafe URL) is gone.
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertStringNotContainsString( 'javascript:', $rendered );
		$this->assertStringNotContainsString( 'onerror', $rendered );
	}

	/**
	 * An image that can't be rebuilt (its src was rejected by sanitizing) is dropped rather than left
	 * behind in the preserved remainder, so its original unsanitized tag never reaches the email.
	 */
	public function testItDropsUnrenderableImageInsteadOfLeakingIt(): void {
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
		// Each card has a valid image plus a second image whose src esc_url() empties, in one figure.
		$card    = '<figure class="wp-block-image">'
			. '<img src="https://example.com/good.png" alt="Good" width="800" height="400"/>'
			. '<img src="javascript:alert(1)" alt="Bad"/>'
			. '</figure>';
		$content = $this->build_list( array( $card, $card ), 2 );

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The valid image is rebuilt; the unrenderable one is gone entirely — no raw src leaks through.
		$this->assertStringContainsString( 'good.png', $rendered );
		$this->assertStringNotContainsString( 'javascript:', $rendered );
		$this->assertStringNotContainsString( 'Bad', $rendered );
	}

	/**
	 * An item with no image (e.g. a title/excerpt-only card) is passed through unchanged, since text
	 * content stacks correctly on its own and needs no rebuilding.
	 */
	public function testItPassesThroughItemsWithoutImages(): void {
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
		$content      = $this->build_list(
			array(
				'<h3 class="wp-block-post-title">First post</h3>',
				'<h3 class="wp-block-post-title">Second post</h3>',
			),
			2
		);

		$rendered = $this->renderer->render( $content, $parsed_block, $this->rendering_context );

		// The grid is still built, and the text content survives verbatim in its cell.
		$this->assertSame( 1, $this->count_rows( $rendered ) );
		$this->assertSame( 2, $this->count_cells( $rendered ) );
		$this->assertStringContainsString( 'First post', $rendered );
		$this->assertStringContainsString( 'Second post', $rendered );
	}
}
