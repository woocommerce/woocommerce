<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\CapabilityEnforcement;
use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Install;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;

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
		remove_all_filters( 'rest_pre_dispatch' );
		remove_all_filters( 'rest_request_before_callbacks' );
		remove_all_filters( 'rest_post_dispatch' );
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
	 * @testdox A user without refund_shop_orders cannot create refunds.
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
	 * @testdox A user with refund_shop_orders can create refunds.
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
	 * @testdox A POS manager can cancel orders through the REST API.
	 */
	public function test_pos_manager_can_cancel_orders_via_rest_api(): void {
		wp_set_current_user( $this->capable_user_id );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
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
				if ( 'refund_shop_orders' === $capability ) {
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
			'refund_shop_orders',
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
	 * @testdox A user cannot refund with an approval token that is missing order scope.
	 */
	public function test_user_cannot_refund_with_unscoped_approval_token(): void {
		wp_set_current_user( $this->limited_user_id );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array()
		);

		$_POST['_pos_approval'] = $token;
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/v3/orders/123/refunds';

		$result = $this->sut->enforce_capabilities( true, 'create', 0, 'shop_order_refund' );

		unset( $_POST['_pos_approval'], $_SERVER['REQUEST_URI'] );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox A refund approval token scoped to order A cannot be used on order B.
	 */
	public function test_refund_token_cannot_be_used_across_orders(): void {
		wp_set_current_user( $this->limited_user_id );
		$order_a = $this->create_order_as_current_user( 'completed' );
		$order_b = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order_a->get_id() )
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order_b->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'         => '1.00',
				'reason'         => 'Testing cross-order token',
				'api_refund'     => false,
				'_pos_approval'  => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox An approval token is consumed after a single use and cannot be reused.
	 */
	public function test_approval_token_is_single_use(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		$first_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$first_request->set_body_params(
			array(
				'amount'         => '1.00',
				'reason'         => 'First refund',
				'api_refund'     => false,
				'_pos_approval'  => $token,
			)
		);

		$first_response = $this->server->dispatch( $first_request );
		$this->assertNotSame( 403, $first_response->get_status() );

		$second_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$second_request->set_body_params(
			array(
				'amount'         => '1.00',
				'reason'         => 'Second refund with same token',
				'api_refund'     => false,
				'_pos_approval'  => $token,
			)
		);

		$second_response = $this->server->dispatch( $second_request );
		$this->assertSame( 401, $second_response->get_status() );
	}

	/**
	 * @testdox An expired (deleted) approval token is rejected.
	 */
	public function test_expired_approval_token_is_rejected(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		delete_transient( '_wc_pos_approval_' . hash( 'sha256', $token ) );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'         => '1.00',
				'reason'         => 'Refund with expired token',
				'api_refund'     => false,
				'_pos_approval'  => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
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
	 * @testdox POS-only users cannot list WordPress users outside the POS surface.
	 */
	public function test_pos_only_users_cannot_list_wordpress_users(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	/**
	 * @testdox POS-only users can still read their own profile endpoint.
	 */
	public function test_pos_only_users_can_access_own_user_endpoint(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
	}

	/**
	 * @testdox Cashiers can read customers but cannot edit them.
	 */
	public function test_cashier_customer_access_matches_pos_caps(): void {
		wp_set_current_user( $this->limited_user_id );

		$list_request  = new WP_REST_Request( 'GET', '/wc/v3/customers' );
		$list_response = $this->server->dispatch( $list_request );

		$this->assertSame( 200, $list_response->get_status() );

		$edit_request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
		$edit_request->set_body_params(
			array(
				'email'      => 'cashier-created@example.com',
				'first_name' => 'Cashier',
				'last_name'  => 'Customer',
				'username'   => 'cashier-created',
			)
		);

		$edit_response = $this->server->dispatch( $edit_request );

		$this->assertSame( 403, $edit_response->get_status() );
	}

	/**
	 * @testdox POS managers can read and edit customers through the REST API.
	 */
	public function test_pos_manager_customer_access_matches_pos_caps(): void {
		wp_set_current_user( $this->capable_user_id );

		$list_request  = new WP_REST_Request( 'GET', '/wc/v3/customers' );
		$list_response = $this->server->dispatch( $list_request );

		$this->assertSame( 200, $list_response->get_status() );

		$create_request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
		$create_request->set_body_params(
			array(
				'email'      => 'manager-created@example.com',
				'first_name' => 'Manager',
				'last_name'  => 'Customer',
				'username'   => 'manager-created',
			)
		);

		$create_response = $this->server->dispatch( $create_request );

		$this->assertSame( 201, $create_response->get_status() );
	}

	/**
	 * @testdox Cashiers cannot access sales reports without the dedicated capability.
	 */
	public function test_cashier_cannot_access_reports_without_sales_capability(): void {
		wp_set_current_user( $this->limited_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/reports' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	/**
	 * @testdox Financial report fields are removed for users without financial report access.
	 */
	public function test_financial_report_fields_are_filtered_for_non_financial_users(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/revenue/stats' );
		$response = new WP_REST_Response(
			array(
				'total_sales'   => 20,
				'gross_profit'  => 10,
				'intervals'     => array(
					array(
						'subtotal'      => 15,
						'profit_margin' => 0.5,
					),
				),
			)
		);

		$filtered = $this->sut->filter_sensitive_report_data( $response, rest_get_server(), $request );
		$data     = $filtered->get_data();

		$this->assertArrayHasKey( 'total_sales', $data );
		$this->assertArrayNotHasKey( 'gross_profit', $data );
		$this->assertArrayNotHasKey( 'profit_margin', $data['intervals'][0] );
	}

	/**
	 * @testdox Stock-only product updates are allowed with edit_products (pos_manager).
	 */
	public function test_stock_only_product_updates_are_allowed_with_adjust_stock_capability(): void {
		wp_set_current_user( $this->capable_user_id );

		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'manage_stock'   => true,
				'stock_quantity' => 4,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status() );
		$this->assertSame( 4, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Product edits beyond stock still require the normal product-edit permissions.
	 */
	public function test_non_stock_product_updates_still_require_product_edit_permissions(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$user    = new \WP_User( $user_id );
		$user->add_cap( 'manage_woocommerce' );

		wp_set_current_user( $user_id );

		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'name' => 'Changed product name',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox POS cashiers cannot create products via the REST API.
	 */
	public function test_pos_cashier_cannot_create_products(): void {
		wp_set_current_user( $this->limited_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request->set_body_params(
			array(
				'name'          => 'Test Product',
				'type'          => 'simple',
				'regular_price' => '9.99',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS cashiers cannot delete products via the REST API.
	 */
	public function test_pos_cashier_cannot_delete_products(): void {
		wp_set_current_user( $this->admin_id );
		$product = WC_Helper_Product::create_simple_product();

		wp_set_current_user( $this->limited_user_id );

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/' . $product->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS cashiers cannot list coupons via the REST API.
	 */
	public function test_pos_cashier_cannot_list_coupons(): void {
		wp_set_current_user( $this->limited_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/coupons' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS managers cannot manage coupons via the REST API.
	 */
	public function test_pos_manager_cannot_manage_coupons(): void {
		wp_set_current_user( $this->capable_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v3/coupons' );
		$request->set_body_params(
			array(
				'code'          => 'test-coupon',
				'discount_type' => 'percent',
				'amount'        => '10',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS cashiers cannot access WooCommerce settings via the REST API.
	 */
	public function test_pos_cashier_cannot_access_wc_settings(): void {
		wp_set_current_user( $this->limited_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/settings' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS managers cannot access WooCommerce settings via the REST API.
	 */
	public function test_pos_manager_cannot_access_wc_settings(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/settings' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS managers cannot change user roles via the WordPress REST API.
	 */
	public function test_pos_manager_cannot_change_user_roles(): void {
		wp_set_current_user( $this->capable_user_id );

		$target_user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/users/' . $target_user_id );
		$request->set_body_params(
			array(
				'roles' => array( 'administrator' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox POS cashiers cannot list application passwords via the WordPress REST API.
	 */
	public function test_pos_cashier_cannot_list_application_passwords(): void {
		wp_set_current_user( $this->limited_user_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/users/' . $this->limited_user_id . '/application-passwords' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox Refund request with no approval token returns the generic 403 denial.
	 */
	public function test_refund_without_token_returns_generic_permission_denial(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'reason'     => 'Testing refund',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_create', $response->get_data()['code'] );
	}

	/**
	 * @testdox Refund request with an expired approval token returns a structured 401 approval error.
	 */
	public function test_refund_with_expired_token_returns_approval_error(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		delete_transient( '_wc_pos_approval_' . hash( 'sha256', $token ) );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'Refund with expired token',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_approval_invalid_or_expired', $response->get_data()['code'] );
	}

	/**
	 * @testdox Refund request with an already-consumed approval token returns a structured 401 approval error.
	 */
	public function test_refund_with_consumed_token_returns_approval_error(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		$first_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$first_request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'First refund',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);
		$first_response = $this->server->dispatch( $first_request );
		$this->assertNotSame( 403, $first_response->get_status() );

		$second_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$second_request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'Second refund with same token',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);

		$second_response = $this->server->dispatch( $second_request );

		$this->assertSame( 401, $second_response->get_status() );
		$this->assertSame( 'woocommerce_pos_approval_invalid_or_expired', $second_response->get_data()['code'] );
	}

	/**
	 * @testdox Refund request with a token scoped to a different order returns a structured 401 approval error.
	 */
	public function test_refund_with_wrong_order_token_returns_approval_error(): void {
		wp_set_current_user( $this->limited_user_id );
		$order_a = $this->create_order_as_current_user( 'completed' );
		$order_b = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order_a->get_id() )
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order_b->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'Cross-order token',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_approval_order_mismatch', $response->get_data()['code'] );
	}

	/**
	 * @testdox Refund request with a valid approval token succeeds with a 2xx status.
	 */
	public function test_refund_with_valid_token_succeeds(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'Valid token refund',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * @testdox log_approval_consumed writes a log entry to the woocommerce-pos source when a token is consumed.
	 */
	public function test_log_approval_consumed_writes_log_entry(): void {
		wp_set_current_user( $this->limited_user_id );
		$order = $this->create_order_as_current_user( 'completed' );

		$captured_logs = array();
		$capture       = function ( $message, $level, $context ) use ( &$captured_logs ) {
			unset( $level );
			if ( is_array( $context ) && isset( $context['source'] ) && 'woocommerce-pos' === $context['source'] ) {
				$captured_logs[] = $message;
			}
			return $message;
		};
		add_filter( 'woocommerce_logger_log_message', $capture, 10, 3 );

		$token = $this->approval_service->create_approval(
			$this->capable_user_id,
			'refund_shop_orders',
			array( 'order_id' => $order->get_id() )
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'        => '1.00',
				'reason'        => 'Logged refund',
				'api_refund'    => false,
				'_pos_approval' => $token,
			)
		);

		$response = $this->server->dispatch( $request );

		remove_filter( 'woocommerce_logger_log_message', $capture, 10 );

		$this->assertSame( 201, $response->get_status() );

		$matching = array_filter(
			$captured_logs,
			static function ( $message ) {
				return is_string( $message )
					&& str_contains( $message, 'POS override consumed' )
					&& str_contains( $message, 'refund_shop_orders' );
			}
		);
		$this->assertNotEmpty( $matching, 'A woocommerce-pos log entry should be written on approval consumption.' );
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
	 * @testdox register adds REST permission and dispatch filters.
	 */
	public function test_register_adds_rest_filters(): void {
		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'rest_pre_dispatch' );
		remove_all_filters( 'rest_request_before_callbacks' );
		remove_all_filters( 'rest_post_dispatch' );

		$handler = new CapabilityEnforcement();
		$handler->init( $this->approval_service );
		$handler->register();

		$this->assertNotFalse(
			has_filter(
				'woocommerce_rest_check_permissions',
				array( $handler, 'enforce_capabilities' )
			)
		);
		$this->assertNotFalse(
			has_filter(
				'rest_pre_dispatch',
				array( $handler, 'capture_current_rest_route' )
			)
		);
		$this->assertNotFalse(
			has_filter(
				'rest_request_before_callbacks',
				array( $handler, 'enforce_route_access' )
			)
		);
		$this->assertNotFalse(
			has_filter(
				'rest_post_dispatch',
				array( $handler, 'filter_sensitive_report_data' )
			)
		);

		remove_all_filters( 'woocommerce_rest_check_permissions' );
		remove_all_filters( 'rest_pre_dispatch' );
		remove_all_filters( 'rest_request_before_callbacks' );
		remove_all_filters( 'rest_post_dispatch' );
	}

	/**
	 * @testdox Administrator POST /wc/v3/customers returns 201.
	 */
	public function test_admin_post_customers_allowed(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
		$request->set_body_params(
			array(
				'email'      => 'admin-created@example.com',
				'first_name' => 'Admin',
				'last_name'  => 'Customer',
				'username'   => 'admin-created-customer',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * @testdox POS manager POST /wc/v3/customers returns 201 when they have create_customers.
	 */
	public function test_pos_manager_post_customers_allowed(): void {
		wp_set_current_user( $this->capable_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
		$request->set_body_params(
			array(
				'email'      => 'manager-created-extra@example.com',
				'first_name' => 'Manager',
				'last_name'  => 'Extra',
				'username'   => 'manager-created-extra',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * @testdox POS cashier POST /wc/v3/customers returns 201 because the role has create_customers.
	 */
	public function test_pos_cashier_post_customers_allowed(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
		$request->set_body_params(
			array(
				'email'      => 'cashier-created@example.com',
				'first_name' => 'Cashier',
				'last_name'  => 'Walkin',
				'username'   => 'cashier-created-walkin',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * @testdox POS manager GET /wc/v3/settings/point-of-sale returns 200 via view_pos_settings.
	 */
	public function test_pos_manager_reads_pos_settings_group(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/settings/point-of-sale' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * @testdox POS cashier GET /wc/v3/settings/point-of-sale returns 403 (lacks view_pos_settings).
	 */
	public function test_pos_cashier_cannot_read_pos_settings_group(): void {
		wp_set_current_user( $this->limited_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/settings/point-of-sale' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox POS manager cannot read unrelated settings group (e.g. general) without manage_woocommerce.
	 */
	public function test_pos_manager_cannot_read_unrelated_settings_group(): void {
		wp_set_current_user( $this->capable_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/settings/general' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}
}
