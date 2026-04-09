<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\CapabilityEnforcement;
use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use WC_Helper_Order;
use WC_Install;
use WC_REST_Unit_Test_Case;

/**
 * Tests for capability enforcement on REST API endpoints.
 *
 * @covers \Automattic\WooCommerce\Internal\CapabilityEnforcement
 */
class CapabilityEnforcementTest extends WC_REST_Unit_Test_Case {

	/**
	 * @var CapabilityEnforcement
	 */
	private $sut;

	/**
	 * @var POSApprovalService
	 */
	private POSApprovalService $approval_service;

	/**
	 * @var int
	 */
	private $limited_user_id;

	/**
	 * @var int
	 */
	private $capable_user_id;

	/**
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_roles();

		$this->limited_user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->capable_user_id = $this->factory->user->create( array( 'role' => 'pos_manager' ) );
		$this->admin_id        = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$this->approval_service = new POSApprovalService();

		$this->sut = new CapabilityEnforcement();
		$this->sut->init( $this->approval_service );
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'woocommerce_pos_capability_check' );
		remove_all_filters( 'woocommerce_rest_pre_insert_shop_order_object' );
		$this->reset_roles();
		parent::tearDown();
	}

	/**
	 * Remove and recreate all WC roles.
	 */
	private function reset_roles(): void {
		WC_Install::remove_roles();
		WC_Install::create_roles();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_roles'] = new \WP_Roles();
	}

	/**
	 * Create an order as the current user.
	 *
	 * @param string $status Order status.
	 * @return \WC_Order
	 */
	private function create_order_as_current_user( string $status = 'pending' ): \WC_Order {
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$order->set_status( $status );
		$order->save();
		return $order;
	}

	/**
	 * Create a user with order editing capabilities but without a specific capability.
	 *
	 * @param string $missing_cap The capability the user should lack.
	 * @return int User ID.
	 */
	private function create_user_without_cap( string $missing_cap ): int {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$user    = new \WP_User( $user_id );
		$user->remove_cap( $missing_cap );
		return $user_id;
	}

