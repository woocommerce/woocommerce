<?php
/**
 * Coupon REST API tests.
 *
 * @package WooCommerce/RestApi/Tests
 */

namespace WooCommerce\RestApi\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\Tests\AbstractRestApiTest;

/**
 * Abstract Rest API Test Class
 *
 * @extends AbstractRestApiTest
 */
class Coupons extends AbstractRestApiTest {
	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/v4/coupons',
		'/wc/v4/coupons/(?P<id>[\d]+)',
		'/wc/v4/coupons/batch',
	];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [
		'id'                          => array( 'view', 'edit' ),
		'code'                        => array( 'view', 'edit' ),
		'amount'                      => array( 'view', 'edit' ),
		'date_created'                => array( 'view', 'edit' ),
		'date_created_gmt'            => array( 'view', 'edit' ),
		'date_modified'               => array( 'view', 'edit' ),
		'date_modified_gmt'           => array( 'view', 'edit' ),
		'discount_type'               => array( 'view', 'edit' ),
		'description'                 => array( 'view', 'edit' ),
		'date_expires'                => array( 'view', 'edit' ),
		'date_expires_gmt'            => array( 'view', 'edit' ),
		'usage_count'                 => array( 'view', 'edit' ),
		'individual_use'              => array( 'view', 'edit' ),
		'product_ids'                 => array( 'view', 'edit' ),
		'excluded_product_ids'        => array( 'view', 'edit' ),
		'usage_limit'                 => array( 'view', 'edit' ),
		'usage_limit_per_user'        => array( 'view', 'edit' ),
		'limit_usage_to_x_items'      => array( 'view', 'edit' ),
		'free_shipping'               => array( 'view', 'edit' ),
		'product_categories'          => array( 'view', 'edit' ),
		'excluded_product_categories' => array( 'view', 'edit' ),
		'exclude_sale_items'          => array( 'view', 'edit' ),
		'minimum_amount'              => array( 'view', 'edit' ),
		'maximum_amount'              => array( 'view', 'edit' ),
		'email_restrictions'          => array( 'view', 'edit' ),
		'used_by'                     => array( 'view', 'edit' ),
		'meta_data'                   => array( 'view', 'edit' ),
	];

	/**
	 * Test create.
	 */
	public function test_create() {
		$valid_data = [
			'code'                        => 'test-coupon',
			'amount'                      => '5.00',
			'discount_type'               => 'fixed_product',
			'description'                 => 'Test description.',
			'date_expires'                => date( 'Y-m-d\T00:00:00', strtotime( '+1 day' ) ),
			'individual_use'              => true,
			'product_ids'                 => [ 1, 2, 3 ],
			'excluded_product_ids'        => [ 3, 4, 5 ],
			'usage_limit'                 => 10,
			'usage_limit_per_user'        => 10,
			'limit_usage_to_x_items'      => 10,
			'free_shipping'               => false,
			'product_categories'          => [ 1, 2, 3 ],
			'excluded_product_categories' => [ 3, 4, 5 ],
			'exclude_sale_items'          => true,
			'minimum_amount'              => '100',
			'maximum_amount'              => '200',
			'email_restrictions'          => [ 'test@test.com' ],
			'meta_data'                   => [
				[
					'key'   => 'test_key',
					'value' => 'test_value',
				]
			]
		];
		$response = $this->do_request( '/wc/v4/coupons', 'POST', $valid_data );
		$this->assertExpectedResponse( $response, 201, $valid_data );
	}

	/**
	 * Test read.
	 */
	public function test_read() {
		$coupon1 = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$coupon2 = \WC_Helper_Coupon::create_coupon( 'testcoupon-2' );
		$coupon3 = \WC_Helper_Coupon::create_coupon( 'anothertestcoupon-3' );
		$coupon4 = \WC_Helper_Coupon::create_coupon( 'anothertestcoupon-4' );

		// Collection.
		$response = $this->do_request( '/wc/v4/coupons', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 4, count( $response->data ) );

		// Collection args.
		$response = $this->do_request( '/wc/v4/coupons', 'GET', [ 'code' => 'testcoupon-1' ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 1, count( $response->data ) );

		$response = $this->do_request( '/wc/v4/coupons', 'GET', [ 'search' => 'anothertestcoupon' ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 2, count( $response->data ) );

		// Single.
		$response = $this->do_request( '/wc/v4/coupons/' . $coupon1->get_id(), 'GET' );
		$this->assertExpectedResponse( $response, 200 );

		foreach ( $this->get_properties( 'view' ) as $property ) {
			$this->assertArrayHasKey( $property, $response->data );
		}

		// Invalid.
		$response = $this->do_request( '/wc/v4/coupons/0', 'GET' );
		$this->assertExpectedResponse( $response, 404 );
	}

	/**
	 * Test update.
	 */
	public function test_update() {
		// Invalid.
		$response = $this->do_request( '/wc/v4/coupons/0', 'POST', [ 'code' => 'test' ] );
		$this->assertExpectedResponse( $response, 404 );

		// Update existing.
		$coupon   = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$response = $this->do_request(
			'/wc/v4/coupons/' . $coupon->get_id(),
			'POST',
			[
				'code'        => 'new-code',
				'description' => 'new description',
			]
		);
		$this->assertExpectedResponse( $response, 200 );

		foreach ( $this->get_properties( 'view' ) as $property ) {
			$this->assertArrayHasKey( $property, $response->data );
		}

		$this->assertEquals( $coupon->get_id(), $response->data['id'] );
		$this->assertEquals( 'new-code', $response->data['code'] );
		$this->assertEquals( 'new description', $response->data['description'] );
	}

	/**
	 * Test delete.
	 */
	public function test_delete() {
		// Invalid.
		$result = $this->do_request( '/wc/v4/coupons/0', 'DELETE', [ 'force' => false ] );
		$this->assertEquals( 404, $result->status );

		// Trash.
		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );

		$result = $this->do_request( '/wc/v4/coupons/' . $coupon->get_id(), 'DELETE', [ 'force' => false ] );
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( 'trash', get_post_status( $coupon->get_id() ) );

		// Force.
		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-2' );

		$result = $this->do_request( '/wc/v4/coupons/' . $coupon->get_id(), 'DELETE', [ 'force' => true ] );
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( false, get_post( $coupon->get_id() ) );
	}

	/**
	 * Test read.
	 */
	public function test_guest_create() {
		parent::test_guest_create();

		$valid_data = [
			'code'                        => 'test-coupon',
			'amount'                      => '5.00',
			'discount_type'               => 'fixed_product',
			'description'                 => 'Test description.',
			'date_expires'                => date( 'Y-m-d\T00:00:00', strtotime( '+1 day' ) ),
			'individual_use'              => true,
			'product_ids'                 => [ 1, 2, 3 ],
			'excluded_product_ids'        => [ 3, 4, 5 ],
			'usage_limit'                 => 10,
			'usage_limit_per_user'        => 10,
			'limit_usage_to_x_items'      => 10,
			'free_shipping'               => false,
			'product_categories'          => [ 1, 2, 3 ],
			'excluded_product_categories' => [ 3, 4, 5 ],
			'exclude_sale_items'          => true,
			'minimum_amount'              => '100',
			'maximum_amount'              => '200',
			'email_restrictions'          => [ 'test@test.com' ],
			'meta_data'                   => [
				[
					'key'   => 'test_key',
					'value' => 'test_value',
				]
			]
		];
		$response = $this->do_request( '/wc/v4/coupons', 'POST', $valid_data );
		$this->assertExpectedResponse( $response, 401 );
	}

	/**
	 * Test read.
	 */
	public function test_guest_read() {
		parent::test_guest_read();

		$response = $this->do_request( '/wc/v4/coupons', 'GET' );
		$this->assertExpectedResponse( $response, 401 );
	}

	/**
	 * Test update.
	 */
	public function test_guest_update() {
		parent::test_guest_update();

		$coupon   = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$response = $this->do_request(
			'/wc/v4/coupons/' . $coupon->get_id(),
			'POST',
			[
				'code'        => 'new-code',
				'description' => 'new description',
			]
		);
		$this->assertExpectedResponse( $response, 401 );
	}

	/**
	 * Test delete.
	 */
	public function test_guest_delete() {
		parent::test_guest_delete();

		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$result = $this->do_request( '/wc/v4/coupons/' . $coupon->get_id(), 'DELETE', [ 'force' => false ] );
		$this->assertEquals( 401, $result->status );
	}

	/**
	 * Test validation.
	 */
	public function test_enum_discount_type() {
		$result = $this->do_request(
			'/wc/v4/coupons',
			'POST',
			[
				'code'          => 'test',
				'amount'        => '5.00',
				'discount_type' => 'fake',
			]
		);

		$this->assertEquals( 400, $result->status );
		$this->assertEquals( 'Invalid parameter(s): discount_type', $result->data['message'] );
	}

	/**
	 * Test a batch update.
	 */
	public function test_batch() {
		$coupon_1 = \WC_Helper_Coupon::create_coupon( 'batchcoupon-1' );
		$coupon_2 = \WC_Helper_Coupon::create_coupon( 'batchcoupon-2' );
		$coupon_3 = \WC_Helper_Coupon::create_coupon( 'batchcoupon-3' );
		$coupon_4 = \WC_Helper_Coupon::create_coupon( 'batchcoupon-4' );

		$result = $this->do_request(
			'/wc/v4/coupons/batch',
			'POST',
			array(
				'update' => array(
					array(
						'id'     => $coupon_1->get_id(),
						'amount' => '5.15',
					),
				),
				'delete' => array(
					$coupon_2->get_id(),
					$coupon_3->get_id(),
				),
				'create' => array(
					array(
						'code'   => 'new-coupon',
						'amount' => '11.00',
					),
				),
			)
		);

		$this->assertEquals( '5.15', $result->data['update'][0]['amount'] );
		$this->assertEquals( '11.00', $result->data['create'][0]['amount'] );
		$this->assertEquals( 'new-coupon', $result->data['create'][0]['code'] );
		$this->assertEquals( $coupon_2->get_id(), $result->data['delete'][0]['previous']['id'] );
		$this->assertEquals( $coupon_3->get_id(), $result->data['delete'][1]['previous']['id'] );

		$result = $this->do_request( '/wc/v4/coupons' );
		$this->assertEquals( 3, count( $result->data ) );
	}
}
