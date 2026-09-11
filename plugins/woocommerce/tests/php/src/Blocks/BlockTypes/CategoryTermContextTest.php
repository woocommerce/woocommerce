<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for category blocks using term context.
 */
class CategoryTermContextTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should render category text with Core taxonomy context, legacy context, or the product category fallback.
	 * @testWith ["category-title", "category", {"taxonomy":"category"}, "Context category"]
	 *           ["category-description", "category", {"taxonomy":"category"}, "Context description"]
	 *           ["category-title", "product_cat", {"termTaxonomy":"product_cat", "taxonomy":"category"}, "Context category"]
	 *           ["category-description", "product_cat", {"termTaxonomy":"product_cat", "taxonomy":"category"}, "Context description"]
	 *           ["category-title", "product_cat", {}, "Context category"]
	 *           ["category-description", "product_cat", {}, "Context description"]
	 *
	 * @param string $name Block name without the namespace.
	 * @param string $taxonomy Fixture taxonomy.
	 * @param array  $context Available block context.
	 * @param string $expected Expected rendered text.
	 */
	public function test_category_text_context( string $name, string $taxonomy, array $context, string $expected ): void {
		$context['termId'] = self::factory()->term->create(
			array(
				'taxonomy'    => $taxonomy,
				'name'        => 'Context category',
				'description' => 'Context description',
			)
		);
		$sut               = new \WP_Block( parse_blocks( '<!-- wp:woocommerce/' . $name . ' /-->' )[0], $context );

		$this->assertStringContainsString( $expected, $sut->render(), 'The block should render the term from the effective taxonomy.' );
	}

	/**
	 * @testdox Should render an inherited product category with the existing Featured Category markup and inner blocks.
	 * @testWith [{}, {"taxonomy":"product_cat"}]
	 *           [{"categoryId":0}, {"taxonomy":"product_cat"}]
	 *           [{}, {"termTaxonomy":"product_cat"}]
	 *
	 * @param array $attributes Block attributes.
	 * @param array $context Available block context.
	 */
	public function test_featured_category_inherits_context( array $attributes, array $context ): void {
		$context['termId'] = self::factory()->term->create(
			array(
				'taxonomy'    => 'product_cat',
				'name'        => 'Inherited category',
				'description' => 'Inherited description',
			)
		);
		$sut               = $this->create_featured_category( $attributes, $context );
		$output            = $sut->render();

		$this->assertStringContainsString( 'wc-block-featured-category__wrapper', $output, 'The existing Featured Category wrapper should be retained.' );
		$this->assertStringContainsString( 'Inherited category</h2>', $output, 'The inner title should inherit the category.' );
		$this->assertStringContainsString( 'Inherited description', $output, 'The inner description should inherit the category.' );
		$this->assertStringNotContainsString( 'wp-block-cover', $output, 'Terms Query compatibility must not convert the block to Cover.' );
	}

	/**
	 * @testdox Should prefer the selected product category for Featured Category and its inner blocks.
	 * @testWith ["product_cat"]
	 *           ["category"]
	 *
	 * @param string $taxonomy Inherited taxonomy.
	 */
	public function test_featured_category_selection_takes_precedence( string $taxonomy ): void {
		$selected_id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Selected category',
			)
		);
		$context_id  = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'name'     => 'Inherited category',
			)
		);
		$sut         = $this->create_featured_category(
			array( 'categoryId' => $selected_id ),
			array(
				'termId'   => $context_id,
				'taxonomy' => $taxonomy,
			)
		);

		$output = $sut->render();
		$this->assertStringContainsString( 'Selected category</h2>', $output, 'The selected category should supply the inner title.' );
		$this->assertStringNotContainsString( 'Inherited category', $output, 'The inherited category must not replace the selection.' );
	}

	/**
	 * @testdox Should ignore missing or non-product taxonomy context in Featured Category.
	 * @testWith [{}]
	 *           [{"taxonomy":"category"}]
	 *           [{"termTaxonomy":"category", "taxonomy":"product_cat"}]
	 *
	 * @param array $context Available block context.
	 */
	public function test_featured_category_rejects_other_taxonomies( array $context ): void {
		$context['termId'] = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$sut               = $this->create_featured_category( array(), $context );

		$this->assertSame( '', $sut->render(), 'A term ID alone must not be treated as a product category.' );
	}

	/**
	 * @testdox Should resolve the bound button URL for each inherited term without changing the saved link.
	 * @testWith ["taxonomy"]
	 *           ["termTaxonomy"]
	 *
	 * @param string $taxonomy_key Context key supplying the taxonomy.
	 */
	public function test_inherited_button_link( string $taxonomy_key ): void {
		if ( ! get_block_bindings_source( 'core/term-data' ) ) {
			$this->markTestSkipped( 'Core term-data bindings are not available.' );
		}

		$category_ids = self::factory()->term->create_many( 2, array( 'taxonomy' => 'product_cat' ) );
		foreach ( $category_ids as $category_id ) {
			$sut    = $this->create_category_with_button(
				array( 'categoryId' => 0 ),
				array(
					'termId'      => $category_id,
					$taxonomy_key => 'product_cat',
				),
				true
			);
			$output = $sut->render();
			$this->assertStringContainsString( 'href="' . esc_url( get_term_link( $category_id, 'product_cat' ) ) . '"', $output, 'Each repeated category must link to its own archive.' );
			$this->assertStringNotContainsString( 'href="https://example.com/custom"', $output, 'The saved fallback must not override the term binding.' );
			$this->assertStringContainsString( 'https://example.com/custom', serialize_block( $sut->parsed_block ), 'Rendering must not rewrite saved button markup.' );
		}
	}

	/**
	 * @testdox Should resolve a previously bound button to the selected product category even inside another taxonomy loop.
	 */
	public function test_bound_button_uses_selected_category(): void {
		if ( ! get_block_bindings_source( 'core/term-data' ) ) {
			$this->markTestSkipped( 'Core term-data bindings are not available.' );
		}

		$selected_id  = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$inherited_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		$sut          = $this->create_category_with_button(
			array( 'categoryId' => $selected_id ),
			array(
				'termId'   => $inherited_id,
				'taxonomy' => 'category',
			),
			true
		);

		$this->assertStringContainsString( 'href="' . esc_url( get_term_link( $selected_id, 'product_cat' ) ) . '"', $sut->render(), 'An explicit selection must also take precedence for a bound button.' );
	}

	/**
	 * @testdox Should preserve an unbound custom button URL in selected and inherited categories.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $selected Whether the category is selected explicitly.
	 */
	public function test_custom_button_link_is_preserved( bool $selected ): void {
		$category_id = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$sut         = $this->create_category_with_button(
			$selected ? array( 'categoryId' => $category_id ) : array(),
			array(
				'termId'   => $category_id,
				'taxonomy' => 'product_cat',
			),
			false
		);

		$this->assertStringContainsString( 'href="https://example.com/custom"', $sut->render(), 'Unbound custom URLs must not be replaced with category permalinks.' );
	}

	/**
	 * Create a Featured Category containing a button with an optional term binding.
	 *
	 * @param array $attributes Featured Category attributes.
	 * @param array $context Available block context.
	 * @param bool  $bound Whether to bind the button URL to the term link.
	 * @return \WP_Block
	 */
	private function create_category_with_button( array $attributes, array $context, bool $bound ): \WP_Block {
		$button_attributes = $bound ? '{"metadata":{"bindings":{"url":{"source":"core/term-data","args":{"field":"link"}}}}}' : '{}';
		$markup            = '<!-- wp:woocommerce/featured-category --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button ' . $button_attributes . ' --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com/custom">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-category -->';
		$parsed            = parse_blocks( $markup )[0];
		$parsed['attrs']   = $attributes;
		return new \WP_Block( $parsed, $context );
	}

	/**
	 * Create a Featured Category block with category text children.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $context Available block context.
	 * @return \WP_Block
	 */
	private function create_featured_category( array $attributes, array $context ): \WP_Block {
		$parsed          = parse_blocks( '<!-- wp:woocommerce/featured-category --><!-- wp:woocommerce/category-title /--><!-- wp:woocommerce/category-description /--><!-- /wp:woocommerce/featured-category -->' )[0];
		$parsed['attrs'] = $attributes;
		return new \WP_Block( $parsed, $context );
	}
}
