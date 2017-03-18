<?php
/**
 * Legacy Coupon API Tests
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */
class WC_Tests_API_Legacy_Coupons extends WC_API_Unit_Test_Case {

	/** @var WC_API_Coupons instance */
	protected $endpoint;

	/**
	 * Setup test coupon data.
	 * @see WC_API_UnitTestCase::setup()
	 * @since 3.0.0
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = WC()->api->WC_API_Coupons;
		$this->coupon   = WC_Helper_Coupon::create_coupon();
	}

	/**
	 * Ensure valid coupon data response.
	 * @since 3.0.0
	 * @param array $response
	 * @param WC_Coupon $coupon
	 */
	protected function check_get_coupon_response( $response, $coupon ) {
		$this->assertEquals( (int) $coupon->get_id(), $response['id'] );
		$this->assertEquals( $coupon->get_code(), $response['code'] );
		$this->assertEquals( $coupon->get_discount_type(), $response['type'] );
		$this->assertEquals( $coupon->get_amount(), $response['amount'] );
		$this->assertEquals( $coupon->get_individual_use(), $response['individual_use'] );
		$this->assertEquals( $coupon->get_product_ids(), $response['product_ids'] );
		$this->assertEquals( $coupon->get_excluded_product_ids(), $response['exclude_product_ids'] );
		$this->assertEquals( (int) $coupon->get_usage_limit(), $response['usage_limit'] );
		$this->assertEquals( (int) $coupon->get_usage_limit_per_user(), $response['usage_limit_per_user'] );
		$this->assertEquals( (int) $coupon->get_limit_usage_to_x_items(), $response['limit_usage_to_x_items'] );
		$this->assertEquals( (int) $coupon->get_usage_count(), $response['usage_count'] );
		$this->assertEquals( $coupon->get_date_expires(), $response['expiry_date'] );
		$this->assertEquals( $coupon->get_free_shipping(), $response['enable_free_shipping'] );
		$this->assertEquals( $coupon->get_product_categories(), $response['product_category_ids'] );
		$this->assertEquals( $coupon->get_excluded_product_categories(), $response['exclude_product_category_ids'] );
		$this->assertEquals( $coupon->get_exclude_sale_items(), $response['exclude_sale_items'] );
		$this->assertEquals( wc_format_decimal( $coupon->get_minimum_amount(), 2 ), $response['minimum_amount'] );
		$this->assertEquals( wc_format_decimal( $coupon->get_maximum_amount(), 2 ), $response['maximum_amount'] );
		$this->assertEquals( $coupon->get_email_restrictions(), $response['customer_emails'] );
		$this->assertEquals( $coupon->get_description(), $response['description'] );
		$this->assertArrayHasKey( 'created_at', $response );
		$this->assertArrayHasKey( 'updated_at', $response );
	}

