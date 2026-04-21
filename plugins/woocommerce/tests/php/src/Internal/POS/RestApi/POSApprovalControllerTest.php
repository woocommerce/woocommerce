<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\RestApi\POSApprovalController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Install;
use WC_REST_Unit_Test_Case;

/**
 * Tests for POSApprovalController.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\RestApi\POSApprovalController
 * @since 10.8.0
 */
class POSApprovalControllerTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wc/v3/pos/auth/approve';

	private POSPinService $pin_service;

	private int $admin_id;

	private int $cashier_id;

	private int $manager_id;

	public function setUp(): void {
		parent::setUp();

		$this->reset_roles();

		$this->admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->manager_id = $this->factory->user->create( array( 'role' => 'pos_manager' ) );

		$this->pin_service = new POSPinService();
		$this->pin_service->set_pin( $this->cashier_id, '8472' );
		$this->pin_service->set_pin( $this->manager_id, '7391' );

		$controller = wc_get_container()->get( POSApprovalController::class );
		$controller->register();
		$controller->register_routes();
	}

	public function tearDown(): void {
		if ( isset( $this->cashier_id ) ) {
			$this->pin_service->delete_pin( $this->cashier_id );
			wp_delete_user( $this->cashier_id );
		}
		if ( isset( $this->manager_id ) ) {
			$this->pin_service->delete_pin( $this->manager_id );
			wp_delete_user( $this->manager_id );
		}
		if ( isset( $this->admin_id ) ) {
			wp_delete_user( $this->admin_id );
		}
		$this->reset_roles();
		parent::tearDown();
	}

	/**
	 * @testdox Valid manager PIN and valid action returns 200 with approval_token.
	 */
	public function test_valid_manager_pin_returns_200_with_approval_token(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'approval_token', $data );
		$this->assertNotEmpty( $data['approval_token'] );
	}

	/**
	 * @testdox Response includes approved:true, approver_id, approver_name, expires_in:300.
	 */
	public function test_response_includes_expected_fields(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['approved'] );
		$this->assertSame( $this->manager_id, $data['approver_id'] );
		$this->assertArrayHasKey( 'approver_name', $data );
		$this->assertNotEmpty( $data['approver_name'] );
		$this->assertSame( 300, $data['expires_in'] );
	}

	/**
	 * @testdox Cashier PIN returns 403 because cashier lacks refund_shop_orders.
	 */
	public function test_cashier_pin_returns_403(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '8472',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame(
			'The approver does not have permission for this action.',
			$response->get_data()['message']
		);
	}

	/**
	 * @testdox Wrong PIN returns 422 with generic error.
	 */
	public function test_wrong_pin_returns_422(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '9753',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'The provided PIN is not valid.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Unsupported capability strings are rejected before approval lookup.
	 */
	public function test_manager_without_specific_action_cap_returns_400(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'    => '7391',
				'action' => 'nonexistent_cap',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_invalid_action', $response->get_data()['code'] );
	}

	/**
	 * @testdox Unsupported approval actions return 400.
	 */
	public function test_unsupported_action_returns_400(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'unsupported_action',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_invalid_action', $response->get_data()['code'] );
	}

	/**
	 * @testdox Order scoped approval requests require an order ID.
	 */
	public function test_missing_order_id_returns_400(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'    => '7391',
				'action' => 'refund_shop_orders',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_missing_order_context', $response->get_data()['code'] );
	}

	/**
	 * @testdox Manager PIN approving publish_shop_coupons without order_id returns 200.
	 */
	public function test_manager_pin_approves_publish_shop_coupons_without_order_id(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'    => '7391',
				'action' => 'publish_shop_coupons',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'approval_token', $data );
		$this->assertNotEmpty( $data['approval_token'] );
		$this->assertTrue( $data['approved'] );
		$this->assertSame( $this->manager_id, $data['approver_id'] );
	}

	/**
	 * @testdox Manager PIN approving publish_shop_coupons with irrelevant order_id still returns 200.
	 */
	public function test_manager_pin_approves_publish_shop_coupons_with_order_id(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'publish_shop_coupons',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'approval_token', $data );
		$this->assertNotEmpty( $data['approval_token'] );
	}

	/**
	 * @testdox Cashier PIN attempting publish_shop_coupons approval returns 403.
	 */
	public function test_cashier_pin_publish_shop_coupons_returns_403(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'    => '8472',
				'action' => 'publish_shop_coupons',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame(
			'The approver does not have permission for this action.',
			$response->get_data()['message']
		);
	}

	/**
	 * @testdox Unauthenticated request returns 401.
	 */
	public function test_unauthenticated_returns_401(): void {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox User without pos_access gets 403.
	 */
	public function test_user_without_pos_access_returns_403(): void {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'pin'     => '7391',
				'action'  => 'refund_shop_orders',
				'context' => array( 'order_id' => 42 ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );

		wp_delete_user( $customer_id );
	}

	private function reset_roles(): void {
		WC_Install::remove_roles();
		WC_Install::create_roles();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_roles'] = new \WP_Roles();
	}
}
