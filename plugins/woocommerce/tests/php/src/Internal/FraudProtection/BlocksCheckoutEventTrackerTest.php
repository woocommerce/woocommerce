<?php
/**
 * BlocksCheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\BlocksCheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for BlocksCheckoutEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\BlocksCheckoutEventTracker
 */
class BlocksCheckoutEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var BlocksCheckoutEventTracker
	 */
	private $sut;

	/**
	 * Mock checkout event scheduler.
	 *
	 * @var CheckoutEventScheduler|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_scheduler;

	/**
	 * Mock fraud protection controller.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_controller;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		// Create mocks.
		$this->mock_scheduler  = $this->createMock( CheckoutEventScheduler::class );
		$this->mock_controller = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new BlocksCheckoutEventTracker();
		$this->sut->init(
			$this->mock_scheduler,
			$this->mock_controller
		);
	}

	/**
	 * Test that register does not register hooks when feature is disabled.
	 */
	public function test_register_does_not_register_hooks_when_feature_disabled(): void {
		// Mock feature as disabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( false );

		// Call register.
		$this->sut->register();

		// Verify hook was not registered.
		$this->assertFalse( has_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this->sut, 'handle_store_api_customer_update' ) ) );
	}

	/**
	 * Test that register registers hooks when feature is enabled.
	 */
	public function test_register_registers_hooks_when_feature_enabled(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Call register.
		$this->sut->register();

		// Verify hook was registered.
		$this->assertNotFalse( has_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this->sut, 'handle_store_api_customer_update' ) ) );
	}

	/**
	 * Test handle_store_api_customer_update schedules event with billing address.
	 */
	public function test_handle_store_api_customer_update_schedules_event_with_billing_address(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Create mock customer and request.
		$customer = $this->createMock( \WC_Customer::class );
		$request  = $this->createMock( \WP_REST_Request::class );

		// Mock request to return billing address with all fields.
		$request->method( 'get_param' )->willReturnMap(
			array(
				array(
					'billing_address',
					array(
						'first_name' => 'John',
						'last_name'  => 'Doe',
						'email'      => 'john@example.com',
						'phone'      => '555-1234',
						'address_1'  => '123 Main St',
						'address_2'  => 'Apt 4B',
						'city'       => 'New York',
						'state'      => 'NY',
						'postcode'   => '10001',
						'country'    => 'US',
					),
				),
				array( 'shipping_address', array() ),
			)
		);

		// Mock scheduler to capture event data.
		$captured_event_data = null;
		$this->mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' )
			->with(
				$this->equalTo( 'checkout_blocks_customer_update' ),
				$this->callback(
					function ( $event_data ) use ( &$captured_event_data ) {
						$captured_event_data = $event_data;
						return isset( $event_data['action'] )
							&& 'store_api_update' === $event_data['action']
							&& isset( $event_data['billing_email'] )
							&& 'john@example.com' === $event_data['billing_email']
							&& isset( $event_data['billing_first_name'] )
							&& 'John' === $event_data['billing_first_name'];
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Call the handler.
		$this->sut->handle_store_api_customer_update( $customer, $request );

		// Verify all billing fields were extracted.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'store_api_update', $captured_event_data['action'] );
		$this->assertEquals( 'John', $captured_event_data['billing_first_name'] );
		$this->assertEquals( 'Doe', $captured_event_data['billing_last_name'] );
		$this->assertEquals( 'john@example.com', $captured_event_data['billing_email'] );
		$this->assertEquals( '555-1234', $captured_event_data['billing_phone'] );
		$this->assertEquals( '123 Main St', $captured_event_data['billing_address_1'] );
		$this->assertEquals( 'Apt 4B', $captured_event_data['billing_address_2'] );
		$this->assertEquals( 'New York', $captured_event_data['billing_city'] );
		$this->assertEquals( 'NY', $captured_event_data['billing_state'] );
		$this->assertEquals( '10001', $captured_event_data['billing_postcode'] );
		$this->assertEquals( 'US', $captured_event_data['billing_country'] );
	}

	/**
	 * Test handle_store_api_customer_update schedules event with shipping address.
	 */
	public function test_handle_store_api_customer_update_schedules_event_with_shipping_address(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Create mock customer and request.
		$customer = $this->createMock( \WC_Customer::class );
		$request  = $this->createMock( \WP_REST_Request::class );

		// Mock request to return both billing and shipping addresses.
		$request->method( 'get_param' )->willReturnMap(
			array(
				array(
					'billing_address',
					array(
						'email' => 'john@example.com',
					),
				),
				array(
					'shipping_address',
					array(
						'first_name' => 'Jane',
						'last_name'  => 'Smith',
						'address_1'  => '456 Oak Ave',
						'city'       => 'Los Angeles',
						'country'    => 'US',
					),
				),
			)
		);

		// Mock scheduler to capture event data.
		$captured_event_data = null;
		$this->mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
				}
			);

		// Register hooks.
		$this->sut->register();

		// Call the handler.
		$this->sut->handle_store_api_customer_update( $customer, $request );

		// Verify shipping fields were extracted.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'Jane', $captured_event_data['shipping_first_name'] );
		$this->assertEquals( 'Smith', $captured_event_data['shipping_last_name'] );
		$this->assertEquals( '456 Oak Ave', $captured_event_data['shipping_address_1'] );
		$this->assertEquals( 'Los Angeles', $captured_event_data['shipping_city'] );
	}

	/**
	 * Test handle_store_api_customer_update handles empty addresses gracefully.
	 */
	public function test_handle_store_api_customer_update_handles_empty_addresses(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Create mock customer and request.
		$customer = $this->createMock( \WC_Customer::class );
		$request  = $this->createMock( \WP_REST_Request::class );

		// Mock request with empty addresses.
		$request->method( 'get_param' )->willReturnMap(
			array(
				array( 'billing_address', array() ),
				array( 'shipping_address', array() ),
			)
		);

		// Mock scheduler - should still be called.
		$this->mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' );

		// Register hooks.
		$this->sut->register();

		// Call the handler - should not throw errors.
		$this->sut->handle_store_api_customer_update( $customer, $request );
	}
}
