<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_Block;
use WP_UnitTestCase;

/**
 * Tests for the CategoryDescription block type.
 */
class CategoryDescriptionTest extends WP_UnitTestCase {

	/**
	 * Term ID created for tests.
	 *
	 * @var int
	 */
	private $term_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$term          = wp_insert_term(
			'Described Category',
			'product_cat',
			array(
				'description' => 'Original term description.',
			)
		);
		$this->term_id = $term['term_id'];
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_delete_term( $this->term_id, 'product_cat' );
		parent::tearDown();
	}

	/**
	 * Render the block directly with injected context.
	 *
	 * @param array $attrs        Block attributes.
	 * @param int   $term_id      Term ID to supply as context.
	 * @param bool  $decoupled    Whether decoupled edit context is active.
	 * @param bool  $include_term Whether to include term context at all.
	 * @return string Rendered markup.
	 */
	private function render_with_context( array $attrs, int $term_id, bool $decoupled = true, bool $include_term = true ): string {
		$parsed_block = array(
			'blockName'    => 'woocommerce/category-description',
			'attrs'        => $attrs,
			'innerContent' => array(),
			'innerBlocks'  => array(),
		);

		$context = array( 'decoupledEdit' => $decoupled );
		if ( $include_term ) {
			$context['termId']       = $term_id;
			$context['termTaxonomy'] = 'product_cat';
		}

		$instance = new WP_Block( $parsed_block, $context );

		// Inject decoupledEdit context directly (simulating what
		// FeaturedItem::update_context does via render_block_context filter
		// in production) so it is available even when the test env's block
		// type registration does not populate uses_context.
		$instance->context['decoupledEdit'] = $decoupled;

		return $instance->render();
	}

	/**
	 * @testdox The content attribute overrides the term description when decoupled editing is enabled.
	 */
	public function test_content_attribute_overrides_term_description(): void {
		$markup = $this->render_with_context(
			array( 'content' => 'Custom Featured Description' ),
			$this->term_id
		);

		$this->assertStringContainsString( 'Custom Featured Description', $markup, 'Locally edited content should be rendered.' );
		$this->assertStringNotContainsString( 'Original term description', $markup, 'Term description should not appear when content is set.' );
	}

	/**
	 * @testdox The content attribute is ignored when decoupled editing is disabled.
	 */
	public function test_content_ignored_when_decoupled_edit_disabled(): void {
		$markup = $this->render_with_context(
			array( 'content' => 'Custom Featured Description' ),
			$this->term_id,
			false
		);

		// The visible description should be the term description, not the locally edited content.
		$this->assertMatchesRegularExpression(
			'/class="[^"]*">\s*<p>Original term description\.<\/p>\s*<\/div>/',
			$markup,
			'Term description should render as the visible text when decoupled editing is off.'
		);
	}

	/**
	 * @testdox The term description is used when the content attribute is not set (unset).
	 */
	public function test_term_description_used_when_content_unset(): void {
		$markup = $this->render_with_context( array(), $this->term_id );

		$this->assertStringContainsString( 'Original term description', $markup, 'Term description should be rendered as fallback.' );
	}

	/**
	 * @testdox Once content is set (even to whitespace), the block stays detached and renders empty.
	 */
	public function test_set_whitespace_content_stays_detached(): void {
		$markup = $this->render_with_context(
			array( 'content' => '   ' ),
			$this->term_id
		);

		$this->assertStringNotContainsString( 'Original term description', $markup, 'Whitespace-only set content should not fall back to the term description.' );
		$this->assertEmpty( trim( wp_strip_all_tags( $markup ) ), 'Detached empty content should render an empty description.' );
	}

	/**
	 * @testdox An empty-but-set content string keeps the block detached from the term.
	 */
	public function test_empty_string_content_stays_detached(): void {
		$markup = $this->render_with_context(
			array( 'content' => '' ),
			$this->term_id
		);

		$this->assertStringNotContainsString( 'Original term description', $markup, 'An explicitly empty content should not fall back to the term description.' );
	}

	/**
	 * @testdox The block renders nothing when the term description and content are both empty.
	 */
	public function test_renders_empty_without_content(): void {
		$empty_term = wp_insert_term( 'Empty Category', 'product_cat' );

		$markup = $this->render_with_context( array(), $empty_term['term_id'] );

		$this->assertSame( '', $markup, 'Block should render nothing without a description or content.' );

		wp_delete_term( $empty_term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox The block renders nothing when there is no term context.
	 */
	public function test_renders_empty_without_term(): void {
		$markup = $this->render_with_context( array(), 0 );

		$this->assertSame( '', $markup, 'Block should render nothing with a falsy term ID.' );
	}

	/**
	 * @testdox The block renders nothing when term context is missing entirely.
	 */
	public function test_renders_empty_when_term_context_missing(): void {
		$markup = $this->render_with_context(
			array(),
			$this->term_id,
			true,
			false
		);

		$this->assertSame( '', $markup, 'Block should render nothing when term context is absent.' );
	}
}
