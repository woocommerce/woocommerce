<?php
/**
 * Tests for Coupons API.
 *
 * @package WooCommerce\Tests\API
 */

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

// phpcs:ignore Squiz.Commenting.FileComment.Missing
require_once __DIR__ . '/date-filtering.php';

/**
 * Coupon API Tests
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Coupons extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;
	use DateFilteringForCrudControllers;

	/**
	 * @var WC_REST_Coupons_Controller
	 */
	protected $endpoint;

	/**
	 * Setup test coupon data.
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Coupons_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/coupons', $routes );
		$this->assertArrayHasKey( '/wc/v3/coupons/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/coupons/batch', $routes );
	}

	/**
	 * Test getting coupons.
	 * @since 3.5.0
	 */
	public function test_get_coupons() {
		wp_set_current_user( $this->user );

		$coupon_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$post_1   = get_post( $coupon_1->get_id() );
		$coupon_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-2' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons' ) );
		$coupons  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $coupons ) );

		$matching_coupon_data = current(
			array_filter(
				$coupons,
				function( $coupon ) use ( $coupon_1 ) {
					return $coupon['id'] === $coupon_1->get_id();
				}
			)
		);
		$this->assertIsArray( $matching_coupon_data );

		$this->assertArraySubset(
			array(
				'id'                          => $coupon_1->get_id(),
				'code'                        => 'dummycoupon-1',
				'amount'                      => '1.00',
				'status'                      => $coupon_1->get_status(),
				'date_created'                => wc_rest_prepare_date_response( $post_1->post_date_gmt, false ),
				'date_created_gmt'            => wc_rest_prepare_date_response( $post_1->post_date_gmt ),
				'date_modified'               => wc_rest_prepare_date_response( $post_1->post_modified_gmt, false ),
				'date_modified_gmt'           => wc_rest_prepare_date_response( $post_1->post_modified_gmt ),
				'discount_type'               => 'fixed_cart',
				'description'                 => 'This is a dummy coupon',
				'date_expires'                => '',
				'date_expires_gmt'            => '',
				'usage_count'                 => 0,
				'individual_use'              => false,
				'product_ids'                 => array(),
				'excluded_product_ids'        => array(),
				'usage_limit'                 => '',
				'usage_limit_per_user'        => '',
				'limit_usage_to_x_items'      => null,
				'free_shipping'               => false,
				'product_categories'          => array(),
				'excluded_product_categories' => array(),
				'exclude_sale_items'          => false,
				'minimum_amount'              => '0.00',
				'maximum_amount'              => '0.00',
				'email_restrictions'          => array(),
				'used_by'                     => array(),
				'meta_data'                   => array(),
				'_links'                      => array(
					'self'       => array(
						array(
							'href' => rest_url( '/wc/v3/coupons/' . $coupon_1->get_id() ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v3/coupons' ),
						),
					),
				),
			),
			$matching_coupon_data
		);
	}

	/**
	 * Test getting coupons without valid permissions.
	 * @since 3.5.0
	 */
	public function test_get_coupons_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single coupon.
	 * @since 3.5.0
	 */
	public function test_get_coupon() {
		wp_set_current_user( $this->user );
		$coupon   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$post     = get_post( $coupon->get_id() );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                          => $coupon->get_id(),
				'code'                        => 'dummycoupon-1',
				'amount'                      => '1.00',
				'status'                      => $coupon->get_status(),
				'date_created'                => wc_rest_prepare_date_response( $post->post_date_gmt, false ),
				'date_created_gmt'            => wc_rest_prepare_date_response( $post->post_date_gmt ),
				'date_modified'               => wc_rest_prepare_date_response( $post->post_modified_gmt, false ),
				'date_modified_gmt'           => wc_rest_prepare_date_response( $post->post_modified_gmt ),
				'discount_type'               => 'fixed_cart',
				'description'                 => 'This is a dummy coupon',
				'date_expires'                => null,
				'date_expires_gmt'            => null,
				'usage_count'                 => 0,
				'individual_use'              => false,
				'product_ids'                 => array(),
				'excluded_product_ids'        => array(),
				'usage_limit'                 => null,
				'usage_limit_per_user'        => null,
				'limit_usage_to_x_items'      => null,
				'free_shipping'               => false,
				'product_categories'          => array(),
				'excluded_product_categories' => array(),
				'exclude_sale_items'          => false,
				'minimum_amount'              => '0.00',
				'maximum_amount'              => '0.00',
				'email_restrictions'          => array(),
				'used_by'                     => array(),
				'meta_data'                   => array(),
			),
			$data
		);
	}

	/**
	 * Test getting a single coupon with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_get_coupon_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single coupon without valid permissions.
	 * @since 3.5.0
	 */
	public function test_get_coupon_without_permission() {
		wp_set_current_user( 0 );
		$coupon   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test creating a single coupon.
	 * @since 3.5.0
	 */
	public function test_create_coupon() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'test',
				'amount'        => '5.00',
				'discount_type' => 'fixed_product',
				'description'   => 'Test',
				'usage_limit'   => 10,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                          => $data['id'],
				'code'                        => 'test',
				'amount'                      => '5.00',
				'status'                      => 'publish',
				'date_created'                => $data['date_created'],
				'date_created_gmt'            => $data['date_created_gmt'],
				'date_modified'               => $data['date_modified'],
				'date_modified_gmt'           => $data['date_modified_gmt'],
				'discount_type'               => 'fixed_product',
				'description'                 => 'Test',
				'date_expires'                => null,
				'date_expires_gmt'            => null,
				'usage_count'                 => 0,
				'individual_use'              => false,
				'product_ids'                 => array(),
				'excluded_product_ids'        => array(),
				'usage_limit'                 => 10,
				'usage_limit_per_user'        => null,
				'limit_usage_to_x_items'      => null,
				'free_shipping'               => false,
				'product_categories'          => array(),
				'excluded_product_categories' => array(),
				'exclude_sale_items'          => false,
				'minimum_amount'              => '0.00',
				'maximum_amount'              => '0.00',
				'email_restrictions'          => array(),
				'used_by'                     => array(),
				'meta_data'                   => array(),
			),
			$data
		);
	}

	/**
	 * Test creating a single coupon with invalid fields.
	 * @since 3.5.0
	 */
	public function test_create_coupon_invalid_fields() {
		wp_set_current_user( $this->user );

		// test no code...
		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'amount'        => '5.00',
				'discount_type' => 'fixed_product',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single coupon without valid permissions.
	 * @since 3.5.0
	 */
	public function test_create_coupon_without_permission() {
		wp_set_current_user( 0 );

		// test no code...
		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'fail',
				'amount'        => '5.00',
				'discount_type' => 'fixed_product',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single coupon.
	 * @since 3.5.0
	 */
	public function test_update_coupon() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() ) );
		$data     = $response->get_data();
		$this->assertEquals( 'This is a dummy coupon', $data['description'] );
		$this->assertEquals( 'fixed_cart', $data['discount_type'] );
		$this->assertEquals( '1.00', $data['amount'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params(
			array(
				'amount'               => '10.00',
				'description'          => 'New description',
				'maximum_amount'       => '500.00',
				'usage_limit_per_user' => 1,
				'free_shipping'        => true,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '10.00', $data['amount'] );
		$this->assertEquals( 'New description', $data['description'] );
		$this->assertEquals( 'fixed_cart', $data['discount_type'] );
		$this->assertSame( '500.00', $data['maximum_amount'] );
		$this->assertSame( 1, $data['usage_limit_per_user'] );
		$this->assertTrue( $data['free_shipping'] );

		$persisted_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( '10', $persisted_coupon->get_amount() );
		$this->assertSame( 'New description', $persisted_coupon->get_description() );
		$this->assertSame( '500', $persisted_coupon->get_maximum_amount() );
		$this->assertSame( 1, $persisted_coupon->get_usage_limit_per_user() );
		$this->assertTrue( $persisted_coupon->get_free_shipping() );
	}

	/**
	 * Test updating a single coupon with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_update_coupon_invalid_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/0' );
		$request->set_body_params(
			array(
				'code'        => 'tester',
				'amount'      => '10.00',
				'description' => 'New description',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test updating a single coupon without valid permissions.
	 * @since 3.5.0
	 */
	public function test_update_coupon_without_permission() {
		wp_set_current_user( 0 );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$post   = get_post( $coupon->get_id() );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params(
			array(
				'amount'      => '10.00',
				'description' => 'New description',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single coupon.
	 * @since 3.5.0
	 */
	public function test_delete_coupon() {
		wp_set_current_user( $this->user );
		$coupon  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() ) );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_shop_coupon_invalid_id', $response->get_data()['code'] );
	}

	/**
	 * Test deleting a single coupon with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_delete_coupon_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/coupons/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test deleting a single coupon without valid permissions.
	 * @since 3.5.0
	 */
	public function test_delete_coupon_without_permission() {
		wp_set_current_user( 0 );
		$coupon   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/coupons/' . $coupon->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch operations on coupons.
	 * @since 3.5.0
	 */
	public function test_batch_coupon() {
		wp_set_current_user( $this->user );

		$coupon_ids = array();

		try {
			$coupon_1     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
			$coupon_ids[] = $coupon_1->get_id();
			$coupon_2     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-2' );
			$coupon_ids[] = $coupon_2->get_id();
			$coupon_3     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-3' );
			$coupon_ids[] = $coupon_3->get_id();
			$coupon_4     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-4' );
			$coupon_ids[] = $coupon_4->get_id();

			$request = new WP_REST_Request( 'POST', '/wc/v3/coupons/batch' );
			$request->set_body_params(
				array(
					'update' => array(
						array(
							'id'          => $coupon_1->get_id(),
							'amount'      => '5.15',
							'description' => 'Updated first coupon',
						),
						array(
							'id'                   => $coupon_4->get_id(),
							'free_shipping'        => true,
							'usage_limit_per_user' => 2,
						),
					),
					'delete' => array(
						$coupon_2->get_id(),
						$coupon_3->get_id(),
					),
					'create' => array(
						array(
							'code'   => 'new-coupon-one',
							'amount' => '11.00',
						),
						array(
							'code'          => 'new-coupon-two',
							'amount'        => '12.00',
							'free_shipping' => true,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();

			foreach ( $data['create'] as $created_coupon ) {
				$created_coupon_id = $created_coupon['id'] ?? null;
				if ( is_numeric( $created_coupon_id ) ) {
					$coupon_ids[] = (int) $created_coupon_id;
				}
				$this->assertIsInt( $created_coupon_id );
			}

			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 2, $data['update'] );
			$this->assertCount( 2, $data['delete'] );
			$this->assertCount( 2, $data['create'] );
			$this->assertSame( array( $coupon_1->get_id(), $coupon_4->get_id() ), wp_list_pluck( $data['update'], 'id' ) );
			$this->assertSame( array( $coupon_2->get_id(), $coupon_3->get_id() ), wp_list_pluck( $data['delete'], 'id' ) );
			$this->assertSame( array( 'new-coupon-one', 'new-coupon-two' ), wp_list_pluck( $data['create'], 'code' ) );
			$this->assertSame( '5.15', $data['update'][0]['amount'] );
			$this->assertSame( 'Updated first coupon', $data['update'][0]['description'] );
			$this->assertTrue( $data['update'][1]['free_shipping'] );
			$this->assertSame( 2, $data['update'][1]['usage_limit_per_user'] );
			$this->assertSame( '11.00', $data['create'][0]['amount'] );
			$this->assertSame( '12.00', $data['create'][1]['amount'] );
			$this->assertTrue( $data['create'][1]['free_shipping'] );

			$persisted_coupon_1 = new WC_Coupon( $coupon_1->get_id() );
			$persisted_coupon_4 = new WC_Coupon( $coupon_4->get_id() );
			$this->assertSame( '5.15', $persisted_coupon_1->get_amount() );
			$this->assertSame( 'Updated first coupon', $persisted_coupon_1->get_description() );
			$this->assertTrue( $persisted_coupon_4->get_free_shipping() );
			$this->assertSame( 2, $persisted_coupon_4->get_usage_limit_per_user() );

			foreach ( array( $coupon_2->get_id(), $coupon_3->get_id() ) as $deleted_coupon_id ) {
				$deleted_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $deleted_coupon_id ) );
				$this->assertSame( 404, $deleted_response->get_status() );
			}

			$list_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons' ) );
			$this->assertSame( 200, $list_response->get_status() );
			$expected_ids = array( $coupon_1->get_id(), $coupon_4->get_id(), $data['create'][0]['id'], $data['create'][1]['id'] );
			$actual_ids   = wp_list_pluck( $list_response->get_data(), 'id' );
			sort( $expected_ids );
			sort( $actual_ids );
			$this->assertSame( $expected_ids, $actual_ids );
		} finally {
			$this->delete_coupon_fixtures( $coupon_ids );
		}
	}

	/**
	 * Test collection filters and pagination through the registered route.
	 */
	public function test_collection_filters_and_pagination() {
		wp_set_current_user( $this->user );

		$coupons = array();
		$ids     = array();

		try {
			foreach ( array( 'slice040-alpha', 'slice040-beta', 'slice040-gamma' ) as $coupon_code ) {
				$coupon    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( $coupon_code );
				$coupons[] = $coupon;
				$ids[]     = $coupon->get_id();
			}

			$descriptions = array( 'red signal', 'blue signal', 'green needle' );
			foreach ( $coupons as $index => $coupon ) {
				$coupon->set_description( $descriptions[ $index ] );
				$coupon->save();
			}

			$request = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
			$request->set_param( 'include', $ids );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$expected_ids = $ids;
			$actual_ids   = wp_list_pluck( $response->get_data(), 'id' );
			sort( $expected_ids );
			sort( $actual_ids );
			$this->assertSame( $expected_ids, $actual_ids );

			$request = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
			$request->set_param( 'code', 'slice040-beta' );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array( $coupons[1]->get_id() ), wp_list_pluck( $response->get_data(), 'id' ) );

			$request = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
			$request->set_param( 'include', $ids );
			$request->set_param( 'orderby', 'id' );
			$request->set_param( 'order', 'asc' );
			$request->set_param( 'per_page', 2 );
			$request->set_param( 'page', 1 );
			$response   = $this->server->dispatch( $request );
			$sorted_ids = $ids;
			sort( $sorted_ids );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array_slice( $sorted_ids, 0, 2 ), wp_list_pluck( $response->get_data(), 'id' ) );

			$request->set_param( 'page', 2 );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array_slice( $sorted_ids, 2 ), wp_list_pluck( $response->get_data(), 'id' ) );

			$request = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
			$request->set_param( 'search', 'green needle' );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array( $coupons[2]->get_id() ), wp_list_pluck( $response->get_data(), 'id' ) );
		} finally {
			$this->delete_coupon_fixtures( $ids );
		}
	}

	/**
	 * Test coupon schema.
	 * @since 3.5.0
	 */
	public function test_coupon_schema() {
		wp_set_current_user( $this->user );
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/coupons' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 28, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'code', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'date_modified_gmt', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'discount_type', $properties );
		$this->assertArrayHasKey( 'amount', $properties );
		$this->assertArrayHasKey( 'date_expires', $properties );
		$this->assertArrayHasKey( 'date_expires_gmt', $properties );
		$this->assertArrayHasKey( 'usage_count', $properties );
		$this->assertArrayHasKey( 'individual_use', $properties );
		$this->assertArrayHasKey( 'product_ids', $properties );
		$this->assertArrayHasKey( 'excluded_product_ids', $properties );
		$this->assertArrayHasKey( 'usage_limit', $properties );
		$this->assertArrayHasKey( 'usage_limit_per_user', $properties );
		$this->assertArrayHasKey( 'limit_usage_to_x_items', $properties );
		$this->assertArrayHasKey( 'free_shipping', $properties );
		$this->assertArrayHasKey( 'product_categories', $properties );
		$this->assertArrayHasKey( 'excluded_product_categories', $properties );
		$this->assertArrayHasKey( 'exclude_sale_items', $properties );
		$this->assertArrayHasKey( 'minimum_amount', $properties );
		$this->assertArrayHasKey( 'maximum_amount', $properties );
		$this->assertArrayHasKey( 'email_restrictions', $properties );
		$this->assertArrayHasKey( 'used_by', $properties );
	}

	/**
	 * Create an object for the tests in DateFilteringForCrudControllers.
	 *
	 * @return object The created object.
	 */
	private function get_item_for_date_filtering_tests() {
		return \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1' );
	}

	/**
	 * Get the REST API endpoint for the tests in DateFilteringForCrudControllers.
	 *
	 * @return string REST API endpoint for querying items.
	 */
	private function get_endpoint_for_date_filtering_tests() {
		return '/wc/v3/coupons';
	}

	/**
	 * Delete coupon fixtures that may have survived an assertion failure.
	 *
	 * @param int[] $coupon_ids Coupon IDs.
	 */
	private function delete_coupon_fixtures( $coupon_ids ) {
		foreach ( array_unique( array_filter( $coupon_ids ) ) as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );
			if ( $coupon->get_id() ) {
				$coupon->delete( true );
			}
		}
	}
}
