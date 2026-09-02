<?php
/**
 * Controller Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStatus;

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
	 * @testdox Reviews are only returned for published products.
	 */
	public function test_reviews_are_limited_to_published_products() {
		$fixtures      = new FixtureData();
		$draft_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Draft Review Product',
				'regular_price' => 10,
			)
		);
		$fixtures->add_product_review( $draft_product->get_id(), 5, 'Hidden review' );

		// Product becomes non-public after the review exists.
		$draft_product->set_status( ProductStatus::DRAFT );
		$draft_product->save();

		$response    = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' ) );
		$product_ids = wp_list_pluck( $response->get_data(), 'product_id' );

		$this->assertSame( 200, $response->get_status() );
		// Reviews for the published products from setUp are still returned (regression)...
		$this->assertContains( $this->products[0]->get_id(), $product_ids );
		$this->assertContains( $this->products[1]->get_id(), $product_ids );
		// ...but the non-public product's review is excluded.
		$this->assertNotContains( $draft_product->get_id(), $product_ids );

		// A targeted query for the non-public product returns nothing.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'product_id', (string) $draft_product->get_id() );
		$targeted = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $targeted->get_status() );
		$this->assertCount( 0, $targeted->get_data() );
		$this->assertSame( 0, (int) $targeted->get_headers()['X-WP-Total'] );
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
	 * @testdox Reviews are returned in the requested sort order and respect offset.
	 * @dataProvider get_items_sort_and_offset_data
	 *
	 * @param string $orderby Review field used for sorting.
	 * @param string $order Sort direction.
	 * @param int    $offset Number of reviews to skip.
	 * @param array  $expected_contents Expected review contents in response order.
	 */
	public function test_get_items_sort_and_offset_matrix( string $orderby, string $order, int $offset, array $expected_contents ): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Review ordering product',
				'regular_price' => 20,
			)
		);

		$fixtures->add_product_review(
			$product->get_id(),
			1,
			'Oldest one-star review',
			array(
				'comment_date'     => '2024-01-01 09:00:00',
				'comment_date_gmt' => '2024-01-01 09:00:00',
			)
		);
		$fixtures->add_product_review(
			$product->get_id(),
			5,
			'Middle five-star review',
			array(
				'comment_date'     => '2024-01-02 09:00:00',
				'comment_date_gmt' => '2024-01-02 09:00:00',
			)
		);
		$fixtures->add_product_review(
			$product->get_id(),
			3,
			'Newest three-star review',
			array(
				'comment_date'     => '2024-01-03 09:00:00',
				'comment_date_gmt' => '2024-01-03 09:00:00',
			)
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'product_id', (string) $product->get_id() );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'orderby', $orderby );
		$request->set_param( 'order', $order );
		$request->set_param( 'offset', $offset );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame(
			array_map( 'wpautop', $expected_contents ),
			wp_list_pluck( $response->get_data(), 'review' ),
			'Reviews were not returned in the requested order.'
		);
		$this->assertSame( 3, $response->get_headers()['X-WP-Total'], 'Unexpected total review count.' );
	}

	/**
	 * Data provider for review sort and offset requests.
	 *
	 * @return array<string, array{string, string, int, string[]}>
	 */
	public function get_items_sort_and_offset_data(): array {
		return array(
			'recent'             => array(
				'date_gmt',
				'desc',
				0,
				array( 'Newest three-star review', 'Middle five-star review', 'Oldest one-star review' ),
			),
			'rating ascending'   => array(
				'rating',
				'asc',
				0,
				array( 'Oldest one-star review', 'Newest three-star review', 'Middle five-star review' ),
			),
			'rating descending'  => array(
				'rating',
				'desc',
				0,
				array( 'Middle five-star review', 'Newest three-star review', 'Oldest one-star review' ),
			),
			'recent with offset' => array(
				'date_gmt',
				'desc',
				1,
				array( 'Middle five-star review', 'Oldest one-star review' ),
			),
		);
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
