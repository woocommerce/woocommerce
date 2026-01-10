<?php
/**
 * CheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker;
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
	 * Mock fraud protection controller.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_controller;

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
		$this->mock_dispatcher     = $this->createMock( FraudProtectionDispatcher::class );
		$this->mock_controller     = $this->createMock( FraudProtectionController::class );
		$this->mock_data_collector = $this->createMock( SessionDataCollector::class );

		// Create system under test.
		$this->sut = new CheckoutEventTracker();
		$this->sut->init(
			$this->mock_dispatcher,
			$this->mock_controller,
			$this->mock_data_collector
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
		$this->assertFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_shortcode_checkout_field_update' ) ) );
	}

	/**
	 * Test that register registers shortcode checkout hooks when feature is enabled.
	 */
	public function test_register_registers_hooks_when_feature_enabled(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Call register.
		$this->sut->register();

		// Verify hook was registered.
		$this->assertNotFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_shortcode_checkout_field_update' ) ) );
	}

	// ========================================
	// Blocks Checkout Tests
	// ========================================

	/**
	 * Test track_blocks_checkout_update dispatches event with session data.
	 */
	public function test_track_blocks_checkout_update_dispatches_event_with_session_data(): void {
		// Mock data collector to return session data.
		$session_data = array(
			'session_id'       => 'test_session_123',
			'billing_email'    => 'test@example.com',
			'billing_address'  => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
			),
			'shipping_address' => array(
				'city' => 'New York',
			),
		);
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->equalTo( array() )
			)
			->willReturn( $session_data );

		// Mock dispatcher to verify event is dispatched.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->equalTo( $session_data )
			);

		// Call the method.
		$this->sut->track_blocks_checkout_update();
	}

	/**
	 * Test track_blocks_checkout_update can be called directly without hooks.
	 */
	public function test_track_blocks_checkout_update_works_without_hooks(): void {
		// Mock data collector to return minimal session data.
		$session_data = array(
			'session_id' => 'test_session_456',
		);
		$this->mock_data_collector
			->method( 'collect' )
			->willReturn( $session_data );

		// Mock dispatcher to verify event is dispatched.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' );

		// Call the method directly (as done from CartUpdateCustomer endpoint).
		$this->sut->track_blocks_checkout_update();
	}

	// ========================================
	// Shortcode Checkout Tests
	// ========================================

	/**
	 * Test handle_shortcode_checkout_field_update schedules event with billing email.
	 */
	public function test_handle_shortcode_checkout_field_update_schedules_event_with_billing_email(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock scheduler to verify dispatch_event is called.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'checkout_field_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& 'field_update' === $event_data['action']
							&& isset( $event_data['billing_email'] )
							&& 'test@example.com' === $event_data['billing_email'];
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with billing email.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe';
		$this->sut->handle_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test handle_shortcode_checkout_field_update extracts billing fields correctly.
	 */
	public function test_handle_shortcode_checkout_field_update_extracts_billing_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with multiple billing fields.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US&billing_city=New+York';
		$this->sut->handle_shortcode_checkout_field_update( $posted_data );

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
	 * Test handle_shortcode_checkout_field_update extracts shipping fields when ship_to_different_address is set.
	 */
	public function test_handle_shortcode_checkout_field_update_extracts_shipping_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update with shipping fields.
		$posted_data = 'billing_email=test@example.com&ship_to_different_address=1&shipping_first_name=Jane&shipping_last_name=Smith&shipping_city=Los+Angeles';
		$this->sut->handle_shortcode_checkout_field_update( $posted_data );

		// Verify extracted fields.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'Jane', $captured_event_data['shipping_first_name'] );
		$this->assertEquals( 'Smith', $captured_event_data['shipping_last_name'] );
		$this->assertEquals( 'Los Angeles', $captured_event_data['shipping_city'] );
	}

	/**
	 * Test handle_shortcode_checkout_field_update does not extract shipping fields when ship_to_different_address is not set.
	 */
	public function test_handle_shortcode_checkout_field_update_skips_shipping_fields_when_not_different_address(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Register hooks.
		$this->sut->register();

		// Simulate checkout field update without ship_to_different_address.
		$posted_data = 'billing_email=test@example.com&shipping_first_name=Jane&shipping_last_name=Smith';
		$this->sut->handle_shortcode_checkout_field_update( $posted_data );

		// Verify shipping fields are not extracted.
		$this->assertNotNull( $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_first_name', $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_last_name', $captured_event_data );
	}
}
