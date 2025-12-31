<?php
/**
 * CheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
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
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_data_collector;

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
		$this->mock_tracker        = $this->createMock( FraudProtectionTracker::class );
		$this->mock_data_collector = $this->createMock( SessionDataCollector::class );
		$this->mock_controller     = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new CheckoutEventTracker();
		$this->sut->init(
			$this->mock_tracker,
			$this->mock_data_collector,
			$this->mock_controller
		);
	}

	/**
	 * Helper method to trigger scheduled tracking for testing.
	 *
	 * Simulates the scheduled action running by manually calling process_scheduled_tracking
	 * with test data. In real scenarios, Action Scheduler would call this automatically.
	 *
	 * @param string $event_type     Event type for the scheduled tracking.
	 * @param array  $collected_data Collected session data.
	 */
	private function trigger_scheduled_tracking( string $event_type = 'checkout_field_update', array $collected_data = array() ): void {
		$session_id = WC()->session->get_customer_id();

		$this->sut->process_scheduled_tracking(
			array(
				'session_id'     => $session_id,
				'event_type'     => $event_type,
				'collected_data' => $collected_data,
				'timestamp'      => time(),
			)
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
		$this->assertFalse( has_action( 'woocommerce_fraud_protection_track_checkout_event', array( $this->sut, 'process_scheduled_tracking' ) ) );
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
		$this->assertNotFalse( has_action( 'woocommerce_fraud_protection_track_checkout_event', array( $this->sut, 'process_scheduled_tracking' ) ) );
	}

	/**
	 * Test handle_checkout_field_update tracks event with billing email.
	 */
	public function test_handle_checkout_field_update_tracks_event_with_billing_email(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		$collected_data = array(
			'action'        => 'field_update',
			'billing_email' => 'test@example.com',
			'session'       => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector to return collected data.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock tracker to verify track_event is called with collected data.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->equalTo( $collected_data )
			);

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with billing email.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe';
		$this->sut->handle_checkout_field_update( $posted_data );

		// Trigger the scheduled action to track the event.
		$this->trigger_scheduled_tracking( 'checkout_field_update', $collected_data );
	}

	/**
	 * Test batching mechanism prevents rapid successive events.
	 */
	public function test_batching_prevents_rapid_successive_events(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		$collected_data1 = array(
			'action'        => 'field_update',
			'billing_email' => 'test1@example.com',
			'session'       => array( 'session_id' => 'test-session' ),
		);

		$collected_data2 = array(
			'action'        => 'field_update',
			'billing_email' => 'test2@example.com',
			'session'       => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector to return collected data for each call.
		$this->mock_data_collector
			->expects( $this->exactly( 2 ) )
			->method( 'collect' )
			->willReturnOnConsecutiveCalls( $collected_data1, $collected_data2 );

		// Mock tracker - should only be called once (for the last event).
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->equalTo( $collected_data2 )
			);

		// Register hooks.
		$this->sut->register();

		// First event - should be tracked.
		$posted_data1 = 'billing_email=test1@example.com';
		$this->sut->handle_checkout_field_update( $posted_data1 );

		// Second event immediately after - replaces the first one due to debouncing.
		$posted_data2 = 'billing_email=test2@example.com';
		$this->sut->handle_checkout_field_update( $posted_data2 );

		// Trigger the scheduled action to track only the last event.
		$this->trigger_scheduled_tracking( 'checkout_field_update', $collected_data2 );
	}

	/**
	 * Test handle_checkout_field_update tracks shipping method.
	 */
	public function test_handle_checkout_field_update_tracks_shipping_method(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		$collected_data = array(
			'action'           => 'field_update',
			'billing_email'    => 'test@example.com',
			'shipping_methods' => array( 'flat_rate:1' => 'Flat rate' ),
			'session'          => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector to return collected data.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock tracker to verify track_event is called with collected data.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->equalTo( $collected_data )
			);

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with shipping method.
		// shipping_method is passed as an array in the posted data.
		$posted_data = 'billing_email=test@example.com&shipping_method[0]=flat_rate:1';
		$this->sut->handle_checkout_field_update( $posted_data );

		// Trigger the scheduled action to track the event.
		$this->trigger_scheduled_tracking( 'checkout_field_update', $collected_data );
	}

	public function test_schedule_tracking_cancels_existing_actions(){

		WC()->session = $this->createMock( \WC_Session::class );
		WC()->session
			->method( 'get_customer_id' )
			->willReturn( 'test-session' );

			$collected_data = array(
			'action'           => 'field_update',
			'billing_email'    => 'test@example.com',
			'shipping_methods' => array( 'flat_rate:1' => 'Flat rate' ),
			'session'          => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector to return collected data.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );
		
		$this->sut->schedule_tracking( 'checkout_field_update', array( 'billing_email' => 'test@example.com' ) );

		// Verify action was scheduled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );

		$this->assertCount(
			1,
			$action_ids,
			'Expected one scheduled Action Scheduler action for fraud protection event, but found none.'
		);

		$this->sut->cancel_scheduled_tracking( 'test-session', 'checkout_field_update' );

		// Verify the action was cancelled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );
		$this->assertCount( 0, $action_ids, 'Expected no pending actions after cancellation' );

		// Cleanup: Remove all actions for this hook.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_fraud_protection_track_checkout_event', array(), 'woocommerce-fraud-protection' );
		}
	}
}