	/**
	 * Get default arguments for creating/editing a coupon.
	 * @since 3.0.0
	 * @param  array $args
	 * @return array
	 */
	protected function get_defaults( $args = array() ) {
		$defaults = array(
			'code'        => 'api-dummycoupon',
			'description' => 'Test API Coupon',
			'amount'      => '5',
			'type'        => 'percent',
		);
		return array( 'coupon' => wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Clears out the post title from our post data before inserting the coupon into the database.
	 * @since 3.0.0
	 * @param  array $data
	 * @return array
	 */
	public function clear_code_from_post_data( $data ) {
		$data['post_title'] = '';
		return $data;
	}

	/**
	 * Test route registration.
	 * @since 3.0.0
	 */
	public function test_register_routes() {
		$routes = $this->endpoint->register_routes( array() );
		$this->assertArrayHasKey( '/coupons', $routes );
		$this->assertArrayHasKey( '/coupons/count', $routes );
		$this->assertArrayHasKey( '/coupons/(?P<id>\d+)', $routes );
		$this->assertArrayHasKey( '/coupons/code/(?P<code>\w[\w\s\-]*)', $routes );
		$this->assertArrayHasKey( '/coupons/bulk', $routes );
	}

	/**
	 * Test GET /coupons/{id}.
	 * @since 3.0.0
	 */
	public function test_get_coupon() {
		// invalid ID
		$response = $this->endpoint->get_coupon( 0 );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_id', 404, $response );

		// valid request
		$response = $this->endpoint->get_coupon( $this->coupon->get_id() );
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupon', $response );
		$this->check_get_coupon_response( $response['coupon'], $this->coupon );
	}

	/**
	 * Test GET /coupons/{id} without valid permissions.
	 * @since 3.0.0
	 */
	public function test_get_coupon_without_permission() {
		$this->disable_capability( 'read_private_shop_coupons' );
		$response = $this->endpoint->get_coupon( $this->coupon->get_id() );
		$this->assertHasAPIError( 'woocommerce_api_user_cannot_read_coupon', 401, $response );
	}

	/**
	 * Test GET /coupons/code/{code}.
	 * @since 3.0.0
	 */
	public function test_get_get_coupon_by_code() {
		// invalid ID
		$response = $this->endpoint->get_coupon_by_code( 'bogus' );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_code', 404, $response );

		// valid request
		$response = $this->endpoint->get_coupon_by_code( 'dummycoupon' );
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupon', $response );
		$this->check_get_coupon_response( $response['coupon'], $this->coupon );
	}

	/**
	 * Test GET /coupons/code/{code} without valid permissions.
	 * @since 3.0.0
	 */
	public function test_get_coupon_by_code_without_permission() {
		$this->disable_capability( 'read_private_shop_coupons' );
		$response = $this->endpoint->get_coupon( $this->coupon->get_id() );
		$this->assertHasAPIError( 'woocommerce_api_user_cannot_read_coupon', 401, $response );
	}

	/**
	 * Test GET /coupons.
	 * @since 3.0.0
	 */
	public function test_get_coupons() {
		$response = $this->endpoint->get_coupons();
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupons', $response );
		$this->assertCount( 1, $response['coupons'] );
		$this->check_get_coupon_response( $response['coupons'][0], $this->coupon );
	}

	/**
	 * Test GET /coupons without valid permissions.
	 * @since 3.0.0
	 */
	public function test_get_coupons_without_permission() {
		$this->disable_capability( 'read_private_shop_coupons' );
		$response = $this->endpoint->get_coupons();
		$this->assertArrayHasKey( 'coupons', $response );
		$this->assertEmpty( $response['coupons'] );
	}

	/**
	 * Test GET /coupons/count.
	 * @since 3.0.0
	 */
	public function test_get_coupons_count() {
		$response = $this->endpoint->get_coupons_count();
		$this->assertArrayHasKey( 'count', $response );
		$this->assertEquals( 1, $response['count'] );
	}

	/**
	 * Test GET /coupons/count without valid permissions.
	 * @since 3.0.0
	 */
	public function test_get_coupons_count_without_permission() {
		$this->disable_capability( 'read_private_shop_coupons' );
		$response = $this->endpoint->get_coupons_count();
		$this->assertHasAPIError( 'woocommerce_api_user_cannot_read_coupons_count', 401, $response );
	}

	/**
	 * Test POST /coupons.
	 * @since 3.0.0
	 */
	public function test_create_coupon() {
		$response = $this->endpoint->create_coupon( $this->get_defaults() );
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupon', $response );
		$this->check_get_coupon_response( $response['coupon'], new WC_Coupon( $response['coupon']['code'] ) );
	}

	/**
	 * Test POST /coupons without valid permissions.
	 * @since 3.0.0
	 */
	public function test_create_coupon_without_permission() {
		$this->disable_capability( 'publish_shop_coupons' );
		$response = $this->endpoint->create_coupon( $this->get_defaults() );
		$this->assertHasAPIError( 'woocommerce_api_user_cannot_create_coupon', 401, $response );
	}

	/**
	 * Test an empty coupon code for POST /coupons.
	 * @since 3.0.0
	 */
	public function test_create_coupon_empty_code() {
		$response = $this->endpoint->create_coupon( $this->get_defaults( array( 'code' => null ) ) );
		$this->assertHasAPIError( 'woocommerce_api_missing_coupon_code', 400, $response );
	}

	/**
	 * Test an empty or invalid discount type for POST /coupons.
	 * @since 3.0.0
	 */
	public function test_create_coupon_invalid_discount_type() {
		// empty
		$response = $this->endpoint->create_coupon( $this->get_defaults( array( 'type' => null ) ) );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_type', 400, $response );
		// invalid
		$response = $this->endpoint->create_coupon( $this->get_defaults( array( 'type' => 'bogus' ) ) );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_type', 400, $response );
	}

	/**
	 * Test wp_insert_post() failure for POST /coupons.
	 * @since 3.0.0
	 */
	public function test_create_coupon_insert_post_failure() {
		add_filter( 'wp_insert_post_empty_content', '__return_true' );
		add_filter( 'wp_insert_post_data', array( $this, 'clear_code_from_post_data' ) );
		$response = $this->endpoint->create_coupon( $this->get_defaults( array( 'description' => null, 'code' => '' ) ) );
		$this->assertHasAPIError( 'woocommerce_api_cannot_create_coupon', 400, $response );
	}

	/**
	 * Test PUT /coupons/{id}.
	 * @since 3.0.0
	 */
	public function test_edit_coupon() {
		// invalid ID
		$response = $this->endpoint->edit_coupon( 0, $this->get_defaults() );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_id', 404, $response );

		$args = array(
			'description' => rand_str(),
			'code'        => rand_str(),
		);

		// valid request
		$response = $this->endpoint->edit_coupon( $this->coupon->get_id(), $this->get_defaults( $args ) );

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupon', $response );
		$this->check_get_coupon_response( $response['coupon'], new WC_Coupon( $response['coupon']['code'] ) );
	}

	/**
	 * Test PUT /coupons/{id} without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_edit_coupon_without_permission() {
		$this->disable_capability( 'edit_published_shop_coupons' );
		$response = $this->endpoint->edit_coupon( $this->coupon->get_id(), $this->get_defaults() );
		$this->assertHasAPIError( 'woocommerce_api_user_cannot_edit_coupon', 401, $response );
	}

	/**
	 * Test DELETE /coupons/{id}.
	 *
	 * @since 2.2
	 */
	public function test_delete_coupon() {
		$response = $this->endpoint->delete_coupon( 0 );
		$this->assertHasAPIError( 'woocommerce_api_invalid_coupon_id', 404, $response );

		$response = $this->endpoint->delete_coupon( $this->coupon->get_id() );
		$this->assertArrayHasKey( 'message', $response );
		$this->assertEquals( 'Deleted coupon', $response['message'] );
	}

	/**
	 * Test POST /coupons/bulk.
	 * @since 3.0.0
	 */
	public function test_create_coupon_bulk() {
		$test_coupon_data = $this->get_defaults();
		$test_coupon_data_2 = $this->get_defaults( array( 'code' => time() ) );
		$coupons = array( 'coupons' => array( $test_coupon_data['coupon'], $test_coupon_data_2['coupon'] ) );
		$response = $this->endpoint->bulk( $coupons );
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupons', $response );
		$this->assertCount( 2, $response['coupons'] );
		$this->check_get_coupon_response( $response['coupons'][0], new WC_Coupon( $response['coupons'][0]['code'] ) );
		$this->check_get_coupon_response( $response['coupons'][1], new WC_Coupon( $response['coupons'][1]['code'] ) );
	}

	/**
	 * Test PUT /coupons/bulk.
	 * @since 3.0.0
	 */
	public function test_edit_coupon_bulk() {
		$coupon_1 = WC_Helper_Coupon::create_coupon( 'dummycoupon-1-' . time() );
		$test_coupon_data = $this->get_defaults( array( 'description' => rand_str() ) );
		$test_coupon_data['coupon']['id'] = $coupon_1->get_id();
		$coupons = array( 'coupons' => array( $test_coupon_data['coupon'] ) );
		$response = $this->endpoint->bulk( $coupons );
		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'coupons', $response );
		$this->check_get_coupon_response( $response['coupons'][0], new WC_Coupon( $response['coupons'][0]['code'] ) );
	}
}
