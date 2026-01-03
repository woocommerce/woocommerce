<?php
/**
 * FraudProtectionPaymentMethodSelected Route Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler;

/**
 * FraudProtectionPaymentMethodSelected Route Tests.
 */
class FraudProtectionPaymentMethodSelectedTest extends ControllerTestCase {

	/**
	 * Test route registration.
	 */
	public function test_route_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/v1/fraud-protection/payment-method-selected', $routes );
	}

	/**
	 * Test POST request with valid payment method.
	 */
	public function test_post_request_with_valid_payment_method(): void {
		// Create a mock scheduler to capture the tracking call.
		$scheduler_called  = false;
		$captured_event    = null;
		$captured_data     = null;
		$mock_scheduler    = $this->createMock( CheckoutEventScheduler::class );
		$mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' )
			->willReturnCallback(
				function ( $event, $data ) use ( &$scheduler_called, &$captured_event, &$captured_data ) {
					$scheduler_called = true;
					$captured_event   = $event;
					$captured_data    = $data;
				}
			);

		// Replace the container instance with our mock.
		wc_get_container()->replace( CheckoutEventScheduler::class, $mock_scheduler );

		// Make POST request to the endpoint.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/fraud-protection/payment-method-selected' );
		$request->set_param( 'payment_method', 'stripe' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertIsBool( $data['success'] );
		$this->assertIsString( $data['message'] );

		// Assert response is successful.
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'Payment method tracked.', $data['message'] );

		// Assert scheduler was called with correct parameters.
		$this->assertTrue( $scheduler_called, 'Scheduler should have been called' );
		$this->assertEquals( 'checkout_blocks_payment_method_selected', $captured_event );
		$this->assertArrayHasKey( 'action', $captured_data );
		$this->assertEquals( 'payment_method_selected', $captured_data['action'] );
		$this->assertArrayHasKey( 'payment', $captured_data );
		$this->assertArrayHasKey( 'payment_method_type', $captured_data['payment'] );
		$this->assertEquals( 'stripe', $captured_data['payment']['payment_method_type'] );
	}

	/**
	 * Test POST request without payment_method parameter.
	 */
	public function test_post_request_without_payment_method(): void {
		// Make POST request without payment_method parameter.
		$request  = new \WP_REST_Request( 'POST', '/wc/store/v1/fraud-protection/payment-method-selected' );
		$response = rest_get_server()->dispatch( $request );

		// Assert response is an error.
		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test that the endpoint is publicly accessible (no authentication required).
	 */
	public function test_endpoint_publicly_accessible(): void {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Create a mock scheduler.
		$mock_scheduler = $this->createMock( CheckoutEventScheduler::class );
		$mock_scheduler->expects( $this->once() )->method( 'schedule_tracking' );

		// Replace the container instance with our mock.
		wc_get_container()->replace( CheckoutEventScheduler::class, $mock_scheduler );

		// Make POST request to the endpoint.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/fraud-protection/payment-method-selected' );
		$request->set_param( 'payment_method', 'stripe' );

		$response = rest_get_server()->dispatch( $request );

		// Assert response is successful even without authentication.
		$this->assertEquals( 200, $response->get_status() );
	}
}
