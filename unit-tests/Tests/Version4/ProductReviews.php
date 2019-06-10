<?php
/**
 * Product Reviews REST API tests.
 *
 * @package WooCommerce/RestApi/Tests
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\AbstractRestApiTest;
use \WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Abstract Rest API Test Class
 *
 * @extends AbstractRestApiTest
 */
class ProductReviews extends AbstractRestApiTest {
	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/v4/products/reviews',
		'/wc/v4/products/reviews/(?P<id>[\d]+)',
		'/wc/v4/products/reviews/batch',
	];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [
		'id'                   => array( 'view', 'edit' ),
		'date_created'         => array( 'view', 'edit' ),
		'date_created_gmt'     => array( 'view', 'edit' ),
		'product_id'           => array( 'view', 'edit' ),
		'status'               => array( 'view', 'edit' ),
		'reviewer'             => array( 'view', 'edit' ),
		'reviewer_email'       => array( 'view', 'edit' ),
		'review'               => array( 'view', 'edit' ),
		'rating'               => array( 'view', 'edit' ),
		'verified'             => array( 'view', 'edit' ),
		'reviewer_avatar_urls' => array( 'view', 'edit' ),
	];

	/**
	 * Test creation using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_create() {
		$product = ProductHelper::create_simple_product();
		$data    = [
			'review'         => 'Hello world.',
			'reviewer'       => 'Admin',
			'reviewer_email' => 'woo@woo.local',
			'rating'         => '5',
			'product_id'     => $product->get_id(),
		];
		$response = $this->do_request( '/wc/v4/products/reviews', 'POST', $data );
		$this->assertExpectedResponse( $response, 201, $data );
		$this->assertEquals(
			array(
				'id'                   => $response->data['id'],
				'date_created'         => $response->data['date_created'],
				'date_created_gmt'     => $response->data['date_created_gmt'],
				'product_id'           => $product->get_id(),
				'status'               => 'approved',
				'reviewer'             => 'Admin',
				'reviewer_email'       => 'woo@woo.local',
				'review'               => 'Hello world.',
				'rating'               => 5,
				'verified'             => false,
				'reviewer_avatar_urls' => $response->data['reviewer_avatar_urls'],
			),
			$response->data
		);
	}

	/**
	 * Test get/read using this method.
	 */
	public function test_read() {
		$product = ProductHelper::create_simple_product();
		for ( $i = 0; $i < 10; $i++ ) {
			$review_id = ProductHelper::create_product_review( $product->get_id() );
		}

		// Invalid.
		$response = $this->do_request( '/wc/v4/products/0/reviews' );
		$this->assertExpectedResponse( $response, 404 );

		// Collections.
		$response        = $this->do_request( '/wc/v4/products/reviews' );
		$product_reviews = $response->data;

		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 10, count( $product_reviews ) );
		$this->assertContains(
			array(
				'id'                   => $review_id,
				'date_created'         => $product_reviews[0]['date_created'],
				'date_created_gmt'     => $product_reviews[0]['date_created_gmt'],
				'product_id'           => $product->get_id(),
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
							'href' => rest_url( '/wc/v4/products/reviews/' . $review_id ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v4/products/reviews' ),
						),
					),
					'up'         => array(
						array(
							'embeddable' => true,
							'href'       => rest_url( '/wc/v4/products/' . $product->get_id() ),
						),
					),
				),
			),
			$product_reviews
		);
	}

	/**
	 * Test updates using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_update() {
		$product           = ProductHelper::create_simple_product();
		$product_review_id = ProductHelper::create_product_review( $product->get_id() );

		$response          = $this->do_request( '/wc/v4/products/reviews/' . $product_review_id );
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( "<p>Review content here</p>\n", $response->data['review'] );
		$this->assertEquals( 'admin', $response->data['reviewer'] );
		$this->assertEquals( 'woo@woo.local', $response->data['reviewer_email'] );
		$this->assertEquals( 0, $response->data['rating'] );

		$data     = [
			'review'         => 'Hello world - updated.',
			'reviewer'       => 'Justin',
			'reviewer_email' => 'woo2@woo.local',
			'rating'         => 3,
		];
		$response = $this->do_request( '/wc/v4/products/reviews/' . $product_review_id, 'PUT', $data );

		$this->assertExpectedResponse( $response, 200, $data );

		foreach ( $this->get_properties( 'view' ) as $property ) {
			$this->assertArrayHasKey( $property, $response->data );
		}
	}

	/**
	 * Test delete using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_delete() {
		// Invalid.
		$result = $this->do_request( '/wc/v4/products/reviews/0', 'DELETE', [ 'force' => true ] );
		$this->assertEquals( 404, $result->status );

		// Valid.
		$product           = ProductHelper::create_simple_product();
		$product_review_id = ProductHelper::create_product_review( $product->get_id() );
		$result            = $this->do_request( '/wc/v4/products/reviews/' . $product_review_id, 'DELETE', [ 'force' => true ] );
		$this->assertEquals( 200, $result->status );
	}

	/**
	 * Test get/read using this method.
	 */
	public function test_guest_read() {
		wp_set_current_user( 0 );
		$result = $this->do_request( '/wc/v4/products/reviews' );
		$this->assertEquals( 401, $result->status );
	}

	/**
	 * Tests getting a single product review.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_review() {
		$product           = ProductHelper::create_simple_product();
		$product_review_id = ProductHelper::create_product_review( $product->get_id() );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v4/products/reviews/' . $product_review_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                   => $product_review_id,
				'date_created'         => $data['date_created'],
				'date_created_gmt'     => $data['date_created_gmt'],
				'product_id'           => $product->get_id(),
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
	 * Tests getting a product review with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_review_invalid_id() {
		$product  = ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v4/products/reviews/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating a product review without required fields.
	 *
	 * @since 3.5.0
	 */
	public function test_create_product_review_invalid_fields() {
		$product = ProductHelper::create_simple_product();

		// missing review
		$request = new \WP_REST_Request( 'POST', '/wc/v4/products/reviews' );
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
		$request = new \WP_REST_Request( 'POST', '/wc/v4/products/reviews' );
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
		$request = new \WP_REST_Request( 'POST', '/wc/v4/products/reviews' );
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
	 * Tests updating a product review without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_review_without_permission() {
		wp_set_current_user( 0 );
		$product           = ProductHelper::create_simple_product();
		$product_review_id = ProductHelper::create_product_review( $product->get_id() );

		$request = new \WP_REST_Request( 'PUT', '/wc/v4/products/reviews/' . $product_review_id );
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
		$product = ProductHelper::create_simple_product();

		$request = new \WP_REST_Request( 'PUT', '/wc/v4/products/reviews/0' );
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
	 * Test deleting a product review without permission/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_without_permission() {
		wp_set_current_user( 0 );
		$product           = ProductHelper::create_simple_product();
		$product_review_id = ProductHelper::create_product_review( $product->get_id() );

		$request  = new \WP_REST_Request( 'DELETE', '/wc/v4/products/reviews/' . $product_review_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_product_reviews_batch() {
		$product = ProductHelper::create_simple_product();

		$review_1_id = ProductHelper::create_product_review( $product->get_id() );
		$review_2_id = ProductHelper::create_product_review( $product->get_id() );
		$review_3_id = ProductHelper::create_product_review( $product->get_id() );
		$review_4_id = ProductHelper::create_product_review( $product->get_id() );

		$request = new \WP_REST_Request( 'POST', '/wc/v4/products/reviews/batch' );
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

		$request = new \WP_REST_Request( 'GET', '/wc/v4/products/reviews' );
		$request->set_param( 'product', $product->get_id() );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}
}

