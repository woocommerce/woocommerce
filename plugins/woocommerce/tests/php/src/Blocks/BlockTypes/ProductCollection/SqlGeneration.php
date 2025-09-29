<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection\Utils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Helper_Product;
use WC_Tax;
use WP_Query;

/**
 * Tests for the ProductCollection block SQL generation and database optimization
 *
 * @group sql-generation
 */
class SqlGeneration extends \WP_UnitTestCase {
	/**
	 * This variable holds our Product Query object.
	 *
	 * @var ProductCollectionMock
	 */
	private $block_instance;

	/**
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		$this->block_instance = new ProductCollectionMock();
	}

	/**
	 * Tests that empty price range clauses are not added to the query.
	 */
	public function test_price_range_clauses_empty() {
		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 0,
			'max' => 0,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 0,
				'max' => 0,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		$this->assertStringNotContainsString( 'wc_product_meta_lookup.min_price', $query->request );
		$this->assertStringNotContainsString( 'wc_product_meta_lookup.max_price', $query->request );
	}

	/**
	 * Tests that the minimum in a price range is added if set.
	 */
	public function test_price_range_clauses_min_price() {
		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 1,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 1,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.max_price >= 1.', $query->request );
	}

	/**
	 * Tests that the maximum in a price range is added if set.
	 */
	public function test_price_range_clauses_max_price() {
		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'max' => 1,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'max' => 1,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.min_price <= 1.', $query->request );
	}

	/**
	 * Tests that the both the minimum and maximum in a price range is added if set.
	 */
	public function test_price_range_clauses_min_max_price() {
		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 1,
			'max' => 2,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 1,
				'max' => 2,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.max_price >= 1.', $query->request );
		$this->assertStringContainsString( 'wc_product_meta_lookup.min_price <= 2.', $query->request );
	}

	/**
	 * Tests that the both the minimum and maximum in a price range is added if set.
	 */
	public function test_price_range_clauses_min_max_price_tax_exclusive() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 1,
			'max' => 2,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 1,
				'max' => 2,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		delete_option( 'woocommerce_tax_display_shop' );
		delete_option( 'woocommerce_prices_include_tax' );

