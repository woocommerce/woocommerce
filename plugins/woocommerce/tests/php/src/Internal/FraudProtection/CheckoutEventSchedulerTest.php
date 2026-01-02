<?php
/**
 * CheckoutEventSchedulerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * Tests for CheckoutEventScheduler.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler
 */
class CheckoutEventSchedulerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var CheckoutEventScheduler
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

		// Create system under test.
		$this->sut = new CheckoutEventScheduler();
		$this->sut->init(
			$this->mock_tracker,
			$this->mock_data_collector
		);
	}

	/**
	 * Test that register_hooks registers the scheduled action hook.
	 */
	public function test_register_hooks_registers_scheduled_action_hook(): void {
		// Call register_hooks.
		$this->sut->register_hooks();

		// Verify hook was registered.
		$this->assertNotFalse( has_action( 'woocommerce_fraud_protection_track_checkout_event', array( $this->sut, 'process_scheduled_tracking' ) ) );
	}

	/**
	 * Test schedule_tracking schedules an action.
	 */
	public function test_schedule_tracking_schedules_action(): void {
		// Mock session.
		WC()->session = $this->createMock( \WC_Session::class );
		WC()->session
			->method( 'get_customer_id' )
			->willReturn( 'test-session' );

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

		// Schedule tracking.
		$this->sut->schedule_tracking( 'checkout_field_update', array( 'billing_email' => 'test@example.com' ) );

		// Verify action was scheduled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );

		$this->assertCount(
			1,
			$action_ids,
			'Expected one scheduled Action Scheduler action for fraud protection event.'
		);

		// Cleanup.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_fraud_protection_track_checkout_event', array(), 'woocommerce-fraud-protection' );
		}
	}

	/**
	 * Test cancel_scheduled_tracking cancels pending actions.
	 */
	public function test_cancel_scheduled_tracking_cancels_actions(): void {
		// Mock session.
		WC()->session = $this->createMock( \WC_Session::class );
		WC()->session
			->method( 'get_customer_id' )
			->willReturn( 'test-session' );

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

		// Schedule tracking.
		$this->sut->schedule_tracking( 'checkout_field_update', array( 'billing_email' => 'test@example.com' ) );

		// Verify action was scheduled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );
		$this->assertCount( 1, $action_ids );

		// Cancel scheduled tracking.
		$this->sut->cancel_scheduled_tracking( 'test-session', 'checkout_field_update' );

		// Verify the action was cancelled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );
		$this->assertCount( 0, $action_ids, 'Expected no pending actions after cancellation' );

		// Cleanup.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_fraud_protection_track_checkout_event', array(), 'woocommerce-fraud-protection' );
		}
	}

	/**
	 * Test process_scheduled_tracking calls tracker with collected data.
	 */
	public function test_process_scheduled_tracking_calls_tracker(): void {
		$collected_data = array(
			'action'        => 'field_update',
			'billing_email' => 'test@example.com',
			'session'       => array( 'session_id' => 'test-session' ),
		);

		// Mock tracker to verify track_event is called with collected data.
		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->equalTo( $collected_data )
			);

		// Process scheduled tracking.
		$this->sut->process_scheduled_tracking(
			array(
				'session_id'     => 'test-session',
				'event_type'     => 'checkout_field_update',
				'collected_data' => $collected_data,
				'timestamp'      => time(),
			)
		);
	}

	/**
	 * Test process_scheduled_tracking handles missing parameters gracefully.
	 */
	public function test_process_scheduled_tracking_handles_missing_parameters(): void {
		// Mock tracker should not be called.
		$this->mock_tracker
			->expects( $this->never() )
			->method( 'track_event' );

		// Process scheduled tracking with missing parameters.
		$this->sut->process_scheduled_tracking( array() );

		// Process with missing event_type.
		$this->sut->process_scheduled_tracking(
			array(
				'session_id'     => 'test-session',
				'collected_data' => array(),
				'timestamp'      => time(),
			)
		);

		// Process with missing collected_data.
		$this->sut->process_scheduled_tracking(
			array(
				'session_id' => 'test-session',
				'event_type' => 'checkout_field_update',
				'timestamp'  => time(),
			)
		);
	}

	/**
	 * Test that scheduling tracking cancels existing actions for debouncing.
	 */
	public function test_schedule_tracking_implements_debouncing(): void {
		// Mock session.
		WC()->session = $this->createMock( \WC_Session::class );
		WC()->session
			->method( 'get_customer_id' )
			->willReturn( 'test-session' );

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

		// Schedule first tracking.
		$this->sut->schedule_tracking( 'checkout_field_update', array( 'billing_email' => 'test1@example.com' ) );

		// Verify one action was scheduled.
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );
		$this->assertCount( 1, $action_ids );

		// Schedule second tracking (should cancel the first one).
		$this->sut->schedule_tracking( 'checkout_field_update', array( 'billing_email' => 'test2@example.com' ) );

		// Verify still only one action is scheduled (the second one).
		$action_ids = $this->sut->get_scheduled_action_ids( 'test-session', 'checkout_field_update' );
		$this->assertCount( 1, $action_ids );

		// Cleanup.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_fraud_protection_track_checkout_event', array(), 'woocommerce-fraud-protection' );
		}
	}
}
