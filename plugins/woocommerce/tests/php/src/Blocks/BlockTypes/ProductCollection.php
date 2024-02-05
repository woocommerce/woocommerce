<?php
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;

/**
 * Tests for the ProductCollection block type
 */
class ProductCollection extends \WP_UnitTestCase {
	/**
	 * This variable holds our Product Query object.
	 *
	 * @var ProductCollectionMock
	 */
	private $block_instance;

	/**
	 * Return starting point for parsed block test data.
	 * Using a method instead of property to avoid sharing data between tests.
	 */
	private function get_base_parsed_block() {
		return array(
			'blockName' => 'woocommerce/product-collection',
			'attrs'     => array(
				'query' => array(
					'perPage'                  => 9,
					'pages'                    => 0,
					'offset'                   => 0,
					'postType'                 => 'product',
					'order'                    => 'desc',
					'orderBy'                  => 'date',
					'search'                   => '',
					'exclude'                  => array(),
					'sticky'                   => '',
					'inherit'                  => false,
					'isProductCollectionBlock' => true,
					'woocommerceAttributes'    => array(),
					'woocommerceStockStatus'   => array(
						'instock',
						'outofstock',
						'onbackorder',
					),
				),
			),
		);
	}

	/**
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		$this->block_instance = new ProductCollectionMock();
	}

	/**
	 * Build the merged_query for testing
	 *
	 * @param array $parsed_block Parsed block data.
	 */
	private function initialize_merged_query( $parsed_block = array() ) {
		if ( empty( $parsed_block ) ) {
			$parsed_block = $this->get_base_parsed_block();
		}

		$this->block_instance->set_parsed_block( $parsed_block );

		$block          = new \stdClass();
		$block->context = $parsed_block['attrs'];

		$query = build_query_vars_from_query_block( $block, 1 );

		return $this->block_instance->build_frontend_query( $query, $block, 1 );
	}

	/**
	 * Build a simplified request for testing.
	 *
	 * @param bool  $woocommerce_on_sale WooCommerce on sale.
	 * @param array $woocommerce_attributes WooCommerce attributes.
	 * @param array $woocommerce_stock_status WooCommerce stock status.
	 * @return WP_REST_Request
	 */
	private function build_request( $woocommerce_on_sale = 'false', $woocommerce_attributes = array(), $woocommerce_stock_status = array() ) {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/product' );
		$request->set_param( 'woocommerceOnSale', $woocommerce_on_sale );
		$request->set_param( 'woocommerceAttributes', $woocommerce_attributes );
		$request->set_param( 'woocommerceStockStatus', $woocommerce_stock_status );
		$request->set_param( 'isProductCollectionBlock', true );

		return $request;
	}

