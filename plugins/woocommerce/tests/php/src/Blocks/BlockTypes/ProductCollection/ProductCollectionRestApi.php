<?php

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Helper_Product;
use WP_Query;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Tests for the ProductCollection block REST API integration
 *
 * @group rest-api
 */
class ProductCollectionRestApi extends \WP_UnitTestCase {
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
					'inherit'                  => true,
					'isProductCollectionBlock' => true,
					'woocommerceAttributes'    => array(),
					'woocommerceStockStatus'   => array(
						ProductStockStatus::IN_STOCK,
						ProductStockStatus::OUT_OF_STOCK,
						ProductStockStatus::ON_BACKORDER,
					),
				),
			),
		);
	}

	/**
	 * Build a simplified request for testing.
	 *
	 * @param array $params The parameters to set on the request.
	 * @return WP_REST_Request
	 */
	private function build_request( $params = array() ) {
		$params = wp_parse_args(
			$params,
			array(
				'featured'               => false,
				'woocommerceOnSale'      => false,
				'woocommerceAttributes'  => array(),
				'woocommerceStockStatus' => array(),
				'timeFrame'              => array(),
				'priceRange'             => array(),
			)
		);

		$params['isProductCollectionBlock'] = true;

		$request = new \WP_REST_Request( 'GET', '/wp/v2/product' );
		foreach ( $params as $param => $value ) {
			$request->set_param( $param, $value );
		}

		return $request;
	}

	/**
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		$this->block_instance = new ProductCollectionMock();
	}

	/**
	 * Test merging multiple filter queries on Editor side
	 */
	public function test_updating_rest_query_without_attributes() {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		$args    = array(
			'posts_per_page' => 9,
		);
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

		$args            = array(
			'posts_per_page' => 9,
		);
		$time_frame_date = gmdate( 'Y-m-d H:i:s' );
		$params          = array(
			'featured'               => 'true',
			'woocommerceOnSale'      => 'true',
			'woocommerceAttributes'  => array(
				array(
					'taxonomy' => 'pa_test',
					'termId'   => 1,
				),
			),
			'woocommerceStockStatus' => array( ProductStockStatus::IN_STOCK, ProductStockStatus::OUT_OF_STOCK ),
			'timeFrame'              => array(
				'operator' => 'in',
				'value'    => $time_frame_date,
			),
			'priceRange'             => array(
				'min' => 1,
				'max' => 100,
			),
		);

		$request = $this->build_request( $params );

		$updated_query = $this->block_instance->update_rest_query_in_editor( $args, $request );

		$this->assertContainsEquals(
			array(
				'key'     => '_stock_status',
				'value'   => array( ProductStockStatus::IN_STOCK, ProductStockStatus::OUT_OF_STOCK ),
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
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => 'IN',
			),
			$updated_query['tax_query'],
		);

		$this->assertContains(
			array(
				'column'    => 'post_date_gmt',
				'after'     => $time_frame_date,
				'inclusive' => true,
			),
			$updated_query['date_query'],
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

		$this->assertEquals(
			array(
				'min' => 1,
				'max' => 100,
			),
			$updated_query['priceRange'],
		);
	}
}
