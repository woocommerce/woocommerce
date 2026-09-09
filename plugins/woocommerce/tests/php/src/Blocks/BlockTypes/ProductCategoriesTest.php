<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Categories List block type.
 */
class ProductCategoriesTest extends WC_Unit_Test_Case {

	/**
	 * Category term IDs created for the tests, keyed by name.
	 *
	 * @var array<string, int>
	 */
	private $categories = array();

	/**
	 * Set up a small category tree with one product per leaf category.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->categories['Apparel']     = $this->create_category( 'Apparel' );
		$this->categories['Hats']        = $this->create_category( 'Hats', $this->categories['Apparel'] );
		$this->categories['Memberships'] = $this->create_category( 'Memberships' );
		$this->categories['Events']      = $this->create_category( 'Events' );

		foreach ( array( 'Hats', 'Memberships', 'Events' ) as $name ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_category_ids( array( $this->categories[ $name ] ) );
			$product->save();
		}

		wc_recount_all_terms();
		delete_transient( 'wc_term_counts' );
	}

	/**
	 * @testdox Should let the query args filter exclude a category and its descendants.
	 */
	public function test_query_args_filter_can_exclude_a_category_tree(): void {
		$excluded = array( $this->categories['Apparel'], $this->categories['Memberships'] );
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) use ( $excluded ) {
				$args['exclude_tree'] = $excluded;
				return $args;
			}
		);

		$output = $this->render_block();

		$this->assertStringContainsString( $this->item_name_markup( 'Events' ), $output, 'Events should still be rendered.' );
		foreach ( array( 'Apparel', 'Hats', 'Memberships' ) as $name ) {
			$this->assertStringNotContainsString( $this->item_name_markup( $name ), $output, "$name should be excluded." );
		}
	}

	/**
	 * @testdox Should pass the block attributes to the query args filter.
	 */
	public function test_query_args_filter_receives_block_attributes(): void {
		$received = null;
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args, $attributes ) use ( &$received ) {
				$received = $attributes;
				return $args;
			},
			10,
			2
		);

		$this->render_block( '{"hasCount":false,"isDropdown":true}' );

		$this->assertIsArray( $received, 'The filter should receive the block attributes.' );
		$this->assertFalse( $received['hasCount'], 'hasCount should reflect the block markup.' );
		$this->assertTrue( $received['isDropdown'], 'isDropdown should reflect the block markup.' );
	}

	/**
	 * @testdox Should ignore a non-array filter return value and keep the default query.
	 */
	public function test_non_array_filter_return_falls_back_to_defaults(): void {
		add_filter( 'woocommerce_blocks_product_categories_query_args', '__return_false' );

		$output = $this->render_block();

		foreach ( array( 'Apparel', 'Hats', 'Memberships', 'Events' ) as $name ) {
			$this->assertStringContainsString( $this->item_name_markup( $name ), $output, "$name should be rendered." );
		}
	}

	/**
	 * @testdox Should keep querying product categories even if the filter changes the taxonomy.
	 */
	public function test_filter_cannot_change_the_taxonomy(): void {
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) {
				$args['taxonomy'] = 'category';
				return $args;
			}
		);

		$output = $this->render_block();

		$this->assertStringContainsString( $this->item_name_markup( 'Events' ), $output, 'Product categories should still be rendered.' );
	}

	/**
	 * Render the block via do_blocks().
	 *
	 * @param string $attrs_json JSON object string for block attributes.
	 * @return string Rendered markup.
	 */
	private function render_block( string $attrs_json = '' ): string {
		$attrs = '' !== $attrs_json ? ' ' . $attrs_json : '';
		return do_blocks( "<!-- wp:woocommerce/product-categories{$attrs} /-->" );
	}

	/**
	 * Markup fragment that identifies a rendered list item by category name.
	 *
	 * @param string $name Category name.
	 * @return string
	 */
	private function item_name_markup( string $name ): string {
		return 'list-item__name">' . $name . '</span>';
	}

	/**
	 * Create a product category term.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID.
	 * @return int Term ID.
	 */
	private function create_category( string $name, int $parent_id = 0 ): int {
		$term = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent_id ) );
		$this->assertIsArray( $term, "Category $name should be created." );
		return (int) $term['term_id'];
	}
}