	/**
	 * @testdox A user with edit_shop_orders can create orders via the REST API.
	 */
	public function test_user_with_order_caps_can_create_orders(): void {
		wp_set_current_user( $this->limited_user_id );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'status' => 'pending',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * @testdox A user without woocommerce_refund_orders cannot create refunds.
	 */
	public function test_user_without_refund_cap_cannot_create_refunds(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'reason'     => 'Testing refund',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox A user with woocommerce_refund_orders can create refunds.
	 */
	public function test_user_with_refund_cap_can_create_refunds(): void {
		wp_set_current_user( $this->capable_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'reason'     => 'Testing refund',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
	}

	/**
	 * @testdox enforce_cancel_capability blocks users without woocommerce_void_orders from cancelling.
	 */
	public function test_user_without_void_cap_cannot_cancel_orders(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'pending' );

		$request = new \WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params( array( 'status' => 'cancelled' ) );

		$result = $this->sut->enforce_cancel_capability( $order, $request, false );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'woocommerce_rest_cannot_cancel', $result->get_error_code() );
	}

	/**
	 * @testdox enforce_cancel_capability allows users with woocommerce_void_orders to cancel.
	 */
	public function test_user_with_void_cap_can_cancel_orders(): void {
		wp_set_current_user( $this->capable_user_id );
		$order = $this->create_order_as_current_user( 'pending' );

		$request = new \WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params( array( 'status' => 'cancelled' ) );

		$result = $this->sut->enforce_cancel_capability( $order, $request, false );

		$this->assertNotInstanceOf( \WP_Error::class, $result );
		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox enforce_cancel_capability does not restrict non-cancel status updates.
	 */
	public function test_user_can_update_order_status_to_non_cancelled(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'pending' );

		$request = new \WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params( array( 'status' => 'processing' ) );

		$result = $this->sut->enforce_cancel_capability( $order, $request, false );

		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox enforce_cancel_capability skips check during order creation.
	 */
	public function test_enforce_cancel_capability_skips_on_create(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'pending' );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params( array( 'status' => 'cancelled' ) );

		$result = $this->sut->enforce_cancel_capability( $order, $request, true );

		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox Administrator can create refunds.
	 */
	public function test_administrator_can_create_refunds(): void {
		wp_set_current_user( $this->admin_id );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'reason'     => 'Testing refund',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
	}

	/**
	 * @testdox Administrator can cancel orders.
	 */
	public function test_administrator_can_cancel_orders(): void {
		wp_set_current_user( $this->admin_id );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$request = new \WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'status' => 'cancelled',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
	}

	/**
	 * @testdox Enforcement applies universally, not just to POS roles.
	 */
	public function test_enforcement_applies_to_any_user_without_cap(): void {
		$editor_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		$user      = new \WP_User( $editor_id );
		$user->add_cap( 'edit_shop_orders' );
		$user->add_cap( 'publish_shop_orders' );
		$user->add_cap( 'read_shop_order' );

		wp_set_current_user( $editor_id );

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox The woocommerce_pos_capability_check filter allows granting refund capability.
	 */
	public function test_capability_check_can_be_filtered(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		add_filter(
			'woocommerce_pos_capability_check',
			function ( $has_cap, $capability, $user_id ) {
				unset( $user_id );
				if ( 'woocommerce_refund_orders' === $capability ) {
					return true;
				}
				return $has_cap;
			},
			10,
			3
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'reason'     => 'Testing refund',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
	}

	/**
	 * @testdox enforce_capabilities returns false when permission is already false.
	 */
	public function test_enforce_capabilities_preserves_denial(): void {
		wp_set_current_user( $this->limited_user_id );

		$result = $this->sut->enforce_capabilities( false, 'create', 0, 'shop_order_refund' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox enforce_capabilities denies refund creation for user without cap.
	 */
	public function test_enforce_capabilities_denies_refund_without_cap(): void {
		wp_set_current_user( $this->limited_user_id );

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox enforce_capabilities allows refund creation for user with cap.
	 */
	public function test_enforce_capabilities_allows_refund_with_cap(): void {
		wp_set_current_user( $this->capable_user_id );

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox A user can process a refund when providing a valid approval token via the filter.
	 */
	public function test_user_can_refund_with_approval_token(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'woocommerce_refund_orders',
			array( 'order_id' => $order->get_id() )
		);

		$_POST['_pos_approval']    = $token;
		$_SERVER['REQUEST_URI']    = '/wp-json/wc/v3/orders/' . $order->get_id() . '/refunds';

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		unset( $_POST['_pos_approval'], $_SERVER['REQUEST_URI'] );

		$this->assertTrue( $result );

		$notes      = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$found_note = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'POS override' ) ) {
				$found_note = true;
				break;
			}
		}
		$this->assertTrue( $found_note, 'An order note should be added when an approval token is consumed.' );
	}

	/**
	 * @testdox A user cannot refund with an invalid approval token.
	 */
	public function test_user_cannot_refund_with_invalid_approval_token(): void {
		wp_set_current_user( $this->limited_user_id );

		$_POST['_pos_approval'] = 'invalid-token-value';

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		unset( $_POST['_pos_approval'] );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox A user cannot refund without an approval token.
	 */
	public function test_user_cannot_refund_without_approval_token(): void {
		wp_set_current_user( $this->limited_user_id );

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox enforce_capabilities does not restrict non-refund contexts.
	 */
	public function test_enforce_capabilities_allows_order_create(): void {
		wp_set_current_user( $this->limited_user_id );

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox register adds the woocommerce_rest_check_permissions filter.
	 */
	public function test_register_adds_permission_filter(): void {
		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'woocommerce_rest_pre_insert_shop_order_object' );

		$handler = new CapabilityEnforcement();
		$handler->init( $this->approval_service );
		$handler->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_rest_check_permissions', array( $handler, 'enforce_capabilities' ) )
		);

		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'woocommerce_rest_pre_insert_shop_order_object' );
	}

	/**
	 * @testdox register adds the order pre-insert filter.
	 */
	public function test_register_adds_pre_insert_filter(): void {
		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'woocommerce_rest_pre_insert_shop_order_object' );

		$handler = new CapabilityEnforcement();
		$handler->init( $this->approval_service );
		$handler->register();

		$this->assertNotFalse(
			has_filter(
				'woocommerce_rest_pre_insert_shop_order_object',
				array( $handler, 'enforce_cancel_capability' )
			)
		);

		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'woocommerce_rest_pre_insert_shop_order_object' );
	}
}
