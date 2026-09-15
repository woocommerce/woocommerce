<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for category blocks using term context.
 */
class CategoryTermContextTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should read Core taxonomy context while preserving legacy context precedence.
	 * @testWith ["category-title", {"taxonomy":"category"}]
	 *           ["category-description", {"taxonomy":"category"}]
	 *           ["category-title", {"termTaxonomy":"category", "taxonomy":"product_cat"}]
	 *           ["category-description", {"termTaxonomy":"category", "taxonomy":"product_cat"}]
	 *
	 * @param string $name Block name without the namespace.
	 * @param array  $context Available block context.
	 */
	public function test_category_text_context( string $name, array $context ): void {
		$context['termId'] = self::factory()->term->create(
			array(
				'taxonomy'    => 'category',
				'name'        => 'Context content',
				'description' => 'Context content',
			)
		);
		$sut               = new \WP_Block( parse_blocks( '<!-- wp:woocommerce/' . $name . ' /-->' )[0], $context );

		$this->assertStringContainsString( 'Context content', $sut->render(), 'The block should render the term from the effective taxonomy.' );
	}

	/**
	 * @testdox Should resolve category text and bound links without overwriting custom URLs.
	 * @testWith [false, "taxonomy", true]
	 *           [false, "termTaxonomy", true]
	 *           [true, "taxonomy", true]
	 *           [false, "taxonomy", false]
	 *           [true, "taxonomy", false]
	 *
	 * @param bool   $selected Whether the category is selected explicitly.
	 * @param string $taxonomy_key Context key supplying the taxonomy.
	 * @param bool   $bound Whether the button uses the term-data binding.
	 */
	public function test_featured_category_context( bool $selected, string $taxonomy_key, bool $bound ): void {
		if ( $bound && ! get_block_bindings_source( 'core/term-data' ) ) {
			$this->markTestSkipped( 'Core term-data bindings are not available.' );
		}

		$selected_id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Selected category',
			)
		);
		$term_ids    = self::factory()->term->create_many(
			2,
			array(
				'taxonomy'    => 'product_cat',
				'description' => 'Inherited description',
			)
		);
		$binding     = $bound ? '{"metadata":{"bindings":{"url":{"source":"core/term-data","args":{"field":"link"}}}}}' : '{}';
		$markup      = '<!-- wp:woocommerce/featured-category --><!-- wp:woocommerce/category-title /--><!-- wp:woocommerce/category-description /--><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button ' . $binding . ' --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com/custom">Shop now</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:woocommerce/featured-category -->';
		$parsed      = parse_blocks( $markup )[0];

		$parsed['attrs']['categoryId'] = $selected ? $selected_id : 0;

		foreach ( $term_ids as $term_id ) {
			$sut           = new \WP_Block(
				$parsed,
				array(
					'termId'      => $term_id,
					$taxonomy_key => 'product_cat',
				)
			);
			$output        = $sut->render();
			$expected_term = get_term( $selected ? $selected_id : $term_id, 'product_cat' );
			$expected_url  = $bound ? get_term_link( $expected_term ) : 'https://example.com/custom';

			$this->assertStringContainsString( esc_html( $expected_term->name ) . '</h2>', $output, 'The title should follow the selected or inherited category.' );
			$this->assertStringContainsString( 'href="' . esc_url( $expected_url ) . '"', $output, 'Bound links should follow each category; custom URLs should remain unchanged.' );
			$this->assertSame( ! $selected, str_contains( $output, 'Inherited description' ), 'The description should use the same category as the title.' );
		}
	}

	/**
	 * @testdox Should reject inherited terms from other taxonomies.
	 */
	public function test_featured_category_taxonomy_guard(): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$sut     = new \WP_Block(
			parse_blocks( '<!-- wp:woocommerce/featured-category /-->' )[0],
			array(
				'termId'   => $term_id,
				'taxonomy' => 'category',
			)
		);

		$this->assertSame( '', $sut->render(), 'A term from another taxonomy must not be treated as a product category.' );
	}
}
