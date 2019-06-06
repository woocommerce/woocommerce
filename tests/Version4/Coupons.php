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
	 * @var array
	 */
	protected $properties = [
		'id',
		'code',
		'amount',
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
	];

	/**
	 * Test delete.
	 *
	 * @return void
	 */
	public function test_delete() {
		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$result = $this->do_request(
			'/wc/v4/coupons/' . $coupon->get_id(),
			'DELETE',
			[ 'force' => false ]
		);
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( 'trash', get_post_status( $coupon->get_id() ) );
	}

	/**
	 * Test force delete.
	 *
	 * @return void
	 */
	public function test_force_delete() {
		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$result = $this->do_request(
			'/wc/v4/coupons/' . $coupon->get_id(),
			'DELETE',
			[ 'force' => true ]
		);
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( false, get_post( $coupon->get_id() ) );
	}

	/**
	 * Test delete.
	 *
	 * @return void
	 */
	public function test_delete_without_permission() {
		$coupon = \WC_Helper_Coupon::create_coupon( 'testcoupon-1' );
		$result = $this->do_request(
			'/wc/v4/coupons/' . $coupon->get_id(),
			'DELETE',
			[ 'force' => false ],
			false
		);
		$this->assertEquals( 401, $result->status );
		$this->assertNotEquals( 'trash', get_post_status( $coupon->get_id() ) );
	}

	/**
	 * Test delete.
	 *
	 * @return void
	 */
	public function test_delete_non_existing() {
		$result = $this->do_request(
			'/wc/v4/coupons/0',
			'DELETE',
			[ 'force' => false ]
		);
		$this->assertEquals( 404, $result->status );
	}

	/**
	 * Test creation.
	 */
	public function test_create() {
		$result = $this->do_request(
			'/wc/v4/coupons',
			'POST',
			[
				'code'          => 'test',
				'amount'        => '5.00',
				'discount_type' => 'fixed_product',
				'description'   => 'Test',
				'usage_limit'   => 10,
			]
		);
		$this->assertEquals( 201, $result->status );
		$this->assertEquals( 'test', $result->data['code'] );
		$this->assertEquals( '5.00', $result->data['amount'] );
		$this->assertEquals( 'fixed_product', $result->data['discount_type'] );
		$this->assertEquals( 'Test', $result->data['description'] );
		$this->assertEquals( 10, $result->data['usage_limit'] );
	}

	/**
	 * Test creation.
	 */
	public function test_create_without_permission() {
		$result = $this->do_request(
			'/wc/v4/coupons',
			'POST',
			[
				'code'          => 'test',
				'amount'        => '5.00',
				'discount_type' => 'fixed_product',
				'description'   => 'Test',
				'usage_limit'   => 10,
			],
			false
		);
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
				'description'   => 'Test',
				'usage_limit'   => 10,
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
		$this->assertEquals( $coupon_2->get_id(), $result->data['delete'][0]['id'] );
		$this->assertEquals( $coupon_3->get_id(), $result->data['delete'][1]['id'] );

		$result = $this->do_request(
			'/wc/v4/coupons'
		);
		$this->assertEquals( 3, count( $result->data ) );
	}
}
