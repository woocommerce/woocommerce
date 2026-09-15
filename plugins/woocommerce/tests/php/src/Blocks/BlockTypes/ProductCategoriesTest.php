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
	 * Term ID of "Hoodies", a child of "Clothing" with one product.
	 *
	 * @var int
	 */
	private int $hoodies_id;

	/**
	 * Build the category tree the tests assert against.
	 *
	 * Nothing pre-existing is removed. The only term a fresh install ships with is the
	 * default "Uncategorized", and it holds no products, so `hide_empty` keeps it out of
	 * the rendered list and the counts below stay exact.
	 */
	public function setUp(): void {
		parent::setUp();

		$clothing_id      = (int) wp_insert_term( 'Clothing', 'product_cat' )['term_id'];
		$this->hoodies_id = (int) wp_insert_term( 'Hoodies', 'product_cat', array( 'parent' => $clothing_id ) )['term_id'];
		$music_id         = (int) wp_insert_term( 'Music', 'product_cat' )['term_id'];
		wp_insert_term( 'Empty', 'product_cat' );

		wp_set_object_terms( WC_Helper_Product::create_simple_product()->get_id(), array( $this->hoodies_id ), 'product_cat' );
		wp_set_object_terms( WC_Helper_Product::create_simple_product()->get_id(), array( $music_id ), 'product_cat' );
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
}
