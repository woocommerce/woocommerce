<?php
/**
 * Controller Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Product Reviews Controller Tests.
 */
class ProductReviews extends ControllerTestCase {

	/**
	 * Setup test review data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product_category = $fixtures->get_product_category(
			array(
				'name' => 'Test Category 1',
			)
		);

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'regular_price' => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'regular_price' => 100,
					'category_ids'  => array( $this->product_category['term_id'] ),
				)
			),
		);

		$fixtures->add_product_review( $this->products[0]->get_id(), 5 );
		$fixtures->add_product_review( $this->products[1]->get_id(), 4 );
	}

	/**
	 * Test getting reviews.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' ) );
		$data     = $response->get_data();

		// Assert correct response format.
		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 2, count( $data ), 'Unexpected item count.' );

		// Assert response items contain the correct properties.
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'date_created', $data[0] );
		$this->assertArrayHasKey( 'formatted_date_created', $data[0] );
		$this->assertArrayHasKey( 'date_created_gmt', $data[0] );
		$this->assertArrayHasKey( 'product_id', $data[0] );
		$this->assertArrayHasKey( 'product_name', $data[0] );
		$this->assertArrayHasKey( 'product_permalink', $data[0] );
		$this->assertArrayHasKey( 'product_image', $data[0] );
		$this->assertArrayHasKey( 'product_permalink', $data[0] );
		$this->assertArrayHasKey( 'reviewer', $data[0] );
		$this->assertArrayHasKey( 'review', $data[0] );
		$this->assertArrayHasKey( 'rating', $data[0] );
		$this->assertArrayHasKey( 'verified', $data[0] );
		$this->assertArrayHasKey( 'reviewer_avatar_urls', $data[0] );

		// Assert response items contain the correct review data.
		$this->assertSame( 'Test Product 2', $data[0]['product_name'] );
		$this->assertSame( 4, $data[0]['rating'] );
		$this->assertSame( 'Test Product 1', $data[1]['product_name'] );
		$this->assertSame( 5, $data[1]['rating'] );
	}

	/**
	 * Test getting reviews with specific order and per_page parameters.
	 */
	public function test_get_items_with_order_params() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'per_page', 1 );
		$request->set_param( 'orderby', 'rating' );
		$request->set_param( 'order', 'desc' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertCount( 1, $data, 'Unexpected item count.' );
		$this->assertSame( 5, $data[0]['rating'] );
	}

	/**
	 * Test getting reviews from a specific product.
	 */
	public function test_get_items_with_product_id_param() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'product_id', (string) $this->products[0]->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertCount( 1, $data, 'Unexpected item count.' );
		$this->assertSame( 5, $data[0]['rating'] );
	}

	/**
	 * Test getting reviews from a specific category.
	 */
	public function test_get_items_with_category_id_param() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'category_id', (string) $this->product_category['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertCount( 1, $data, 'Unexpected item count.' );
		$this->assertSame( 4, $data[0]['rating'] );
	}
}
