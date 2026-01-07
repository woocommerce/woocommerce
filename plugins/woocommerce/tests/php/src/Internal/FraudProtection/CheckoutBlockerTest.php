<?php
/**
 * CheckoutBlockerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutBlocker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for CheckoutBlocker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CheckoutBlocker
 */
class CheckoutBlockerTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutBlocker
	 */
	private $sut;

	/**
	 * Mock session clearance manager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_session_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_session_manager = $this->createMock( SessionClearanceManager::class );

		$this->sut = new CheckoutBlocker();
		$this->sut->init( $this->mock_session_manager );
		$this->sut->register();

		// Enable BACS payment gateway for testing.
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes' ) );
		WC()->payment_gateways()->init();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_available_payment_gateways' );
		remove_all_filters( 'rest_pre_dispatch' );
		remove_all_actions( 'woocommerce_before_checkout_form' );
	}

	/**
	 * @testdox Should filter out payment gateways via WC()->payment_gateways() when session is not allowed.
	 */
	public function test_wc_payment_gateways_returns_empty_for_non_allowed_session(): void {
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		$this->assertEmpty( $gateways, 'Non-allowed sessions should have no payment gateways available' );
	}

	/**
	 * @testdox Should preserve payment gateways via WC()->payment_gateways() when session is allowed.
	 */
	public function test_wc_payment_gateways_returns_gateways_for_allowed_session(): void {
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( true );

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		$this->assertNotEmpty( $gateways, 'Allowed sessions should have payment gateways available' );
	}

	/**
	 * @testdox Should display error notice when woocommerce_before_checkout_form action fires for blocked sessions.
	 */
	public function test_checkout_action_displays_blocked_message(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_before_checkout_form' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'unable to process your order', $output, 'Should display blocked message on checkout' );
	}

	/**
	 * @testdox Should not display message when checkout action fires for non-blocked sessions.
	 */
	public function test_checkout_action_no_message_for_non_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( false );

		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_before_checkout_form' );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Non-blocked sessions should not display any message' );
	}

	/**
	 * @testdox Should block checkout requests for blocked sessions.
	 */
	public function test_rest_server_blocks_checkout_for_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		$request  = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Blocked session checkout request should return 403' );
		$this->assertStringContainsString( 'unable to process your order', $response->get_data()['message'], 'Blocked session checkout request should return blocked message' );
	}

	/**
	 * @testdox Should allow checkout requests for non-blocked sessions.
	 */
	public function test_rest_server_allows_checkout_for_non_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( false );

		$request  = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertStringNotContainsString( 'unable to process your order', $response->get_data()['message'] ?? '', 'Non-blocked session checkout request should not return blocked message' );
	}

	/**
	 * @testdox Should not block non-checkout Store API routes for blocked sessions.
	 */
	public function test_rest_server_allows_non_checkout_routes_for_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertStringNotContainsString( 'unable to process your order', $response->get_data()['message'] ?? '', 'Non-checkout Store API routes should not return blocked message' );
	}

	/**
	 * @testdox Should not block WC REST API admin routes for blocked sessions.
	 */
	public function test_rest_server_allows_admin_api_for_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		$request  = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertStringNotContainsString( 'unable to process your order', $response->get_data()['message'] ?? '', 'WC REST API admin routes should not return blocked message' );
	}
}
