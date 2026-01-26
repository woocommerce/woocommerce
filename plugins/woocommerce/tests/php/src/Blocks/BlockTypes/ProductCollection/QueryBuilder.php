<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection\Utils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use WC_Helper_Product;
use WP_Query;

/**
 * Tests for the ProductCollection block query building and merging logic
 *
 * @group query-building
 */
class QueryBuilder extends \WP_UnitTestCase {
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
	 * Test merging featured queries.
	 */
	public function test_merging_featured_queries() {
		$parsed_block                               = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['featured'] = true;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => 'IN',
				'taxonomy' => 'product_visibility',
			),
			$merged_query['tax_query']
		);
	}

	/**
	 * Test merging on sale queries.
	 */
	public function test_merging_on_sale_queries() {
		// Mock the on sale product ids.
		$on_sale_product_ids = array( 1, 2, 3, 4 );
		set_transient( 'wc_products_onsale', $on_sale_product_ids, DAY_IN_SECONDS * 30 );

		$parsed_block                                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceOnSale'] = true;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		foreach ( $on_sale_product_ids as $id ) {
			$this->assertContainsEquals( $id, $merged_query['post__in'] );
		}

		$this->assertCount( 4, $merged_query['post__in'] );

		delete_transient( 'wc_products_onsale' );
	}

	/**
	 * Test merging stock status queries.
	 */
	public function test_merging_stock_status_queries() {
		$parsed_block = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			ProductStockStatus::OUT_OF_STOCK,
			ProductStockStatus::ON_BACKORDER,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'value'   => array( ProductStockStatus::OUT_OF_STOCK, ProductStockStatus::ON_BACKORDER ),
				'compare' => 'IN',
				'key'     => '_stock_status',
			),
			$merged_query['meta_query']
		);
	}

	/**
	 * Test merging default stock queries that should use product visibility
	 * queries instead of meta query for stock status.
	 */
	public function test_merging_default_stock_queries() {
		$parsed_block = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			ProductStockStatus::IN_STOCK,
			ProductStockStatus::OUT_OF_STOCK,
			ProductStockStatus::ON_BACKORDER,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEmpty( $merged_query['meta_query'] );

		// Test with hide out of stock items option enabled.
		$parsed_block = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			ProductStockStatus::IN_STOCK,
			ProductStockStatus::ON_BACKORDER,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEmpty( $merged_query['meta_query'] );
	}

	/**
	 * Test merging attribute queries.
	 */
	public function test_merging_attribute_queries() {
		$parsed_block = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceAttributes'] = array(
			array(
				'taxonomy' => 'pa_test',
				'termId'   => 1,
			),
			array(
				'taxonomy' => 'pa_test',
				'termId'   => 2,
			),
			array(
				'taxonomy' => 'pa_another_test',
				'termId'   => 3,
			),
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'field'    => 'term_id',
				'terms'    => array( 3 ),
				'operator' => 'IN',
				'taxonomy' => 'pa_another_test',
			),
			$merged_query['tax_query']
		);

		$this->assertContainsEquals(
			array(
				'taxonomy' => 'pa_test',
				'field'    => 'term_id',
				'terms'    => array( 1, 2 ),
				'operator' => 'IN',
			),
			$merged_query['tax_query']
		);
	}

	/**
	 * Test merging order by rating queries.
	 */
	public function test_merging_order_by_rating_queries() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'rating';

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals( 'meta_value_num', $merged_query['orderby'] );
		$this->assertEquals( '_wc_average_rating', $merged_query['meta_key'] );
	}

	/**
	 * Test product visibility query exist in merged query.
	 */
	public function test_product_visibility_query_exist_in_merged_query() {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		$parsed_block = Utils::get_base_parsed_block();

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_not_in,
				'operator' => 'NOT IN',
			),
			$merged_query['tax_query']
		);
	}

	/**
	 * Test merging multiple queries.
	 */
	public function test_merging_multiple_queries() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'rating';
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			ProductStockStatus::IN_STOCK,
			ProductStockStatus::OUT_OF_STOCK,
		);
		$parsed_block['attrs']['query']['woocommerceAttributes']  = array(
			array(
				'taxonomy' => 'pa_test',
				'termId'   => 1,
			),
			array(
				'taxonomy' => 'pa_test',
				'termId'   => 2,
			),
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals( 'meta_value_num', $merged_query['orderby'] );
		$this->assertEquals( '_wc_average_rating', $merged_query['meta_key'] );
		$this->assertContainsEquals(
			array(
				'compare' => 'IN',
				'key'     => '_stock_status',
				'value'   => array( ProductStockStatus::IN_STOCK, ProductStockStatus::OUT_OF_STOCK ),
			),
			$merged_query['meta_query']
		);
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'pa_test',
				'field'    => 'term_id',
				'terms'    => array( 1, 2 ),
				'operator' => 'IN',
			),
			$merged_query['tax_query']
		);
	}

	/**
	 * Test merging filter by max price queries.
	 */
	public function test_merging_filter_by_max_price_queries() {
		set_query_var( 'max_price', 100 );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(),
				'relation' => 'AND',
			),
			$merged_query['meta_query']
		);
		set_query_var( 'max_price', '' );
	}

	/**
	 * Test merging filter by min price queries.
	 */
	public function test_merging_filter_by_min_price_queries() {
		set_query_var( 'min_price', 20 );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );

		$this->assertContainsEquals(
			array(
				array(),
				array(
					'key'     => '_price',
					'value'   => 20,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				'relation' => 'AND',
			),
			$merged_query['meta_query']
		);
		set_query_var( 'min_price', '' );
	}

	/**
	 * Test merging filter by min and max price queries.
	 */
	public function test_merging_filter_by_min_and_max_price_queries() {
		set_query_var( 'max_price', 100 );
		set_query_var( 'min_price', 20 );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'key'     => '_price',
					'value'   => 20,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				'relation' => 'AND',
			),
			$merged_query['meta_query']
		);

		set_query_var( 'max_price', '' );
		set_query_var( 'min_price', '' );
	}

	/**
	 * Test merging filter by stock status queries.
	 */
	public function test_merging_filter_by_stock_status_queries() {
		set_query_var( 'filter_stock_status', ProductStockStatus::IN_STOCK );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );

		$this->assertContainsEquals(
			array(
				'operator' => 'IN',
				'key'      => '_stock_status',
				'value'    => array( ProductStockStatus::IN_STOCK ),
			),
			$merged_query['meta_query']
		);

		set_query_var( 'filter_stock_status', '' );
	}

	/**
	 * Test merging time range queries.
	 */
	public function test_merging_time_frame_before_queries() {
		$time_frame_date = gmdate( 'Y-m-d H:i:s' );

		$parsed_block                                = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['timeFrame'] = array(
			'operator' => 'not-in',
			'value'    => $time_frame_date,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'column'    => 'post_date_gmt',
				'before'    => $time_frame_date,
				'inclusive' => true,
			),
			$merged_query['date_query'],
		);
	}

	/**
	 * Test merging time range queries.
	 */
	public function test_merging_time_frame_after_queries() {
		$time_frame_date = gmdate( 'Y-m-d H:i:s' );

		$parsed_block                                = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['timeFrame'] = array(
			'operator' => 'in',
			'value'    => $time_frame_date,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertContainsEquals(
			array(
				'column'    => 'post_date_gmt',
				'after'     => $time_frame_date,
				'inclusive' => true,
			),
			$merged_query['date_query'],
		);
	}

	/**
	 * Test merging filter by stock status queries.
	 */
	public function test_merging_filter_by_attribute_queries() {
		// Mock the attribute data.
		$this->block_instance->set_attributes_filter_query_args(
			array(
				array(
					'filter'     => 'filter_color',
					'query_type' => 'query_type_color',
				),
				array(
					'filter'     => 'filter_size',
					'query_type' => 'query_type_size',
				),
			)
		);

		set_query_var( 'filter_color', 'blue' );
		set_query_var( 'query_type_color', 'or' );
		set_query_var( 'filter_size', 'xl,xxl' );
		set_query_var( 'query_type_size', 'and' );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );
		$tax_queries  = $merged_query['tax_query'];

		$and_query = array();
		foreach ( $tax_queries as $tax_query ) {
			if ( isset( $tax_query['relation'] ) && 'AND' === $tax_query['relation'] ) {
				$and_query = $tax_query;
			}
		}

		// Check if the AND query is an array.
		$this->assertIsArray( $and_query );

		$attribute_queries = array();
		foreach ( $and_query as $and_query_item ) {
			if ( is_array( $and_query_item ) ) {
				$attribute_queries = $and_query_item;
			}
		}

		$this->assertContainsEquals(
			array(
				'taxonomy' => 'pa_color',
				'field'    => 'slug',
				'terms'    => array( 'blue' ),
				'operator' => 'IN',
			),
			$attribute_queries
		);

		$this->assertContainsEquals(
			array(
				'taxonomy' => 'pa_size',
				'field'    => 'slug',
				'terms'    => array( 'xl', 'xxl' ),
				'operator' => 'AND',
			),
			$attribute_queries
		);

		set_query_var( 'filter_color', '' );
		set_query_var( 'query_type_color', '' );
		set_query_var( 'filter_size', '' );
		set_query_var( 'query_type_size', '' );
	}

	/**
	 * Test merging multiple filter queries.
	 */
	public function test_merging_multiple_filter_queries() {
		set_query_var( 'max_price', 100 );
		set_query_var( 'min_price', 20 );
		set_query_var( 'filter_stock_status', ProductStockStatus::IN_STOCK );

		$merged_query = Utils::initialize_merged_query( $this->block_instance );

		$this->assertContainsEquals(
			array(
				'operator' => 'IN',
				'key'      => '_stock_status',
				'value'    => array( ProductStockStatus::IN_STOCK ),
			),
			$merged_query['meta_query']
		);

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				array(
					'key'     => '_price',
					'value'   => 20,
					'compare' => '>=',
					'type'    => 'numeric',
				),
				'relation' => 'AND',
			),
			$merged_query['meta_query']
		);

		set_query_var( 'max_price', '' );
		set_query_var( 'min_price', '' );
		set_query_var( 'filter_stock_status', '' );
	}

	/**
	 * Test merging taxonomies query i.e.
	 * - Product categories
	 * - Product tags
	 */
	public function test_merging_taxonomies_query() {
		$merged_query = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			// Since we aren't calling the Query Loop build function, we need to provide
			// a tax_query rather than relying on it generating one from the input.
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => array( 1, 2 ),
						'include_children' => false,
					),
					array(
						'taxonomy'         => 'product_tag',
						'terms'            => array( 3, 4 ),
						'include_children' => false,
					),
				),
			)
		);

		$this->assertContains(
			array(
				'taxonomy'         => 'product_cat',
				'terms'            => array( 1, 2 ),
				'include_children' => false,
			),
			$merged_query['tax_query']
		);

		$this->assertContains(
			array(
				'taxonomy'         => 'product_tag',
				'terms'            => array( 3, 4 ),
				'include_children' => false,
			),
			$merged_query['tax_query']
		);
	}

	/**
	 * Test that price range queries are set so they can be picked up in the `posts_clauses` filter.
	 */
	public function test_price_range_queries() {
		$parsed_block                                 = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['priceRange'] = array(
			'min' => 1,
			'max' => 100,
		);

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals(
			array(
				'min' => 1,
				'max' => 100,
			),
			$merged_query['priceRange'],
		);
	}

	/**
	 * Test handpicked products queries.
	 */
	public function test_handpicked_products_queries() {
		$handpicked_product_ids = array( 1, 2, 3, 4 );

		$parsed_block = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = $handpicked_product_ids;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		foreach ( $handpicked_product_ids as $id ) {
			$this->assertContainsEquals( $id, $merged_query['post__in'] );
		}

		$this->assertCount( 4, $merged_query['post__in'] );
	}

	/**
	 * Test merging exclusive id filters.
	 */
	public function test_merges_post__in() {
		$existing_id_filter     = array( 1, 4 );
		$handpicked_product_ids = array( 3, 4, 5, 6 );
		// The only ID present in ALL of the exclusive filters is 4.
		$expected_product_ids = array( 4 );

		$parsed_block                               = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['post__in'] = $existing_id_filter;
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = $handpicked_product_ids;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		foreach ( $expected_product_ids as $id ) {
			$this->assertContainsEquals( $id, $merged_query['post__in'] );
		}

		$this->assertCount( 1, $merged_query['post__in'] );
	}

	/**
	 * Test merging exclusive id filters with no intersection.
	 */
	public function test_merges_post__in_empty_result_without_intersection() {
		$existing_id_filter     = array( 1, 4 );
		$handpicked_product_ids = array( 2, 3 );

		$parsed_block                               = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['post__in'] = $existing_id_filter;
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = $handpicked_product_ids;

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals( array( -1 ), $merged_query['post__in'] );
	}

	/**
	 * Test the menu_order sorting functionality.
	 */
	public function test_menu_order_sorting() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'menu_order';
		$parsed_block['attrs']['query']['order']   = 'asc';
		$merged_query                              = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals( 'menu_order', $merged_query['orderby'] );
		$this->assertEquals( 'asc', $merged_query['order'] );
	}

	/**
	 * Test the random sorting functionality.
	 */
	public function test_random_sorting() {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'random';
		$merged_query                              = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertEquals( 'rand', $merged_query['orderby'] );
	}

	/**
	 * Tests that the by-category collection handler works as expected.
	 */
	public function test_collection_by_category() {
		$electronics_cat    = wp_create_term( 'Electronics', 'product_cat' );
		$electronics_cat_id = $electronics_cat['term_id'];

		$clothing_cat    = wp_create_term( 'Clothing', 'product_cat' );
		$clothing_cat_id = $clothing_cat['term_id'];

		$laptop = WC_Helper_Product::create_simple_product();
		$laptop->set_name( 'Laptop' );
		$laptop->save();

		$phone = WC_Helper_Product::create_simple_product();
		$phone->set_name( 'Phone' );
		$phone->save();

		$tshirt = WC_Helper_Product::create_simple_product();
		$tshirt->set_name( 'T-Shirt' );
		$tshirt->save();

		$unassigned_product = WC_Helper_Product::create_simple_product();
		$unassigned_product->set_name( 'Unassigned Product' );
		$unassigned_product->save();

		// Assign products to categories.
		wp_set_object_terms( $laptop->get_id(), $electronics_cat_id, 'product_cat' );
		wp_set_object_terms( $phone->get_id(), $electronics_cat_id, 'product_cat' );
		wp_set_object_terms( $tshirt->get_id(), $clothing_cat_id, 'product_cat' );
		// unassigned_product has no category.

		// Test filtering by Electronics category - Frontend.
		$merged_query = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => array( $electronics_cat_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query             = new WP_Query( $merged_query );
		$found_product_ids = wp_list_pluck( $query->posts, 'ID' );

		// Should return laptop and phone (both in Electronics category).
		$this->assertContains( $laptop->get_id(), $found_product_ids );
		$this->assertContains( $phone->get_id(), $found_product_ids );
		$this->assertNotContains( $tshirt->get_id(), $found_product_ids );
		$this->assertNotContains( $unassigned_product->get_id(), $found_product_ids );

		// Test filtering by Electronics category - Editor.
		$args    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_cat',
					'terms'            => array( $electronics_cat_id ),
					'include_children' => false,
				),
			),
		);
		$request = Utils::build_request();

		$updated_query    = $this->block_instance->update_rest_query_in_editor( $args, $request );
		$editor_query     = new WP_Query( $updated_query );
		$editor_found_ids = wp_list_pluck( $editor_query->posts, 'ID' );

		// Should return laptop and phone in editor as well.
		$this->assertContains( $laptop->get_id(), $editor_found_ids );
		$this->assertContains( $phone->get_id(), $editor_found_ids );
		$this->assertNotContains( $tshirt->get_id(), $editor_found_ids );
		$this->assertNotContains( $unassigned_product->get_id(), $editor_found_ids );

		// Test filtering by Clothing category.
		$merged_query_clothing = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => array( $clothing_cat_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query_clothing     = new WP_Query( $merged_query_clothing );
		$found_clothing_ids = wp_list_pluck( $query_clothing->posts, 'ID' );

		// Should return only t-shirt.
		$this->assertNotContains( $laptop->get_id(), $found_clothing_ids );
		$this->assertNotContains( $phone->get_id(), $found_clothing_ids );
		$this->assertContains( $tshirt->get_id(), $found_clothing_ids );
		$this->assertNotContains( $unassigned_product->get_id(), $found_clothing_ids );

		// Test filtering by Clothing category - Editor.
		$args_clothing    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_cat',
					'terms'            => array( $clothing_cat_id ),
					'include_children' => false,
				),
			),
		);
		$request_clothing = Utils::build_request();

		$updated_query_clothing = $this->block_instance->update_rest_query_in_editor( $args_clothing, $request_clothing );
		$editor_query_clothing  = new WP_Query( $updated_query_clothing );
		$editor_clothing_ids    = wp_list_pluck( $editor_query_clothing->posts, 'ID' );

		// Should return only t-shirt in editor as well.
		$this->assertNotContains( $laptop->get_id(), $editor_clothing_ids );
		$this->assertNotContains( $phone->get_id(), $editor_clothing_ids );
		$this->assertContains( $tshirt->get_id(), $editor_clothing_ids );
		$this->assertNotContains( $unassigned_product->get_id(), $editor_clothing_ids );

		$laptop->delete();
		$phone->delete();
		$tshirt->delete();
		$unassigned_product->delete();
		wp_delete_term( $electronics_cat_id, 'product_cat' );
		wp_delete_term( $clothing_cat_id, 'product_cat' );
	}

	/**
	 * Tests that the by-tag collection handler works as expected.
	 */
	public function test_collection_by_tag() {
		// Create test tags.
		$featured_tag    = wp_create_term( 'Featured', 'product_tag' );
		$featured_tag_id = $featured_tag['term_id'];

		$sale_tag    = wp_create_term( 'Sale', 'product_tag' );
		$sale_tag_id = $sale_tag['term_id'];

		// Create test products.
		$featured_product = WC_Helper_Product::create_simple_product();
		$featured_product->set_name( 'Featured Product' );
		$featured_product->save();

		$sale_product = WC_Helper_Product::create_simple_product();
		$sale_product->set_name( 'Sale Product' );
		$sale_product->save();

		$regular_product = WC_Helper_Product::create_simple_product();
		$regular_product->set_name( 'Regular Product' );
		$regular_product->save();

		// Assign products to tags.
		wp_set_object_terms( $featured_product->get_id(), $featured_tag_id, 'product_tag' );
		wp_set_object_terms( $sale_product->get_id(), $sale_tag_id, 'product_tag' );
		// regular_product has no tags.

		// Test filtering by Featured tag - Frontend.
		$merged_query = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_tag',
						'terms'            => array( $featured_tag_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query             = new WP_Query( $merged_query );
		$found_product_ids = wp_list_pluck( $query->posts, 'ID' );

		// Should return only featured product.
		$this->assertContains( $featured_product->get_id(), $found_product_ids );
		$this->assertNotContains( $sale_product->get_id(), $found_product_ids );
		$this->assertNotContains( $regular_product->get_id(), $found_product_ids );

		// Test filtering by Featured tag - Editor.
		$args    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_tag',
					'terms'            => array( $featured_tag_id ),
					'include_children' => false,
				),
			),
		);
		$request = Utils::build_request();

		$updated_query    = $this->block_instance->update_rest_query_in_editor( $args, $request );
		$editor_query     = new WP_Query( $updated_query );
		$editor_found_ids = wp_list_pluck( $editor_query->posts, 'ID' );

		// Should return only featured product in editor as well.
		$this->assertContains( $featured_product->get_id(), $editor_found_ids );
		$this->assertNotContains( $sale_product->get_id(), $editor_found_ids );
		$this->assertNotContains( $regular_product->get_id(), $editor_found_ids );

		// Test filtering by Sale tag - Frontend.
		$merged_query_sale = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_tag',
						'terms'            => array( $sale_tag_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query_sale     = new WP_Query( $merged_query_sale );
		$found_sale_ids = wp_list_pluck( $query_sale->posts, 'ID' );

		// Should return only sale product.
		$this->assertNotContains( $featured_product->get_id(), $found_sale_ids );
		$this->assertContains( $sale_product->get_id(), $found_sale_ids );
		$this->assertNotContains( $regular_product->get_id(), $found_sale_ids );

		// Test filtering by Sale tag - Editor.
		$args_sale    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_tag',
					'terms'            => array( $sale_tag_id ),
					'include_children' => false,
				),
			),
		);
		$request_sale = Utils::build_request();

		$updated_query_sale = $this->block_instance->update_rest_query_in_editor( $args_sale, $request_sale );
		$editor_query_sale  = new WP_Query( $updated_query_sale );
		$editor_sale_ids    = wp_list_pluck( $editor_query_sale->posts, 'ID' );

		// Should return only sale product in editor as well.
		$this->assertNotContains( $featured_product->get_id(), $editor_sale_ids );
		$this->assertContains( $sale_product->get_id(), $editor_sale_ids );
		$this->assertNotContains( $regular_product->get_id(), $editor_sale_ids );

		$featured_product->delete();
		$sale_product->delete();
		$regular_product->delete();
		wp_delete_term( $featured_tag_id, 'product_tag' );
		wp_delete_term( $sale_tag_id, 'product_tag' );
	}

	/**
	 * Tests that the by-brand collection handler works as expected.
	 */
	public function test_collection_by_brand() {
		// Create test brands.
		$nike_brand    = wp_create_term( 'Nike', 'product_brand' );
		$nike_brand_id = $nike_brand['term_id'];

		$adidas_brand    = wp_create_term( 'Adidas', 'product_brand' );
		$adidas_brand_id = $adidas_brand['term_id'];

		// Create test products.
		$nike_shoes = WC_Helper_Product::create_simple_product();
		$nike_shoes->set_name( 'Nike Shoes' );
		$nike_shoes->save();

		$nike_shirt = WC_Helper_Product::create_simple_product();
		$nike_shirt->set_name( 'Nike Shirt' );
		$nike_shirt->save();

		$adidas_shoes = WC_Helper_Product::create_simple_product();
		$adidas_shoes->set_name( 'Adidas Shoes' );
		$adidas_shoes->save();

		$unbranded_product = WC_Helper_Product::create_simple_product();
		$unbranded_product->set_name( 'Unbranded Product' );
		$unbranded_product->save();

		// Assign products to brands.
		wp_set_object_terms( $nike_shoes->get_id(), $nike_brand_id, 'product_brand' );
		wp_set_object_terms( $nike_shirt->get_id(), $nike_brand_id, 'product_brand' );
		wp_set_object_terms( $adidas_shoes->get_id(), $adidas_brand_id, 'product_brand' );
		// unbranded_product has no brand.

		// Test filtering by Nike brand - Frontend.
		$merged_query = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_brand',
						'terms'            => array( $nike_brand_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query             = new WP_Query( $merged_query );
		$found_product_ids = wp_list_pluck( $query->posts, 'ID' );

		// Should return Nike shoes and Nike shirt.
		$this->assertContains( $nike_shoes->get_id(), $found_product_ids );
		$this->assertContains( $nike_shirt->get_id(), $found_product_ids );
		$this->assertNotContains( $adidas_shoes->get_id(), $found_product_ids );
		$this->assertNotContains( $unbranded_product->get_id(), $found_product_ids );

		// Test filtering by Nike brand - Editor.
		$args    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_brand',
					'terms'            => array( $nike_brand_id ),
					'include_children' => false,
				),
			),
		);
		$request = Utils::build_request();

		$updated_query    = $this->block_instance->update_rest_query_in_editor( $args, $request );
		$editor_query     = new WP_Query( $updated_query );
		$editor_found_ids = wp_list_pluck( $editor_query->posts, 'ID' );

		// Should return Nike shoes and Nike shirt in editor as well.
		$this->assertContains( $nike_shoes->get_id(), $editor_found_ids );
		$this->assertContains( $nike_shirt->get_id(), $editor_found_ids );
		$this->assertNotContains( $adidas_shoes->get_id(), $editor_found_ids );
		$this->assertNotContains( $unbranded_product->get_id(), $editor_found_ids );

		// Test filtering by Adidas brand - Frontend.
		$merged_query_adidas = Utils::initialize_merged_query(
			$this->block_instance,
			null,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query' => array(
					array(
						'taxonomy'         => 'product_brand',
						'terms'            => array( $adidas_brand_id ),
						'include_children' => false,
					),
				),
			)
		);

		$query_adidas     = new WP_Query( $merged_query_adidas );
		$found_adidas_ids = wp_list_pluck( $query_adidas->posts, 'ID' );

		// Should return only Adidas shoes.
		$this->assertNotContains( $nike_shoes->get_id(), $found_adidas_ids );
		$this->assertNotContains( $nike_shirt->get_id(), $found_adidas_ids );
		$this->assertContains( $adidas_shoes->get_id(), $found_adidas_ids );
		$this->assertNotContains( $unbranded_product->get_id(), $found_adidas_ids );

		// Test filtering by Adidas brand - Editor.
		$args_adidas    = array(
			'posts_per_page' => 10,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_brand',
					'terms'            => array( $adidas_brand_id ),
					'include_children' => false,
				),
			),
		);
		$request_adidas = Utils::build_request();

		$updated_query_adidas = $this->block_instance->update_rest_query_in_editor( $args_adidas, $request_adidas );
		$editor_query_adidas  = new WP_Query( $updated_query_adidas );
		$editor_adidas_ids    = wp_list_pluck( $editor_query_adidas->posts, 'ID' );

		// Should return only Adidas shoes in editor as well.
		$this->assertNotContains( $nike_shoes->get_id(), $editor_adidas_ids );
		$this->assertNotContains( $nike_shirt->get_id(), $editor_adidas_ids );
		$this->assertContains( $adidas_shoes->get_id(), $editor_adidas_ids );
		$this->assertNotContains( $unbranded_product->get_id(), $editor_adidas_ids );

		$nike_shoes->delete();
		$nike_shirt->delete();
		$adidas_shoes->delete();
		$unbranded_product->delete();
		wp_delete_term( $nike_brand_id, 'product_brand' );
		wp_delete_term( $adidas_brand_id, 'product_brand' );
	}
}
