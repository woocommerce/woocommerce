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
	 * Test track_shortcode_checkout_field_update schedules event with billing email when billing country changes.
	 */
	public function test_track_shortcode_checkout_field_update_schedules_event_with_billing_email(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock SessionDataCollector to return different billing country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' ); // Current country is CA.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Mock scheduler to verify dispatch_event is called.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& 'field_update' === $event_data['action']
							&& isset( $event_data['billing_email'] )
							&& 'test@example.com' === $event_data['billing_email'];
					}
				)
			);

		// Simulate checkout field update with billing email and country change (CA -> US).
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test track_shortcode_checkout_field_update extracts billing fields correctly when country changes.
	 */
	public function test_track_shortcode_checkout_field_update_extracts_billing_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock SessionDataCollector to return different billing country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' ); // Current country is CA.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Mock scheduler to capture event data.
		$captured_event_data = null;
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
				}
			);

		// Simulate checkout field update with multiple billing fields and country change.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US&billing_city=New+York';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		// Verify extracted fields.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'field_update', $captured_event_data['action'] );
		$this->assertEquals( 'test@example.com', $captured_event_data['billing_email'] );
		$this->assertEquals( 'John', $captured_event_data['billing_first_name'] );
		$this->assertEquals( 'Doe', $captured_event_data['billing_last_name'] );
		$this->assertEquals( 'US', $captured_event_data['billing_country'] );
		$this->assertEquals( 'New York', $captured_event_data['billing_city'] );
	}

	/**
	 * Test track_shortcode_checkout_field_update extracts shipping fields when ship_to_different_address is set and shipping country changes.
	 */
	public function test_track_shortcode_checkout_field_update_extracts_shipping_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock SessionDataCollector to return different shipping country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( null );

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'CA' ); // Current shipping country is CA.

		// Mock scheduler to capture event data.
		$captured_event_data = null;
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
				}
			);

		// Simulate checkout field update with shipping fields and country change.
		$posted_data = 'billing_email=test@example.com&ship_to_different_address=1&shipping_first_name=Jane&shipping_last_name=Smith&shipping_city=Los+Angeles&shipping_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		// Verify extracted fields.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'Jane', $captured_event_data['shipping_first_name'] );
		$this->assertEquals( 'Smith', $captured_event_data['shipping_last_name'] );
		$this->assertEquals( 'Los Angeles', $captured_event_data['shipping_city'] );
	}

	/**
	 * Test track_shortcode_checkout_field_update does not extract shipping fields when ship_to_different_address is not set.
	 */
	public function test_track_shortcode_checkout_field_update_skips_shipping_fields_when_not_different_address(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock SessionDataCollector to return different billing country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' ); // Current billing country is CA.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Mock scheduler to capture event data.
		$captured_event_data = null;
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
				}
			);

		// Simulate checkout field update without ship_to_different_address but with billing country change.
		$posted_data = 'billing_email=test@example.com&billing_country=US&shipping_first_name=Jane&shipping_last_name=Smith';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		// Verify shipping fields are not extracted.
		$this->assertNotNull( $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_first_name', $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_last_name', $captured_event_data );
	}

	// ========================================
	// Country Change Detection Tests
	// ========================================

	/**
	 * Test event is dispatched when billing country changes.
	 */
	public function test_event_dispatched_when_billing_country_changes(): void {
		// Mock SessionDataCollector to return different billing country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' ); // Current country is CA.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Expect event to be dispatched once.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['billing_country'] ) && 'US' === $event_data['billing_country'];
					}
				)
			);

		// Posted data with billing country changing from CA to US.
		$posted_data = 'billing_email=test@example.com&billing_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is dispatched when shipping country changes.
	 */
	public function test_event_dispatched_when_shipping_country_changes(): void {
		// Mock SessionDataCollector to return current countries.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' ); // Current billing country matches posted.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'CA' ); // Current shipping country is CA.

		// Expect event to be dispatched once.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['shipping_country'] ) && 'US' === $event_data['shipping_country'];
					}
				)
			);

		// Posted data with shipping country changing from CA to US.
		$posted_data = 'billing_country=US&ship_to_different_address=1&shipping_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is NOT dispatched when neither country changes.
	 */
	public function test_event_not_dispatched_when_no_country_changes(): void {
		// Mock SessionDataCollector to return same countries as posted.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' ); // Same as posted.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'US' ); // Same as posted.

		// Expect event to NOT be dispatched.
		$this->mock_dispatcher
			->expects( $this->never() )
			->method( 'dispatch_event' );

		// Posted data with no country changes.
		$posted_data = 'billing_email=test@example.com&billing_country=US&shipping_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is NOT dispatched when only non-country fields change.
	 */
	public function test_event_not_dispatched_when_only_non_country_fields_change(): void {
		// Mock SessionDataCollector to return countries.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Expect event to NOT be dispatched.
		$this->mock_dispatcher
			->expects( $this->never() )
			->method( 'dispatch_event' );

		// Posted data with only non-country fields (email, name, phone).
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_phone=1234567890';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is NOT dispatched when ship_to_different_address is not set and current shipping matches billing.
	 */
	public function test_event_not_dispatched_when_shipping_already_matches_billing(): void {
		// Mock SessionDataCollector: shipping already matches billing.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'US' ); // Already matches billing - no change.

		// Expect event to NOT be dispatched (no effective change).
		$this->mock_dispatcher
			->expects( $this->never() )
			->method( 'dispatch_event' );

		// Posted data with NO ship_to_different_address flag, billing stays US.
		$posted_data = 'billing_country=US&billing_email=test@example.com';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is dispatched when billing country changes from null.
	 */
	public function test_event_dispatched_when_billing_country_changes_from_null(): void {
		// Mock SessionDataCollector to return null for current billing country.
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( null ); // No current billing country.

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		// Expect event to be dispatched.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' );

		// Posted data with billing country (first time setting).
		$posted_data = 'billing_email=test@example.com&billing_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is dispatched when user unchecks ship_to_different_address and current shipping country differs from billing.
	 *
	 * Scenario: User had different shipping address with different country (e.g., shipping=CA, billing=US),
	 * then unchecks "ship to different address". The effective shipping country changes from CA to US.
	 */
	public function test_event_dispatched_when_ship_to_different_address_unchecked_with_different_countries(): void {
		// Mock SessionDataCollector: billing=US, shipping=CA (previously different).
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'CA' ); // Was different.

		// Expect event to be dispatched (shipping effectively changed from CA to US).
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->anything()
			);

		// Posted data: ship_to_different_address NOT set (unchecked), billing country is US.
		$posted_data = 'billing_country=US&billing_email=test@example.com';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test event is NOT dispatched when user unchecks ship_to_different_address but countries are already the same.
	 */
	public function test_event_not_dispatched_when_ship_to_different_address_unchecked_with_same_countries(): void {
		// Mock SessionDataCollector: billing=US, shipping=US (already same).
		$this->mock_session_data_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_session_data_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'US' ); // Same as billing.

		// Expect event to NOT be dispatched (no effective change).
		$this->mock_dispatcher
			->expects( $this->never() )
			->method( 'dispatch_event' );

		// Posted data: ship_to_different_address NOT set, billing country is US.
		$posted_data = 'billing_country=US&billing_email=test@example.com';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
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
