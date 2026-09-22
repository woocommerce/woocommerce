<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Helpers\ImageAttachmentTrait;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Categories List block.
 */
class ProductCategoriesTest extends WC_Unit_Test_Case {

	use ImageAttachmentTrait;

	/**
	 * Term ID of "Clothing", a top-level category whose only product sits in "Hoodies".
	 *
	 * @var int
	 */
	private int $clothing_id;

	/**
	 * Term ID of "Hoodies", a child of "Clothing" with one product.
	 *
	 * @var int
	 */
	private int $hoodies_id;

	/**
	 * Term ID of "Music", a top-level category with one product.
	 *
	 * @var int
	 */
	private int $music_id;

	/**
	 * Build the category tree the tests assert against.
	 *
	 * Nothing pre-existing is removed. The only term a fresh install ships with is the
	 * default "Uncategorized", and it holds no products, so `hide_empty` keeps it out of
	 * the rendered list and the counts below stay exact.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clothing_id = (int) wp_insert_term( 'Clothing', 'product_cat' )['term_id'];
		$this->hoodies_id  = (int) wp_insert_term( 'Hoodies', 'product_cat', array( 'parent' => $this->clothing_id ) )['term_id'];
		$this->music_id    = (int) wp_insert_term( 'Music', 'product_cat' )['term_id'];
		wp_insert_term( 'Empty', 'product_cat' );

		wp_set_object_terms( WC_Helper_Product::create_simple_product()->get_id(), array( $this->hoodies_id ), 'product_cat' );
		wp_set_object_terms( WC_Helper_Product::create_simple_product()->get_id(), array( $this->music_id ), 'product_cat' );
	}

	/**
	 * Remove the image files create_image_attachment() wrote into the uploads directory.
	 */
	public function tearDown(): void {
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Render the Product Categories List block via do_blocks().
	 *
	 * @param string $attrs JSON object string for block attributes.
	 * @return string Rendered markup.
	 */
	private function render_product_categories( string $attrs = '' ): string {
		return do_blocks( "<!-- wp:woocommerce/product-categories {$attrs} /-->" );
	}

	/**
	 * @testdox Should render a hierarchical list with product counts and no images by default.
	 */
	public function test_default_attributes(): void {
		$markup = $this->render_product_categories();

		$this->assertStringContainsString( 'wc-block-product-categories is-list', $markup );
		$this->assertStringContainsString( '>Clothing<', $markup );
		$this->assertStringContainsString( '>Hoodies<', $markup );
		$this->assertStringContainsString( '>Music<', $markup );
		$this->assertStringContainsString( 'wc-block-product-categories-list--depth-1', $markup );
		$this->assertSame( 3, substr_count( $markup, 'wc-block-product-categories-list-item-count' ) );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list-item__image', $markup );
	}

	/**
	 * @testdox Should render a dropdown instead of a list when isDropdown is true.
	 */
	public function test_is_dropdown(): void {
		$markup = $this->render_product_categories( '{"isDropdown":true}' );

		$this->assertStringContainsString( 'wc-block-product-categories is-dropdown', $markup );
		$this->assertStringContainsString( 'wc-block-product-categories__dropdown', $markup );
		$this->assertStringNotContainsString( '<li', $markup );
		$this->assertSame( 4, substr_count( $markup, '<option' ) ); // The three categories plus the "Select a category" placeholder.
	}

	/**
	 * @testdox Should omit the product counts when hasCount is false.
	 */
	public function test_has_count_false(): void {
		$markup = $this->render_product_categories( '{"hasCount":false}' );

		$this->assertStringContainsString( '>Clothing<', $markup );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list-item-count', $markup );
	}

	/**
	 * @testdox Should render a category image when hasImage is true, falling back to a placeholder.
	 */
	public function test_has_image_true(): void {
		update_term_meta( $this->hoodies_id, 'thumbnail_id', $this->create_image_attachment( 100, 100, 'hoodie.jpg' ) );

		$markup = $this->render_product_categories( '{"hasImage":true}' );

		$this->assertStringContainsString( 'wc-block-product-categories-list--has-images', $markup );
		$this->assertStringContainsString( 'hoodie.jpg', $markup );
		$this->assertStringContainsString( 'wc-block-product-categories-list-item__image--placeholder', $markup );
	}

	/**
	 * @testdox Should render every category at the top level when isHierarchical is false.
	 */
	public function test_is_hierarchical_false(): void {
		$markup = $this->render_product_categories( '{"isHierarchical":false}' );

		$this->assertStringContainsString( '>Clothing<', $markup );
		$this->assertStringContainsString( '>Hoodies<', $markup );
		$this->assertStringContainsString( '>Music<', $markup );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list--depth-1', $markup );
		$this->assertSame( 1, substr_count( $markup, '<ul' ) );
	}

	/**
	 * @testdox Should include categories without products only when hasEmpty is true.
	 */
	public function test_has_empty(): void {
		$this->assertStringNotContainsString( '>Empty<', $this->render_product_categories() );
		$this->assertStringContainsString( '>Empty<', $this->render_product_categories( '{"hasEmpty":true}' ) );
	}

	/**
	 * @testdox Should let the query args filter exclude a category and its descendants.
	 */
	public function test_query_args_filter_can_exclude_a_category_tree(): void {
		$excluded = array( $this->clothing_id );
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) use ( $excluded ) {
				$args['exclude_tree'] = $excluded;
				return $args;
			}
		);

