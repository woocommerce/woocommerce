<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for the Group renderer's grid layouts.
 */
class Group_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Group renderer instance.
	 *
	 * @var Group
	 */
	private Group $group_renderer;

	/**
	 * Rendering context instance.
	 *
	 * @var Rendering_Context
	 */
	private Rendering_Context $rendering_context;

	/**
	 * Original block types replaced by a copy with an email render callback, restored in tearDown.
	 *
	 * @var \WP_Block_Type[]
	 */
	private array $original_block_types = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->group_renderer    = new Group();
		$this->rendering_context = new Rendering_Context( $this->di_container->get( Theme_Controller::class )->get_theme() );
	}

	/**
	 * Restore the block types a test replaced.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( $this->original_block_types as $block_type ) {
			$registry->unregister( $block_type->name );
			$registry->register( $block_type );
		}
		$this->original_block_types = array();
		parent::tearDown();
	}

	/**
	 * Build a grid group's content the way the content renderer hands it over: the group wrapper
	 * holding each child's rendered output, including the Outlook conditional wrappers.
	 *
	 * @param int $count Number of grid items.
	 * @return string
	 */
	private function build_grid_content( int $count ): string {
		$items = '';
		for ( $i = 1; $i <= $count; $i++ ) {
			$items .= '<!--[if mso | IE]><table role="presentation"><tr><td><![endif]-->'
				. '<div class="email-block-layout"><p>Item ' . $i . '</p></div>'
				. '<!--[if mso | IE]></td></tr></table><![endif]-->';
		}

		return '<div class="wp-block-group is-layout-grid wp-container-core-group-is-layout-1a2b3c4d wp-block-group-is-layout-grid">' . $items . '</div>';
	}

	/**
	 * Build a parsed grid group block.
	 *
	 * @param array $email_attrs Email attributes set by the preprocessors.
	 * @param int   $column_count Grid column count.
	 * @return array
	 */
	private function build_grid_block( array $email_attrs, int $column_count = 2 ): array {
		return array(
			'blockName'   => 'core/group',
			'attrs'       => array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => $column_count,
				),
			),
			'email_attrs' => $email_attrs,
			'innerBlocks' => array(),
		);
	}

	/**
	 * Test a two-column grid renders two cells per row, with the gap split between them.
	 */
	public function testItRendersTwoColumnGridAsTableRows(): void {
		$rendered = $this->group_renderer->render(
			$this->build_grid_content( 3 ),
			$this->build_grid_block(
				array(
					'two-column-grid' => true,
					'grid-row-gap'    => '24px',
					'grid-column-gap' => '20px',
				)
			),
			$this->rendering_context
		);

		$this->checkValidHTML( $rendered );
		$this->assertSame( 2, substr_count( $rendered, 'class="email-block-grid-row"' ) );
		$this->assertSame( 4, substr_count( $rendered, 'class="email-block-grid-cell"' ), 'An odd last item should sit next to an empty cell, keeping its column width.' );

		// Items stay in order, two per row.
		$this->assertMatchesRegularExpression( '/Item 1.*Item 2.*email-block-grid-row.*Item 3/s', $rendered );

		// The gap is split between the two cells, and rows after the first are spaced by the row gap.
		$this->assertStringContainsString( 'style="width:50%;vertical-align:top;padding-right:10px;"', $rendered );
		$this->assertStringContainsString( 'style="width:50%;vertical-align:top;padding-left:10px;"', $rendered );
		$this->assertStringContainsString( 'padding-right:10px;padding-top:24px;', $rendered );
		$this->assertStringContainsString( 'padding-left:10px;padding-top:24px;', $rendered );

		$this->assertGridLayoutClassesDropped( $rendered );

		// Rows use the package's presentation table attributes.
		$this->assertMatchesRegularExpression( '/<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="email-block-grid-row"/', $rendered );
	}

	/**
	 * Test a grid that isn't flagged as two-column keeps its children stacked in order.
	 */
	public function testItStacksOtherGrids(): void {
		$rendered = $this->group_renderer->render(
			$this->build_grid_content( 3 ),
			$this->build_grid_block( array(), 3 ),
			$this->rendering_context
		);

		$this->checkValidHTML( $rendered );
		$this->assertStringNotContainsString( 'email-block-grid-row', $rendered );
		$this->assertMatchesRegularExpression( '/Item 1.*Item 2.*Item 3/s', $rendered );
		$this->assertGridLayoutClassesDropped( $rendered );
	}

	/**
	 * Assert the grid layout classes were dropped from the wrapper, so CSS grid rules can't be
	 * inlined onto the table.
	 *
	 * @param string $rendered Rendered group HTML.
	 */
	private function assertGridLayoutClassesDropped( string $rendered ): void {
		$this->assertStringContainsString( 'class="email-block-group wp-block-group"', $rendered );
		$this->assertStringNotContainsString( 'is-layout-grid', $rendered );
		$this->assertStringNotContainsString( 'wp-container-core-group-is-layout', $rendered );
	}

	/**
	 * Test the gap sits between the cells in right-to-left emails too.
	 */
	public function testItSplitsTheGapBetweenCellsInRtl(): void {
		$rtl_context = new Rendering_Context( $this->di_container->get( Theme_Controller::class )->get_theme(), array( 'is_rtl' => true ) );

		$rendered = $this->group_renderer->render(
			$this->build_grid_content( 2 ),
			$this->build_grid_block(
				array(
					'two-column-grid' => true,
					'grid-column-gap' => '20px',
				)
			),
			$rtl_context
		);

		// In RTL the first cell is on the right, so its gap is on its left side.
		$this->assertMatchesRegularExpression( '/padding-left:10px;"[^>]*>[^<]*<div[^>]*><p>Item 1.*padding-right:10px;"[^>]*>[^<]*<div[^>]*><p>Item 2/s', $rendered );
	}

	/**
	 * Test the full pipeline: a saved two-column grid renders side by side, one cell per child, with images capped to their cell.
	 */
	public function testItRendersSavedTwoColumnGridEndToEnd(): void {
		$initializer = $this->di_container->get( Initializer::class );
		$initializer->initialize();
		// Production attaches the email render callback while core registers its blocks. They are
		// registered already here, so swap in copies that keep every block support and add the callback.
		$block_names  = array( 'core/group', 'core/paragraph', 'core/image' );
		$add_callback = static function ( array $args, string $name ) use ( $block_names, $initializer ): array {
			if ( in_array( $name, $block_names, true ) ) {
				$args['render_email_callback'] = array( $initializer, 'render_block' );
			}
			return $args;
		};
		add_filter( 'register_block_type_args', $add_callback, 10, 2 );
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( $block_names as $block_name ) {
			$block_type = $registry->get_registered( $block_name );
			$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
			$copy = clone $block_type;
			$copy->set_props( array( 'render_callback' => $block_type->render_callback ) );
			$registry->unregister( $block_name );
			$registry->register( $copy );
			$this->original_block_types[] = $block_type;
		}
		remove_filter( 'register_block_type_args', $add_callback, 10 );

		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:group {"layout":{"type":"grid","columnCount":2}} --><div class="wp-block-group">'
					. '<!-- wp:image {"width":"1000px","sizeSlug":"large"} --><figure class="wp-block-image size-large is-resized"><img src="https://example.com/photo.jpg" alt="" style="width:1000px"/></figure><!-- /wp:image -->'
					. '<!-- wp:test/two-paragraphs /-->'
					. '<!-- wp:paragraph --><p>Second row</p><!-- /wp:paragraph -->'
					. '</div><!-- /wp:group -->',
			)
		);
		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		// A block whose email renderer returns two elements must still fill a single cell.
		register_block_type(
			'test/two-paragraphs',
			array(
				'render_email_callback' => function () {
					return '<p>Part A</p><p>Part B</p>';
				},
			)
		);

		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';

		$rendered = $this->di_container->get( Content_Renderer::class )->render( $post, $template );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/two-paragraphs' );

		$this->assertSame( 2, substr_count( $rendered, 'class="email-block-grid-row"' ) );
		$this->assertSame( 4, substr_count( $rendered, 'class="email-block-grid-cell"' ) );
		$this->assertMatchesRegularExpression( '/photo\.jpg.*Part A.*Part B.*email-block-grid-row.*Second row/s', $rendered );

		// The image, saved at 1000px, is capped to one cell (half the content width, less the gap).
		$this->assertSame( 1, preg_match( '/<img[^>]*width="(\d+)"/', $rendered, $matches ) );
		$content_width = (int) $this->rendering_context->get_layout_width_without_padding();
		$this->assertLessThan( $content_width / 2, (int) $matches[1] );
		$this->assertGreaterThan( $content_width / 2 - 50, (int) $matches[1] );
	}
}
