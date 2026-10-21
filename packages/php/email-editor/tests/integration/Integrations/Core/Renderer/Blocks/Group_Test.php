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
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for how the Group renderer lays out grids.
 */
class Group_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Block types given an email render callback, removed again in tearDown.
	 *
	 * @var \WP_Block_Type[]
	 */
	private array $block_types = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$initializer = $this->di_container->get( Initializer::class );
		$initializer->initialize();
		// Production attaches the email render callback while core registers its blocks, which
		// happened before this test, so attach it here.
		foreach ( array( 'core/group', 'core/paragraph', 'core/image' ) as $block_name ) {
			$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
			$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
			$block_type->set_props( array( 'render_email_callback' => array( $initializer, 'render_block' ) ) );
			$this->block_types[] = $block_type;
		}
	}

	/**
	 * Remove the email render callbacks set up for the test.
	 */
	public function tearDown(): void {
		foreach ( $this->block_types as $block_type ) {
			unset( $block_type->render_email_callback );
		}
		$this->block_types = array();
		parent::tearDown();
	}

	/**
	 * Test a two-column grid renders as table rows of two cells, in order.
	 */
	public function testItRendersTwoColumnGridAsTableRows(): void {
		$rendered = $this->render_grid( '{"type":"grid","columnCount":2}', array( 'First', 'Second', 'Third' ) );

		$this->assertSame( 2, substr_count( $rendered, 'class="email-block-grid-row"' ) );
		// The odd last item keeps its column width next to an empty cell.
		$this->assertSame( 4, substr_count( $rendered, 'class="email-block-grid-cell"' ) );
		$this->assertMatchesRegularExpression( '/First.*Second.*email-block-grid-row.*Third/s', $rendered );
	}

	/**
	 * Test grids that can't be mapped to two cells per row keep their items stacked, in order.
	 *
	 * @testWith ["{\"type\":\"grid\",\"columnCount\":3}", ""]
	 *           ["{\"type\":\"grid\",\"columnCount\":2}", "{\"style\":{\"layout\":{\"columnSpan\":2}}}"]
	 * @param string $layout Grid layout attribute.
	 * @param string $first_item_attrs Attributes of the first item.
	 */
	public function testItStacksOtherGrids( string $layout, string $first_item_attrs ): void {
		$rendered = $this->render_grid( $layout, array( 'First', 'Second', 'Third' ), $first_item_attrs );

		$this->assertStringNotContainsString( 'email-block-grid-row', $rendered );
		$this->assertMatchesRegularExpression( '/First.*Second.*Third/s', $rendered );
		// The grid layout classes are dropped, so no CSS grid rules get inlined onto the wrapper table.
		$this->assertStringNotContainsString( 'is-layout-grid', $rendered );
	}

	/**
	 * Test an image resized wider than its cell is capped to the cell.
	 */
	public function testItCapsResizedImageToItsCell(): void {
		$rendered = $this->render_post(
			'<!-- wp:group {"layout":{"type":"grid","columnCount":2}} --><div class="wp-block-group">'
			. '<!-- wp:image {"width":"1000px","sizeSlug":"large"} --><figure class="wp-block-image size-large is-resized"><img src="https://example.com/photo.jpg" alt="" style="width:1000px;height:500px"/></figure><!-- /wp:image -->'
			. '<!-- wp:paragraph --><p>Beside the image</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group -->'
		);

		// One cell is half the 612px content width, less the 16px gap: 298px. The height keeps the 2:1 ratio.
		$this->assertSame( 1, preg_match( '/<img[^>]*>/', $rendered, $image ) );
		$this->assertStringContainsString( 'width="298"', $image[0] );
		$this->assertStringContainsString( 'height="149"', $image[0] );
	}

	/**
	 * Render a grid of paragraphs through the full content renderer.
	 *
	 * @param string   $layout Grid layout attribute, as JSON.
	 * @param string[] $items Text of each paragraph.
	 * @param string   $first_item_attrs Attributes of the first paragraph, as JSON.
	 * @return string
	 */
	private function render_grid( string $layout, array $items, string $first_item_attrs = '' ): string {
		$content = '';
		foreach ( $items as $key => $text ) {
			$attrs    = 0 === $key && $first_item_attrs ? ' ' . $first_item_attrs : '';
			$content .= '<!-- wp:paragraph' . $attrs . ' --><p>' . $text . '</p><!-- /wp:paragraph -->';
		}
		return $this->render_post( '<!-- wp:group {"layout":' . $layout . '} --><div class="wp-block-group is-layout-grid">' . $content . '</div><!-- /wp:group -->' );
	}

	/**
	 * Render post content through the full content renderer.
	 *
	 * @param string $post_content Post content.
	 * @return string
	 */
	private function render_post( string $post_content ): string {
		$post = $this->factory->post->create_and_get( array( 'post_content' => $post_content ) );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';

		return $this->di_container->get( Content_Renderer::class )->render( $post, $template );
	}
}
