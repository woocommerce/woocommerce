<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\RestApi\POSPinManageController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Install;
use WC_REST_Unit_Test_Case;

/**
 * Tests for POSPinManageController.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\RestApi\POSPinManageController
 * @since 10.8.0
 */
class POSPinManageControllerTest extends WC_REST_Unit_Test_Case {

	private const MANAGE_ROUTE = '/wc/v3/pos/auth/pin/manage';
	private const STATUS_ROUTE = '/wc/v3/pos/auth/pin/status';

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var int
	 */
	private int $pos_manager_id;

	/**
	 * @var int
	 */
	private int $pos_cashier_id;

	/**
	 * @var int
	 */
	private int $pos_cashier2_id;

	public function setUp(): void {
		parent::setUp();

		$this->reset_roles();

		$this->pos_manager_id  = $this->factory->user->create(
			array(
				'role'         => 'pos_manager',
				'display_name' => 'Test Manager',
			)
		);
		$this->pos_cashier_id  = $this->factory->user->create(
			array(
				'role'         => 'pos_cashier',
				'display_name' => 'Test Cashier',
			)
		);
		$this->pos_cashier2_id = $this->factory->user->create(
			array(
				'role'         => 'pos_cashier',
				'display_name' => 'Test Cashier 2',
			)
		);

		$this->pin_service = new POSPinService();

		$controller = new POSPinManageController();
		$controller->init( $this->pin_service );
		$controller->register();
		$controller->register_routes();
	}

	public function tearDown(): void {
		if ( isset( $this->pos_cashier_id ) ) {
			$this->pin_service->delete_pin( $this->pos_cashier_id );
			wp_delete_user( $this->pos_cashier_id );
		}
		if ( isset( $this->pos_cashier2_id ) ) {
			$this->pin_service->delete_pin( $this->pos_cashier2_id );
			wp_delete_user( $this->pos_cashier2_id );
		}
		if ( isset( $this->pos_manager_id ) ) {
			$this->pin_service->delete_pin( $this->pos_manager_id );
			wp_delete_user( $this->pos_manager_id );
		}
		$this->reset_roles();
		parent::tearDown();
	}

	/**
	 * @testdox Manager can set PIN for cashier and receives 200 with success true.
	 */
	public function test_manager_can_set_pin_for_cashier(): void {
		wp_set_current_user( $this->pos_manager_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier_id,
				'action'  => 'set',
				'pin'     => '8472',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $this->pin_service->has_pin( $this->pos_cashier_id ) );
	}

	/**
	 * @testdox Manager can delete PIN for cashier and receives 200 with success true.
	 */
	public function test_manager_can_delete_pin_for_cashier(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_manager_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier_id,
				'action'  => 'delete',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertFalse( $this->pin_service->has_pin( $this->pos_cashier_id ) );
	}

	/**
	 * @testdox Cashier cannot set PIN for another user and gets 403.
	 */
	public function test_cashier_cannot_set_pin_for_another_user(): void {
		wp_set_current_user( $this->pos_cashier_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier2_id,
				'action'  => 'set',
				'pin'     => '8472',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox PIN status returns list of POS users with has_pin field.
	 */
	public function test_pin_status_returns_pos_users_with_has_pin(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_manager_id );

		$request  = new \WP_REST_Request( 'GET', self::STATUS_ROUTE );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'users', $data );
		$this->assertIsArray( $data['users'] );

		$user_ids = array_column( $data['users'], 'user_id' );
		$this->assertContains( $this->pos_cashier_id, $user_ids );
		$this->assertContains( $this->pos_manager_id, $user_ids );

		foreach ( $data['users'] as $user ) {
			$this->assertArrayHasKey( 'user_id', $user );
			$this->assertArrayHasKey( 'display_name', $user );
			$this->assertArrayHasKey( 'role', $user );
			$this->assertArrayHasKey( 'has_pin', $user );
		}

		$cashier_entry = null;
		foreach ( $data['users'] as $user ) {
			if ( $user['user_id'] === $this->pos_cashier_id ) {
				$cashier_entry = $user;
				break;
			}
		}
		$this->assertNotNull( $cashier_entry );
		$this->assertTrue( $cashier_entry['has_pin'] );
	}

	/**
	 * @testdox PIN status requires woocommerce_manage_pos_staff, cashier gets 403.
	 */
	public function test_pin_status_requires_manage_pos_staff(): void {
		wp_set_current_user( $this->pos_cashier_id );

		$request  = new \WP_REST_Request( 'GET', self::STATUS_ROUTE );
		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox Setting a blocked PIN returns 422.
	 */
	public function test_setting_blocked_pin_returns_422(): void {
		wp_set_current_user( $this->pos_manager_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier_id,
				'action'  => 'set',
				'pin'     => '1234',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
	}

	/**
	 * @testdox Setting a duplicate PIN returns 422 with same generic message.
	 */
	public function test_setting_duplicate_pin_returns_422(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_manager_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier2_id,
				'action'  => 'set',
				'pin'     => '8472',
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'invalid_pin', $data['code'] );
	}

	/**
	 * @testdox Self-update PIN requires current_pin when user already has a PIN.
	 */
	public function test_self_update_pin_requires_current_pin(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_cashier_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'action' => 'set',
				'pin'    => '9753',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_missing_current_pin', $response->get_data()['code'] );
	}

	/**
	 * @testdox Self-update PIN fails when current_pin is incorrect.
	 */
	public function test_self_update_pin_fails_with_wrong_current_pin(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_cashier_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'action'      => 'set',
				'pin'         => '9753',
				'current_pin' => '0000',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_invalid_current_pin', $response->get_data()['code'] );
	}

	/**
	 * @testdox Self-update PIN succeeds when correct current_pin is provided.
	 */
	public function test_self_update_pin_succeeds_with_correct_current_pin(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_cashier_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'action'      => 'set',
				'pin'         => '9753',
				'current_pin' => '8472',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * @testdox First-time self PIN setup does not require current_pin.
	 */
	public function test_first_time_self_pin_setup_does_not_require_current_pin(): void {
		wp_set_current_user( $this->pos_cashier_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'action' => 'set',
				'pin'    => '8472',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * @testdox Manager setting PIN for another user does not require current_pin.
	 */
	public function test_manager_set_pin_for_other_does_not_require_current_pin(): void {
		$this->pin_service->set_pin( $this->pos_cashier_id, '8472' );
		wp_set_current_user( $this->pos_manager_id );

		$request = new \WP_REST_Request( 'POST', self::MANAGE_ROUTE );
		$request->set_body_params(
			array(
				'user_id' => $this->pos_cashier_id,
				'action'  => 'set',
				'pin'     => '9753',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	private function reset_roles(): void {
		WC_Install::remove_roles();
		WC_Install::create_roles();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_roles'] = new \WP_Roles();
	}
}
