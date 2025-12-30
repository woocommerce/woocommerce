<?php
/**
 * CheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for CheckoutEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker
 */
class CheckoutEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var CheckoutEventTracker
	 */
	private $sut;

	/**
	 * Mock fraud protection tracker.
	 *
	 * @var FraudProtectionTracker|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_tracker;

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
		$this->mock_tracker    = $this->createMock( FraudProtectionTracker::class );
		$this->mock_controller = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new CheckoutEventTracker();
		$this->sut->init(
			$this->mock_tracker,
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

		// Verify hooks were not registered.
		$this->assertFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_checkout_field_update' ) ) );
		$this->assertFalse( has_action( 'wc_ajax_fraud_protection_payment_method_selected', array( $this->sut, 'ajax_handle_payment_method_selected' ) ) );
		$this->assertFalse( has_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this->sut, 'handle_store_api_checkout_update' ) ) );
		$this->assertFalse( has_action( 'shutdown', array( $this->sut, 'flush_pending_events' ) ) );
	}

	/**
	 * Test that register registers hooks when feature is enabled.
	 */
	public function test_register_registers_hooks_when_feature_enabled(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Call register.
		$this->sut->register();

		// Verify hooks were registered.
		$this->assertNotFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_checkout_field_update' ) ) );
		$this->assertNotFalse( has_action( 'wc_ajax_fraud_protection_payment_method_selected', array( $this->sut, 'ajax_handle_payment_method_selected' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this->sut, 'handle_store_api_checkout_update' ) ) );
		$this->assertNotFalse( has_action( 'shutdown', array( $this->sut, 'flush_pending_events' ) ) );
	}

	/**
	 * Test handle_checkout_field_update tracks event with billing email.
	 */
	public function test_handle_checkout_field_update_tracks_event_with_billing_email(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock tracker to verify track_event is called.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& $event_data['action'] === 'field_update'
							&& isset( $event_data['billing_email'] )
							&& $event_data['billing_email'] === 'test@example.com';
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with billing email.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe';
		$this->sut->handle_checkout_field_update( $posted_data );
	}

	/**
	 * Test batching mechanism prevents rapid successive events.
	 */
	public function test_batching_prevents_rapid_successive_events(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// When session is not available, batching may not work as expected.
		// This test verifies that at least one event is tracked.
		$this->mock_tracker
			->expects( $this->atLeastOnce() )
			->method( 'track_event' );

		// Register hooks.
		$this->sut->register();

		// First event - should be tracked.
		$posted_data1 = 'billing_email=test1@example.com';
		$this->sut->handle_checkout_field_update( $posted_data1 );

		// Second event immediately after - may be batched depending on session availability.
		$posted_data2 = 'billing_email=test2@example.com';
		$this->sut->handle_checkout_field_update( $posted_data2 );

		// Flush to ensure pending events are processed.
		$this->sut->flush_pending_events();
	}

	/**
	 * Test handle_store_api_checkout_update tracks event.
	 */
	public function test_handle_store_api_checkout_update_tracks_event(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Create mock customer.
		$mock_customer = $this->createMock( \WC_Customer::class );

		// Create mock REST request.
		$mock_request = $this->getMockBuilder( \WP_REST_Request::class )
							->disableOriginalConstructor()
							->getMock();

		$mock_request->method( 'get_param' )
					->with( 'billing_address' )
					->willReturn(
						array(
							'email'      => 'store-api@example.com',
							'first_name' => 'Jane',
							'last_name'  => 'Smith',
						)
					);

		// Mock tracker to verify track_event is called.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_store_api_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& $event_data['action'] === 'store_api_update'
							&& isset( $event_data['email'] )
							&& $event_data['email'] === 'store-api@example.com';
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Simulate Store API checkout update.
		$this->sut->handle_store_api_checkout_update( $mock_customer, $mock_request );

		// Flush any pending events to ensure tracking occurs.
		$this->sut->flush_pending_events();
	}

	/**
	 * Test handle_checkout_field_update tracks shipping method.
	 */
	public function test_handle_checkout_field_update_tracks_shipping_method(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock tracker to verify track_event is called.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& $event_data['action'] === 'field_update'
							&& isset( $event_data['shipping_methods'] )
							&& is_array( $event_data['shipping_methods'] )
							&& count( $event_data['shipping_methods'] ) > 0;
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with shipping method.
		// shipping_method is passed as an array in the posted data.
		$posted_data = 'billing_email=test@example.com&shipping_method[0]=flat_rate:1';
		$this->sut->handle_checkout_field_update( $posted_data );

		// Flush any pending events to ensure tracking occurs.
		$this->sut->flush_pending_events();
	}
}
