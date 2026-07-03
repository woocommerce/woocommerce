<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_Block;
use WP_UnitTestCase;

/**
 * Tests for the FeaturedProductTitle block type.
 */
class FeaturedProductTitleTest extends WP_UnitTestCase {

	/**
	 * Product ID created for tests.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_id = $this->factory->post->create(
			array(
				'post_type'  => 'product',
				'post_title' => 'Original Product Name',
			)
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_delete_post( $this->product_id, true );
		parent::tearDown();
	}

	/**
	 * Render the block directly with injected context.
	 *
	 * @param array $attrs         Block attributes.
	 * @param int   $post_id       Post ID to supply as context.
	 * @param bool  $decoupled     Whether decoupled edit context is active.
	 * @param bool  $include_post  Whether to include post context at all.
	 * @return string Rendered markup.
	 */
	private function render_with_context( array $attrs, int $post_id, bool $decoupled = true, bool $include_post = true ): string {
		$parsed_block = array(
			'blockName'    => 'woocommerce/featured-product-title',
			'attrs'        => $attrs,
			'innerContent' => array(),
			'innerBlocks'  => array(),
		);

		$context = array( 'decoupledEdit' => $decoupled );
		if ( $include_post ) {
			$context['postId']   = $post_id;
			$context['postType'] = 'product';
		}

		$instance = new WP_Block( $parsed_block, $context );

		return $instance->render();
	}

	/**
	 * @testdox The content attribute overrides the product title when decoupled editing is enabled.
	 */
	public function test_content_attribute_overrides_product_title(): void {
		$markup = $this->render_with_context(
			array(
				'content' => 'Custom Featured Title',
				'level'   => 2,
			),
			$this->product_id
		);

		$this->assertStringContainsString( 'Custom Featured Title', $markup, 'Locally edited content should be rendered.' );
		$this->assertStringNotContainsString( 'Original Product Name', $markup, 'Product title should not appear when content is set.' );
	}

	/**
	 * @testdox The content attribute is ignored when decoupled editing is disabled.
	 */
	public function test_content_ignored_when_decoupled_edit_disabled(): void {
		$markup = $this->render_with_context(
			array(
				'content' => 'Custom Featured Title',
				'level'   => 2,
			),
			$this->product_id,
			false
		);

		// The visible title should be the product name, not the locally edited content.
		$this->assertMatchesRegularExpression(
			'/class="[^"]*">Original Product Name<\/h2>/',
			$markup,
			'Product name should render as the visible title when decoupled editing is off.'
		);
	}

	/**
	 * @testdox The product title is used when the content attribute is not set.
	 */
	public function test_product_title_used_when_content_empty(): void {
		$markup = $this->render_with_context(
			array( 'level' => 2 ),
			$this->product_id
		);

		$this->assertStringContainsString( 'Original Product Name', $markup, 'Product title should be rendered as fallback.' );
	}

	/**
	 * @testdox Whitespace-only content falls back to the product title.
	 */
	public function test_empty_content_falls_back_to_product_title(): void {
		$markup = $this->render_with_context(
			array(
				'content' => '   ',
				'level'   => 2,
			),
			$this->product_id
		);

		$this->assertStringContainsString( 'Original Product Name', $markup, 'Whitespace-only content should fall back to product title.' );
	}

	/**
	 * @testdox The block renders nothing when there is no post context.
	 */
	public function test_renders_empty_without_post(): void {
		$markup = $this->render_with_context( array( 'level' => 2 ), 0 );

		$this->assertSame( '', $markup, 'Block should render nothing with a falsy post ID.' );
	}

	/**
	 * @testdox The block renders nothing when post context is missing entirely.
	 */
	public function test_renders_empty_when_post_context_missing(): void {
		$markup = $this->render_with_context(
			array( 'level' => 2 ),
			$this->product_id,
			true,
			false
		);

		$this->assertSame( '', $markup, 'Block should render nothing when post context is absent.' );
	}

	/**
	 * @testdox The title is wrapped in a link when isLink is true.
	 */
	public function test_title_wrapped_in_link_when_is_link(): void {
		$markup = $this->render_with_context(
			array(
				'content' => 'Linked Title',
				'isLink'  => true,
				'level'   => 2,
			),
			$this->product_id
		);

		$this->assertStringContainsString( '<a href=', $markup, 'Title should be wrapped in an anchor when isLink is true.' );
		$this->assertStringContainsString( 'Linked Title', $markup, 'Custom content should appear inside the link.' );
	}
}
