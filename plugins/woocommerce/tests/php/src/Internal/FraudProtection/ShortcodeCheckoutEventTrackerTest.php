<?php
/**
 * ShortcodeCheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ShortcodeCheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventScheduler;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for ShortcodeCheckoutEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\ShortcodeCheckoutEventTracker
 */
class ShortcodeCheckoutEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var ShortcodeCheckoutEventTracker
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
		$this->sut = new ShortcodeCheckoutEventTracker();
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

		// Verify hooks were not registered.
		$this->assertFalse( has_action( 'woocommerce_checkout_update_order_review', array( $this->sut, 'handle_checkout_field_update' ) ) );
		$this->assertFalse( has_action( 'wc_ajax_fraud_protection_payment_method_selected', array( $this->sut, 'ajax_handle_payment_method_selected' ) ) );
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
	}

	/**
	 * Test handle_checkout_field_update schedules event with billing email.
	 */
	public function test_handle_checkout_field_update_schedules_event_with_billing_email(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock scheduler to verify schedule_tracking is called.
		$this->mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' )
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
		$this->sut->handle_checkout_field_update( $posted_data );
	}

	/**
	 * Test handle_checkout_field_update extracts billing fields correctly.
	 */
	public function test_handle_checkout_field_update_extracts_billing_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Simulate checkout field update with multiple billing fields.
		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US&billing_city=New+York';
		$this->sut->handle_checkout_field_update( $posted_data );

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
	 * Test handle_checkout_field_update extracts shipping fields when ship_to_different_address is set.
	 */
	public function test_handle_checkout_field_update_extracts_shipping_fields(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Simulate checkout field update with shipping fields.
		$posted_data = 'billing_email=test@example.com&ship_to_different_address=1&shipping_first_name=Jane&shipping_last_name=Smith&shipping_city=Los+Angeles';
		$this->sut->handle_checkout_field_update( $posted_data );

		// Verify extracted fields.
		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'Jane', $captured_event_data['shipping_first_name'] );
		$this->assertEquals( 'Smith', $captured_event_data['shipping_last_name'] );
		$this->assertEquals( 'Los Angeles', $captured_event_data['shipping_city'] );
	}

	/**
	 * Test handle_checkout_field_update does not extract shipping fields when ship_to_different_address is not set.
	 */
	public function test_handle_checkout_field_update_skips_shipping_fields_when_not_different_address(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

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

		// Simulate checkout field update without ship_to_different_address.
		$posted_data = 'billing_email=test@example.com&shipping_first_name=Jane&shipping_last_name=Smith';
		$this->sut->handle_checkout_field_update( $posted_data );

		// Verify shipping fields are not extracted.
		$this->assertNotNull( $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_first_name', $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_last_name', $captured_event_data );
	}

	/**
	 * Test ajax_handle_payment_method_selected schedules event with payment method.
	 */
	public function test_ajax_handle_payment_method_selected_schedules_event(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Mock scheduler to verify schedule_tracking is called.
		$this->mock_scheduler
			->expects( $this->once() )
			->method( 'schedule_tracking' )
			->with(
				$this->equalTo( 'checkout_payment_method_selected' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& 'payment_method_selected' === $event_data['action']
							&& isset( $event_data['payment']['payment_method_type'] )
							&& 'stripe' === $event_data['payment']['payment_method_type'];
					}
				)
			);

		// Register hooks.
		$this->sut->register();

		// Set up POST data.
		$_POST['payment_method'] = 'stripe';

		// Suppress the JSON output to prevent breaking the test.
		add_filter( 'wp_die_ajax_handler', '__return_false' );

		// Call the handler.
		try {
			$this->sut->ajax_handle_payment_method_selected();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected exception from wp_send_json_success.
			unset( $e );
		}

		// Clean up.
		unset( $_POST['payment_method'] );
		remove_filter( 'wp_die_ajax_handler', '__return_false' );
	}
}
