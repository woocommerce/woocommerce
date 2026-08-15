<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
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
		parent::setUp();
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
	 * @testdox Rating filters return the matching published product identity.
	 */
	public function test_filter_by_rating_returns_exact_products(): void {
		$had_query_var            = array_key_exists( 'rating_filter', $GLOBALS['wp_query']->query_vars );
		$previous_query_var       = get_query_var( 'rating_filter' );
		$matching_product         = null;
		$non_matching_product     = null;
		$product_ids_with_reviews = array();

		try {
			$fixtures = new FixtureData();

			$matching_product = $fixtures->get_simple_product(
				array(
					'name'          => 'One-star query target',
					'regular_price' => '10',
					'status'        => 'publish',
				)
			);
			$fixtures->add_product_review( $matching_product->get_id(), 1 );
			$product_ids_with_reviews[] = $matching_product->get_id();

			$non_matching_product = $fixtures->get_simple_product(
				array(
					'name'          => 'Five-star query distractor',
					'regular_price' => '10',
					'status'        => 'publish',
				)
			);
			$fixtures->add_product_review( $non_matching_product->get_id(), 5 );
			$product_ids_with_reviews[] = $non_matching_product->get_id();

			set_query_var( 'rating_filter', '1' );

			$merged_query      = Utils::initialize_merged_query( $this->block_instance );
			$query             = new WP_Query( $merged_query );
			$found_product_ids = wp_list_pluck( $query->posts, 'ID' );

			$this->assertContains( $matching_product->get_id(), $found_product_ids, 'The one-star target should be returned.' );
			$this->assertNotContains( $non_matching_product->get_id(), $found_product_ids, 'The five-star distractor should be excluded.' );
		} finally {
			if ( $had_query_var ) {
				set_query_var( 'rating_filter', $previous_query_var );
			} else {
				unset( $GLOBALS['wp_query']->query_vars['rating_filter'] );
			}

			foreach ( $product_ids_with_reviews as $product_id ) {
				/** @var int[] $review_ids */
				$review_ids = get_comments(
					array(
						'fields'  => 'ids',
						'post_id' => $product_id,
						'type'    => 'review',
					)
				);
				foreach ( $review_ids as $review_id ) {
					wp_delete_comment( $review_id, true );
				}
				\WC_Comments::clear_transients( $product_id );
			}

			if ( $matching_product instanceof \WC_Product ) {
				$matching_product->delete( true );
			}
			if ( $non_matching_product instanceof \WC_Product ) {
				$non_matching_product->delete( true );
			}
		}
	}

	/**
	 * @testdox Stock filters return the matching published product identity.
	 */
	public function test_filter_by_stock_status_returns_exact_products(): void {
		$had_query_var        = array_key_exists( 'filter_stock_status', $GLOBALS['wp_query']->query_vars );
		$previous_query_var   = get_query_var( 'filter_stock_status' );
		$matching_product     = null;
		$non_matching_product = null;

		try {
			$fixtures = new FixtureData();

			$matching_product     = $fixtures->get_simple_product(
				array(
					'name'          => 'Out-of-stock query target',
					'regular_price' => '10',
					'status'        => 'publish',
					'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				)
			);
			$non_matching_product = $fixtures->get_simple_product(
				array(
					'name'          => 'In-stock query distractor',
					'regular_price' => '10',
					'status'        => 'publish',
					'stock_status'  => ProductStockStatus::IN_STOCK,
				)
			);

			set_query_var( 'filter_stock_status', ProductStockStatus::OUT_OF_STOCK );

			$merged_query      = Utils::initialize_merged_query( $this->block_instance );
			$query             = new WP_Query( $merged_query );
			$found_product_ids = wp_list_pluck( $query->posts, 'ID' );

			$this->assertContains( $matching_product->get_id(), $found_product_ids, 'The out-of-stock target should be returned.' );
			$this->assertNotContains( $non_matching_product->get_id(), $found_product_ids, 'The in-stock distractor should be excluded.' );
		} finally {
			if ( $had_query_var ) {
				set_query_var( 'filter_stock_status', $previous_query_var );
			} else {
				unset( $GLOBALS['wp_query']->query_vars['filter_stock_status'] );
			}

			if ( $matching_product instanceof \WC_Product ) {
				$matching_product->delete( true );
			}
			if ( $non_matching_product instanceof \WC_Product ) {
				$non_matching_product->delete( true );
			}
		}
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
	 * @testdox Should use a deterministic random seed for frontend random sorting.
	 */
	public function test_random_sorting_uses_deterministic_seed(): void {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['queryId']          = 53919;
		$parsed_block['attrs']['query']['orderBy'] = 'random';

		$first_merged_query  = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
		$second_merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertMatchesRegularExpression(
			'/^RAND\([1-9][0-9]*\)$/',
			$first_merged_query['orderby'],
			'Random sorting should use a seeded random order.'
		);
		$this->assertSame(
			$first_merged_query['orderby'],
			$second_merged_query['orderby'],
			'Random sorting should use the same seed for the same Product Collection query.'
		);
	}

	/**
	 * @testdox Should include the Product Collection query ID in the random seed.
	 */
	public function test_random_sorting_seed_uses_query_id(): void {
		$first_parsed_block                              = Utils::get_base_parsed_block();
		$first_parsed_block['attrs']['queryId']          = 53919;
		$first_parsed_block['attrs']['query']['orderBy'] = 'random';

		$second_parsed_block                     = $first_parsed_block;
		$second_parsed_block['attrs']['queryId'] = 53920;

		$first_merged_query  = Utils::initialize_merged_query( $this->block_instance, $first_parsed_block );
		$second_merged_query = Utils::initialize_merged_query( $this->block_instance, $second_parsed_block );

		$this->assertNotSame(
			$first_merged_query['orderby'],
			$second_merged_query['orderby'],
			'Random sorting should use a different seed for different Product Collection query IDs.'
		);
	}

	/**
	 * @testdox Should include the daily rotation key in the random seed.
	 */
	public function test_random_sorting_seed_uses_rotation_key(): void {
		$query_context = array(
			'orderby' => 'random',
		);
		$rotation_key  = '2026-06-03';
		$wp_date_mock  = static function ( $date, $format ) use ( &$rotation_key ) {
			return 'Y-m-d' === $format ? $rotation_key : $date;
		};

		add_filter( 'wp_date', $wp_date_mock, 10, 2 );

		try {
			$first_seed   = ProductCollectionUtils::get_random_order_seed( 53919, $query_context );
			$rotation_key = '2026-06-04';
			$second_seed  = ProductCollectionUtils::get_random_order_seed( 53919, $query_context );

			$this->assertNotSame(
				$first_seed,
				$second_seed,
				'Random sorting should use a different seed when the daily rotation key changes.'
			);
		} finally {
			remove_filter( 'wp_date', $wp_date_mock, 10 );
		}
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

	/**
	 * Test merging filter queries by Category Slug (e.g. ?categories=accessories).
	 */
	public function test_merging_filter_by_category_slug() {
		// Set the URL query variables.
		set_query_var( 'categories', 'accessories' );

		// Execute the query builder.
		$merged_query   = Utils::initialize_merged_query( $this->block_instance );
		$filter_clauses = $this->extract_filter_clauses( $merged_query['tax_query'] );

		// Assertions.
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array( 'accessories' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should contain correct product_cat tax query using slug.'
		);

		// Clean up.
		set_query_var( 'categories', '' );
	}

	/**
	 * Test merging filter queries specifically for Tags.
	 * Scenario: ?tags=tag-new (Slug)
	 */
	public function test_merging_filter_by_tags() {
		// Set the URL query variables.
		set_query_var( 'tags', 'tag-new' );

		// Execute the query builder.
		$merged_query   = Utils::initialize_merged_query( $this->block_instance );
		$filter_clauses = $this->extract_filter_clauses( $merged_query['tax_query'] );

		// Assertions.
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => array( 'tag-new' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should contain correct product_tag tax query with IN operator.'
		);

		// Clean up.
		set_query_var( 'tags', '' );
	}

	/**
	 * Test merging filter queries for Categories, Tags, and Brands simultaneously.
	 * Scenario: ?categories=accessories&tags=tag-new&brands=nike
	 */
	public function test_merging_filter_by_all_taxonomies_together() {
		// Set the URL query variables.
		set_query_var( 'categories', 'accessories' );
		set_query_var( 'tags', 'tag-new' );
		set_query_var( 'brands', 'nike' );

		// Execute the query builder.
		$merged_query   = Utils::initialize_merged_query( $this->block_instance );
		$filter_clauses = $this->extract_filter_clauses( $merged_query['tax_query'] );

		// Assertions.
		// Verify Category.
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array( 'accessories' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should contain correct product_cat tax query.'
		);

		// Verify Tag.
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => array( 'tag-new' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should contain correct product_tag tax query.'
		);

		// Verify Brand.
		$this->assertContainsEquals(
			array(
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => array( 'nike' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should contain correct product_brand tax query.'
		);

		// Clean up global state.
		set_query_var( 'categories', '' );
		set_query_var( 'tags', '' );
		set_query_var( 'brands', '' );
	}

	/**
	 * Test that the strictly string-based filter logic works and SAFELY ignores arrays.
	 * Matches logic: if ( ! is_string($param_value) ) continue;
	 */
	public function test_filter_strict_string_handling() {
		// Scenario: Array Input (Should be IGNORED).
		// ?categories[]=hats.
		set_query_var( 'categories', array( 'hats' ) );

		// Execute.
		$merged_query   = Utils::initialize_merged_query( $this->block_instance );
		$tax_queries    = $merged_query['tax_query'] ?? array();
		$filter_clauses = $this->extract_filter_clauses( $tax_queries );

		// Assertion: The array input should have been ignored.
		$this->assertNotContainsEquals(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array( 'hats' ),
				'operator' => 'IN',
			),
			$filter_clauses,
			'Should not contain product_cat tax query because array input should be ignored.'
		);

		// Clean up.
		set_query_var( 'categories', '' );
	}

	/**
	 * Helper to extract filter clauses from the tax_query array.
	 *
	 * @param array $tax_queries The tax_query array from the merged query.
	 * @return array The extracted filter clauses.
	 */
	private function extract_filter_clauses( array $tax_queries ) {

		$and_query = array();

		// Find the 'AND' relation group where filters are stored.
		foreach ( $tax_queries as $tax_query ) {
			if ( isset( $tax_query['relation'] ) && 'AND' === $tax_query['relation'] ) {
				$and_query = $tax_query;
				break;
			}
		}

		$clauses = array();
		if ( ! empty( $and_query ) ) {
			foreach ( $and_query as $item ) {
				if ( is_array( $item ) ) {
					$clauses[] = $item;
				}
			}
		}

		return $clauses;
	}

	/**
	 * @testdox Empty string values for perPage and offset fall back to defaults.
	 */
	public function test_per_page_and_offset_empty_string_handling() {
		$parsed_block = Utils::get_base_parsed_block();

		// Set values as empty strings.
		$parsed_block['attrs']['query']['perPage'] = '';
		$parsed_block['attrs']['query']['offset']  = '';

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		$this->assertSame( 0, $merged_query['offset'] );
		$this->assertSame( 9, $merged_query['posts_per_page'] );
	}

	/**
	 * @testdox Inspector query controls return exact product identities through the real Controller to QueryBuilder path.
	 *
	 * @dataProvider inspector_query_result_provider
	 *
	 * @param string   $scenario Inspector query scenario.
	 * @param string[] $expected Expected product identities in order.
	 */
	public function test_inspector_query_result( string $scenario, array $expected ): void {
		$products              = array();
		$terms                 = array();
		$registered_taxonomies = array();

		try {
			$fixtures   = new FixtureData();
			$products[] = $fixtures->get_simple_product(
				array(
					'name'          => 'Alpha inspector target',
					'regular_price' => '10',
					'status'        => 'publish',
					'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				)
			);
			$products[] = $fixtures->get_simple_product(
				array(
					'name'          => 'Zulu inspector distractor',
					'regular_price' => '15',
					'status'        => 'publish',
					'stock_status'  => ProductStockStatus::IN_STOCK,
				)
			);
			$products[] = $fixtures->get_simple_product(
				array(
					'name'          => 'Below inspector distractor',
					'regular_price' => '5',
					'status'        => 'publish',
					'stock_status'  => ProductStockStatus::IN_STOCK,
				)
			);

			$product_ids = array_map(
				static function ( \WC_Product $product ): int {
					return $product->get_id();
				},
				$products
			);
			$query       = array(
				'post__in' => $product_ids,
			);
			$base_query  = array();

			switch ( $scenario ) {
				case 'category':
				case 'tag':
				case 'brand':
					$taxonomy = array(
						'category' => 'product_cat',
						'tag'      => 'product_tag',
						'brand'    => 'product_brand',
					)[ $scenario ];
					if ( ! taxonomy_exists( $taxonomy ) ) {
						register_taxonomy( $taxonomy, 'product' );
						$registered_taxonomies[] = $taxonomy;
					}
					$term = wp_insert_term( "Inspector {$scenario} target", $taxonomy );
					if ( is_wp_error( $term ) ) {
						throw new \RuntimeException( $term->get_error_message() );
					}
					$term_id = (int) $term['term_id'];
					$terms[] = array( $term_id, $taxonomy );
					wp_set_object_terms( $product_ids[0], array( $term_id ), $taxonomy );
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					$base_query['tax_query'] = array(
						array(
							'field'    => 'term_id',
							'taxonomy' => $taxonomy,
							'terms'    => array( $term_id ),
						),
					);
					break;
				case 'attributes':
					foreach ( array( 'pa_inspector_color', 'pa_inspector_size' ) as $taxonomy ) {
						register_taxonomy( $taxonomy, 'product' );
						$registered_taxonomies[] = $taxonomy;

						$term = wp_insert_term( "{$taxonomy} target", $taxonomy );
						if ( is_wp_error( $term ) ) {
							throw new \RuntimeException( $term->get_error_message() );
						}
						$term_id = (int) $term['term_id'];
						$terms[] = array( $term_id, $taxonomy );
						wp_set_object_terms( $product_ids[0], array( $term_id ), $taxonomy );
						if ( 'pa_inspector_color' === $taxonomy ) {
							wp_set_object_terms( $product_ids[1], array( $term_id ), $taxonomy );
						}
						$query['woocommerceAttributes'][] = array(
							'taxonomy' => $taxonomy,
							'termId'   => $term_id,
						);
					}
					break;
				case 'stock':
					$query['woocommerceStockStatus'] = array( ProductStockStatus::OUT_OF_STOCK );
					break;
				case 'minimum-price':
					$query['priceRange'] = array(
						'min' => 10,
					);
					$query['orderBy']    = 'title';
					$query['order']      = 'asc';
					break;
				case 'maximum-price':
					$query['priceRange'] = array(
						'max' => 10,
					);
					$query['orderBy']    = 'title';
					$query['order']      = 'asc';
					break;
				case 'inclusive-price':
					$query['priceRange'] = array(
						'min' => 10,
						'max' => 10,
					);
					$query['orderBy']    = 'title';
					$query['order']      = 'asc';
					break;
				case 'created-before':
				case 'created-within':
					wp_update_post(
						array(
							'ID'            => $product_ids[0],
							'post_date'     => '2024-01-01 00:00:00',
							'post_date_gmt' => '2024-01-01 00:00:00',
						)
					);
					wp_update_post(
						array(
							'ID'            => $product_ids[1],
							'post_date'     => '2024-02-01 00:00:00',
							'post_date_gmt' => '2024-02-01 00:00:00',
						)
					);
					$query['post__in']  = array( $product_ids[0], $product_ids[1] );
					$query['timeFrame'] = array(
						'operator' => 'created-before' === $scenario ? 'not-in' : 'in',
						'value'    => '2024-01-15 00:00:00',
					);
					break;
				case 'hand-picked':
					unset( $query['post__in'] );
					wp_update_post(
						array(
							'ID'            => $product_ids[0],
							'post_date'     => '2024-03-01 00:00:00',
							'post_date_gmt' => '2024-03-01 00:00:00',
						)
					);
					wp_update_post(
						array(
							'ID'            => $product_ids[1],
							'post_date'     => '2024-01-01 00:00:00',
							'post_date_gmt' => '2024-01-01 00:00:00',
						)
					);
					$query['woocommerceHandPickedProducts'] = array( $product_ids[1], $product_ids[0] );
					$query['orderBy']                       = 'post__in';
					break;
				case 'title-descending':
					wp_update_post(
						array(
							'ID'            => $product_ids[0],
							'post_date'     => '2024-03-01 00:00:00',
							'post_date_gmt' => '2024-03-01 00:00:00',
						)
					);
					wp_update_post(
						array(
							'ID'            => $product_ids[1],
							'post_date'     => '2024-01-01 00:00:00',
							'post_date_gmt' => '2024-01-01 00:00:00',
						)
					);
					$query['post__in'] = array( $product_ids[0], $product_ids[1] );
					$query['orderBy']  = 'title';
					$query['order']    = 'desc';
					break;
			}

			$parsed_block = Utils::get_base_parsed_block();

			$parsed_block['attrs']['query'] = array_merge( $parsed_block['attrs']['query'], $query );

			$parsed_block['attrs']['query']['inherit'] = false;

			$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block, $base_query );

			$result = new WP_Query( $merged_query );

			$product_names = wp_list_pluck( $result->posts, 'post_title' );

			$this->assertSame( $expected, $product_names, "Unexpected product order for {$scenario}." );
		} finally {
			foreach ( $products as $product ) {
				$product->delete( true );
			}
			foreach ( $terms as list( $term_id, $taxonomy ) ) {
				wp_delete_term( $term_id, $taxonomy );
			}
			foreach ( $registered_taxonomies as $taxonomy ) {
				unregister_taxonomy( $taxonomy );
			}
		}
	}

	/**
	 * Inspector query result cases.
	 *
	 * @return array<string, array{string, string[]}>
	 */
	public function inspector_query_result_provider(): array {
		return array(
			'category'         => array( 'category', array( 'Alpha inspector target' ) ),
			'tag'              => array( 'tag', array( 'Alpha inspector target' ) ),
			'brand'            => array( 'brand', array( 'Alpha inspector target' ) ),
			'combined attrs'   => array( 'attributes', array( 'Alpha inspector target' ) ),
			'stock'            => array( 'stock', array( 'Alpha inspector target' ) ),
			'minimum price'    => array( 'minimum-price', array( 'Alpha inspector target', 'Zulu inspector distractor' ) ),
			'maximum price'    => array( 'maximum-price', array( 'Alpha inspector target', 'Below inspector distractor' ) ),
			'inclusive price'  => array( 'inclusive-price', array( 'Alpha inspector target' ) ),
			'created before'   => array( 'created-before', array( 'Alpha inspector target' ) ),
			'created within'   => array( 'created-within', array( 'Zulu inspector distractor' ) ),
			'hand picked'      => array( 'hand-picked', array( 'Zulu inspector distractor', 'Alpha inspector target' ) ),
			'title descending' => array( 'title-descending', array( 'Zulu inspector distractor', 'Alpha inspector target' ) ),
		);
	}

	/**
	 * @testdox Inspector pagination controls produce nonempty page-one and page-two windows through the Controller page argument.
	 *
	 * @dataProvider inspector_pagination_result_provider
	 *
	 * @param int      $page Requested page.
	 * @param int      $expected_offset Expected query offset.
	 * @param string[] $expected_names Expected product names.
	 */
	public function test_inspector_pagination_result( int $page, int $expected_offset, array $expected_names ): void {
		$products = array();

		try {
			$fixtures = new FixtureData();
			foreach ( array( 'Alpha', 'Beta', 'Gamma', 'Kappa', 'Omega' ) as $name ) {
				$products[] = $fixtures->get_simple_product(
					array(
						'name'          => "{$name} pagination product",
						'regular_price' => '10',
						'status'        => 'publish',
					)
				);
			}

			$parsed_block = Utils::get_base_parsed_block();

			$parsed_block['attrs']['query']['inherit'] = false;

			$parsed_block['attrs']['query']['orderBy'] = 'title';

			$parsed_block['attrs']['query']['order'] = 'asc';

			$parsed_block['attrs']['query']['perPage'] = 2;

			$parsed_block['attrs']['query']['offset'] = 1;

			$parsed_block['attrs']['query']['post__in'] = array_map(
				static function ( \WC_Product $product ): int {
					return $product->get_id();
				},
				$products
			);

			$this->block_instance->set_parsed_block( $parsed_block );
			$block          = new \stdClass();
			$block->context = $parsed_block['attrs'];

			$merged_query = $this->block_instance->build_frontend_query( array(), $block, $page );

			$result = new WP_Query( $merged_query );

			$this->assertSame( 2, $merged_query['posts_per_page'], 'The requested page size should reach WP_Query.' );
			$this->assertSame( $expected_offset, $merged_query['offset'], "Page {$page} should use the cumulative inspector offset." );
			$this->assertSame( $expected_names, wp_list_pluck( $result->posts, 'post_title' ), "Page {$page} should contain the expected nonempty product window." );
		} finally {
			foreach ( $products as $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Inspector pagination result cases.
	 *
	 * @return array<string, array{int, int, string[]}>
	 */
	public function inspector_pagination_result_provider(): array {
		return array(
			'page one' => array( 1, 1, array( 'Beta pagination product', 'Gamma pagination product' ) ),
			'page two' => array( 2, 3, array( 'Kappa pagination product', 'Omega pagination product' ) ),
		);
	}
}
