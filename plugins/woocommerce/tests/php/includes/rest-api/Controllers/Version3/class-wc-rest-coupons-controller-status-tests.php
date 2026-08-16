<?php
declare( strict_types = 1 );

/**
 * class WC_REST_Coupons_Controller_Status_Tests.
 * Additional tests for coupon status changes in V3 REST API.
 */
class WC_REST_Coupons_Controller_Status_Tests extends WC_REST_Unit_Test_Case {

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
	 * Test that coupon status can be updated via PATCH request.
	 */
	public function test_patch_coupon_status() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'draft' );
		$coupon->save();

		$request = new WP_REST_Request( 'PATCH', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params( array( 'status' => 'publish' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'publish', $data['status'] );

		$updated_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'publish', $updated_coupon->get_status() );
	}

	/**
	 * Test that valid status values are accepted.
	 */
	public function test_coupon_status_change() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'draft' );
		$coupon->save();

		$statuses = array( 'publish', 'draft' );

		foreach ( $statuses as $status ) {
			$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
			$request->set_body_params( array( 'status' => $status ) );
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status(), "Status '{$status}' should be accepted" );
			$data = $response->get_data();
			$this->assertEquals( $status, $data['status'] );
		}
	}

	/**
	 * Test that status update works with other coupon properties.
	 */
	public function test_coupon_status_update_with_other_properties() {
		wp_set_current_user( $this->user );
		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'draft' );
		$coupon->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params(
			array(
				'status'      => 'publish',
				'description' => 'Updated description',
				'amount'      => '15.00',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'publish', $data['status'] );
		$this->assertEquals( 'Updated description', $data['description'] );
		$this->assertEquals( '15.00', $data['amount'] );
	}

	/**
	 * Test that non-admin users cannot update coupon status.
	 */
	public function test_coupon_status_update_permissions() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$coupon = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon', 'draft' );
		$coupon->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/coupons/' . $coupon->get_id() );
		$request->set_body_params( array( 'status' => 'publish' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test that status update works with batch operations.
	 */
	public function test_batch_coupon_status_updates() {
		wp_set_current_user( $this->user );
		$coupon1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon-1', 'draft' );
		$coupon2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'test-coupon-2', 'publish' );
		$coupon1->save();
		$coupon2->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'     => $coupon1->get_id(),
						'status' => 'publish',
					),
					array(
						'id'     => $coupon2->get_id(),
						'status' => 'draft',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data['update'] );
		$this->assertEquals( 'publish', $data['update'][0]['status'] );
		$this->assertEquals( 'draft', $data['update'][1]['status'] );

		$updated_coupon1 = new WC_Coupon( $coupon1->get_id() );
		$updated_coupon2 = new WC_Coupon( $coupon2->get_id() );
		$this->assertEquals( 'publish', $updated_coupon1->get_status() );
		$this->assertEquals( 'draft', $updated_coupon2->get_status() );
	}
}
