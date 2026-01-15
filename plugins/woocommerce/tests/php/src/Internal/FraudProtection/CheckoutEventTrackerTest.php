<?php
/**
 * CheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\DecisionHandler;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

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
	 * Mock fraud protection dispatcher.
	 *
	 * @var FraudProtectionDispatcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_dispatcher;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_session_data_collector;

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
		$this->mock_dispatcher             = $this->createMock( FraudProtectionDispatcher::class );
		$this->mock_session_data_collector = $this->createMock( SessionDataCollector::class );
		$this->mock_controller             = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new CheckoutEventTracker();
		$this->sut->init( $this->mock_dispatcher, $this->mock_session_data_collector );
	}

	// ========================================
	// Checkout Page Load Tests
	// ========================================

	/**
	 * Test track_checkout_page_loaded dispatches event.
	 * The CheckoutEventTracker::track_checkout_page_loaded does not add any event data.
	 * The data collection is handled by the SessionDataCollector.
	 * So we only need to test if the dispatcher is called with no event data.
	 */
	public function test_track_checkout_page_loaded_dispatches_event(): void {
		// Mock dispatcher to verify event is dispatched with empty event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_page_loaded' ),
				$this->equalTo( array() )
			);

		// Call the method.
		$this->sut->track_checkout_page_loaded();
	}

	// ========================================
	// Blocks Checkout Tests
	// ========================================

	/**
	 * Test track_blocks_checkout_update dispatches event with session data.
	 * The CheckoutEventTracker::track_blocks_checkout_update does not add any event data.
	 * The data collection is handled by the SessionDataCollector.
	 * So we only need to test if the dispatcher is called with no event data.
	 */
	public function test_track_blocks_checkout_update_dispatches_event_with_empty_session_data(): void {
		// Mock dispatcher to verify event is dispatched with empty event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->equalTo( array() )
			);

		// Call the method.
		$this->sut->track_blocks_checkout_update();
	}

	// ========================================
	// Shortcode Checkout Tests
	// ========================================

	/**
	 * Test track_shortcode_checkout_field_update dispatches event with empty data.
	 * The CheckoutEventTracker::track_shortcode_checkout_field_update does not add any event data.
	 * The data collection is handled by the SessionDataCollector.
	 * So we only need to test if the dispatcher is called with no event data.
	 */
	public function test_track_shortcode_checkout_field_update_dispatches_event(): void {
		// Mock dispatcher to verify event is dispatched with empty event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->equalTo( array() )
			);

		// Call the method.
		$this->sut->track_shortcode_checkout_field_update();
	}

	/**
	 * Test track_order_placed dispatches event with correct data structure.
	 */
	public function test_track_order_placed_dispatches_event(): void {
		$order = \WC_Helper_Order::create_order();

		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'order_placed' ),
				$this->callback(
					function ( $event_data ) use ( $order ) {
						$this->assertArrayHasKey( 'order_id', $event_data );
						$this->assertEquals( $order->get_id(), $event_data['order_id'] );
						$this->assertArrayHasKey( 'payment_method', $event_data );
						$this->assertArrayHasKey( 'total', $event_data );
						$this->assertArrayHasKey( 'currency', $event_data );
						$this->assertArrayHasKey( 'customer_id', $event_data );
						$this->assertArrayHasKey( 'status', $event_data );
						return true;
					}
				)
			);

		$this->sut->track_order_placed( $order->get_id(), $order );

		// Clean up.
		$order->delete( true );
	}
}