		$markup = $this->render_product_categories();

		$this->assertStringContainsString( '>Music<', $markup, 'Music should still be rendered.' );
		$this->assertStringNotContainsString( '>Clothing<', $markup, 'Clothing should be excluded.' );
		$this->assertStringNotContainsString( '>Hoodies<', $markup, 'Hoodies should be excluded with its parent.' );
	}

	/**
	 * @testdox Should pass the default query args and the block attributes to the filter.
	 */
	public function test_query_args_filter_receives_query_args_and_block_attributes(): void {
		$received_args       = null;
		$received_attributes = null;
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args, $attributes ) use ( &$received_args, &$received_attributes ) {
				$received_args       = $args;
				$received_attributes = $attributes;
				return $args;
			},
			10,
			2
		);

		$this->render_product_categories( '{"hasCount":false,"isDropdown":true}' );

		$this->assertIsArray( $received_args, 'The filter should receive the query args.' );
		$this->assertSame( 'product_cat', $received_args['taxonomy'], 'The taxonomy should be product_cat.' );
		$this->assertTrue( $received_args['hide_empty'], 'Empty categories should be hidden by default.' );
		$this->assertTrue( $received_args['pad_counts'], 'Counts should be padded.' );
		$this->assertTrue( $received_args['hierarchical'], 'The query should be hierarchical.' );
		$this->assertArrayNotHasKey( 'child_of', $received_args, 'child_of should only be set on category archives.' );

		$this->assertIsArray( $received_attributes, 'The filter should receive the block attributes.' );
		$this->assertFalse( $received_attributes['hasCount'], 'hasCount should reflect the block markup.' );
		$this->assertTrue( $received_attributes['isDropdown'], 'isDropdown should reflect the block markup.' );
	}

	/**
	 * @testdox Should ignore a non-array filter return value and keep the default query.
	 */
	public function test_non_array_filter_return_falls_back_to_defaults(): void {
		add_filter( 'woocommerce_blocks_product_categories_query_args', '__return_false' );

		$markup = $this->render_product_categories();

		$this->assertStringContainsString( '>Clothing<', $markup );
		$this->assertStringContainsString( '>Hoodies<', $markup );
		$this->assertStringContainsString( '>Music<', $markup );
	}

	/**
	 * @testdox Should keep querying full product category objects even if the filter changes the taxonomy or fields.
	 */
	public function test_filter_cannot_change_the_taxonomy_or_fields(): void {
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) {
				$args['taxonomy'] = 'category';
				$args['fields']   = 'ids';
				return $args;
			}
		);

		$this->assertStringContainsString( '>Music<', $this->render_product_categories(), 'Product categories should still be rendered.' );
	}

	/**
	 * @testdox Should show the children of an excluded parent at the top level.
	 */
	public function test_excluding_a_parent_keeps_its_children_at_the_top_level(): void {
		$excluded = array( $this->clothing_id );
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) use ( $excluded ) {
				$args['exclude'] = $excluded;
				return $args;
			}
		);

		$markup = $this->render_product_categories();

		$this->assertStringNotContainsString( '>Clothing<', $markup, 'Clothing should be excluded.' );
		$this->assertStringContainsString( '>Hoodies<', $markup, 'Hoodies should still be rendered.' );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list--depth-1', $markup, 'Hoodies should be rendered at the top level.' );
	}

	/**
	 * @testdox Should render an included child category without its parent.
	 */
	public function test_including_only_a_child_renders_it(): void {
		$included = array( $this->hoodies_id );
		add_filter(
			'woocommerce_blocks_product_categories_query_args',
			function ( $args ) use ( $included ) {
				$args['include'] = $included;
				return $args;
			}
		);

		$markup = $this->render_product_categories();

		$this->assertStringContainsString( '>Hoodies<', $markup, 'Hoodies should be rendered.' );
		$this->assertStringNotContainsString( '>Clothing<', $markup, 'Clothing should not be rendered.' );
		$this->assertStringNotContainsString( '>Music<', $markup, 'Music should not be rendered.' );
	}

	/**
	 * @testdox Should show the child of a category with no product count at the top level.
	 */
	public function test_child_of_a_zero_count_parent_is_promoted_with_no_filter(): void {
		delete_term_meta( $this->clothing_id, 'product_count_product_cat' );
		delete_transient( 'wc_term_counts' );
		clean_term_cache( array( $this->clothing_id, $this->hoodies_id, $this->music_id ), 'product_cat' );

		$markup = $this->render_product_categories();

		$this->assertStringNotContainsString( '>Clothing<', $markup, 'Clothing has no count and should be dropped.' );
		$this->assertStringContainsString( '>Hoodies<', $markup, 'Hoodies should still be rendered.' );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list--depth-1', $markup, 'Hoodies should be rendered at the top level.' );
	}

	/**
	 * @testdox Should show an orphaned grandchild at the top level in children-only mode.
	 */
	public function test_children_only_promotes_an_orphaned_grandchild(): void {
		$caps_id = (int) wp_insert_term( 'Caps', 'product_cat', array( 'parent' => $this->hoodies_id ) )['term_id'];
		wp_set_object_terms( WC_Helper_Product::create_simple_product()->get_id(), array( $caps_id ), 'product_cat' );
		wc_recount_all_terms();

		delete_term_meta( $this->hoodies_id, 'product_count_product_cat' );
		delete_transient( 'wc_term_counts' );
		clean_term_cache( array( $this->clothing_id, $this->hoodies_id, $this->music_id, $caps_id ), 'product_cat' );

		$this->go_to( get_term_link( $this->clothing_id, 'product_cat' ) );
		$this->assertTrue( is_product_category(), 'The request should be a product category archive.' );

		$markup = $this->render_product_categories( '{"showChildrenOnly":true}' );

		$this->assertStringContainsString( '>Caps<', $markup, 'Caps should be rendered.' );
		$this->assertStringNotContainsString( '>Hoodies<', $markup, 'Hoodies has no count and should be dropped.' );
		$this->assertStringNotContainsString( 'wc-block-product-categories-list--depth-1', $markup, 'Caps should be rendered at the top level.' );
	}
}
