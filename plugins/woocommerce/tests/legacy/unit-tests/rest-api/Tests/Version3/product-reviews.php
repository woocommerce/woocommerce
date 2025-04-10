<?php

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Tests for the product reviews REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class WC_Tests_API_Product_Reviews extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/products/reviews', $routes );
		$this->assertArrayHasKey( '/wc/v3/products/reviews/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/products/reviews/batch', $routes );
	}

	/**
	 * Test getting all product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_reviews() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		// Create 10 products reviews for the product
		for ( $i = 0; $i < 10; $i++ ) {
			$review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		}

		$response        = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews' ) );
		$product_reviews = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $product_reviews ) );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'                   => $review_id,
					'date_created'         => $product_reviews[0]['date_created'],
					'date_created_gmt'     => $product_reviews[0]['date_created_gmt'],
					'product_id'           => $product->get_id(),
					'product_name'         => $product->get_name(),
					'product_permalink'    => $product->get_permalink(),
					'status'               => 'approved',
					'reviewer'             => 'admin',
					'reviewer_email'       => 'woo@woo.local',
					'review'               => "<p>Review content here</p>\n",
					'rating'               => 0,
					'verified'             => false,
					'reviewer_avatar_urls' => $product_reviews[0]['reviewer_avatar_urls'],
					'_links'               => array(
						'self'       => array(
							array(
								'href' => rest_url( '/wc/v3/products/reviews/' . $review_id ),
							),
						),
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/products/reviews' ),
							),
						),
						'up'         => array(
							array(
								'href' => rest_url( '/wc/v3/products/' . $product->get_id() ),
							),
						),
					),
				),
				$product_reviews[0]
			)
		);
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_reviews_without_permission() {
		wp_set_current_user( 0 );
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests to make sure an error is returned when an invalid product is loaded.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_reviews_invalid_product() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/0/reviews' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests getting a single product review.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_review() {
		wp_set_current_user( $this->user );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews/' . $product_review_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                   => $product_review_id,
				'date_created'         => $data['date_created'],
				'date_created_gmt'     => $data['date_created_gmt'],
				'product_id'           => $product->get_id(),
				'product_name'         => $product->get_name(),
				'product_permalink'    => $product->get_permalink(),
				'status'               => 'approved',
				'reviewer'             => 'admin',
				'reviewer_email'       => 'woo@woo.local',
				'review'               => "<p>Review content here</p>\n",
				'rating'               => 0,
				'verified'             => false,
				'reviewer_avatar_urls' => $data['reviewer_avatar_urls'],
			),
			$data
		);
	}

	/**
	 * Tests getting a single product review without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_review_without_permission() {
		wp_set_current_user( 0 );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$response          = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews/' . $product_review_id ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a product review with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating a product review.
	 *
	 * @since 3.5.0
	 */
	public function test_create_product_review() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'review'         => 'Hello world.',
				'reviewer'       => 'Admin',
				'reviewer_email' => 'woo@woo.local',
				'rating'         => '5',
				'product_id'     => $product->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                   => $data['id'],
				'date_created'         => $data['date_created'],
				'date_created_gmt'     => $data['date_created_gmt'],
				'product_id'           => $product->get_id(),
				'product_name'         => $product->get_name(),
				'product_permalink'    => $product->get_permalink(),
				'status'               => 'approved',
				'reviewer'             => 'Admin',
				'reviewer_email'       => 'woo@woo.local',
				'review'               => 'Hello world.',
				'rating'               => 5,
				'verified'             => false,
				'reviewer_avatar_urls' => $data['reviewer_avatar_urls'],
			),
			$data
		);
	}

	/**
	 * Tests creating a product review without required fields.
	 *
	 * @since 3.5.0
	 */
	public function test_create_product_review_invalid_fields() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		// missing review
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'reviewer'       => 'Admin',
				'reviewer_email' => 'woo@woo.local',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// Missing reviewer.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'review'         => 'Hello world.',
				'reviewer_email' => 'woo@woo.local',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// missing reviewer_email
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'review'   => 'Hello world.',
				'reviewer' => 'Admin',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Tests updating a product review.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_review() {
		wp_set_current_user( $this->user );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/reviews/' . $product_review_id ) );
		$data     = $response->get_data();
		$this->assertEquals( "<p>Review content here</p>\n", $data['review'] );
		$this->assertEquals( 'admin', $data['reviewer'] );
		$this->assertEquals( 'woo@woo.local', $data['reviewer_email'] );
		$this->assertEquals( 0, $data['rating'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $product_review_id );
		$request->set_body_params(
			array(
				'review'         => 'Hello world - updated.',
				'reviewer'       => 'Justin',
				'reviewer_email' => 'woo2@woo.local',
				'rating'         => 3,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'Hello world - updated.', $data['review'] );
		$this->assertEquals( 'Justin', $data['reviewer'] );
		$this->assertEquals( 'woo2@woo.local', $data['reviewer_email'] );
		$this->assertEquals( 3, $data['rating'] );
	}

	/**
	 * Tests updating a product review without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_review_without_permission() {
		wp_set_current_user( 0 );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $product_review_id );
		$request->set_body_params(
			array(
				'review'         => 'Hello world.',
				'reviewer'       => 'Admin',
				'reviewer_email' => 'woo@woo.dev',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests that updating a product review with an invalid id fails.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/0' );
		$request->set_body_params(
			array(
				'review'         => 'Hello world.',
				'reviewer'       => 'Admin',
				'reviewer_email' => 'woo@woo.dev',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test deleting a product review.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_review() {
		wp_set_current_user( $this->user );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/' . $product_review_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test deleting a product review without permission/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_without_permission() {
		wp_set_current_user( 0 );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/' . $product_review_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a product review with an invalid id.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product           = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_review_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_product_reviews_batch() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$review_1_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$review_2_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$review_3_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );
		$review_4_id = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'     => $review_1_id,
						'review' => 'Updated review.',
					),
				),
				'delete' => array(
					$review_2_id,
					$review_3_id,
				),
				'create' => array(
					array(
						'review'         => 'New review.',
						'reviewer'       => 'Justin',
						'reviewer_email' => 'woo3@woo.local',
						'product_id'     => $product->get_id(),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'Updated review.', $data['update'][0]['review'] );
		$this->assertEquals( 'New review.', $data['create'][0]['review'] );
		$this->assertEquals( $review_2_id, $data['delete'][0]['previous']['id'] );
		$this->assertEquals( $review_3_id, $data['delete'][1]['previous']['id'] );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/reviews' );
		$request->set_param( 'product', $product->get_id() );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * Test the product review schema.
	 *
	 * @since 3.5.0
	 */
	public function test_product_review_schema() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/products/reviews' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 13, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'product_name', $properties );
		$this->assertArrayHasKey( 'product_permalink', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'reviewer', $properties );
		$this->assertArrayHasKey( 'reviewer_email', $properties );
		$this->assertArrayHasKey( 'review', $properties );
		$this->assertArrayHasKey( 'rating', $properties );
		$this->assertArrayHasKey( 'verified', $properties );

		if ( get_option( 'show_avatars' ) ) {
			$this->assertArrayHasKey( 'reviewer_avatar_urls', $properties );
		}
	}
}
