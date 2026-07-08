<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_Block;
use WP_UnitTestCase;

/**
 * Tests for the CategoryTitle block type.
 */
class CategoryTitleTest extends WP_UnitTestCase {

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
		$term          = wp_insert_term( 'Original Category Name', 'product_cat' );
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
			'blockName'    => 'woocommerce/category-title',
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
	 * @testdox The content attribute overrides the term name when decoupled editing is enabled.
	 */
	public function test_content_attribute_overrides_term_name(): void {
		$markup = $this->render_with_context(
			array(
				'content' => 'Custom Featured Title',
				'level'   => 2,
			),
			$this->term_id
		);

		$this->assertStringContainsString( 'Custom Featured Title', $markup, 'Locally edited content should be rendered.' );
		$this->assertStringNotContainsString( 'Original Category Name', $markup, 'Term name should not appear when content is set.' );
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
			$this->term_id,
			false
		);

		// The visible title should be the term name, not the locally edited content.
		$this->assertMatchesRegularExpression(
			'/class="[^"]*">Original Category Name<\/h2>/',
			$markup,
			'Term name should render as the visible title when decoupled editing is off.'
		);
	}

	/**
	 * @testdox The term name is used when the content attribute is not set (unset).
	 */
	public function test_term_name_used_when_content_unset(): void {
		$markup = $this->render_with_context(
			array( 'level' => 2 ),
			$this->term_id
		);

		$this->assertStringContainsString( 'Original Category Name', $markup, 'Term name should be rendered as fallback.' );
	}

	/**
	 * @testdox Once content is set (even to whitespace), the block stays detached and renders empty.
	 */
	public function test_set_whitespace_content_stays_detached(): void {
		$markup = $this->render_with_context(
			array(
				'content' => '   ',
				'level'   => 2,
			),
			$this->term_id
		);

		$this->assertStringNotContainsString( 'Original Category Name', $markup, 'Whitespace-only set content should not fall back to term name.' );
		$this->assertEmpty( trim( wp_strip_all_tags( $markup ) ), 'Detached empty content should render an empty title.' );
	}

	/**
	 * @testdox An empty-but-set content string keeps the block detached from the term.
	 */
	public function test_empty_string_content_stays_detached(): void {
		$markup = $this->render_with_context(
			array(
				'content' => '',
				'level'   => 2,
			),
			$this->term_id
		);

		$this->assertStringNotContainsString( 'Original Category Name', $markup, 'An explicitly empty content should not fall back to the term name.' );
	}

	/**
	 * @testdox The block renders nothing when there is no term context.
	 */
	public function test_renders_empty_without_term(): void {
		$markup = $this->render_with_context( array( 'level' => 2 ), 0 );

		$this->assertSame( '', $markup, 'Block should render nothing with a falsy term ID.' );
	}

	/**
	 * @testdox The block renders nothing when term context is missing entirely.
	 */
	public function test_renders_empty_when_term_context_missing(): void {
		$markup = $this->render_with_context(
			array( 'level' => 2 ),
			$this->term_id,
			true,
			false
		);

		$this->assertSame( '', $markup, 'Block should render nothing when term context is absent.' );
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
			$this->term_id
		);

		$this->assertStringContainsString( '<a href=', $markup, 'Title should be wrapped in an anchor when isLink is true.' );
		$this->assertStringContainsString( 'Linked Title', $markup, 'Custom content should appear inside the link.' );
	}

	/**
	 * @testdox User-provided markup in the content attribute is escaped, not rendered raw.
	 */
	public function test_content_escape_user_markup(): void {
		$markup = $this->render_with_context(
			array(
				'content' => '<script>alert("xss")</script>',
				'level'   => 2,
			),
			$this->term_id
		);

		$this->assertStringContainsString(
			'&lt;script&gt;',
			$markup,
			'Safe HTML entities should be used instead of raw tags.'
		);
		$this->assertStringNotContainsString(
			'<script>',
			$markup,
			'Raw script tags should not be present in the rendered output.'
		);
	}
}
