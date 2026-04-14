<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Term_Functions_Tests.
 */
class WC_Term_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * @var WP_Term[] Test terms.
	 */
	private $terms = array();

	/**
	 * @var WC_Product_Simple[] Test products.
	 */
	private $products = array();

	/**
	 * Setup before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->terms['parent'] = wp_insert_term( 'Parent term', 'product_cat' );
		$this->terms['child1'] = wp_insert_term( 'Child term 1', 'product_cat', array( 'parent' => $this->terms['parent']['term_id'] ) );
		$this->terms['child2'] = wp_insert_term( 'Child term 2', 'product_cat', array( 'parent' => $this->terms['parent']['term_id'] ) );

		$this->terms['tag1'] = wp_insert_term( 'Tag 1', 'product_tag' );
		$this->terms['tag2'] = wp_insert_term( 'Tag 2', 'product_tag' );

		$this->products['product1'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['child1']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag1']['term_id'] ),
			)
		);
		$this->products['product2'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['child2']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag2']['term_id'] ),
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);
		$this->products['product3'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['parent']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag1']['term_id'], $this->terms['tag2']['term_id'] ),
			)
		);
	}

	/**
	 * Teardown after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->terms as $term ) {
			wp_delete_term( $term['term_id'], $term['term_taxonomy_id'] );
		}
		$this->terms = array();

		foreach ( $this->products as $product ) {
			$product->delete();
		}
		$this->products = array();

		parent::tearDown();
	}

	/**
	 * @testdox Term product counts with default settings.
	 */
	public function test_term_count_baseline(): void {
		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 3, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag2']['term_id'] ] );
	}

	/**
	 * @testdox Term product counts when a product is hidden from the catalog.
	 */
	public function test_product_visibility(): void {
		$this->products['product1']->set_catalog_visibility( 'hidden' );
		$this->products['product1']->save();

		wc_recount_all_terms();
		delete_transient( 'wc_term_counts' );

		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 2, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 0, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag2']['term_id'] ] );
	}

	/**
	 * @testdox Term product counts when a product is out of stock and OOS products are hidden from the catalog.
	 */
	public function test_hide_out_of_stock_products(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		wc_recount_all_terms( false );
		delete_transient( 'wc_term_counts' );

		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 2, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 0, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['tag2']['term_id'] ] );

		delete_option( 'woocommerce_hide_out_of_stock_items' );
	}

	/**
	 * @testdox The call to WP Core's _update_post_term_count function in _wc_term_recount should receive
	 *          term_taxonomy_id values rather than term_id values for its first parameter.
	 */
	public function test_standard_callback_gets_correct_params(): void {
		$target_tt_id = $this->terms['parent']['term_taxonomy_id'];
		$success      = false;

		$action_callback = function ( $tt_id ) use ( $target_tt_id, &$success ) {
			if ( $tt_id === $target_tt_id ) {
				$success = true;
			}
		};

		add_action( 'edited_term_taxonomy', $action_callback );

		$target_term = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => $this->terms['parent']['term_id'],
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);

		_wc_term_recount( $target_term, get_taxonomy( 'product_cat' ), true, false );

		$this->assertTrue( $success );

		remove_action( 'edited_term_taxonomy', $action_callback );
	}

	/**
	 * @testdox Featured term ID matches current site.
	 */
	public function test_get_product_visibility_term_ids_includes_featured(): void {
		$term_ids = wc_get_product_visibility_term_ids();

		$this->assertIsArray( $term_ids );
		$this->assertArrayHasKey( 'featured', $term_ids );

		$featured_term = get_term_by( 'name', 'featured', 'product_visibility' );

		$this->assertNotFalse( $featured_term );
		$this->assertSame( (int) $featured_term->term_taxonomy_id, (int) $term_ids['featured'] );
	}

	/**
	 * @testdox Featured filter returns only featured products.
	 */
	public function test_wc_get_products_featured_returns_featured_products(): void {
		$featured_product = WC_Helper_Product::create_simple_product( true );
		$featured_product->set_featured( true );
		$featured_product->save();

		$regular_product = WC_Helper_Product::create_simple_product( true );
		$regular_product->set_featured( false );
		$regular_product->save();

		$featured_products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 50,
				'featured' => true,
			)
		);

		$featured_ids = array_map( fn( $product ) => $product->get_id(), $featured_products );

		$this->assertSame( array( $featured_product->get_id() ), $featured_ids );

		$featured_product->delete();
		$regular_product->delete();
	}
}
