<?php

/**
 * class WC_REST_Coupons_Controller_Tests.
 * Coupons Controller tests for V3 REST API.
 */
class WC_REST_Coupons_Controller_Tests extends WC_REST_Unit_Test_Case {



	/**
	 * Setup our test server, endpoints, and user info.
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
	 * Get all expected fields.
	 */
	public function get_expected_response_fields() {
		return array(
			'id',
			'code',
			'amount',
			'status',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'discount_type',
			'description',
			'date_expires',
			'date_expires_gmt',
			'usage_count',
			'individual_use',
			'product_ids',
			'excluded_product_ids',
			'usage_limit',
			'usage_limit_per_user',
			'limit_usage_to_x_items',
			'free_shipping',
			'product_categories',
			'excluded_product_categories',
			'exclude_sale_items',
			'minimum_amount',
			'maximum_amount',
			'email_restrictions',
			'used_by',
			'meta_data',
		);
	}

	/**
	 * Test that all expected response fields are present.
	 * Note: This has fields hardcoded intentionally instead of fetching from schema to test for any bugs in schema result. Add new fields manually when added to schema.
	 */
	public function test_coupon_api_get_all_fields() {
		wp_set_current_user( $this->user );
		$expected_response_fields = $this->get_expected_response_fields();

		$coupon   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() ) );

		$this->assertEquals( 200, $response->get_status() );

		$response_fields = array_keys( $response->get_data() );

		$this->assertEmpty( array_diff( $expected_response_fields, $response_fields ), 'These fields were expected but not present in API response: ' . print_r( array_diff( $expected_response_fields, $response_fields ), true ) );

		$this->assertEmpty( array_diff( $response_fields, $expected_response_fields ), 'These fields were not expected in the API response: ' . print_r( array_diff( $response_fields, $expected_response_fields ), true ) );
	}

	/**
	 * Test that all fields are returned when requested one by one.
	 */
	public function test_coupons_get_each_field_one_by_one() {
		wp_set_current_user( $this->user );
		$expected_response_fields = $this->get_expected_response_fields();
		$coupon                   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon();

		foreach ( $expected_response_fields as $field ) {
			$request = new WP_REST_Request( 'GET', '/wc/v3/coupons/' . $coupon->get_id() );
			$request->set_param( '_fields', $field );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$response_fields = array_keys( $response->get_data() );

			$this->assertContains( $field, $response_fields, "Field $field was expected but not present in coupon API response." );
		}
	}

	/**
	 * Test that coupons are filtered by status when requested.
	 */
	public function test_filter_coupons_by_status() {
		wp_set_current_user( $this->user );

		$coupon_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-1', 'draft' );
		$post_1   = get_post( $coupon_1->get_id() );
		$coupon_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'dummycoupon-2' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
		$request->set_query_params(
			array(
				'status' => 'publish',
			)
		);
		$response = $this->server->dispatch( $request );
		$coupons  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $coupons ) );
	}

	/**
	 * Test that `prepare_object_for_response` method works.
	 */
	public function test_prepare_object_for_response() {
		$coupon = WC_Helper_Coupon::create_coupon();
		$coupon->save();
		$response = ( new WC_REST_Coupons_Controller() )->prepare_object_for_response( $coupon, new WP_REST_Request() );
		$this->assertArrayHasKey( 'id', $response->data );
		$this->assertEquals( $coupon->get_id(), $response->data['id'] );
		$this->assertEquals( $coupon->get_status(), $response->data['status'] );
	}

	/**
	 * @testDox Coupon is deleted as expected.
	 */
	public function test_delete_coupon() {
		wp_set_current_user( $this->user );
		$coupon = WC_Helper_Coupon::create_coupon();
		$coupon->save();

		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/coupons/' . $coupon->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'trash', get_post_status( $coupon->get_id() ) );
	}

	/**
	 * Test creating coupon with invalid percentage amount.
	 */
	public function test_create_coupon_invalid_percentage_amount() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'invalid-percent-test',
				'discount_type' => 'percent',
				'amount'        => '200',
			)
		);

		$response = $this->server->dispatch( $request );

		// Should return 400 error.
		$this->assertEquals( 400, $response->get_status() );

		// Should contain validation error message.
		$data = $response->get_data();
		$this->assertStringContainsString( 'Invalid discount amount', $data['message'] );

		// Ensure coupon was not created.
		$this->assertEquals( 0, wc_get_coupon_id_by_code( 'invalid-percent-test' ) );
	}

	/**
	 * Test creating coupon with valid percentage amount.
	 */
	public function test_create_coupon_valid_percentage_amount() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'valid-percent-test',
				'discount_type' => 'percent',
				'amount'        => '50',
			)
		);

		$response = $this->server->dispatch( $request );

		// Should succeed.
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( '50.00', $data['amount'] );
		$this->assertEquals( 'percent', $data['discount_type'] );
	}

	/**
	 * Test creating coupon with negative amount.
	 */
	public function test_create_coupon_negative_amount() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'negative-amount-test',
				'discount_type' => 'fixed_cart',
				'amount'        => '-10',
			)
		);

		$response = $this->server->dispatch( $request );

		// Should return 400 error.
		$this->assertEquals( 400, $response->get_status() );

		// Should contain validation error message.
		$data = $response->get_data();
		$this->assertStringContainsString( 'Invalid discount amount', $data['message'] );

		// Ensure coupon was not created.
		$this->assertEquals( 0, wc_get_coupon_id_by_code( 'negative-amount-test' ) );
	}

	/**
	 * Test creating coupon with negative percentage amount.
	 */
	public function test_create_coupon_negative_percentage_amount() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'negative-percent-test',
				'discount_type' => 'percent',
				'amount'        => '-25',
			)
		);

		$response = $this->server->dispatch( $request );

		// Should return 400 error.
		$this->assertEquals( 400, $response->get_status() );

		// Should contain validation error message from coupon class.
		$data = $response->get_data();
		$this->assertStringContainsString( 'Invalid discount amount', $data['message'] );

		// Ensure coupon was not created.
		$this->assertEquals( 0, wc_get_coupon_id_by_code( 'negative-percent-test' ) );
	}

	/**
	 * Test updating coupon status via REST API.
	 */
	public function test_update_coupon_status() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'draft' );
		$coupon->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params( array( 'status' => 'publish' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'publish', $data['status'] );

		$updated_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'publish', $updated_coupon->get_status() );
	}

	/**
	 * Test updating coupon status from publish to draft.
	 */
	public function test_update_coupon_status_publish_to_draft() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'publish' );
		$coupon->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params( array( 'status' => 'draft' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'draft', $data['status'] );

		$updated_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'draft', $updated_coupon->get_status() );
	}

	/**
	 * Test creating coupon with specific status.
	 */
	public function test_create_coupon_with_status() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'draft-coupon-test',
				'discount_type' => 'fixed_cart',
				'amount'        => '10.00',
				'status'        => 'draft',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'draft', $data['status'] );

		$coupon = new WC_Coupon( $data['id'] );
		$this->assertEquals( 'draft', $coupon->get_status() );
	}
}