		$this->assertStringContainsString( 'wc_product_meta_lookup.max_price >= 1.', $query->request );
		$this->assertStringContainsString( 'wc_product_meta_lookup.min_price <= 2.', $query->request );
	}

	/**
	 * Tests that the both the minimum and maximum in a price range with taxes inclusive is added if set.
	 */
	public function test_price_range_clauses_min_max_price_tax_inclusive() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		WC_Tax::create_tax_class( 'collection-test' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_tax_class( 'collection-test' );
		$product->save();

		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 1,
			'max' => 2,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 1,
				'max' => 2,
			),
			$merged_query['priceRange'],
		);

		$query = new WP_Query( $merged_query );

		delete_option( 'woocommerce_tax_display_shop' );
		delete_option( 'woocommerce_prices_include_tax' );
		$product->delete();
		WC_Tax::delete_tax_class_by( 'slug', 'collection-test' );

		$this->assertStringContainsString( "( wc_product_meta_lookup.tax_class = 'collection-test' AND wc_product_meta_lookup.`max_price` >= 1.", $query->request );
		$this->assertStringContainsString( "( wc_product_meta_lookup.tax_class = 'collection-test' AND wc_product_meta_lookup.`min_price` <= 2.", $query->request );
	}

	/**
	 * Test the add_price_sorting_posts_clauses method.
	 */
	public function test_add_price_sorting_posts_clauses() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'price';

		$parsed_block['attrs']['query']['order'] = 'asc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.min_price ASC', $query->request );

		$parsed_block['attrs']['query']['order'] = 'desc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.max_price DESC', $query->request );
	}

	/**
	 * Test the add_sales_sorting_posts_clauses method.
	 */
	public function test_add_sales_sorting_posts_clauses() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'sales';

		$parsed_block['attrs']['query']['order'] = 'asc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.total_sales ASC', $query->request );

		$parsed_block['attrs']['query']['order'] = 'desc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'wc_product_meta_lookup.total_sales DESC', $query->request );
	}

	/**
	 * Tests alphabetical sorting by title for products with identical menu_order values in frontend context.
	 */
	public function test_frontend_menu_order_sorting_with_title_fallback() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_menu_order( 10 );
		$product1->set_name( 'Pennant' );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_menu_order( 10 );
		$product2->set_name( 'Album' );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_menu_order( 5 );
		$product3->set_name( 'Beanie' );
		$product3->save();

		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'menu_order';
		$parsed_block['attrs']['query']['order']   = 'asc';
		$parsed_block['attrs']['query']['perPage'] = 10;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query        = new WP_Query( $merged_query );

		$this->assertGreaterThanOrEqual( 3, $query->post_count );

		$ordered_product_ids = wp_list_pluck( $query->posts, 'ID' );

		$pos_product1 = array_search( $product1->get_id(), $ordered_product_ids, true );
		$pos_product2 = array_search( $product2->get_id(), $ordered_product_ids, true );
		$pos_product3 = array_search( $product3->get_id(), $ordered_product_ids, true );

		// Product3 (menu_order 5) should come before the others.
		$this->assertLessThan( $pos_product1, $pos_product3 );
		$this->assertLessThan( $pos_product2, $pos_product3 );

		// Product2 (Album) should come before Product1 (Pennant) when menu_order is same.
		$this->assertLessThan( $pos_product1, $pos_product2 );

		// Test descending order.
		$parsed_block['attrs']['query']['order'] = 'desc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$ordered_product_ids_desc = wp_list_pluck( $query->posts, 'ID' );

		$pos_product1_desc = array_search( $product1->get_id(), $ordered_product_ids_desc, true );
		$pos_product2_desc = array_search( $product2->get_id(), $ordered_product_ids_desc, true );
		$pos_product3_desc = array_search( $product3->get_id(), $ordered_product_ids_desc, true );

		// Product3 (menu_order 5) should come after the others in DESC order.
		$this->assertGreaterThan( $pos_product1_desc, $pos_product3_desc );
		$this->assertGreaterThan( $pos_product2_desc, $pos_product3_desc );

		// Between products with same menu_order (10), Pennant should come before Album in DESC.
		$this->assertLessThan( $pos_product2_desc, $pos_product1_desc );

		$product1->delete();
		$product2->delete();
		$product3->delete();
	}

	/**
	 * Tests that editor REST API queries correctly implement title fallback with menu_order sorting.
	 */
	public function test_editor_menu_order_sorting_with_title_fallback() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_name( 'Pennant' );
		$product1->set_menu_order( 10 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_name( 'Album' );
		$product2->set_menu_order( 10 );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_name( 'Beanie' );
		$product3->set_menu_order( 5 );
		$product3->save();

		$request = Utils::build_request(
			array(
				'orderby'  => 'menu_order',
				'order'    => 'asc',
				'per_page' => 10,
			)
		);

		$query = array(
			'order'          => 'asc',
			'posts_per_page' => 10,
			'post_type'      => 'product',
			'post_status'    => 'publish',
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( $query, $request );

		$this->assertEquals( 'menu_order', $updated_query['orderby'] );

		$query_obj = new WP_Query( $updated_query );
		$posts     = $query_obj->posts;

		$test_product_ids = array( $product1->get_id(), $product2->get_id(), $product3->get_id() );
		$found_products   = array();

		foreach ( $posts as $post ) {
			if ( in_array( $post->ID, $test_product_ids, true ) ) {
				$found_products[] = $post->ID;
			}
		}

		$this->assertCount( 3, $found_products );

		$pos_product1 = array_search( $product1->get_id(), $found_products, true );
		$pos_product2 = array_search( $product2->get_id(), $found_products, true );
		$pos_product3 = array_search( $product3->get_id(), $found_products, true );

		// Product3 (menu_order 5) should come before the others (menu_order 10).
		$this->assertLessThan( $pos_product1, $pos_product3 );
		$this->assertLessThan( $pos_product2, $pos_product3 );

		// Between products with same menu_order (10), Album should come before Pennant.
		$this->assertLessThan( $pos_product1, $pos_product2 );

		// Test descending order.
		$request = Utils::build_request(
			array(
				'orderby'  => 'menu_order',
				'order'    => 'desc',
				'per_page' => 10,
			)
		);

		$query = array(
			'order'          => 'desc',
			'posts_per_page' => 10,
			'post_type'      => 'product',
			'post_status'    => 'publish',
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( $query, $request );
		$query_obj     = new WP_Query( $updated_query );
		$posts         = $query_obj->posts;

		$found_products_desc = array();
		foreach ( $posts as $post ) {
			if ( in_array( $post->ID, $test_product_ids, true ) ) {
				$found_products_desc[] = $post->ID;
			}
		}

		$pos_product1_desc = array_search( $product1->get_id(), $found_products_desc, true );
		$pos_product2_desc = array_search( $product2->get_id(), $found_products_desc, true );
		$pos_product3_desc = array_search( $product3->get_id(), $found_products_desc, true );

		// Product3 (menu_order 5) should come after the others in DESC order.
		$this->assertGreaterThan( $pos_product1_desc, $pos_product3_desc );
		$this->assertGreaterThan( $pos_product2_desc, $pos_product3_desc );

		// Between products with same menu_order (10), Pennant should come before Album in DESC.
		$this->assertLessThan( $pos_product2_desc, $pos_product1_desc );

		$product1->delete();
		$product2->delete();
		$product3->delete();
	}

	/**
	 * Tests that menu_order REST query parameters are correctly processed in editor context.
	 */
	public function test_editor_menu_order_query_parameters() {
		$initial_query = array(
			'order' => 'desc',
		);

		$request = Utils::build_request(
			array(
				'orderby' => 'menu_order',
			)
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( $initial_query, $request );

		$this->assertEquals( 'menu_order', $updated_query['orderby'] );
		$this->assertEquals( 'desc', $updated_query['order'] );

		$request = Utils::build_request(
			array(
				'orderby' => 'menu_order',
			)
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( array(), $request );

		$this->assertEquals( 'menu_order', $updated_query['orderby'] );
		$this->assertTrue( ! isset( $updated_query['order'] ) || 'desc' === $updated_query['order'] );
	}

	/**
	 * Tests that menu_order sorting generates correct SQL clauses with title fallback.
	 */
	public function test_menu_order_sql_clauses_with_title_fallback() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'menu_order';
		unset( $parsed_block['attrs']['query']['order'] );

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query        = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'menu_order ASC, post_title ASC', $query->request );

		// Test descending order SQL clause.
		$parsed_block['attrs']['query']['order'] = 'desc';
		$merged_query                            = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$query                                   = new WP_Query( $merged_query );

		$this->assertStringContainsString( 'menu_order DESC, post_title DESC', $query->request );
	}
}
