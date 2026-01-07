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
	 * Test that register does not register hooks when feature is disabled.
	 */
	public function test_register_does_not_register_hooks_when_feature_disabled(): void {
		// Mock feature as disabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( false );

		// Call register.
		$this->sut->register();

		// Verify hooks were not registered.
		$this->assertFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_checkout_field_update' ) ) );
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
	}

	/**
	 * Test handle_checkout_field_update tracks event with billing email immediately.
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

		// Mock tracker to verify track_event is called immediately with collected data.
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
	}

	/**
	 * Test handle_checkout_field_update tracks shipping method immediately.
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
	}
}