	/**
	 * Test merging on sale queries.
	 */
	public function test_merging_on_sale_queries() {
		// Mock the on sale product ids.
		$on_sale_product_ids = array( 1, 2, 3, 4 );
		set_transient( 'wc_products_onsale', $on_sale_product_ids, DAY_IN_SECONDS * 30 );

		$parsed_block                                        = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceOnSale'] = true;

		$merged_query = $this->initialize_merged_query( $parsed_block );

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
		$parsed_block = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			'outofstock',
			'onbackorder',
		);

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertContainsEquals(
			array(
				'value'   => array( 'outofstock', 'onbackorder' ),
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
		$parsed_block = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			'instock',
			'outofstock',
			'onbackorder',
		);

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertEmpty( $merged_query['meta_query'] );

		// Test with hide out of stock items option enabled.
		$parsed_block = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			'instock',
			'onbackorder',
		);

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertEmpty( $merged_query['meta_query'] );
	}

	/**
	 * Test merging attribute queries.
	 */
	public function test_merging_attribute_queries() {
		$parsed_block = $this->get_base_parsed_block();
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

		$merged_query = $this->initialize_merged_query( $parsed_block );

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
		$parsed_block                              = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'rating';

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertEquals( 'meta_value_num', $merged_query['orderby'] );
		$this->assertEquals( '_wc_average_rating', $merged_query['meta_key'] );
	}

	/**
	 * Test merging order by popularity queries.
	 */
	public function test_merging_order_by_popularity_queries() {
		$parsed_block                              = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'popularity';

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertEquals( 'meta_value_num', $merged_query['orderby'] );
		$this->assertEquals( 'total_sales', $merged_query['meta_key'] );
	}

	/**
	 * Test product visibility query exist in merged query.
	 */
	public function test_product_visibility_query_exist_in_merged_query() {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		$parsed_block = $this->get_base_parsed_block();

		$merged_query = $this->initialize_merged_query( $parsed_block );

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
		$parsed_block                              = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['orderBy'] = 'rating';
		$parsed_block['attrs']['query']['woocommerceStockStatus'] = array(
			'instock',
			'outofstock',
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

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->assertEquals( 'meta_value_num', $merged_query['orderby'] );
		$this->assertEquals( '_wc_average_rating', $merged_query['meta_key'] );
		$this->assertContainsEquals(
			array(
				'compare' => 'IN',
				'key'     => '_stock_status',
				'value'   => array( 'instock', 'outofstock' ),
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

		$merged_query = $this->initialize_merged_query();

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<',
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

		$merged_query = $this->initialize_merged_query();

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

		$merged_query = $this->initialize_merged_query();

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<',
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
		set_query_var( 'filter_stock_status', 'instock' );

		$merged_query = $this->initialize_merged_query();

		$this->assertContainsEquals(
			array(
				'operator' => 'IN',
				'key'      => '_stock_status',
				'value'    => array( 'instock' ),
			),
			$merged_query['meta_query']
		);

		set_query_var( 'filter_stock_status', '' );
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

		$merged_query = $this->initialize_merged_query();
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
		set_query_var( 'filter_stock_status', 'instock' );

		$merged_query = $this->initialize_merged_query();

		$this->assertContainsEquals(
			array(
				'operator' => 'IN',
				'key'      => '_stock_status',
				'value'    => array( 'instock' ),
			),
			$merged_query['meta_query']
		);

		$this->assertContainsEquals(
			array(
				array(
					'key'     => '_price',
					'value'   => 100,
					'compare' => '<',
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
		$parsed_block                               = $this->get_base_parsed_block();
		$parsed_block['attrs']['query']['taxQuery'] = array(
			'product_cat' => array( 1, 2 ),
			'product_tag' => array( 3, 4 ),
		);

		$merged_query = $this->initialize_merged_query( $parsed_block );

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
	 * Test merging multiple filter queries on Editor side
	 */
	public function test_updating_rest_query_without_attributes() {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		$args    = array();
		$request = $this->build_request();

		$updated_query = $this->block_instance->update_rest_query_in_editor( $args, $request );

		$this->assertContainsEquals(
			array(
				'key'     => '_stock_status',
				'value'   => array(),
				'compare' => 'IN',
			),
			$updated_query['meta_query'],
		);

		$this->assertEquals(
			array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_not_in,
					'operator' => 'NOT IN',
				),
			),
			$updated_query['tax_query'],
		);
	}

	/**
	 * Test merging multiple filter queries.
	 */
	public function test_updating_rest_query_with_attributes() {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		$args         = array();
		$on_sale      = 'true';
		$attributes   = array(
			array(
				'taxonomy' => 'pa_test',
				'termId'   => 1,
			),
		);
		$stock_status = array( 'instock', 'outofstock' );
		$request      = $this->build_request( $on_sale, $attributes, $stock_status );

		$updated_query = $this->block_instance->update_rest_query_in_editor( $args, $request );

		$this->assertContainsEquals(
			array(
				'key'     => '_stock_status',
				'value'   => array( 'instock', 'outofstock' ),
				'compare' => 'IN',
			),
			$updated_query['meta_query'],
		);

		$this->assertContains(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_not_in,
				'operator' => 'NOT IN',
			),
			$updated_query['tax_query'],
		);

		$this->assertContains(
			array(
				'field'    => 'term_id',
				'operator' => 'IN',
				'taxonomy' => 'pa_test',
				'terms'    => array( 1 ),
			),
			$updated_query['tax_query'],
		);
	}
}
