<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\RestApi\POSPinAuthController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use WC_Install;
use WC_REST_Unit_Test_Case;
use WP_Application_Passwords;

/**
 * Tests for POSPinAuthController.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\RestApi\POSPinAuthController
 * @since 10.8.0
 */
class POSPinAuthControllerTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wc/v3/pos/auth/pin';

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var int
	 */
	private int $admin_id;

	/**
	 * @var int
	 */
	private int $cashier_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$this->markTestSkipped( 'WP_Application_Passwords is not available.' );
		}

		$this->reset_roles();

		$this->admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$this->pin_service = new POSPinService();
		$this->pin_service->set_pin( $this->cashier_id, '8472' );

		$controller = wc_get_container()->get( POSPinAuthController::class );
		$controller->register();
		$controller->register_routes();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( isset( $this->cashier_id ) ) {
			$this->cleanup_app_passwords( $this->cashier_id );
			$this->pin_service->delete_pin( $this->cashier_id );
			wp_delete_user( $this->cashier_id );
		}
		if ( isset( $this->admin_id ) ) {
			$this->cleanup_app_passwords( $this->admin_id );
			wp_delete_user( $this->admin_id );
		}
		$this->reset_roles();
		parent::tearDown();
	}

	/**
	 * @testdox Valid PIN returns 200 with expected response keys.
	 */
	public function test_valid_pin_returns_200_with_expected_keys(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '8472' ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'user_id', $data );
		$this->assertSame( $this->cashier_id, $data['user_id'] );
		$this->assertArrayHasKey( 'user_login', $data );
		$this->assertArrayHasKey( 'display_name', $data );
		$this->assertArrayHasKey( 'role', $data );
		$this->assertArrayHasKey( 'capabilities', $data );
		$this->assertArrayHasKey( 'application_password', $data );
		$this->assertNotEmpty( $data['application_password'] );
		$this->assertArrayHasKey( 'application_password_uuid', $data );
		$this->assertNotEmpty( $data['application_password_uuid'] );
		$this->assertArrayHasKey( 'session_expires', $data );
		$this->assertArrayHasKey( 'idle_timeout_seconds', $data );
		$this->assertIsInt( $data['idle_timeout_seconds'] );
	}

	/**
	 * @testdox Wrong PIN returns 422 with generic error.
	 */
	public function test_wrong_pin_returns_422(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '9999' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'The provided PIN is not valid.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Invalid format PIN returns 422 with same generic error.
	 */
	public function test_invalid_format_returns_422(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => 'abc' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'The provided PIN is not valid.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Unauthenticated request returns 401.
	 */
	public function test_unauthenticated_returns_401(): void {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '8472' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox User without view_pos capability gets generic error.
	 */
	public function test_user_without_pos_access_returns_422(): void {
		wp_set_current_user( $this->admin_id );

		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $customer_id, POSPinService::PIN_HASH_META_KEY, $this->pin_service->hash_pin( '5937' ) );
		update_user_meta( $customer_id, POSPinService::PIN_INDEX_META_KEY, $this->pin_service->compute_pin_index( '5937' ) );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '5937' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'The provided PIN is not valid.', $response->get_data()['message'] );

		$this->pin_service->delete_pin( $customer_id );
		wp_delete_user( $customer_id );
	}

	/**
	 * @testdox Response capabilities include all granted capabilities (no woocommerce_ prefix filter).
	 */
	public function test_capabilities_include_all_granted_caps(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '8472' ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data['capabilities'] );
		$this->assertArrayHasKey( 'view_pos', $data['capabilities'] );

		$expected_caps = array(
			'view_pos',
			'edit_shop_orders',
			'read_shop_order',
			'read_private_shop_orders',
			'read_product',
			'read_private_products',
			'read_shop_coupon',
			'read_private_shop_coupons',
		);
		foreach ( $expected_caps as $cap ) {
			$this->assertArrayHasKey( $cap, $data['capabilities'], "Expected capability {$cap} to be present." );
			$this->assertTrue( $data['capabilities'][ $cap ] );
		}

		$non_prefixed_caps = array_filter(
			array_keys( $data['capabilities'] ),
			static function ( string $cap ): bool {
				return 0 !== strpos( $cap, 'woocommerce_' );
			}
		);
		$this->assertNotEmpty(
			$non_prefixed_caps,
			'Capabilities should not be restricted to only woocommerce_ prefixed ones.'
		);

		foreach ( $data['capabilities'] as $cap => $value ) {
			$this->assertTrue( $value );
		}
	}

	/**
	 * @testdox Verify endpoint logs an override entry when context.capability is provided.
	 */
	public function test_verify_logs_override_when_capability_context_provided(): void {
		wp_set_current_user( $this->cashier_id );

		$captured = array();
		add_filter(
			'woocommerce_logger_log_message',
			function ( $message, $level, $context ) use ( &$captured ) {
				if ( isset( $context['source'] ) && 'woocommerce-pos' === $context['source'] ) {
					$captured[] = compact( 'message', 'level' );
				}
				return $message;
			},
			10,
			3
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pos/auth/pin/verify' );
		$request->set_body_params(
			array(
				'pin'     => '8472',
				'context' => array( 'capability' => 'view_pos_settings' ),
			)
		);
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$override_logs = array_filter(
			$captured,
			static fn( $entry ) => false !== strpos( $entry['message'], 'POS verify override' )
		);
		$this->assertNotEmpty( $override_logs, 'Expected a POS verify override log entry.' );
		$entry = array_values( $override_logs )[0];
		$this->assertStringContainsString( 'view_pos_settings', $entry['message'] );
		$this->assertStringContainsString( 'granted', $entry['message'] );
	}

	/**
	 * @testdox Verify endpoint falls back to the standard success log when no override context is provided.
	 */
	public function test_verify_logs_standard_success_without_context(): void {
		wp_set_current_user( $this->admin_id );

		$captured = array();
		add_filter(
			'woocommerce_logger_log_message',
			function ( $message, $level, $context ) use ( &$captured ) {
				if ( isset( $context['source'] ) && 'woocommerce-pos' === $context['source'] ) {
					$captured[] = $message;
				}
				return $message;
			},
			10,
			3
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pos/auth/pin/verify' );
		$request->set_body_params( array( 'pin' => '8472' ) );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$override_lines = array_filter( $captured, static fn( $m ) => false !== strpos( $m, 'POS verify override' ) );
		$standard_lines = array_filter( $captured, static fn( $m ) => false !== strpos( $m, 'PIN verification succeeded' ) );
		$this->assertEmpty( $override_lines, 'Did not expect override log when context is empty.' );
		$this->assertNotEmpty( $standard_lines );
	}

	/**
	 * @testdox Response includes session_expires as ISO 8601 string and idle_timeout_seconds.
	 */
	public function test_response_includes_session_and_idle_timeout(): void {
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( array( 'pin' => '8472' ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		$expires_time = strtotime( $data['session_expires'] );
		$this->assertNotFalse( $expires_time );
		$this->assertGreaterThan( time(), $expires_time );

		$this->assertSame( POSSessionService::DEFAULT_IDLE_TIMEOUT, $data['idle_timeout_seconds'] );
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
	 * Remove all application passwords for a user.
	 *
	 * @param int $user_id The user ID.
	 */
	private function cleanup_app_passwords( int $user_id ): void {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return;
		}
		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		foreach ( $passwords as $pw ) {
			WP_Application_Passwords::delete_application_password( $user_id, $pw['uuid'] );
		}
	}
}
